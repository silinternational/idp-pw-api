<?php

$config = require('../config/load-configs.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (getenv('SSL_CA_BASE64')) {
    $caPath = '/data/console/runtime';
    $caFile = $caPath . '/ca.pem';
    $decoded = base64_decode(getenv('SSL_CA_BASE64'));
    if (file_put_contents($caFile, $decoded) === false) {
        $err = " user: " . get_current_user();
        $err .= " whoami: " . exec('whoami');
        $err .= " perms: " . sprintf('%o', fileperms($caPath));
        $err .= " owner: " . json_encode(posix_getpwuid(fileowner($caPath)));
        fwrite(STDERR, 'Failed to write database SSL certificate file: ' . $caFile . $err);
        exit(1);
    }
    chmod($caFile, 0600);
}

$application = new yii\web\Application($config);
$application->run();
