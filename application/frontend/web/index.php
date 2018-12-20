<?php

try {
    require(__DIR__ . '/../../vendor/autoload.php');

    define('YII_ENV', Env::get('APP_ENV', 'prod'));
    define('YII_DEBUG', YII_ENV !== 'prod');

    require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
    require(__DIR__ . '/../../common/config/bootstrap.php');
    require(__DIR__ . '/../config/bootstrap.php');

    /*
     * Load environment config if present, else expect local.php
     */
    $appEnv = \Sil\PhpEnv\Env::get('APP_ENV', 'production');
    $validEnvs = ['test', 'development', 'dev', 'staging', 'stage', 'stg', 'production', 'prod', 'prd'];
    if ( ! in_array($appEnv, $validEnvs, true)) {
        throw new \yii\web\ServerErrorHttpException('Invalid APP_ENV provided');
    }
    $configPath = __DIR__ . '/../../common/config';
    if (file_exists($configPath . '/' . $appEnv . '.php')) {
        $envConfig = require $configPath . '/' . $appEnv . '.php';
    } elseif (file_exists($configPath . '/local.php')) {
        $envConfig = require $configPath . '/local.php';
    }

    $config = yii\helpers\ArrayHelper::merge(
        require(__DIR__ . '/../../common/config/main.php'),
        $envConfig,
        require(__DIR__ . '/../config/main.php')
    );

    $application = new yii\web\Application($config);
    $application->run();
} catch (Sil\PhpEnv\EnvVarNotFoundException $e) {
    
    // Log to syslog (Logentries).
    openlog('pw-api', LOG_NDELAY | LOG_PERROR, LOG_USER);
    syslog(LOG_CRIT, $e->getMessage());
    closelog();
    
    // Return error response code/message to HTTP request.
    header('Content-Type: application/json');
    http_response_code(500);
    $responseContent = json_encode([
        'name' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'status' => 500,
    ], JSON_PRETTY_PRINT);
    exit($responseContent);
}
