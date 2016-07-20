<?php
namespace tests\mock\personnel;

use Sil\IdpPw\Common\Personnel\PersonnelInterface;
use Sil\IdpPw\Common\Personnel\PersonnelUser;
use Sil\IdpPw\Common\Personnel\NotFoundException;
use yii\base\Component as YiiComponent;

class Component extends YiiComponent implements PersonnelInterface
{

    public function findByEmployeeId($employeeId)
    {
        $data = include __DIR__ . '/data.php';
        foreach ($data as $user) {
            if ($user['employeeId'] == $employeeId) {
                return $this->createPersonnelUserFromData($user);
            }
        }
        throw new NotFoundException();
    }

    public function findByUsername($username)
    {
        $data = include __DIR__ . '/data.php';
        foreach ($data as $user) {
            if ($user['username'] == $username) {
                return $this->createPersonnelUserFromData($user);
            }
        }
        throw new NotFoundException();
    }

    public function findByEmail($email)
    {
        $data = include __DIR__ . '/data.php';
        foreach ($data as $user) {
            if ($user['email'] == $email) {
                return $this->createPersonnelUserFromData($user);
            }
        }
        throw new NotFoundException();
    }

    public function createPersonnelUserFromData($data)
    {
        $user = new PersonnelUser();
        $user->firstName = $data['firstName'];
        $user->lastName = $data['lastName'];
        $user->email = $data['email'];
        $user->employeeId = $data['employeeId'];
        $user->username = $data['username'];
        $user->supervisorEmail = $data['supervisorEmail'];
        $user->spouseEmail = $data['spouseEmail'];

        return $user;
    }

}