<?php
namespace tests\features;

use Sil\PhpEnv\Env;

class DummyUser
{
    public $email;
    public $employee_id;

    /**
     * Return an instance of this which has the given attributes as well as an
     * email address for a real user in Google, as though this search found a
     * matching user record.
     *
     * @param array $condition The search "criteria".
     * @return DummyUser
     */
    public static function findOne($condition)
    {
        $condition['email'] = Env::requireEnv('TEST_GOOGLE_USER_EMAIL');
        $dummyUser = new DummyUser();
        foreach ($condition as $name => $value) {
            $dummyUser->$name = $value;
        }
        return $dummyUser;
    }
}
