<?php

/* Get frontend-specific config settings from ENV vars or set defaults. */
$frontCookieKey = getenv('FRONT_COOKIE_KEY') ?: null;
const UID_ROUTE_PATTERN = '<uid:([a-zA-Z0-9_\-]{32})>';

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'errorHandler'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
        ],
        'log' => [

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
                /*
                 * Auth routes
                 */
                'GET /auth/login' => 'auth/login',
                'POST /auth/login' => 'auth/login',
                'GET /auth/logout' => 'auth/logout',
                'POST /auth/login/token' => 'auth/token',

                /*
                 * Config routes
                 */
                'GET /config' => 'config/index',

                /*
                 * Method routes
                 */
                'GET /method' => 'method/index',
                'GET /method/' . UID_ROUTE_PATTERN => 'method/view',
                'POST /method' => 'method/create',
                'PUT /method/' . UID_ROUTE_PATTERN => 'method/update',
                'DELETE /method/' . UID_ROUTE_PATTERN => 'method/delete',

                /*
                 * Password routes
                 */
                'GET /password' => 'password/view',
                'PUT /password' => 'password/update',

                /*
                 * Reset routes
                 */
                'POST /reset' => 'reset/create',
                'PUT /reset/' . UID_ROUTE_PATTERN => 'reset/update',
                'PUT /reset/' . UID_ROUTE_PATTERN . '/resend' => 'reset/resend',
                'PUT /reset/' . UID_ROUTE_PATTERN . '/validate' => 'reset/validate',

                /*
                 * User  routes
                 */
                'GET /user/me' => 'user/me',

            ]
        ]
    ],
    'params' => [
        'sessionDuration' => 1800, // 30 minutes
    ],
];
