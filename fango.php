<?php
class Fango {
	
	/**
	 *
	 * @var string the default controller if no controller specified
	 */
	public $default_controller = 'default';

	/**
	 *
	 * @var the default action if action not specified
	 */
	public $default_action = 'index';

	/**
	 *
	 * @var the default action if action not found
	 */
	public $notfound_action = 'error404';

	/**
	 *
	 * @var the controller after the routing
	 */
	public $controller;

	/**
	 *
	 * @var the action after the routing
	 */
	public $action;

	/**
	 *
	 * @var array the params
	 */
	public $params = array();
	
	/**
	 *
	 * @var Fangodb
	 */
	public $db;


	/**
	 *
	 * @param FangoModel $model 
	 */
	function  __construct($db = null) {
		$this->db = $db;
	}

	/**
	 *
	 * @param array $custom_rules
	 * @param  $subject
	 * @return Fango
	 */
	function route($custom_rules = array(),$subject = ''){

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
		$rules[] = '(\w+)/?$ controller=$1';
		if ($custom_rules) {
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
		return $this;
	}
	
	/**
	 *
	 * @param string $controller
	 * @param string $action
	 * @param array $params 
	 */
	function dispatch($controller=null,$action=null,array $params=null){
		if ($controller)$this->controller = $controller;
		if ($action) $this->action = $action;
		if ($params!==null) $this->params = $params;

		$class_name = "{$this->controller}Controller";
		$method_name = "{$this->action}Action";

		//If the method doesn't exist use the default controller and method
		if (!class_exists($class_name) || !method_exists($class_name, $method_name)) {
			$this->controller = $this->default_controller;
			$this->action = $this->notfound_action;
			$class_name = "{$this->controller}Controller";
			$method_name = "{$this->action}Action";
		}
		
		$obj_controller = new $class_name($this);
		$obj_controller->$method_name();
	}

	/**
	 *
	 * @param string $name of the request param
	 * @param mixed $default value 
	 */
	static function request($name,$default_value=null) {
		if (!isset($_REQUEST[$name])) {
			$_REQUEST[$name] = $default_value;
		}
		return $_REQUEST[$name];
	}

}

class FangoController {
	/**
	 *
	 * @var Fango The fango front controller
	 */
	public $fango;


	/**
	 *
	 * @param Fango $fango 
	 */
	function __construct(Fango $fango) {
		$this->fango = $fango;
	}
	
	/**
	 * setter/getter for params
	 * 
	 * @param string $param
	 * @param mixed $value
	 * @return mixed
	 */
	function param($param,$value=null){
		if ($value!==null) {
			$this->fango->params[$param] = $value;
		}
		if (isset($this->fango->params[$param])) {
			return $this->fango->params[$param];
		}

		return null;
	}

	/**
	 *
	 * @param string $name of the table
	 * @param string $pk name 
	 * @return FangoModel
	 */
	function model($name,$pk = null) {
		if (isset($this->fango->db)) {
			return $this->fango->db->model($table,$pk);
		}
	}
	
	function error404Action() {}

}

class FangoView {
	public $name;
	public $value;
	public $options = array();
	public $template;

	
	function __construct($name = null){
		if ($name) $this->name = $name;
	}

	function input($properties=''){
		$value = htmlspecialchars($this->value);
		return "<input name=\"$this->name\" value=\"$value\" $properties />";
	}
	
	function select($properties=''){

		if ($this->options == array_values($this->options)) { //This is a standard array and not a map
			$this->options = array_combine($this->options,$this->options); // Tranform a standard array in map value=>value
		} 
		$sreturn = "<select name=\"$this->name\" $properties >";
		foreach ($this->options as $key=>$label) {
			$key = htmlspecialchars($key);
			if ($this->value == $key) {
				$selected = 'selected="selected"';
			} else {
				$selected = '';
			}
			$sreturn .= "<option $selected value=\"$key\">$label</option>";
		}
		$sreturn .= "</select>";
		return $sreturn;
	}
	
	function textarea(){
		
	}


	function render($template=null){
		ob_start();
		if ($template) $this->template = $template;
		include $this->template;
		return ob_get_clean();
	}

	function yeld() {
		static $i = -1;
		$vars = array_keys(get_object_vars($this));

		$n = count($vars);
		while (++$i < $n) {
			$var = $vars[$i];
			$obj = $this->$var;
			if ($obj instanceof FangoView) {
				return $obj;
			}
		}
		$i = -1;
		return false;
	}
	
}

class FangoDB extends PDO {
	/**
	 * Instance a table model an inject it with the database
	 * @param string $table name
	 * @param string $pk name
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
	function execute($sql,$params = null){
		$sth = $this->prepare($sql);
		$sth->execute($params);
		return $sth;
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return array 
	 */
	function getAll($sql,$params = null){
		$sth = $this->execute($sql,$params);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return array 
	 */
	function getRow($sql,$params = null){
		$sth = $this->execute($sql,$params);
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	function getCol($sql,$params = null){
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

class FangoModel {
	/**
	 *
	 * @var FangoDB
	 */
	public $db;
	public $name;
	public $pk;
	protected $fields = array();
	protected $where = array();
	protected $order = array();
	protected $limit = array();

	function __construct($name,$pk=null,$db=null) {
		$this->name = $name;
		$this->pk = $pk;
		$this->db = $db;
	}

	function fields($fields) {
		if (!is_array($fields)) {
			$fields = func_get_args();
		}
		$this->fields = $fields;
		return $this;
	}

	function where($clause,$params=null) {
		if ($params !== null && !is_array($params)) {
			$params = array($params);
		}
		$this->where['clause'][] = $clause;
		if (is_array($this->where['params'])) {
			$this->where['params'] = array_merge($this->where['params'],$params);
		} else {
			$this->where['params'] = $params;
		}
		
		return $this;
	}

	function order($order,$direction=null) {
		$this->order[] = array($order,$direction);
		return $this;
	}

	function limit($limit,$offset = null) {
		$this->limit = array($limit,$offset);
		return $this;
	}

	function params() {
		if (isset($this->where['params'])) {
			return $this->where['params'];
		}
		return array();
	}

	function asSelect() {
		$fields = '*';
		$where = '';
		$params = null;
		$order = '';
		$limit = '';

		if ($this->fields) {
			$fields = join(',',$this->fields);
		} 
		if ($this->where) {
			$where = 'WHERE ' . join (' AND ',$this->where['clause']);
		}
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

	function getAll() {
		return $this->db->getAll($this, $this->params());
	}

	function getRow() {
		return $this->db->getRow($this, $this->params());
	}

	function getCol() {
		return $this->db->getCol($this, $this->params());
	}

	function getOne() {
		return $this->db->getOne($this, $this->params());
	}

	function count() {
		$sql = "SELECT count(*) FROM (".$this->asSelect().") AS A";
		return $this->db->getOne($sql,$this->params());
	}

	function insert($row) {
		$keys = array_keys($row);

		$fields = join(',',$keys);
		$values = ':' . join(',:',$keys);
		$sql = "INSERT INTO {$this->name} ($fields) VALUES($values)";
		return $this->db->execute($sql, $row);
	}

	function update($row,$pk=null) {
		$this->requirePK();
	}

	function delete($row,$pk=null) {
		$this->requirePK();
	}

	function isNew($row,$pk=null) {
		list($pk_where,$pk_values) = $this->pkParts($row,$pk);
		$statement = "SELECT 1 FROM {$this->name} WHERE $pk_where";
		return !$this->db->getOne($statement,$pk_values);
	}

	function reset($what = null) {
		if (in_array($what,array('fields','where','limit','order'))){
			$this->$what = array();
		} else {
			$this->fields = array();
			$this->where = array();
			$this->limit = array();
			$this->order = array();
		}
		return $this;
	}

	function pkParts($row,$pk_value = null) {
		$this->requirePK();

		$pk = $this->pk;
		if (!is_array($pk)) {
			$pk = array($pk);
		}

		if (!$pk_value) { //If no pk specified we read the pk from the row
			$pk_value = array_intersect_key($row,array_flip($pk));
		} elseif (!is_array($pk_value)) { //We need pk_value as key=>value
			$pk_value = array($pk[0]=>$pk_value);
		}

		if (count($pk) != count($pk_value)) throw new Exception('PK not valid');

		$pk_where = '';
		foreach ($pk as $p) {
			$pk_where .= "{$p} = :{$p} AND ";
		}
		$pk_where = substr($pk_where,0,-5);
		return array($pk_where,$pk_value);
	}

	protected function requirePK() {
		if (!isset($this->db) | !isset($this->pk)) throw new Exception("DB or PK not defined");
	}

	function  __toString() {
		return $this->asSelect();
	}
}
