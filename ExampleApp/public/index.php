<?php
//Change the include path to point to your application directory
ini_set('include_path','../application:'. ini_get('include_path'));

//Change the include path of fango if you need
require_once '../../fango.php';
require_once 'controllers/default.php';

//Just to show how plugins work
FangoPlugin::load('stupid');

FangoDB::connect('mysql:dbname=fango;host=127.0.0.1','root');
$fango = new Fango();


//I'd like to have just a Controller for this application so a specify
//a custom rule using a regular expresssion
//By default the rules are /controller/actionname/par1/value1/par2/value2...
//The controller has to have a method named {actionname}Action, eg. indexAction
$fango->run('(\w+)/?(.*)$ controller=default,action=$1,params=$2');

