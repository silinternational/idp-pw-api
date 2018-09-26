<?php
namespace common\components\Personnel;

/**
 * Interface PersonnelInterface
 * @package common\components\Personnel
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