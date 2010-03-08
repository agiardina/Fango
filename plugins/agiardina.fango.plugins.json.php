<?php
class JsonPlugin extends FangoPlugin {

	protected $is_json_request = false;
	protected $is_json_response = false;

	function __construct() {
		//Wake up the plugin on Fango::$onLoad->fire()
		$this->plug('Fango');
	}

	function onPlug($fango) {
		//Intercept the before and after dispatch
		$this->subscribe($fango->beforeDispatch);
		$this->subscribe($fango->afterDispatch);
	}

	function beforeDispatch($e) {
		$fango = $e->params[0];
		//Is a json Call?
		if (isset($_SERVER['HTTP_ACCEPT'])) {
			if (strpos($_SERVER['HTTP_ACCEPT'],'application/json') !== false  ||
				strpos($_SERVER['HTTP_ACCEPT'],'text/javascript') !== false ) {
				$this->is_json_request = true;
			}
		}elseif (isset($fango->params['json'])) {
			$this->is_json_request = true;
		}

		if ($this->is_json_request) {
			$action = $fango->action;
			$methods = get_class_methods($fango->controller . 'Controller');
			//Does exist a valid json method to handle the request?
			if (in_array("{$action}JsonAction", $methods)) {
				$this->is_json_response = true;
				$fango->action = "{$action}Json";
			}
		}
	}

	function afterDispatch($e) {
		if ($this->is_json_response) {
			//Take the reponse and convert on json format
			$result = $e->params[1];
			echo json_encode($result);
		}
	}
}