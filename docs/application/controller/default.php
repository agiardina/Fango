<?php
class DefaultController extends FangoController
{
	function indexAction() {
		echo $this->getIndexView();
	}

	function showClassAction() {
		$view = $this->getIndexView();		
		$view->class_board = $this->getClassView();

		echo $view;
	}

	function showMethodAction() {
		$view = $this->getIndexView();
		$view->class_board = $this->getClassView();
		$view->method_board = $this->getMethodView();
		echo $view;
	}

	function getClasses() {
		static $return = array();
		if (!$return) {
			$classes = get_declared_classes();
			foreach ($classes as $class) {
				if (strpos($class,'Fango') === 0) {
					$return[$class] = new ReflectionClass($class);
				}
			}
		}
		return $return;
	}

	function getMethods($class) {
		$return  = array();

		$classes = $this->getClasses();
		$reflectionClass = $classes[$class];

		if (is_object($reflectionClass)) {
			$methods = $reflectionClass->getMethods();
			foreach ($methods as $method) {
				$return[$method->getName()] = $method;
			}
			ksort($return);
		}
		return $return;
	}

	function getMethod($class,$method) {
		$methods = $this->getMethods($class);
		return $methods[$method];
	}

	function getIndexView() {
		$view = new FangoView('index');
		$view->method_board = '';
		$view->class_board = '';
		$view->classes = $this->getClasses();
		$view->class_name = $this->param('class');
		$view->method_name = $this->param('method');

		if ($view->class_name) {
			$view->methods = $this->getMethods($this->param('class'));
		} else {
			$view->methods = array();
		}

		return $view;
	}

	function getClassView() {

		$classes = $this->getClasses();
		$class = $classes[$this->param('class')];

		$view = new FangoView('class');
		$view->class_name = $this->param('class');
		$view->doc_comment = nl2br($class->getDocComment());
		$view->properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

		return $view;
	}

	function getMethodView() {
		$view = new FangoView('method');
		$view->method = $this->getMethod($this->param('class'), $this->param('method'));
	
		return $view;
	}
}
