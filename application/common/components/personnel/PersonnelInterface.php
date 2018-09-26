<?php
namespace common\components\personnel;

/**
 * Interface PersonnelInterface
 * @package common\components\personnel
 */
interface PersonnelInterface
{
    /**
     * @param mixed $employeeId
     * @return PersonnelUser
     * @throws NotFoundException
     * @throws \Exception
     */
    public function findByEmployeeId($employeeId);

    /**
     * @param mixed $username
     * @return PersonnelUser
     * @throws NotFoundException
     * @throws \Exception
     */
    public function findByUsername($username);

    /**
     * @param mixed $email
     * @return PersonnelUser
     * @throws NotFoundException
     * @throws \Exception
     */
    public function findByEmail($email);
}