<?php
class StupidPlugin extends FangoPlugin {
	public $pluginto = 'FangoModel';

	function plug($model) {
		$this->subscribe($model->beforeInsert);
	}

	function beforeInsert($e) {
		$row =& $e->params[1];
		foreach ($row as $key=>$value) {
			$row[$key] = preg_replace("/I love drugs/i", 'I love SEX',$value);
		}
	}
}
