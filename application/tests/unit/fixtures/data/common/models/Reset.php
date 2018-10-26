<?php
use common\helpers\Utils;

return [
    'reset1' => [
        'id' => 1,
        'uid' => '11111111111111111111111111111111',
        'user_id' => 1,
        'type' => 'primary',
        'method_id' => null,
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
        'method_id' => 1,
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
        'method_id' => 2,
        'code' => '1234',
        'attempts' => 0,
        'expires' => Utils::getDatetime(time() + 900),
        'disable_until' => null,
        'created' => '2016-02-29 13:33:00',
    ],
];
