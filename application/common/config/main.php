<?php
/*
 * Get config settings from ENV vars or set defaults
 */
$mysqlHost = getenv('MYSQL_HOST');
$mysqlDatabase = getenv('MYSQL_DATABASE');
$mysqlUser = getenv('MYSQL_USER');
$mysqlPassword = getenv('MYSQL_PASSWORD');
$adminEmail = getenv('ADMIN_EMAIL');
$appEnv = getenv('APP_ENV');
$recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY');
$recaptchaSecretKey = getenv('RECAPTCHA_SECRET_KEY');

return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf('mysql:host=%s;dbname=%s',$mysqlHost,$mysqlDatabase),
            'username' => $mysqlUser,
            'password' => $mysqlPassword,
            'charset' => 'utf8',
            'emulatePrepare' => false,
            'tablePrefix' => '',
        ],
        'log' => [
            'traceLevel' => 0,
            'targets' => [
                [
                    'class' => 'Sil\JsonSyslog\JsonSyslogTarget',
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'yii\web\HttpException:401',
                        'yii\web\HttpException:404',
                    ],
                    'logVars' => [], // Disable logging of _SERVER, _POST, etc.
                    'prefix' => function($message) use ($appEnv) {
                        $prefixData = array(
                            'env' => $appEnv,
                        );

                        // There is no user when a console command is run
                        try {
                            $appUser = \Yii::$app->user;
                        } catch (\Exception $e) {
                            $appUser = null;
                        }
                        if ($appUser && ! \Yii::$app->user->isGuest) {
                            $prefixData['user'] = \Yii::$app->user->identity->email;
                        }
                        return \yii\helpers\Json::encode($prefixData);
                    },
                ],
            ],
        ],
    ],
    'params' => [
        'adminEmail' => $adminEmail,
        'reset' => [
            'lifetimeSeconds' => 3600, // 1 hour
        ],
        'password' => [
            'minLength' => [
                'value' => 10,
                'phpRegex' => '',
                'jsRegex' => '',
            ],
            'maxLength' => [
                'value' => 255,
                'phpRegex' => '',
                'jsRegex' => '',
            ],
            'minNum' => [
                'value' => 2,
                'phpRegex' => '',
                'jsRegex' => '',
            ],
            'minUpper' => [
                'value' => 0,
                'phpRegex' => '',
                'jsRegex' => '',
            ],
            'minSpecial' => [
                'value' => 0,
                'phpRegex' => '',
                'jsRegex' => '',
            ],
            'blacklist' => [

            ],
            'zxcvbn' => [
                'minScore' => 2,
                'displaySuggestions' => true,
                'displayWarnings' => true,
            ]
        ],
        'recaptcha' => [
            'siteKey' => $recaptchaSiteKey,
            'secretKey' => $recaptchaSecretKey,
        ],
        'support' => [
            'phone' => '',
            'email' => '',
            'url' => '',
            'feedbackUrl' => '',
        ],
        'gaTrackingId' => '',
    ],
];
