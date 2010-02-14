<?php
//Change the include path to point to your application directory
ini_set('include_path','../application:'. ini_get('include_path'));

//Include fango.php
require_once '../../fango.php';
require_once 'controllers/default.php';

//The DB with a dbo connection string
$db = new FangoDB('mysql:dbname=fango;host=127.0.0.1','root');
$fango = new Fango($db);

//I'd like to have just a Controller for this application so a specify a custom rule
$fango->run("/(.*) controller=default,action=$1");
