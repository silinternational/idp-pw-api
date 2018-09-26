<?php
namespace tests\mock\passwordstore;

use common\helpers\Utils;
use common\components\passwordStore\PasswordStoreInterface;
use common\components\passwordStore\UserNotFoundException;
use common\components\passwordStore\UserPasswordMeta;

class Component implements PasswordStoreInterface
{
    /**
     * Get metadata about user's password including last_changed_date and expires_date
     * @param string $employeeId
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throw \common\components\passwordStore\UserNotFoundException
     */
    public function getMeta($employeeId)
    {
        return $this->getFakeUser();
    }

    /**
     * Set user's password
     * @param string $employeeId
     * @param string $password
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throws \Exception
     */
    public function set($employeeId, $password)
    {
        if ( $employeeId == 'notfound') {
            throw new UserNotFoundException();
        }

        return $this->getFakeUser();
    }

    /**
     * @return UserPasswordMeta
     */
    private function getFakeUser()
    {
        return UserPasswordMeta::create(
            Utils::getIso8601(time() + 31556926),
            Utils::getIso8601()
        );
    }
}