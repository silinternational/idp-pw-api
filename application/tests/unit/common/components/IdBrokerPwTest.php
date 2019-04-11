<?php
namespace tests\unit\common\components;

use PHPUnit\Framework\TestCase;
use common\components\passwordStore\AccountLockedException;
use common\components\passwordStore\IdBroker;
use common\components\passwordStore\UserNotFoundException;
use common\components\passwordStore\UserPasswordMeta;

class IdBrokerPwTest extends TestCase
{
    /**
     * Get a mock IdBroker instance that will return user data from the given
     * array of users' information.
     *
     * @param array $listOfUserData
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getIdBrokerForTest($listOfUserData)
    {
        $fakeIdBrokerClient = new FakeIdBrokerClient($listOfUserData);

        $brokerMock = $this->getMockBuilder(IdBroker::class)
            ->setMethods(['getClient'])
            ->getMock();

        $brokerMock->expects($this->any())
            ->method('getClient')
            ->willReturn($fakeIdBrokerClient);

        return $brokerMock;
    }

    public function testGetMetaOk()
    {
        $idbroker = $this->getIdBrokerForTest([
            '10161' => [
                'locked' => 'no',
                'password' => [
                    'created_utc' => time(),
                    'expires_on' => time(),
                ],
            ],
        ]);

        $userMeta = $idbroker->getMeta('10161');

        $this->assertInstanceOf(UserPasswordMeta::class, $userMeta);
        $this->assertNotNull($userMeta->passwordExpireDate);
    }

    public function testGetMetaUserNotFound()
    {
        $idbroker = $this->getIdBrokerForTest([
            '10161' => [
                'locked' => 'no',
                'password' => [
                    'created_utc' => time(),
                    'expires_on' => time(),
                ],
            ],
        ]);

        $this->expectException(UserNotFoundException::class);

        $idbroker->getMeta('badUserId');
    }

    public function testGetMetaAccountLocked()
    {
        $idbroker = $this->getIdBrokerForTest([
            '10161' => [
                'locked' => 'yes',
                'password' => [
                    'created_utc' => time(),
                    'expires_on' => time(),
                ],
            ],
        ]);

        $this->expectException(AccountLockedException::class);

        $idbroker->getMeta('10161');
    }

    public function testSetOk()
    {
        $idbroker = $this->getIdBrokerForTest([
            '10161' => [
                'locked' => 'no',
                'password' => [
                    'created_utc' => time(),
                    'expires_on' => time(),
                ],
            ],
        ]);

        $userMeta = $idbroker->set('10161', 'newPassword');

        $this->assertInstanceOf(UserPasswordMeta::class, $userMeta);
        $this->assertNotNull($userMeta->passwordExpireDate);
    }

    public function testSetUserNotFound()
    {
        $idbroker = $this->getIdBrokerForTest([
            '10161' => [
                'locked' => 'no',
                'password' => [
                    'created_utc' => time(),
                    'expires_on' => time(),
                ],
            ],
        ]);

        $this->expectException(UserNotFoundException::class);

        $idbroker->set('badUserId', 'newPassword');
    }

    public function testSetAccountLocked()
    {
        $idbroker = $this->getIdBrokerForTest([
            '10161' => [
                'locked' => 'yes',
                'password' => [
                    'created_utc' => time(),
                    'expires_on' => time(),
                ],
            ],
        ]);

        $this->expectException(AccountLockedException::class);

        $idbroker->set('10161', 'newPassword');
    }
}
