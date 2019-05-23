<?php

use common\components\Emailer;
use Sil\JsonLog\target\EmailServiceTarget;
use Sil\JsonLog\target\JsonSyslogTarget;
use Sil\PhpEnv\Env;
use yii\helpers\ArrayHelper;

/*
 * Get config settings from ENV vars or set defaults
 */
$mysqlHost = Env::get('MYSQL_HOST');
$mysqlDatabase = Env::get('MYSQL_DATABASE');
$mysqlUser = Env::get('MYSQL_USER');
$mysqlPassword = Env::get('MYSQL_PASSWORD');

$alertsEmail = Env::get('ALERTS_EMAIL');
$alertsEmailEnabled = Env::get('ALERTS_EMAIL_ENABLED');
$emailSignature = Env::get('EMAIL_SIGNATURE', Env::get('FROM_NAME'));
$appEnv = Env::get('APP_ENV');
$idpName = Env::get('IDP_NAME');
$idpDisplayName = Env::get('IDP_DISPLAY_NAME', $idpName);
$recaptchaRequired = Env::get('RECAPTCHA_REQUIRED', true);
$recaptchaSiteKey = Env::get('RECAPTCHA_SITE_KEY');
$recaptchaSecretKey = Env::get('RECAPTCHA_SECRET_KEY');
$uiUrl = Env::get('UI_URL');
$uiCorsOrigin = Env::get('UI_CORS_ORIGIN', $uiUrl);
$helpCenterUrl = Env::get('HELP_CENTER_URL');
$codeLength = Env::get('CODE_LENGTH', 6);
$supportEmail = Env::get('SUPPORT_EMAIL');
$supportName = Env::get('SUPPORT_NAME', 'support');
$supportPhone = Env::get('SUPPORT_PHONE');
$supportUrl = Env::get('SUPPORT_URL');
$supportFeedback = Env::get('SUPPORT_FEEDBACK');
$accessTokenHashKey = Env::get('ACCESS_TOKEN_HASH_KEY');

$emailerClass = Env::get('EMAILER_CLASS', Emailer::class);
$emailServiceConfig = Env::getArrayFromPrefix('EMAIL_SERVICE_');
$emailServiceConfig['validIpRanges'] = Env::getArray('EMAIL_SERVICE_validIpRanges');

$authClass = Env::get('AUTH_CLASS', 'common\components\auth\Saml');
$authConfig = Env::getArrayFromPrefix('AUTH_SAML_');

$personnelClass = Env::get('PERSONNEL_CLASS', 'common\components\personnel\IdBroker');

$passwordStoreClass = Env::get('PASSWORDSTORE_CLASS', 'common\components\passwordStore\IdBroker');

$idBrokerConfig = Env::getArrayFromPrefix('ID_BROKER_');
$idBrokerConfig['validIpRanges'] = Env::getArray('ID_BROKER_validIpRanges');

$zxcvbnApiBaseUrl = Env::get('ZXCVBN_API_BASEURL');

$passwordRulesEnv = Env::getArrayFromPrefix('PASSWORD_RULE_');
$passwordRules = [
    'minLength' => $passwordRulesEnv['minLength'] ?? 10,
    'maxLength' => $passwordRulesEnv['maxLength'] ?? 255,
    'minScore' => $passwordRulesEnv['minScore'] ?? 3,
    'enableHIBP' => $passwordRulesEnv['enableHIBP'] ?? true,
];

return [
    'id' => 'app-common',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'sourceLanguage' => '00',
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
                        'yii\web\HttpException:410',
                        'yii\web\HttpException:422',
                        'yii\web\HttpException:429',
                        'Sil\EmailService\Client\EmailServiceClientException',
                    ],
                    'logVars' => [], // Disable logging of _SERVER, _POST, etc.
                    'message' => [
                        'to' => $alertsEmail ?? '(disabled)',
                        'subject' => 'ALERT - ' . $idpName . ' PW [env=' . $appEnv . ']',
                    ],
                    'baseUrl' => $emailServiceConfig['baseUrl'],
                    'accessToken' => $emailServiceConfig['accessToken'],
                    'assertValidIp' => $emailServiceConfig['assertValidIp'],
                    'validIpRanges' => $emailServiceConfig['validIpRanges'],
                    'enabled' => $alertsEmailEnabled,
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
            ],
        ],
        'emailer' => [
            'class' => $emailerClass,
            'emailServiceConfig' => $emailServiceConfig,
        ],
        'personnel' => ['class' => $personnelClass],
        'auth' => ArrayHelper::merge(
            ['class' => $authClass],
            $authConfig
        ),
        'passwordStore' => ['class' => $passwordStoreClass],
        /*
         * i18n component must be defined in common because of unit tests that depend on it
         */
        'i18n' => [
            'translations' => [
                '*' => [
                    'class'          => 'yii\i18n\PhpMessageSource',
                    'basePath'       => '@frontend/messages',
                    'sourceLanguage' => '00',
                    'fileMap'        => [
                        'app' => 'app.php',
                        'model' => 'model.php',
                    ],
                ],
            ],
        ],
    ],
    'params' => [
        'idpDisplayName' => $idpDisplayName,
        'emailSignature' => $emailSignature,
        'helpCenterUrl' => $helpCenterUrl,
        'uiUrl' => $uiUrl,
        'uiCorsOrigin' => $uiCorsOrigin,
        'reset' => [
            'lifetimeSeconds' => 3600,  // 1 hour
            'gracePeriod' => '-1 week', // time between expiration and deletion, relative to now (time of execution)
            'disableDuration' => 900,   // 15 minutes
            'codeLength' => $codeLength,
            'maxAttempts' => 10,
        ],
        'accessTokenHashKey' => $accessTokenHashKey,
        'accessTokenLifetime' => 1800, // 30 minutes
        'passwordRules' => $passwordRules,
        'zxcvbnApiBaseUrl' => $zxcvbnApiBaseUrl,
        'recaptcha' => [
            'required' => $recaptchaRequired,
            'siteKey' => $recaptchaSiteKey,
            'secretKey' => $recaptchaSecretKey,
        ],
        'support' => [
            'name' => $supportName,
            'phone' => $supportPhone,
            'email' => $supportEmail,
            'url' => $supportUrl,
            'feedbackUrl' => $supportFeedback,
        ],
        'idBrokerConfig' => $idBrokerConfig,
    ],
];
