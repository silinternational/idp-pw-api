<?php
namespace common\components\personnel;

use IPBlock;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use yii\base\Component;

class IdBroker extends Component implements PersonnelInterface
{

    /**
     * @var string
     */
    public $baseUrl;

    /**
     * @var string
     */
    public $accessToken;

    /**
     * @var boolean
     */
    public $assertValidBrokerIp = true;

    /**
     * @var IPBlock[]
     */
    public $validIpRanges = [];

    /**
     * @param $userData
     * @throws \Exception
     */
    private function assertRequiredAttributesPresent($userData)
    {
        $required = ['first_name', 'last_name', 'email', 'employee_id', 'username', 'do_not_disclose'];

        foreach ($required as $requiredAttr) {
            if ( ! array_key_exists($requiredAttr, $userData)) {
                throw new \Exception(
                    'Personnel attributes missing attribute: ' . $requiredAttr,
                    1496328234
                );
            }
        }
    }

    /**
     * @param string $employeeId
     * @return PersonnelUser
     * @throws NotFoundException
     */
    public function findByEmployeeId($employeeId): PersonnelUser
    {
        $results = $this->callIdBrokerGetUser($employeeId);
        return $this->returnPersonnelUserFromResponse('employeeId', $employeeId, $results);
    }

    /**
     * Get the user attributes for the user with the given Employee ID.
     *
     * @param string $employeeId
     * @return array|null
     * @throws NotFoundException
     */
    public function callIdBrokerGetUser($employeeId)
    {

        $idBrokerClient = $this->getIdBrokerClient();

        $results = $idBrokerClient->getUser($employeeId);
        if ($results === null) {
            throw new NotFoundException();
        }

        return $results;
    }

    /**
     * Take the given response that came from the IdBrokerClient and return a
     * PersonnelUser representing the response's data.
     *
     * NOTE: Inactive users will be treated as not found.
     *
     * @param string $field The field searched. EXAMPLE: 'employee_id'
     * @param string $value The value searched for. EXAMPLE: '12345'
     * @param $response array|null The response returned by the IdBrokerClient.
     * @return PersonnelUser
     * @throws NotFoundException
     * @throws \Exception
     */
    public function returnPersonnelUserFromResponse($field, $value, $response): PersonnelUser
    {
        $active = $response['active'] ?? null;
        if ($active === null) {
            throw new \Exception(
                sprintf(
                    'No "active" value returned for user: %s',
                    var_export($response, true)
                ),
                1532961386
            );
        } elseif (strtolower($active) !== 'yes') {
            throw new NotFoundException();
        }
        
        try {
            $this->assertRequiredAttributesPresent($response);
            $pUser = new PersonnelUser();
            $pUser->firstName = $response['first_name'];
            $pUser->lastName = $response['last_name'];
            $pUser->email = $response['email'];
            $pUser->employeeId = $response['employee_id'];
            $pUser->username = $response['username'];
            $pUser->supervisorEmail = $response['manager_email'] ?? null;
            $pUser->spouseEmail = $response['spouse_email'] ?? null;
            $pUser->doNotDisclose = (int)$response['do_not_disclose'];

            return $pUser;
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf('%s for %s=%s', $e->getMessage(), $field, $value),
                1496260921
            );
        }
    }

    /**
     * @param string $username
     * @return PersonnelUser
     * @throws NotFoundException
     * @throws \Exception
     */
    public function findByUsername($username): PersonnelUser
    {
        $idBrokerClient = $this->getIdBrokerClient();

        $results = $idBrokerClient->listUsers(null, ['username' => $username]);
        if (count($results) > 1) {
            throw new \Exception(
                sprintf('More than one user found when searching by username "%s"', $username),
                1497636205
            );
        } elseif (count($results) === 1) {
            if (mb_strtolower($results[0]['username']) == mb_strtolower($username)) {
                return $this->returnPersonnelUserFromResponse('username', $username, $results[0]);
            }
        }

        throw new NotFoundException();
    }

    /**
     * @param string $email
     * @return PersonnelUser
     * @throws NotFoundException
     * @throws \Exception
     */
    public function findByEmail($email): PersonnelUser
    {
        $idBrokerClient = $this->getIdBrokerClient();

        $results = $idBrokerClient->listUsers(null, ['email' => $email]);
        if (count($results) > 1) {
            throw new \Exception(
                sprintf('More than one user found when searching by email "%s"', $email),
                1497636210
            );
        } elseif (count($results) === 1) {
            if (mb_strtolower($results[0]['email']) == mb_strtolower($email)) {
                return $this->returnPersonnelUserFromResponse('email', $email, $results[0]);
            }
        }

        throw new NotFoundException();
    }

    /**
     * @return IdBrokerClient
     */
    private function getIdBrokerClient()
    {
        return new IdBrokerClient(
            $this->baseUrl, // The base URI for the API.
            $this->accessToken, // Your HTTP header authorization bearer token.
            [
                IdBrokerClient::TRUSTED_IPS_CONFIG => $this->validIpRanges,
                IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => $this->assertValidBrokerIp,
                'http_client_options' => [
                    'timeout' => 10, // An (optional) custom HTTP timeout, in seconds.
                ],
            ]
        );
    }

    /**
     * Updates properties on a personnel record. At a minimum, `$properties` must
     * contain an `'employee_id'` key.
     *
     * @param array $properties
     * @throws NotFoundException
     * @throws ServiceException
     */
    public function updateUser($properties)
    {
        $idBrokerClient = $this->getIdBrokerClient();

        try {
            $idBrokerClient->updateUser($properties);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode == 204) {
                throw new NotFoundException();
            } else {
                throw $e;
            }
        }
    }
}
