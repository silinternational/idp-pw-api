<?php

defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'test');

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require_once(__DIR__ . '/../../common/config/bootstrap.php');
require_once(__DIR__ . '/../config/bootstrap.php');

//define('C3_CODECOVERAGE_ERROR_LOG_FILE', __DIR__ . '/../../tests/_output/c3_error.log'); //Optional (if not set the default c3 output dir will be used)
//require_once(__DIR__ . '/c3.backup.php');
//require_once(__DIR__ . '/../../c3.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/local.php'),
    require(__DIR__ . '/../config/main.php')
);

// define('C3_CODECOVERAGE_ERROR_LOG_FILE', './c3_error.log'); //Optional (if not set the default c3 output dir will be used)
require_once(__DIR__ . '/../../c3.php');
define('MY_APP_STARTED', true);
//$_SERVER['SCRIPT_NAME'] = '/index-test.php';
//App::start();

$application = new yii\web\Application($config);
$application->run();
