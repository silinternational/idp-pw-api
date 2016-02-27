<?php

/* Get frontend-specific config settings from ENV vars or set defaults. */
$frontCookieKey = getenv('FRONT_COOKIE_KEY') ?: null;

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log','errorHandler'],
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
                'GET /method/{id}' => 'method/view',
                'POST /method' => 'method/create',
                'PUT /method/{id}' => 'method/update',
                'DELETE /method/{id}' => 'method/delete',

                /*
                 * Password routes
                 */
                'GET /password' => 'password/view',
                'PUT /password' => 'password/update',

                /*
                 * Reset routes
                 */
                'POST /reset' => 'reset/create',
                'PUT /reset/{id}' => 'reset/update',
                'PUT /reset/{id}/resend' => 'reset/resend',
                'PUT /reset/{id}/validate' => 'reset/validate',

                /*
                 * User  routes
                 */
                'GET /user/me' => 'user/me',

            ]
        ]
    ],
    'params' => [
        'saml' => [
            'default-sp' => 'default-sp',
            'fields' => [
                'idp_uid' => ['field' => 'eduPersonPrincipalName', 'element' => 0],
                'first_name' => ['field' => 'givenName', 'element' => 0],
                'last_name' => ['field' => 'sn', 'element' => 0],
                'email' => ['field' => 'mail', 'element' => 0],
                'groups' => ['field' => 'groups'],
                'employee_id' => ['field' => 'gisEisPersonId', 'element' => 0],
            ]
        ],
        'sessionDuration' => 28800, // 8 hours
    ],
];
