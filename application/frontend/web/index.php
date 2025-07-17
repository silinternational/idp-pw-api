<?php

$config = require('../config/load-configs.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$application = new yii\web\Application($config);
$application->run();
