<?php
class Fango {
	static $params;

	static function route($rules = '',$subject = ''){

		/*preg_match_all("~:\w*~", $rule, $matches);
		if ($matches) {
			$matches = $matches[0];
			$replacement = '';
			foreach ($matches as $i=>$match) {
				if ($match == 'params') {
					$rule = str_replace($match, '(\.*)', $rule);
				} else {
					$rule = str_replace($match, '(\w*)', $rule);
				}
				$replacement = substr($match, 1) . '=$' . ($i+1) . ',';
			}
			$replacement = substr($replacement,0,-1);
			self::$rules[$rule] = $replacement;
		} else {
			
		}*/
		
		$controller = 'default';
		$action = 'e404';
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
		return array('controller'=>$controller,'action'=>$action,'params'=>$params);
	}
	
	static function dispatch($class=null,$action=null,array $params=array()){}
	static function run(){}
}

class FangoController {
	function __construct($FrontController) {}
	static function param(){}
	static function view(){}
	static function model(){}
}

class FangoView {
	function input(){}
	function select(){}
	function textarea(){}

	function render($template=null){}
}

class FangoModel {
	function connect(){}
	function getAll(){}
	function getOne(){}
	function getRow(){}
}