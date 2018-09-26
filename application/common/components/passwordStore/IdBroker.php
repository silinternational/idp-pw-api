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
     * @throws \Exception
     * @throw \common\components\passwordStore\UserNotFoundException
     * @throw \common\components\passwordStore\AccountLockedException
     */
    public function getMeta($employeeId)
    {
        try {
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
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set user's password
     * @param string $employeeId
     * @param string $password
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throws \Exception
     * @throw \common\components\passwordStore\UserNotFoundException
     * @throw \common\components\passwordStore\AccountLockedException
     */
    public function set($employeeId, $password)
    {
        try {
            $client = $this->getClient();

            $user = $client->getUser($employeeId);

            if ($user === null) {
                throw new UserNotFoundException();
            }

            if ($user['locked'] == 'yes') {
                throw new AccountLockedException();
            }

            $update = $client->setPassword($employeeId, $password);

            $meta = UserPasswordMeta::create(
                $update['password']['expires_on'] ?? null,
                $update['password']['created_utc'] ?? null
            );
            return $meta;
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 422) {
                throw new PasswordReuseException();
            }
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getClient()
    {
        return new IdBrokerClient($this->baseUrl, $this->accessToken, [
            IdBrokerClient::TRUSTED_IPS_CONFIG => $this->validIpRanges,
            IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => $this->assertValidBrokerIp,
        ]);
    }
}
