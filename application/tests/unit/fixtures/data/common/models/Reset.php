<?php

use common\helpers\Utils;

return [
    'reset1' => [
        'id' => 1,
        'uid' => '11111111111111111111111111111111',
        'user_id' => 1,
        'type' => 'primary',
        'email' => null,
        'code' => null,
        'attempts' => 0,
        'expires' => Utils::getDatetime(time() + 900),
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
    'reset2' => [// phone
        'id' => 2,
        'uid' => '22222222222222222222222222222222',
        'user_id' => 2,
        'type' => 'method',
        'email' => 'email-4825478724@example.org',
        'code' => null,
        'attempts' => 0,
        'expires' => Utils::getDatetime(time() + 900),
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
    'reset3' => [// email
        'id' => 3,
        'uid' => '33333333333333333333333333333333',
        'user_id' => 3,
        'type' => 'method',
        'email' => 'email-1456769679@domain.org',
        'code' => '1234',
        'attempts' => 0,
        'expires' => Utils::getDatetime(time() + 900),
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
    'reset4' => [// email
        'id' => 4,
        'uid' => '44444444444444444444444444444444',
        'user_id' => 4,
        'type' => 'primary',
        'email' => null,
        'code' => '12345',
        'attempts' => 0,
        'expires' => Utils::getDatetime(time() + 900),
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
];
