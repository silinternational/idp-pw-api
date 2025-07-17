<?php

$config = require('../config/load-configs.php');

$application = new yii\web\Application($config);
$application->run();
