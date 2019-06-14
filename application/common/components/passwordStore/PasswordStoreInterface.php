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
     * @throws \common\components\passwordStore\UserNotFoundException
     * @throws \common\components\passwordStore\AccountLockedException
     */
    public function getMeta($employeeId);

    /**
     * Set user's password
     * @param string $employeeId
     * @param string $password
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throws \Exception
     * @throws \common\components\passwordStore\UserNotFoundException
     * @throws \common\components\passwordStore\AccountLockedException
     */
    public function set($employeeId, $password);

    /**
     * Assess a potential new password for a user
     * @param string $employeeId
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function assess($employeeId, $password);

    /**
     * Is user account locked?
     * @param string $employeeId
     * @return bool
     * @throws \common\components\passwordStore\UserNotFoundException
     */
    public function isLocked(string $employeeId): bool;

    /**
     * Get name of passwordStore suitable for display in the user interface.
     * @return string
     */
    public function getDisplayName(): string;
}
