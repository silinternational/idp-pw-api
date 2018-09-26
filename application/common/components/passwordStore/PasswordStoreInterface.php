<?php
namespace common\components\passwordStore;

/**
 * Interface PasswordStoreInterface
 * @package common\components\passwordStore
 */
interface PasswordStoreInterface
{
    /**
     * Get metadata about user's password including last_changed_date and expires_date
     * @param string $employeeId
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throw \common\components\passwordStore\UserNotFoundException
     * @throw \common\components\passwordStore\AccountLockedException
     */
    public function getMeta($employeeId);

    /**
     * Set user's password
     * @param string $employeeId
     * @param string $password
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throws \Exception
     * @throw \common\components\passwordStore\UserNotFoundException
     * @throw \common\components\passwordStore\AccountLockedException
     */
    public function set($employeeId, $password);
}
