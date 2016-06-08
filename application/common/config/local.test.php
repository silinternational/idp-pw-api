<?php

use Sil\PhpEnv\Env;

$zxcvbnApiBaseUrl = Env::get('ZXCVBN_API_BASEURL', 'http://zxcvbn:3000');

return [
    'params' => [
        'password' => [
            'minLength' => [
                'value' => 10,
                'phpRegex' => '/.{10,}/',
                'jsRegex' => '.{10,}',
                'enabled' => true
            ],
            'maxLength' => [
                'value' => 255,
                'phpRegex' => '/.{0,255}/',
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
                'value' => 1,
                'phpRegex' => '/([A-Z].*){1,}/',
                'jsRegex' => '([A-Z].*){1,}',
                'enabled' => true
            ],
            'minSpecial' => [
                'value' => 1,
                'phpRegex' => '/([\W_].*){1,}/',
                'jsRegex' => '([\W_].*){1,}',
                'enabled' => true
            ],
            'zxcvbn' => [
                'minScore' => 2,
                'enabled' => true,
                'apiBaseUrl' => $zxcvbnApiBaseUrl,
            ]
        ],
    ],
    'components' => [
        'mailer' => [
            'useFileTransport' => true,
            'transport' => [
                'host' => null,
            ],
        ],
        'personnel' => [
            'class' => 'tests\mock\personnel\Component',
        ],
        'auth' => [
            'class' => 'tests\mock\auth\Component',
        ],
        'phone' => [
            'class' => 'tests\mock\phone\Component',
            'codeLength' => 4,
        ],
    ],
];