<?php
namespace common\components\passwordStore;

use IPBlock;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use yii\base\Component;

class IdBroker extends Component implements PasswordStoreInterface
{
    /**
     * @var string base Url for the API
     */
    public $baseUrl;

    /**
     * @var string access Token for the API
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
     * Get metadata about user's password including last_changed_date and expires_date
     * @param string $employeeId
     * @return UserPasswordMeta
     * @throws ServiceException
     * @throws UserNotFoundException
     * @throws AccountLockedException
     */
    public function getMeta($employeeId)
    {
        $client = $this->getClient();

        $user = $client->getUser($employeeId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        if ($user['locked'] == 'yes') {
            throw new AccountLockedException();
        }

        $meta = UserPasswordMeta::create(
            $user['password']['expires_on'] ?? null,
            $user['password']['created_utc'] ?? null
        );
        return $meta;
    }

    /**
     * Set user's password
     * @param string $employeeId
     * @param string $password
     * @return UserPasswordMeta
     * @throws UserNotFoundException
     * @throws AccountLockedException
     * @throws ServiceException
     * @throws PasswordReuseException
     */
    public function set($employeeId, $password)
    {
        $client = $this->getClient();

        $user = $client->getUser($employeeId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        if ($user['locked'] == 'yes') {
            throw new AccountLockedException();
        }

        try {
            $update = $client->setPassword($employeeId, $password);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 409) {
                throw new PasswordReuseException();
            }
            throw $e;
        }

        $meta = UserPasswordMeta::create(
            $update['password']['expires_on'] ?? null,
            $update['password']['created_utc'] ?? null
        );
        return $meta;
    }

    /**
     * @return IdBrokerClient
     * @throws \Exception
     */
    public function getClient()
    {
        return new IdBrokerClient($this->baseUrl, $this->accessToken, [
            IdBrokerClient::TRUSTED_IPS_CONFIG => $this->validIpRanges,
            IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => $this->assertValidBrokerIp,
        ]);
    }
}
