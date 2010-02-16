<?php
class StupidPlugin extends FangoObserver {
	function beforeInsert($e) {
		$row =& $e->params[1];
		$row['message'] = preg_replace("/I love drugs/i", 'I love sex',$row['message']);
	}
}
