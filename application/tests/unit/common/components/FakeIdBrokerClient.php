<?php
namespace tests\unit\common\components;

class FakeIdBrokerClient
{
    private $users;

    /**
     * FakeIdBrokerClient constructor.
     * @param array $users
     */
    public function __construct($users)
    {
        $this->users = $users;
    }

    /**
     * @param $employeeId
     * @return null|array
     */
    public function getUser($employeeId)
    {
        if ($employeeId !== null) {
            return $this->users[$employeeId] ?? null;
        }
        return null;
    }

    /**
     * @param $employeeId
     * @param $password
     * @return array
     */
    public function setPassword($employeeId, $password)
    {
        return $this->getUser($employeeId);
    }
}
