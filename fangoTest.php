<?php
require_once 'fango.php';

function test_fangodb_getone() {
	$db = get_db();
	assert($db->getOne("select name,surname from users") == 'andrea');
	assert($db->getOne("select 1 from users") == 1);
	assert($db->getOne("select surname,name from users where name = :name",array('name'=>'andrea')) == 'giardina');
	assert($db->getOne("select surname,name from users where name = ?",array('andrea')) == 'giardina');
}

function test_fangodb_getall() {
	$db = get_db();
	$all = get_all_users();
	$first_row = $all[0];
	assert($db->getAll('select * from users order by id') == $all);
	assert($db->getAll('select * from users where id = :id', array('id'=>1)) == array($first_row));
}

function test_fangodb_getrow() {
	$db = get_db();
	$all = get_all_users();
	$first_row = $all[0];
	$second_row = $all[1];
	assert($db->getRow('select * from users order by id') == $first_row);
	assert($db->getRow('select * from users where id = :id', array('id'=>2)) == $second_row);
}

function test_fangodb_getcol() {
	$db = get_db();
	$col = array('andrea','mario');
	assert($db->getCol('select name from users order by id') == $col);
	assert($db->getCol('select surname from users where id = :id', array('id'=>2)) == array('rossi'));
}

function test_fangomodel_asSelect() {
	$model = get_model();
	assert($model->asSelect() == "SELECT * FROM users");

	$model->reset();
	$model->where("name = ?",'andrea');
	$model->where("surname = ?",'giardina');
	assert($model->asSelect() == "SELECT * FROM users WHERE name = ? AND surname = ?");
	assert($model->params() == array('andrea','giardina'));

	$model->reset();
	$model->where("name = ? AND surname = ?",array('andrea','giardina'));
	assert($model->asSelect() == "SELECT * FROM users WHERE name = ? AND surname = ?");
	assert($model->params() == array('andrea','giardina'));

	$model->reset();
	$model->fields('name','surname')
		  ->where("id = :id",array('id'=>1))
		  ->where("name = :name",array('name'=>'andrea'))
		  ->order('name')
		   ->limit(1,1);
	assert($model == "SELECT name,surname FROM users WHERE id = :id AND name = :name ORDER BY name LIMIT 1 OFFSET 1");
	assert($model->params() == array('id'=>1,'name'=>'andrea'));

}


function test_fangomodel_count() {
	$model = get_model();
	//$model->insert(array('id'=>3,'name'=>'andrea'));
	assert($model->count() == 2);
}

function test_fangomodel_pkparts() {
	$model = get_model();

	$model->pk = 'id';
	$pk_parts = $model->pkParts(array('id'=>1)); 
	assert($pk_parts == array('id = :id',array('id'=>1)));

	$model->pk = array('name','surname');
	try {
		$model->pkParts(array('id'=>1));
		assert(false);
	} catch (Exception $e) {}

	$model->pk = array('name','surname');
	$pk_parts = $model->pkParts(array('name'=>'andrea','surname'=>'giardina'));
	assert($pk_parts == array('name = :name AND surname = :surname',array('name'=>'andrea','surname'=>'giardina')));

	$pk_parts = $model->pkParts(array('name'=>'andrea','surname'=>'sardina'),array('name'=>'andrea','surname'=>'giardina'));
	assert($pk_parts == array('name = :name AND surname = :surname',array('name'=>'andrea','surname'=>'giardina')));

	try {
		$model->pkParts(array('id'),array('name'=>'andrea'));
		assert(false);
	} catch (Exception $e) {}

}

function test_fangomodel_isnew() {
	$model = get_model();
	$model->pk = 'id';
	assert($model->isNew(array('id'=>1)) == false);
	assert($model->isNew(array('id'=>100)) == true);
}


//------------------------------------------------------------------------------
function get_db() {
	$db = new FangoDB('mysql:host=localhost;dbname=fango', 'root', '');
	return $db;
}

function get_model($model = 'users') {
	$db = get_db();
	return $db->model($model);
}

function get_all_users() {
	$all = array();
	$all[] = array('id'=>1,'name'=>'andrea','surname'=>'giardina');
	$all[] = array('id'=>2,'name'=>'mario','surname'=>'rossi');
	return $all;
}

//------------------------------------------------------------------------------

$functions = get_defined_functions();
$functions = $functions['user'];
foreach ($functions as $function) {
	if (strpos($function,'test')===0) {
		call_user_func($function);
	}
}

