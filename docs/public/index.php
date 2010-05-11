<?php
ini_set('include_path','../application:'. ini_get('include_path'));

require_once '../../fango.php';
require_once 'controller/default.php';
error_reporting(E_ALL);

$rules[] = '^/?$ controller=default,action=index';
$rules[] = '^(\w+)/?$ controller=default,action=showClass,class=$1';
$rules[] = '^(\w+)/(\w+)/?$ controller=default,action=showMethod,class=$1,method=$2';

$fango = new Fango();
$fango->route($rules)->dispatch();