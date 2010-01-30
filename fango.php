<?php
class Fango {
	
	/**
	 *
	 * @var string the default controller if no controller specified
	 */
	public $default_controller = 'default';

	/**
	 *
	 * @var the default action if action not found
	 */
	public $default_action = 'error404';

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
	 * @var FangoModel the model
	 */
	public $model;


	/**
	 *
	 * @param FangoModel $model 
	 */
	function  __construct($model = null) {
		$this->model($model);
	}

	/**
	 *
	 * @param FangoModel $model
	 * @return FangoModel
	 */
	function model($model = null) {
		if ($model) {
			$this->model = $model;
		}
		return $this->model;
	}
	/**
	 *
	 * @param array or string $rules
	 * @param  $subject
	 * @return Fango
	 */
	function route($rules = '',$subject = ''){
		
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
					if ($var == 'controller') {
						$controller = $value;
					} elseif ($var == 'action') {
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
			$this->action = $this->default_action;
			$class_name = "{$this->default_controller}Controller";
			$method_name = "{$this->default_action}Action";
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
	public $value;
	public $name;
	public $options = array();

	
	function input($properties=''){
		$value = htmlspecialchars($this->value);
		return "<input name=\"$this->name\" value=\"$value\" $properties />";
	}
	
	function select($properties=''){
		
	}
	
	function textarea(){
		
	}

	function render($template=null){
		ob_start();
		include $template;
		return ob_get_clean();
	}
}

class FangoModel {
	function connect(){}
	function getAll(){}
	function getOne(){}
	function getRow(){}
}