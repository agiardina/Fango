<?php
//Change the include path to point to your application directory
ini_set('include_path','../application:'. ini_get('include_path'));
error_reporting(E_ALL);

//Change the include path of fango if you need
require_once '../../fango.php';
require_once 'controllers/default.php';
require_once 'plugins/stupid.php';

FangoPlugin::load('stupid');

//The DB with a dbo connection string
$db = new FangoDB('mysql:dbname=fango;host=127.0.0.1','root');
$fango = new Fango($db);

//I'd like to have just a Controller for this application so a specify a custom rule using a regular expresssion
$fango->run('(\w+)/?$ controller=default,action=$1');

