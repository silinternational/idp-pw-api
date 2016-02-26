<?php

/* Get frontend-specific config settings from ENV vars or set defaults. */
$frontCookieKey = getenv('FRONT_COOKIE_KEY') ?: null;

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'request' => [
            'enableCookieValidation' => true,
            'enableCsrfValidation' => true,
            'cookieValidationKey' => $frontCookieKey,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
            ]
        ]
    ],
    'params' => [
        'saml' => [
            'default-sp' => 'default-sp',
            'fields' => [
                'idp_uid' => ['field' => 'eduPersonPrincipalName', 'element' => 0 ],
                'first_name' => ['field' => 'givenName', 'element' => 0 ],
                'last_name' => ['field' => 'sn', 'element' => 0 ],
                'email' => ['field' => 'mail', 'element' => 0 ],
                'groups' => ['field' => 'groups'],
                'employee_id' => ['field' => 'gisEisPersonId', 'element' => 0],
            ]
        ],
        'sessionDuration' => 28800, // 8 hours
    ],
];
