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
	 * @var FangoDB
	 */
	public $DB;


	/**
	 *
	 * @param FangoModel $model 
	 */
	function  __construct($DB = null) {
		$this->DB = $DB;
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

class FangoDB extends PDO {
	function model($table,$pk = null) {
		return new FangoModel($table,$pk,$this);
	}

	function exec($select,$params){}
	function getAll(){}
	function getOne(){}
	function getRow(){}
}

class FangoModel {
	public $DB;
	public $name;
	public $pk;
	protected $where = array();
	protected $limit = array();
	protected $order = array();

	function __construct($name,$pk=null,$DB=null) {
		$this->name = $name;
		$this->pk = $pk;
		$this->DB = $DB;
	}

	function limit($limit,$offset = null) {
		$this->limit = array($limit,$offset);
		return $this;
	}

	function order($order,$direction=null) {
		$this->order[] = array($order,$direction);
		return $this;
	}

	function where($statment,$param1=null,$param2=null,$paramX=null) {
		$this->where[] = array($statment,array_shift(func_get_args()));
		return $this;
	}

	function get() {


	}

	function update() {

	}

	function insert() {
		
	}

	function save($row) {
		$this->requirePK();
		return $this->DB->save($this,$row);
	}

	
	function reset($what = null) {
		if (in_array($what,array('where','limit','order'))){
			$this->$what = array();
		} else {
			$this->where = array();
			$this->limit = array();
			$this->order = array();
		}
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

		$pk_where = ':' . implode(' AND :', $pk);
		return array($pk_where,$pk_value);
	}

	protected function requirePK() {
		if (!isset($this->DB) | !isset($this->pk)) throw new Exception("DB or PK not defined");
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
	 * @var array
	 */
	static $views = array();
	
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
	 * @param string $class_name
	 * @return FangoView 
	 */
	function view($class_name){
		if (!isset(self::$views[$class_name])) {
			self::$views[$class_name];
		}
		return self::$views[$class_name];
	}
	
	function model(){}
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