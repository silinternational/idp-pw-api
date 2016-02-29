<?php
namespace common\components;

/**
 * Class PersonnelUser
 * @package common\components
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
     * @var null|PersonnelUser
     */
    public $supervisor;

    /**
     * @var null|PersonnelUser
     */
    public $spouse;
}