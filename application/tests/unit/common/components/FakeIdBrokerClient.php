<?php
namespace tests\unit\common\components;

use Sil\Idp\IdBroker\Client\ServiceException;

class FakeIdBrokerClient
{
    private $users;

    private $passwords;

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
        $this->passwords[$employeeId] = $password;

        return $this->getUser($employeeId);
    }

    /**
     * @param string $employeeId
     * @param string $password
     * @return bool
     * @throws ServiceException
     */
    public function assessPassword($employeeId, $password)
    {
        $currentPassword = $this->passwords[$employeeId] ?? '';
        if ($currentPassword !== $password) {
            return true;
        } else {
            throw new ServiceException('May not be reused yet', 0, 409);
        }
    }
}
