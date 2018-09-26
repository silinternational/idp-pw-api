<?php
namespace common\components\Personnel;

/**
 * Class PersonnelUser
 * @package common\components\Personnel
 */
class PersonnelUser
{
    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string
     */
    public $lastName;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $employeeId;

    /**
     * @var string
     */
    public $username;

    /**
     * @var null|string
     */
    public $supervisorEmail;

    /**
     * @var null|string
     */
    public $spouseEmail;
}