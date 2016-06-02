<?php

return [
    'params' => [
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
                'value' => 1,
                'phpRegex' => '',
                'jsRegex' => '([A-Z].*){1,}',
                'enabled' => true
            ],
            'minSpecial' => [
                'value' => 1,
                'phpRegex' => '',
                'jsRegex' => '([\W_].*){1,}',
                'enabled' => true
            ],
            'zxcvbn' => [
                'minScore' => 2,
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