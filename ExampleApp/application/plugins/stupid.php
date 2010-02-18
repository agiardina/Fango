<?php
/**
 * This is a completely unsefull plugin. I created this plugin just to show
 * the plugin system and the observer pattern
 */
class StupidPlugin extends FangoPlugin {
	/**
	 * "Attach" the plugin to each instance of FangoModel
	 * @var string
	 */
	public $pluginto = 'FangoModel';

	/**
	 * Everty time a FangoModel instance fires the onLoad event the plug method
	 * is called.
	 * @param FangoModel $model
	 */
	function plug($model) {
		//We decided to intercept the beforeInsert event of each FangoModel instance
		$this->subscribe($model->beforeInsert);
	}

	function beforeInsert($e) {
		$row =& $e->params[1];
		foreach ($row as $key=>$value) {
			//Ya, I know, it's very stupid example, but you shoud be able know to do
			//something more useful, for example a validator system :P
			$row[$key] = preg_replace("/I love drugs/i", 'I love SEX',$value);
		}
	}
}
