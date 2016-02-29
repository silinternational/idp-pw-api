<?php
namespace common\components;

use common\components\PersonnelUser;

/**
 * Interface PersonnelInterface
 * @package common\components
 */
interface PersonnelInterface
{
    /**
     * @param mixed $employeeId
     * @return PersonnelUser|null
     */
    public function findByEmployeeId($employeeId);

    /**
     * @param mixed $username
     * @return PersonnelUser|null
     */
    public function findByUsername($username);

    /**
     * @param mixed $email
     * @return PersonnelUser|null
     */
    public function findByEmail($email);
}