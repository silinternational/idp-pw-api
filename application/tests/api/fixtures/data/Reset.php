<?php

return [
    'reset1' => [
        'id' => 1,
        'uid' => '11111111111111111111111111111111',
        'user_id' => 1,
        'type' => 'primary',
        'email' => null,
        'code' => null,
        'attempts' => 0,
        'expires' => '2016-03-01 12:00:00',
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
    'reset2' => [// phone
        'id' => 2,
        'uid' => '22222222222222222222222222222222',
        'user_id' => 2,
        'type' => 'method',
        'email' => null,
        'code' => null,
        'attempts' => 0,
        'expires' => '2016-03-01 12:00:00',
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
    'reset3' => [// email
        'id' => 3,
        'uid' => '33333333333333333333333333333333',
        'user_id' => 3,
        'type' => 'method',
        'email' => 'email-1456769679@domain.org',
        'code' => 333,
        'attempts' => 0,
        'expires' => '2029-03-01 12:00:00',
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
    'reset4' => [// email
        'id' => 4,
        'uid' => '33333333333333333333333333333334',
        'user_id' => 4,
        'type' => 'method',
        'email' => 'email-1456769679@domain.org',
        'code' => 444,
        'attempts' => 0,
        'expires' => '2016-03-01 12:00:00',
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
];
