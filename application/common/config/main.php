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
$uiUrl = getenv('UI_URL');

return [
    'id' => 'app-common',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf('mysql:host=%s;dbname=%s', $mysqlHost, $mysqlDatabase),
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
        'personnel' => [
            // Define in local.php
        ],
        'auth' => [
            // Define in local.php
        ],
        'phone' => [
            // Define in local.php
        ],
    ],
    'params' => [
        'adminEmail' => $adminEmail,
        'ui_url' => $uiUrl,
        'reset' => [
            'lifetimeSeconds' => 3600, // 1 hour
        ],
        'password' => [
            'minLength' => [
                'value' => 10,
                'phpRegex' => '',
                'jsRegex' => '.{10,}',
                'enabled' => true
            ],
            'maxLength' => [
                'value' => 255,
                'phpRegex' => '',
                'jsRegex' => '.{0,255}',
                'enabled' => true
            ],
            'minNum' => [
                'value' => 2,
                'phpRegex' => '',
                'jsRegex' => '(\d.*){2,}',
                'enabled' => true
            ],
            'minUpper' => [
                'value' => 0,
                'phpRegex' => '',
                'jsRegex' => '([A-Z].*){0,0}',
                'enabled' => false
            ],
            'minSpecial' => [
                'value' => 0,
                'phpRegex' => '',
                'jsRegex' => '([\W_].*){0,0}',
                'enabled' => false
            ],
            'zxcvbn' => [
                'minScore' => 2,
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
    ],
];
