<?php

use Sil\PhpEnv\Env;

/* Get frontend-specific config settings from ENV vars or set defaults. */
$frontCookieSecure = Env::get('FRONT_COOKIE_SECURE', false);

$sessionLifetime = 1800; // 30 minutes

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
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'session' => [
            'cookieParams' => [ // http://us2.php.net/manual/en/function.session-set-cookie-params.php
                'lifetime' => $sessionLifetime,
                'path' => '/',
                'httponly' => true,
                'secure' => $frontCookieSecure,
            ],
        ],
        'log' => [

        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'request' => [
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                /*
                 * Auth routes
                 */
                'GET /auth/login' => 'auth/login',
                'POST /auth/login' => 'auth/login',
                'GET /auth/logout' => 'auth/logout',

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
                'OPTIONS /method' => 'method/options',
                'OPTIONS /method/' . UID_ROUTE_PATTERN => 'method/options',

                /*
                 * Password routes
                 */
                'GET /password' => 'password/view',
                'PUT /password' => 'password/update',
                'OPTIONS /password' => 'password/options',

                /*
                 * Reset routes
                 */
                'GET /reset/' . UID_ROUTE_PATTERN => 'reset/view',
                'POST /reset' => 'reset/create',
                'PUT /reset/' . UID_ROUTE_PATTERN => 'reset/update',
                'PUT /reset/' . UID_ROUTE_PATTERN . '/resend' => 'reset/resend',
                'PUT /reset/' . UID_ROUTE_PATTERN . '/validate' => 'reset/validate',
                'OPTIONS /reset' => 'reset/options',
                'OPTIONS /reset/' . UID_ROUTE_PATTERN => 'reset/options',
                'OPTIONS /reset/' . UID_ROUTE_PATTERN . '/resend' => 'reset/options',
                'OPTIONS /reset/' . UID_ROUTE_PATTERN . '/validate' => 'reset/options',

                /*
                 * User  routes
                 */
                'GET /user/me' => 'user/me',
                'OPTIONS /user/me' => 'user/options',
            ]
        ]
    ],
    'params' => [
        
    ],
];
