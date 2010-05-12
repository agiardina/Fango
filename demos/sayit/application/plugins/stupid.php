<?php
/**
 * This is a completely unsefull plugin. I created this plugin just to show
 * the plugin system and the observer pattern
 */
class StupidPlugin extends FangoBase {

	
	function  __construct() {
		//Attach the plugin to each instance of FangoModel
		$this->subscribe(FangoModel::$onNew,'onNewInstance');
	}

	/**
	 * Everty time a FangoModel instance fires the onLoad event the plug method
	 * is called.
	 * @param FangoEvent
	 */
	function onNewInstance($e) {
		$model = $e->target;
		//We decided to intercept the beforeInsert event of each FangoModel instance
		$this->subscribe($model->beforeInsert);
	}

	function beforeInsert($e) {
		$row =& $e->params[0];
		foreach ($row as $key=>$value) {
			//Ya, I know, it's very stupid example, but you shoud be able now to do
			//something more useful, for example a validator system :P
			$row[$key] = preg_replace("/I love drugs/i", 'I love SEX',$value);
		}
	}
}
