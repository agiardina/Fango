<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Andrea Giardina <andrea.giardina@gmail.com>
 * @link http://github.com/agiardina/Fango
 */

/**
 * The main class 
 */
class FangoBase {

	/**
	 * The array of events, used by FangoEvent::lazyLoading to load
	 * an event on demand
	 */
	public $events = array();
	
	function subscribe($event,$handler=null) {
		$event->addObserver($this,$handler);
		return $this;
	}

	function unsubscribe($event) {
		$event->deleteObserver($this);
		return $this;
	}

	/**
	 * Used to lazy loading events
	 */
	function __get($name) {
		FangoEvent::lazyLoading($this,$name);
		return $this->$name;
	}
}

/**
 * The main controller
 */
class Fango extends FangoBase {

	/**
	 * @var string the default controller if no controller specified
	 */
	public $default_controller = 'default';

	/**
	 * @var the default action if action not specified
	 */
	public $default_action = 'index';

	/**
	 * @var the default action if action not found
	 */
	public $notfound_action = 'error404';

	/**
	 * @var the controller after the routing
	 */
	public $controller;

	/**
	 * @var the action after the routing
	 */
	public $action;

	/**
	 * @var array the params
	 */
	public $params = array();

	/**
	 * @var FangoDB
	 */
	public $db;

	/**
	 * @var FangoEvent
	 */
	static $onNew;

	/**
	 * @var FangoEvent
	 */
	public $beforeDispatch;

	/**
	 * @var FangoEvent
	 */
	public $afterDispatch;


	function  __construct() {
		$this->beforeDispatch = new FangoEvent('beforeDispatch');
		$this->afterDispatch = new FangoEvent('afterDispatch');

		self::$onNew->fire($this);
	}

	/**
	 * @param array $custom_rules
	 * @param  $subject
	 * @return Fango
	 */
	function route($custom_rules = array(),$subject = '') {

		//If subject has not been declared we guess that from $_SERVER
		if (!$subject) {
			$script = $_SERVER['PHP_SELF'];
			$script = preg_replace("~\w*\.php$~",'',$script);

			if (isset($_SERVER['REQUEST_URI'])) {
				$subject = $_SERVER['REQUEST_URI'];
				$subject = preg_replace('~\?.*$~','',$subject);

				if (strpos($subject,$script)===0) {
					$subject = substr($subject,strlen($script));
				}
			}
		}

		//Rules merged
		$rules[] = '(\w+)/(\w+)/(.*)$ controller=$1,action=$2,params=$3';
		$rules[] = '(\w+)/(\w+)/?$ controller=$1,action=$2';
                $rules[] = '(\w+)\.html$ action=$1';
		$rules[] = '(\w+)/?$ controller=$1';                
		if ($custom_rules) {
			if (!is_array($custom_rules)) $custom_rules = array($custom_rules);
			$rules = array_merge($custom_rules,$rules);
		}

		$controller = $this->default_controller;
		$action = $this->default_action;
		$params = array();

		foreach ($rules as $rule) {
			list($rule,$replacement) = @preg_split("/[\t\s]/",$rule,-1,PREG_SPLIT_NO_EMPTY); //Split the rule from the replace

			if (preg_match("~$rule~",$subject)) {
                            
				$replacement = preg_replace("~$rule~",$replacement,$subject);
				$a_conf = explode(',',$replacement);

				foreach ($a_conf as $conf) {
					list($var,$value) = explode('=',$conf);
					if ($var == 'controller' && $value) {
						$controller = $value;
					} elseif ($var == 'action' && $action) {
						$action = $value;
					} elseif ($var == 'params') {
						$_params = explode('/',$value);
						foreach ($_params as $i=>$param) {
							if ($i % 2 == 0) {
								$param_name = $param;
							} else {
								$params[$param_name] = $param;
							}
						}
					} else {
						$params[$var] = $value;
					}
				}
				break;
			}
		}
		$this->controller = $controller;
		$this->action = $action;
		$this->params = $params;

		if (isset($_REQUEST)) {
			$this->params = array_merge($_REQUEST,$this->params);

		}

		return $this;
	}

	/**
	 * @param string $controller
	 * @param string $action
	 * @param array $params
	 */
	function dispatch($controller=null,$action=null,array $params=null) {
		if ($controller)$this->controller = $controller;
		if ($action) $this->action = $action;
		if ($params!==null) $this->params = $params;

		$e = $this->beforeDispatch->fire($this); //BeforeDispatch Event Fired
		if ($e->preventDefault()) return;

		$class_name = "{$this->controller}Controller";
		$method_name = "{$this->action}Action";

		//If the method doesn't exist use the default controller and method
		if (!class_exists($class_name) || !method_exists($class_name, $method_name)) {
			$this->controller = $this->default_controller;
			$this->action = $this->notfound_action;
			$class_name = "{$this->controller}Controller";
			$method_name = "{$this->action}Action";
                        
                        if (!class_exists($class_name)) {
                            $this->controller = 'Fango';
                            $class_name = 'FangoController';
                        }
		}

		$obj_controller = new $class_name($this);
		$obj_controller->init();
		$result = $obj_controller->$method_name();

		$this->afterDispatch->fire($this,$result); //AfterDispatch Event Fired
	}

	/**
	 * Shortcut for fango->route()->dispatch();
	 * @param array $custom_rules
	 * @param string $subject
	 */
	function run($custom_rules = array(),$subject = '') {
		return $this->route($custom_rules,$subject)->dispatch();
	}
	
	/**
	 * Return the params array (URL Params + $_REQUEST)
	 * @param array $params
	 * @return array 
	 */
	function params(array $params=null) {
		if ($params !== null) {
			$this->params = $params;
		}
		return $this->params;
	}

	/**
	 * Setter/Getter for param 
	 * @param string $param
	 * @param mixed $value
	 * @return mixed 
	 */
	function param($param,$value=null) {
		
		if ($value!==null) {
			$this->params[$param] = $value;
		}
		
		if (isset($this->params[$param])) {
 			return $this->params[$param];
		}
		return null;
	}

	/** Setter/Getter for request, use param instead
	 * @param string $name of the request param
	 * @param mixed  value
	 * @see param
	 * @deprecated
	 */
	function request($name,$value=null) {
		return $this->param($name,$value);
	}

}

class FangoController extends FangoBase {
	/**
	 * @var Fango The fango front controller
	 */
	public $fango;

	/**
	 * @var FangoEvent
	 */
	static $onNew;

	/**
	 * @param Fango $fango
	 */
	function __construct(Fango $fango) {
		$this->fango = $fango;
		self::$onNew->fire($this);
	}

	/**
	 * Extend this method to run a code every time a controller is created
	 */
	function init() {}


	/**
	 * shortcut for Fango::params()
	 *
	 * @see Fango::params()
	 * @param array $params
	 * @return array
	 */
	function params(array $params=null) {
		return $this->fango->params($params);
	}
	/**
	 * shortcut for Fango::param()
	 *
	 * @see Fango::param()
	 * @param string $param
	 * @param mixed $value
	 * @return mixed
	 */
	function param($param,$value=null) {
		return $this->fango->param($param,$value);
	}


	function error404Action() {
		header("HTTP/1.0 404 Not Found");
		echo "<h1>Page Not Found</h1>";
	}

}

/**
 * @method string getName() getName() return the name of the view
 * @method string getTemplate() getTemplate() return the template path
 * @method FangoView name() $name set the name of the view and return the view
 * @method FangoView template() template($template) set the template path and return the view
 */
class FangoView extends FangoBase {
	/**
	 * The name of the view, used by render as imput/select etc
	 * @var string
	 */
	protected $_name;

	/**
	 * @var string
	 */
	protected $_template;

	/**
	 * @var FangoEvent
	 */
	static $onNew;

	/**
	 * @param string $name of the view
	 * @param string $template to render
	 */
	function __construct($name = null,$template=null) {
		if ($name) $this->_name = $name;
		if (!$template && $name) {
			$this->_template = "templates/$name.phtml";
		} elseif ($template) {
			$this->_template = $template;
		}
		self::$onNew->fire($this);
	}

	/**
	 * Render the default view or the view passed as param
	 * @param string $template
	 * @return string
	 */
	function render($template=null) {
		ob_start();
                extract((array)$this);
		if ($template) $this->_template = $template;
		include $this->_template;
		return ob_get_clean();
	}

	/**
	 * Call the render method
	 * @return string
	 */
	function __toString() {
		return $this->render();
	}

}

class FangoDB extends PDO {

	/**
	 * @var FangoEvent
	 */
	static $onNew;

	/**
	 * @var FangoDB
	 */
	static $db;

	/**
	 * @see PDO::__construct
	 */
	static function connect($dsn, $username=null, $password=null,$driver_options=array()) {
		self::$db = new FangoDB($dsn,$username,$password,$driver_options);
	}

	/**
	 * @see PDO::__construct
	 */
	function __construct($dsn,$username=null,$password=null,$driver_options=array()) {
		parent::__construct($dsn,$username,$password,$driver_options);
		self::$onNew->fire($this);
	}

	/**
	 * Instance a table model an inject it with the database
	 * @param string $table name
	 * @param string $pk name
	 * @param string $class the model class instance
	 * @return FangoModel
	 */
	function model($table,$pk = null) {
		return new FangoModel($table,$pk,$this);
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return PDOStatement
	 */
	function execute($sql,$params = null) {
		$sth = $this->prepare($sql);
		$sth->execute($params);
		return $sth;
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	function getAll($sql,$params = null) {
		$sth = $this->execute($sql,$params);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	function getRow($sql,$params = null) {
		$sth = $this->execute($sql,$params);
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	function getCol($sql,$params = null) {
		$sth = $this->execute($sql,$params);
		return $sth->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return mixed
	 */
	function getOne($sql,$params = null) {
		$sth = $this->execute($sql,$params);
		$res = $sth->fetch(PDO::FETCH_NUM);
		if (is_array($res)) return array_shift($res);
	}
}

class FangoModel extends FangoBase {
	/**
	 *
	 * @var FangoDB
	 */
	public $db;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $pk;

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var array
	 */
	protected $where = array();

	/**
	 * @var array
	 */
	protected $order = array();

	/**
	 * @var array
	 */
	protected $limit = array();

	/**
	 * @var FangoEvent
	 */
	static $onNew;

	/**
	 * @var array
	 */
	public $events = array('beforeUpdate','beforeInsert','afterUpdate','afterInsert');

	/**
	 * @param string $name
	 * @param string $pk
	 * @param FangoDB $db
	 */
	function __construct($name,$pk=null,$db=null) {
		$this->name = $name;
		$this->pk = $pk;
		$this->setDB($db);

		self::$onNew->fire($this);
	}

	/**
	 * @param FangoDB $db
	 */
	function setDB($db=null) {
		if (is_object($db)) {
			$this->db = $db;
		} elseif (is_object(FangoDB::$db)) {
			$this->db = FangoDB::$db;
		}
	}

	/**
	 * Set the fields to extract
	 * @param array $fields
	 * @return FangoModel
	 */
	function fields($fields) {
		if (!is_array($fields)) $fields = func_get_args();

		$this->fields = $fields;
		return $this;
	}

	/**
	 * Add a where clause in the PDO format
	 * @param string $clause
	 * @param array $params
	 * @return FangoModel
	 */
	function where($clause,$params=null) {
		if ($params !== null && !is_array($params)) {
			$params = array($params);
		}
		$this->where['clause'][] = $clause;
		if (isset($this->where['params'])) {
			$this->where['params'] = array_merge($this->where['params'],$params);
		} else {
			$this->where['params'] = $params;
		}

		return $this;
	}

	/**
	 * Add an order clause
	 * @param string $order field
	 * @param string $direction (asc,desc)
	 * @return FangoModel
	 */
	function order($order,$direction=null) {
		$this->order[] = array($order,$direction);
		return $this;
	}

	/**
	 * Set limit and offset
	 * @param int $limit
	 * @param int $offset
	 * @return FangoModel
	 */
	function limit($limit,$offset = null) {
		$this->limit = array($limit,$offset);
		return $this;
	}

	/**
	 * @return array params to use with the where
	 */
	function params() {
		if (isset($this->where['params'])) {
			return $this->where['params'];
		}
		return array();
	}

	/**
	 * @return string the built select using fields, where, order and limit params
	 */
	function asSelect() {
		$fields = '*';
		$where = '';
		$order = '';
		$limit = '';

		if ($this->fields) $fields = join(',',$this->fields);

		if ($this->where) $where = 'WHERE ' . join (' AND ',$this->where['clause']);

		if ($this->order) {
			$order = "ORDER BY ";
			foreach ($this->order as $ao) {
				$order .= trim("{$ao[0]} {$ao[1]}") . ',';
			}
			$order = substr($order,0,-1);
		}

		if ($this->limit) {
			$limit = "LIMIT " . $this->limit[0];
			if ($this->limit[1]) {
				$limit .= " OFFSET " . $this->limit[1];
			}
		}

		$select = trim("SELECT $fields FROM {$this->name} $where $order $limit");
		return $select;
	}

	/**
	 * @return array all rows
	 */
	function getAll() {
		return $this->db->getAll($this, $this->params());
	}

	/**
	 * @return array the first row
	 */
	function getRow() {
		return $this->db->getRow($this, $this->params());
	}

	/**
	 * @return array the first col
	 */
	function getCol() {
		return $this->db->getCol($this, $this->params());
	}

	/**
	 * @return mixed first value of the first row
	 */
	function getOne() {
		return $this->db->getOne($this, $this->params());
	}

	/**
	 * @return int the number of row of the built select
	 */
	function count() {
		$sql = "SELECT count(*) FROM (".$this->asSelect().") AS A";
		return $this->db->getOne($sql,$this->params());
	}

	/**
	 * Insert a row in the related table
	 * @param array $row
	 * @return PDOStatement
	 */
	function insert($row) {
		$e = $this->beforeInsert->fire($this,$row); //BeforeInsert event fired
		list($row) = $e->params;
		if ($e->preventDefault()) return;

		$keys = array_keys($row);

		$fields = join(',',$keys);
		$values = ':' . join(',:',$keys);
		$sql = "INSERT INTO {$this->name} ($fields) VALUES($values)";
		$stm = $this->db->execute($sql, $row);

		$this->afterInsert->fire($this,$row); //AfterInsert event fired
		return !(bool)$stm->errorCode();
	}

	/**
	 * Shortcut for PDO::lastInsertID
	 * @param string $seqname
	 * @return int
	 */
	function lastInsertID($seqname = null) {
		return $this->db->lastInsertId($seqname);
	}

	/**
	 * Delete the row specified by the pk param in the related table
	 * @param mixed $pk
	 * @return DBOStatement
	 */
	function delete($pk) {
		$e = $this->beforeDelete->fire($this,$pk);
		list($pk) = $e->params;
		if ($e->preventDefault()) return;

		$this->requirePK();
		list($where,$params) = $this->pkParts(null,$pk);
		$sql = "DELETE FROM {$this->name} WHERE $where";
		$stm = $this->db->execute($sql,$params);

		$this->afterDelete->fire($this,$pk);
		return !(bool)$stm->errorCode();
	}

	/**
	 * Update a row
	 * @param array $row
	 * @param mixed $pk
	 * @return PDOStatement
	 */
	function update($row,$pk=null) {
		$e = $this->beforeUpdate->fire($this,$row,$pk); //BeforeUpdate event fired
		list($row,$pk) = $e->params;
		if ($e->preventDefault()) return;

		$this->requirePK();
		list($where,$pk_params) = $this->pkParts($row,$pk);
		$sql = "UPDATE {$this->name} SET ";
		foreach ($row as $field=>$value) {
			$sql .= "{$field}=:{$field},";
		}
		$sql = substr($sql,0,-1) . " WHERE $where";
		$params = array_merge($pk_params,$row);

		$return = $this->db->execute($sql,$params);
		$this->afterUpdate->fire($this,$row,$pk,$return); //AfterUpdate event fired
		return $return;
	}

	/**
	 * The arg row is new?
	 * @param array $row to consider
	 * @param mixed $pk
	 * @return boolean
	 */
	function isNew($row,$pk=null) {
		list($pk_where,$pk_values) = $this->pkParts($row,$pk);
		$statement = "SELECT 1 FROM {$this->name} WHERE $pk_where";
		return !$this->db->getOne($statement,$pk_values);
	}

	/**
	 * Reset fields, where, limit and order, useful to reuse the model to run a different query
	 * @param string $what
	 * @return FangoModel
	 */
	function reset($what = null) {
		if (in_array($what,array('fields','where','limit','order'))) {
			$this->$what = array();
		} else {
			$this->fields = array();
			$this->where = array();
			$this->limit = array();
			$this->order = array();
		}
		return $this;
	}

	/**
	 * Return an array with pk where and params ready to be used to compose a query
	 * @param string $row
	 * @param mixed $pk_value
	 * @return array
	 */
	function pkParts($row,$pk_value = null) {
		$this->requirePK();

		$pk = $this->pk;
		if (!is_array($pk)) $pk = array($pk);

		if (!$pk_value) { //If no pk specified we read the pk from the row
			$pk_value = array_intersect_key($row,array_flip($pk));
		} elseif (!is_array($pk_value)) { //We need pk_value as key=>value
			$pk_value = array($pk[0]=>$pk_value);
		}

		if (array_keys($pk_value) != $pk) throw new Exception('PK not valid');

		$pk_params = array();
		$pk_where = '';
		foreach ($pk as $p) {
			$pk_params["__PK__{$p}"] = $pk_value[$p]; //We had __PK__ to key to allow updating on pk (set id:id where :id=__PK__id)
			$pk_where .= "{$p} = :__PK__{$p} AND ";
		}
		$pk_where = substr($pk_where,0,-5);
		return array($pk_where,$pk_params );
	}

	/**
	 * throw an exception if pk is not defined
	 */
	protected function requirePK() {
		if (!isset($this->db) | !isset($this->pk)) throw new Exception("DB or PK not defined");
	}

	/**
	 * return the model as select
	 * @return string
	 */
	function  __toString() {
		return $this->asSelect();
	}

}

class FangoEvent extends FangoBase {
	/**
	 * @var array the params passed to the obsers
	 */
	public $params;

	/**
	 * The objects that has generated the event
	 * @var object 
	 */
	public $target;

	/**
	 * @var string event name
	 */
	public $name;

	/**
	 * @var array list of observer for the events
	 */
	protected $observers = array();

	/**
	 * @var boolean
	 */
	protected $prevent = false;

	/**
	 * @param string $name the event name
	 */
	function __construct($name) {
		$this->name = $name;
	}

	/**
	 * @param boolean $prevent
	 * @return boolean
	 */
	function preventDefault($prevent = null) {
		if ($prevent !== null) $this->prevent = $prevent;
		return $this->prevent;
	}

	/**
	 * @param FangoObserver $observer
	 */
	function addObserver($observer,$handler=null) {
		if ($handler === null) $handler = $this->name;
		if (!in_array($observer,$this->observers)) $this->observers[] = array($observer,$handler);
	}

	/**
	 * @param FangoObserver $observer
	 */
	function deleteObserver($observer) {
		foreach ($this->observers as $key=>$stored_observer) {
			if ($stored_obverser==$observer) {
				unset($this->observers[$key]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Fire the event
	 */
	function fire($target) {
		$this->target = $target;
		$this->params = func_get_args();
		array_shift($this->params); //the param 0 is always the target
		
		foreach ($this->observers as $a) {
			list($observer,$method) = $a;
			if (method_exists($observer,$method)) {
				$observer->$method($this);
			}
		}
		return $this;
	}

	/**
	 * @param Object $subject
	 */
	static function lazyLoading($subject,$event) {
		if (isset($subject->events) && is_array($subject->events) && in_array($event,$subject->events)) {
			$subject->$event = new FangoEvent($event);
			return $subject->$event;
		}
		throw new Exception("Property $event doesn't exists");
	}
}

//onNew events set to class level
Fango::$onNew = new FangoEvent('onNew');
FangoDB::$onNew = new FangoEvent('onNew');
FangoModel::$onNew = new FangoEvent('onNew');
FangoView::$onNew = new FangoEvent('onNew');
FangoController::$onNew = new FangoEvent('onNew');

//CREATE A BASE APPLICATION STRUCTURE
if (PHP_SAPI === 'cli') {
    if (strtolower($argv[1]) === 'create') {
        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            throw new Exception("Version 5.3.0 minimum required");
        }
        
        if ($argc > 2) {
            $dir = $argv[2];
        } else {
            $dir = dirname(PHP_SELF);
        }
        
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new Exception("The $dir is not writable");
        }
        
        $controllers_path = rtrim($dir,'/') . '/controllers';
        $templates_path = rtrim($dir,'/') . '/templates';
        $public_path = rtrim($dir,'/') . '/public';
        
        if (is_dir($controllers_path) || is_dir($templates_path) || is_dir($public_path)) {
            throw new Exception("Directory '$dir' is not empty");
        } else{
            mkdir($controllers_path);
            mkdir($public_path);
            mkdir($templates_path);
        }
        
        $index = <<<'TEXT'
<?php
ini_set('include_path','..:'. ini_get('include_path'));
require_once 'fango.php';
require_once 'controllers/default.php';
//FangoDB::connect('mysql:dbname=fango;host=127.0.0.1','root', 'password');
$fango = new Fango();
$fango->run();
TEXT;
        
        $htaccess = <<<'TEXT'
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
TEXT;
        
        $controller = <<<'TEXT'
<?php
class DefaultController  extends FangoController{
    function indexAction() {
        $view = new FangoView('index');
        $view->title = 'My First Page';
        echo $view;
    }
}
TEXT;
        
        $view = <<<'TEXT'
<!doctype>
<html>
    <h1><?=$title?></h1>
</html>
TEXT;
     
        file_put_contents("$public_path/index.php", "$index");
        file_put_contents("$public_path/.htaccess", $htaccess);
        file_put_contents("$controllers_path/default.php", $controller);
        file_put_contents("$templates_path/index.phtml", $view);
    }
}