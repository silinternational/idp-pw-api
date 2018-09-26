<?php
namespace tests\unit\common\components;

use PHPUnit\Framework\TestCase;

use common\components\personnel\NotFoundException;
use common\components\personnel\IdBroker;
use Sil\Idp\IdBroker\Client\IdBrokerClient;

class IdBrokerTest extends TestCase
{

    public $baseUrl = 'http://broker';
    public $accessToken = 'abc123';

    public function getConfig() {
        return [
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
        ];
    }
    
    protected function ensureUserExists($userInfo)
    {
        $idBrokerClient = new IdBrokerClient($this->baseUrl, $this->accessToken, [
            IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => false,
        ]);
        
        $i = 0;
        $e = null;
        
        $userExistsCode = 1490802526;
        
        // Make sure broker container is available to deal with requests
        while ($i < 60) {
            $i++;
        
            try {
                $idBrokerClient->createUser($userInfo);
                $e = null;
                break;
            } catch (\Exception $e) {
                // If broker not available, wait longer
                if ($e instanceof \GuzzleHttp\Command\Exception\CommandException) {
                    sleep(1);
                
                    // if user already created, ensure it matches
                } else if ($e->getCode() == $userExistsCode) {
                    $idBrokerClient->updateUser($userInfo);
                    $e = null;
                    break;
                } else {
                    throw $e;
                }
            }
        }
        
        if ($e !== null) {
            throw $e;
        }
    }

    private function getMockReturnValue()
    {
       return [
           'uuid' => '11111111-aaaa-1111-aaaa-111111111111',
           'employee_id' => '11111',
           'first_name' => 'John',
           'last_name' => 'Smith',
           'display_name' => 'John Smith',
           'username' => 'john_smith',
           'email' => 'john_smith@example.com',
           'active' => 'yes',
           'locked' => 'no',
           'password' => [
               'created_utc' => '2017-05-24 14:04:51',
               'expiration_utc' => '2018-05-24 14:04:51',
               'grace_period_ends_utc' => '2018-06-23 14:04:51'
           ]
       ];
    }

    public function testReturnPersonnelUserFromResponse_Mocked() {
        $mockReturnValue = $this->getMockReturnValue();
        unset($mockReturnValue['email']);
        $brokerMock = $this->getMockBuilder('common\components\personnel\IdBroker')
            ->setMethods(['callIdBrokerGetUser'])
            ->getMock();
        $brokerMock->expects($this->any())
            ->method('callIdBrokerGetUser')
            ->willReturn($mockReturnValue);

        $employeeId = '11111';
        $this->expectExceptionCode(1496260921);
        $this->expectExceptionMessage(
            'Personnel attributes missing attribute: email for employeeId=' .
            $employeeId);
        $brokerMock->findByEmployeeId($employeeId);
    }

    public function testFindByEmployeeId_Mocked()
    {
        $mockReturnValue = $this->getMockReturnValue();
        $brokerMock = $this->getMockBuilder('common\components\personnel\IdBroker')
                           ->setMethods(['callIdBrokerGetUser'])
                           ->getMock();
        $brokerMock->expects($this->any())
                   ->method('callIdBrokerGetUser')
                   ->willReturn($mockReturnValue);

        $brokerMock->baseUrl = 'some.site.org';
        $brokerMock->accessToken = 'abc123';

        $employeeId = '11111';
        $results = $brokerMock->findByEmployeeId($employeeId);

        $expected = $mockReturnValue['username'];
        $msg = ' *** Bad results for username';
        $this->assertEquals($expected, $results->username, $msg);
    }

    public function testFindByUsername()
    {
        $employeeId = '33333';
        $firstName = 'Tommy';
        $lastName = 'Tester';
        $userName = 'tommy_tester3';
        $email = $userName . '@any.org';

        // Setup
        $this->ensureUserExists([
            'employee_id' => $employeeId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $userName,
            'email' => $email,
        ]);

        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);

        $expected = [
            'employeeId' => $employeeId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'username' => $userName,
            'email' => $email,
            'supervisorEmail' => null,
            'spouseEmail' => null,
        ];

        $results = get_object_vars($idBroker->findByUsername($userName));
        $this->assertEquals($expected, $results);
    }

    public function testFindByEmail()
    {
        $employeeId = '44444';
        $firstName = 'Tommy';
        $lastName = 'Tester';
        $userName = 'tommy_tester4';
        $email = $userName . '@any.org';

        // Setup
        $this->ensureUserExists([
            'employee_id' => $employeeId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $userName,
            'email' => $email,
        ]);

        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);

        $expected = [
            'employeeId' => $employeeId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'username' => $userName,
            'email' => $email,
            'supervisorEmail' => null,
            'spouseEmail' => null,
        ];

        $results = get_object_vars($idBroker->findByEmail($email));
        $this->assertEquals($expected, $results);
    }

    public function testFindByEmployeeId()
    {
        $employeeId = '55555';
        $firstName = 'Tommy';
        $lastName = 'Tester';
        $userName = 'tommy_tester5';
        $email = $userName . '@any.org';

        // Setup
        $this->ensureUserExists([
            'employee_id' => $employeeId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $userName,
            'email' => $email,
        ]);

        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);

        $expected = [
            'employeeId' => $employeeId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'username' => $userName,
            'email' => $email,
            'supervisorEmail' => null,
            'spouseEmail' => null,
        ];

        $results = get_object_vars($idBroker->findByEmployeeId($employeeId));
        $this->assertEquals($expected, $results);
    }


    public function testFindByEmployeeId_MissingUser()
    {
        // Setup
        $employeeId = time();
        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);

        $this->expectException(NotFoundException::class);
        $idBroker->findByEmployeeId($employeeId);
    }
    
    /**
     * Ensure that users who are not flagged as active are not returned, and
     * thus look like they are simply missing.
     *
     * @throws NotFoundException
     */
    public function testReturnPersonnelUserFromResponse_NotActiveEqualsMissing()
    {
        // Arrange:
        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);
        $employeeId = '66666';
        $fakeIdBrokerClientResponse = $this->getMockReturnValue();
        $fakeIdBrokerClientResponse['active'] = 'no';
        
        // Pre-assert:
        $this->expectException(NotFoundException::class);
        
        // Act:
        $idBroker->returnPersonnelUserFromResponse(
            'employee_id',
            $employeeId,
            $fakeIdBrokerClientResponse
        );
    }
    
    /**
     * Ensure that receiving user info back that lacks an `active` value causes
     * an exception.
     */
    public function testReturnPersonnelUserFromResponse_ActiveUnknown()
    {
        // Arrange:
        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);
        $employeeId = '77777';
        $fakeIdBrokerClientResponse = $this->getMockReturnValue();
        unset($fakeIdBrokerClientResponse['active']);
        
        // Pre-assert:
        $this->expectException(\Exception::class);
        
        // Act:
        $idBroker->returnPersonnelUserFromResponse(
            'employee_id',
            $employeeId,
            $fakeIdBrokerClientResponse
        );
    }

    public function testReturnPersonnelUserFromResponse_HasManagerEmail()
    {
        // Arrange:
        $employeeId = '88888';
        $userName = 'tommy_tester8';
        $managerEmail = 'manager@example.com';
        $this->ensureUserExists([
            'employee_id' => $employeeId,
            'first_name' => 'Tommy',
            'last_name' => 'Tester',
            'username' => $userName,
            'email' => $userName . '@example.com',
            'manager_email' => $managerEmail,
        ]);
        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);
        
        // Act:
        $personnelUser = $idBroker->findByEmployeeId($employeeId);
        
        // Assert:
        $this->assertEquals($managerEmail, $personnelUser->supervisorEmail);
    }
    
    public function testReturnPersonnelUserFromResponse_HasSpouseEmail()
    {
        // Arrange:
        $employeeId = '99999';
        $userName = 'tommy_tester9';
        $spouseEmail = 'spouse@example.com';
        $this->ensureUserExists([
            'employee_id' => $employeeId,
            'first_name' => 'Tommy',
            'last_name' => 'Tester',
            'username' => $userName,
            'email' => $userName . '@example.com',
            'spouse_email' => $spouseEmail,
        ]);
        $idBroker = new IdBroker([
            'baseUrl' => $this->baseUrl,
            'accessToken' => $this->accessToken,
            'assertValidBrokerIp' => false,
        ]);
    
        // Act:
        $personnelUser = $idBroker->findByEmployeeId($employeeId);
    
        // Assert:
        $this->assertEquals($spouseEmail, $personnelUser->spouseEmail);
    }
}
