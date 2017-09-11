<?php

use Sil\PhpEnv\Env;
use Sil\JsonLog\target\EmailServiceTarget;
use Sil\JsonLog\target\JsonSyslogTarget;
use Sil\Log\EmailTarget;

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
$alertsEmail = Env::get('ALERTS_EMAIL');
$alertsEmailEnabled = Env::get('ALERTS_EMAIL_ENABLED');
$fromEmail = Env::get('FROM_EMAIL');
$fromName = Env::get('FROM_NAME');
$appEnv = Env::get('APP_ENV');
$idpName = Env::get('IDP_NAME');
$idpDisplayName = Env::get('IDP_DISPLAY_NAME', $idpName);
$idpUsernameHint = Env::get('IDP_USERNAME_HINT', $idpDisplayName . ' username, ex: first_last');
$recaptchaRequired = Env::get('RECAPTCHA_REQUIRED', true);
$recaptchaSiteKey = Env::get('RECAPTCHA_SITE_KEY');
$recaptchaSecretKey = Env::get('RECAPTCHA_SECRET_KEY');
$uiUrl = Env::get('UI_URL');
$logoUrl = Env::get('LOGO_URL');
$uiCorsOrigin = Env::get('UI_CORS_ORIGIN');
$helpCenterUrl = Env::get('HELP_CENTER_URL');
$codeLength = Env::get('CODE_LENGTH', 6);
$supportPhone = Env::get('SUPPORT_PHONE');
$supportEmail = Env::get('SUPPORT_EMAIL');
$supportUrl = Env::get('SUPPORT_URL');
$supportFeedback = Env::get('SUPPORT_FEEDBACK');
$zxcvbnApiBaseUrl = Env::get('ZXCVBN_API_BASEURL');
$accessTokenHashKey = Env::get('ACCESS_TOKEN_HASH_KEY');

/*
 * If using Email Service, the following ENV vars should be set:
 *   EMAIL_SERVICE_useEmailService=true
 *   EMAIL_SERVICE_baseUrl=
 *   EMAIL_SERVICE_accessToken=
 *   EMAIL_SERVICE_assertValidIp=true
 *   EMAIL_SERVICE_validIpRanges=127.0.0.1/32,10.0.55.0/24
 */
$emailServiceConfig = Env::getArrayFromPrefix('EMAIL_SERVICE_');
$emailServiceConfig['useEmailService'] = $emailServiceConfig['useEmailService'] ?? false;
if ( ! $emailServiceConfig['useEmailService']) {
    $emailServiceConfig['baseUrl'] = $emailServiceConfig['baseUrl'] ?? 'invalid';
    $emailServiceConfig['accessToken'] = $emailServiceConfig['accessToken'] ?? 'invalid';
    $emailServiceConfig['assertValidIp'] = $emailServiceConfig['assertValidIp'] ?? false;
}
$emailServiceConfig['validIpRanges'] = Env::getArray('EMAIL_SERVICE_validIpRanges');

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
                    'class' => JsonSyslogTarget::class,
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'yii\web\HttpException:401',
                        'yii\web\HttpException:404',
                    ],
                    'logVars' => [], // Disable logging of _SERVER, _POST, etc.
                    'prefix' => function($message) use ($appEnv) {
                        $prefixData = [
                            'env' => $appEnv,
                        ];

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
                [
                    'class' => EmailServiceTarget::class,
                    'levels' => ['error'],
                    'except' => [
                        'yii\web\HttpException:400',
                        'yii\web\HttpException:401',
                        'yii\web\HttpException:404',
                        'yii\web\HttpException:409',
                        'yii\web\HttpException:422',
                        'Sil\EmailService\Client\EmailServiceClientException',
                    ],
                    'logVars' => [], // Disable logging of _SERVER, _POST, etc.
                    'message' => [
                        'to' => $alertsEmail,
                        'subject' => 'ALERT - ' . $idpName . ' PW [env=' . $appEnv .']',
                    ],
                    'baseUrl' => $emailServiceConfig['baseUrl'],
                    'accessToken' => $emailServiceConfig['accessToken'],
                    'assertValidIp' => $emailServiceConfig['assertValidIp'],
                    'validIpRanges' => $emailServiceConfig['validIpRanges'],
                    'enabled' => $emailServiceConfig['useEmailService'] && $alertsEmailEnabled,
                    'prefix' => function($message) use ($appEnv) {
                        $prefixData = [
                            'env' => $appEnv,
                        ];

                        // There is no user when a console command is run
                        try {
                            $appUser = \Yii::$app->user;
                        } catch (\Exception $e) {
                            $appUser = null;
                        }
                        if ($appUser && ! \Yii::$app->user->isGuest){
                            $prefixData['user'] = \Yii::$app->user->identity->email;
                        }

                        // Try to get requested url and method
                        try {
                            $request = \Yii::$app->request;
                            $prefixData['url'] = $request->getUrl();
                            $prefixData['method'] = $request->getMethod();
                        } catch (\Exception $e) {
                            $prefixData['url'] = 'not available';
                        }

                        return $prefixData;
                    },
                ],
                [
                    'class' => EmailTarget::class,
                    'levels' => ['error'],
                    'except' => [
                        'yii\web\HttpException:400',
                        'yii\web\HttpException:401',
                        'yii\web\HttpException:404',
                        'yii\web\HttpException:409',
                        'yii\web\HttpException:422',
                    ],
                    'logVars' => [], // Disable logging of _SERVER, _POST, etc.
                    'message' => [
                        'from' => $fromEmail,
                        'to' => $alertsEmail,
                        'subject' => 'ALERT - ' . $idpName . ' PW [env=' . $appEnv .']',
                    ],
                    'enabled' => $alertsEmailEnabled && ! $emailServiceConfig['useEmailService'],
                    'prefix' => function($message) use ($appEnv) {
                        $prefix = 'env=' . $appEnv . PHP_EOL;

                        // There is no user when a console command is run
                        try {
                            $appUser = \Yii::$app->user;
                        } catch (\Exception $e) {
                            $appUser = Null;
                        }
                        if ($appUser && ! \Yii::$app->user->isGuest){
                            $prefix .= 'user='.\Yii::$app->user->identity->email . PHP_EOL;
                        }

                        // Try to get requested url and method
                        try {
                            $request = \Yii::$app->request;
                            $prefix .= 'Requested URL: ' . $request->getUrl() . PHP_EOL;
                            $prefix .= 'Request method: ' . $request->getMethod() . PHP_EOL;
                        } catch (\Exception $e) {
                            $prefix .= 'Requested URL: not available';
                        }

                        return PHP_EOL . $prefix;
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
        'idpDisplayName' => $idpDisplayName,
        'idpUsernameHint' => $idpUsernameHint,
        'adminEmail' => $adminEmail,
        'fromEmail' => $fromEmail,
        'fromName' => $fromName,
        'helpCenterUrl' => $helpCenterUrl,
        'uiUrl' => $uiUrl,
        'logoUrl' => $logoUrl,
        'uiCorsOrigin' => $uiCorsOrigin,
        'emailQueueBatchSize' => 5,
        'emailServiceStatus' => $emailServiceConfig,
        'emailVerification' => $emailServiceConfig,
        'reset' => [
            'lifetimeSeconds' => 3600, // 1 hour
            'disableDuration' => 900, // 15 minutes
            'codeLength' => $codeLength,
            'maxAttempts' => 10,
        ],
        'accessTokenHashKey' => $accessTokenHashKey,
        'accessTokenLifetime' => 1800, // 30 minutes
        'passwordLifetime' => 'P1Y', // See http://php.net/manual/en/dateinterval.construct.php
        'password' => [
            'minLength' => [
                'value' => 10,
                'phpRegex' => '/.{10,}/',
                'jsRegex' => '.{10,}',
                'enabled' => true
            ],
            'maxLength' => [
                'value' => 255,
                'phpRegex' => '/^.{0,255}$/',
                'jsRegex' => '.{0,255}',
                'enabled' => true
            ],
            'minNum' => [
                'value' => 2,
                'phpRegex' => '/(\d.*){2,}/',
                'jsRegex' => '(\d.*){2,}',
                'enabled' => true
            ],
            'minUpper' => [
                'value' => 0,
                'phpRegex' => '/([A-Z].*){0,}/',
                'jsRegex' => '([A-Z].*){0,}',
                'enabled' => false
            ],
            'minSpecial' => [
                'value' => 0,
                'phpRegex' => '/([\W_].*){0,}/',
                'jsRegex' => '([\W_].*){0,}',
                'enabled' => false
            ],
            'zxcvbn' => [
                'minScore' => 2,
                'enabled' => true,
                'apiBaseUrl' => $zxcvbnApiBaseUrl,
            ]
        ],
        'recaptcha' => [
            'required' => $recaptchaRequired,
            'siteKey' => $recaptchaSiteKey,
            'secretKey' => $recaptchaSecretKey,
        ],
        'support' => [
            'phone' => $supportPhone,
            'email' => $supportEmail,
            'url' => $supportUrl,
            'feedbackUrl' => $supportFeedback,
        ],
    ],
];
