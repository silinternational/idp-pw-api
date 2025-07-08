<?php

$config = require('../config/load-configs.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (getenv('MYSQL_ATTR_SSL_CA')) {
    $caPath = '/data/console/runtime';
    $caFile = $caPath . '/ca.pem';
    $decoded = base64_decode(getenv('MYSQL_ATTR_SSL_CA'));
    if (file_put_contents($caFile, $decoded) === false) {
        $err = " perms: " . sprintf('%o', fileperms($caPath));
        $err .= " user: " . get_current_user();
        $err .= " whoami: " . exec('whoami');
        $err .= " owner: " . json_encode(posix_getpwuid(fileowner($caPath)));
        die('Failed to write database SSL certificate file: ' . $caFile . $err);
    }
    chmod($caFile, 0600);
}

$application = new yii\web\Application($config);
$application->run();
