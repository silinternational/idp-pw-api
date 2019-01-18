<?php

use Sil\PhpEnv\Env;

$zxcvbnApiBaseUrl = Env::get('ZXCVBN_API_BASEURL', 'http://zxcvbn:3000');

return [
    'params' => [
        'accessTokenHashKey' => 'KEY4TESTING',
        'password' => [
            'minLength' => [
                'value' => 10,
                'phpRegex' => '/.{10,}/',
                'jsRegex' => '.{10,}',
                'enabled' => true
            ],
            'zxcvbn' => [
                'minScore' => 2,
                'enabled' => true,
                'apiBaseUrl' => $zxcvbnApiBaseUrl,
            ]
        ],
    ],
];
