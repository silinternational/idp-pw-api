<?php
/*
 * Get config settings from ENV vars or set defaults
 */
$mysqlHost = getenv('MYSQL_HOST');
$mysqlDatabase = getenv('MYSQL_DATABASE');
$mysqlUser = getenv('MYSQL_USER');
$mysqlPassword = getenv('MYSQL_PASSWORD');
$adminEmail = getenv('ADMIN_EMAIL');

return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf('mysql:host=%s;dbname=%s',$mysqlHost,$mysqlDatabase),
            'username' => $mysqlUser,
            'password' => $mysqlPassword,
            'charset' => 'utf8',
            'emulatePrepare' => false,
            'tablePrefix' => '',
        ],
    ],
    'params' => [
        'adminEmail' => $adminEmail,
        'reset' => [
            'lifetimeSeconds' => 3600, // 1 hour
        ],
    ],
];
