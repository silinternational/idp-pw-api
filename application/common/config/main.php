<?php

use Sil\PhpEnv\Env;

/*
 * Get config settings from ENV vars or set defaults
 */
$mysqlHost = Env::get('MYSQL_HOST');
$mysqlDatabase = Env::get('MYSQL_DATABASE');
$mysqlUser = Env::get('MYSQL_USER');
$mysqlPassword = Env::get('MYSQL_PASSWORD');
$mailerUseFiles = Env::get('MAILER_USEFILES', false);
$mailerHost = Env::get('MAILER_HOST');
$mailerUsername = Env::get('MAILER_USERNAME');
$mailerPassword = Env::get('MAILER_PASSWORD');
$adminEmail = Env::get('ADMIN_EMAIL');
$fromEmail = Env::get('FROM_EMAIL');
$fromName = Env::get('FROM_NAME');
$appEnv = Env::get('APP_ENV');
$idpName = Env::get('IDP_NAME');
$idpUsernameHint = Env::get('IDP_USERNAME_HINT', $idpName . ' username, ex: first_last');
$recaptchaSiteKey = Env::get('RECAPTCHA_SITE_KEY');
$recaptchaSecretKey = Env::get('RECAPTCHA_SECRET_KEY');
$uiUrl = Env::get('UI_URL');
$helpCenterUrl = Env::get('HELP_CENTER_URL');
$codeLength = Env::get('CODE_LENGTH', 6);
$supportPhone = Env::get('SUPPORT_PHONE');
$supportEmail = Env::get('SUPPORT_EMAIL');
$supportUrl = Env::get('SUPPORT_URL');
$supportFeedback = Env::get('SUPPORT_FEEDBACK');

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
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => $mailerUseFiles,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => $mailerHost,
                'username' => $mailerUsername,
                'password' => $mailerPassword,
                'port' => '465',
                'encryption' => 'ssl',
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
        'idpName' => $idpName,
        'idpUsernameHint' => $idpUsernameHint,
        'adminEmail' => $adminEmail,
        'fromEmail' => $fromEmail,
        'fromName' => $fromName,
        'helpCenterUrl' => $helpCenterUrl,
        'ui_url' => $uiUrl,
        'reset' => [
            'lifetimeSeconds' => 3600, // 1 hour
            'disableDuration' => 900, // 15 minutes
            'codeLength' => $codeLength,
            'maxAttempts' => 10,
        ],
        'passwordLifetime' => 15552000, // 6 months
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
                'enabled' => true,
            ]
        ],
        'recaptcha' => [
            'siteKey' => $recaptchaSiteKey,
            'secretKey' => $recaptchaSecretKey,
        ],
        'support' => [
            'phone' => $supportPhone,
            'email' => $supportEmail,
            'url' => $supportEmail,
            'feedbackUrl' => $supportFeedback,
        ],
    ],
];
