<?php

use Sil\PhpEnv\Env;
use yii\helpers\ArrayHelper;

require(__DIR__ . '/../../vendor/autoload.php');

define('YII_ENV', Env::get('APP_ENV', 'prod'));
define('YII_DEBUG', YII_ENV === 'dev' || YII_ENV === 'test');

require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

require(__DIR__ . '/../../common/config/bootstrap.php');

if (is_file(__DIR__ . '/../../common/config/local.php')) {
    $localConfig = require(__DIR__ . '/../../common/config/local.php');
} else {
    $localConfig = [];
}

$config = ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    $localConfig,
    require(__DIR__ . '/../config/main.php')
);

return $config;
