<?php
// This is global bootstrap for autoloading

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../common/config/bootstrap.php');
require(__DIR__ . '/../frontend/config/bootstrap.php');


$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../common/config/main.php'),
    require(__DIR__ . '/../common/config/local.php'),
    require(__DIR__ . '/../frontend/config/main.php')
);

$config['basePath'] = dirname(__DIR__);

(new yii\web\Application($config));