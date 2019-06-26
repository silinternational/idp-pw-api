<?php
namespace common\components\passwordStore;

use IPBlock;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use yii\base\Component;

class IdBroker extends Component implements PasswordStoreInterface
{
    /**
     * @var IdBrokerClient $client
     */
    private $client;

    public $displayName = 'ID Broker';

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     * @throws \Exception if a configured baseUrl falls outside the approved IP range
     * @throws \InvalidArgumentException if configuration is incomplete
     */
    public function init()
    {
        parent::init();
        $config = \Yii::$app->params['idBrokerConfig'];
        $this->client = new IdBrokerClient(
            $config['baseUrl'],
            $config['accessToken'],
            [
                IdBrokerClient::TRUSTED_IPS_CONFIG => $config['validIpRanges'] ?? [],
                IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG => $config['assertValidBrokerIp'] ?? true,
                'http_client_options' => [
                    'timeout' => 10, // An (optional) custom HTTP timeout, in seconds.
                ],
            ]
        );
    }

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
        $user = $this->getUser($employeeId);

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
        $this->getUser($employeeId);

        try {
            $update = $this->getClient()->setPassword($employeeId, $password);
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
     * This getter method facilitates test by allowing a fake client to be substituted.
     * @return IdBrokerClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param string $employeeId
     * @return bool
     * @throws ServiceException
     * @throws UserNotFoundException
     */
    public function isLocked(string $employeeId): bool
    {
        try {
            $this->getUser($employeeId);
        } catch (AccountLockedException $e) {
            return true;
        }

        return false;
    }

    /**
     * @param $employeeId
     * @return array|null
     * @throws ServiceException
     * @throws UserNotFoundException
     * @throws AccountLockedException
     */
    private function getUser($employeeId)
    {
        $user = $this->getClient()->getUser($employeeId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        if ($user['locked'] == 'yes') {
            throw new AccountLockedException();
        }
        return $user;
    }

    /**
     * Assess a potential new password for a user
     * @param string $employeeId
     * @param string $password
     * @return bool
     * @throws ServiceException
     */
    public function assess($employeeId, $password)
    {
        return $this->getClient()->assessPassword($employeeId, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
