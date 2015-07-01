<?php

error_reporting(E_ALL ^ E_NOTICE);

define('hotspot', true);
define('root_dir', dirname(__FILE__));

require_once(root_dir . '/engine/init.php');

$tpl->compile('main');
