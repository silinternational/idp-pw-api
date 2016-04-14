<?php

return [
    'components' => [
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
    ]
];