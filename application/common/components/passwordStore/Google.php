<?php
namespace common\components\passwordStore;

use Exception;
use Google_Client;
use Google_Service_Directory;
use Google_Service_Directory_Resource_Users;
use Google_Service_Directory_User;
use InvalidArgumentException;
use yii\base\Component;
use yii\helpers\Json;

class Google extends Component implements PasswordStoreInterface
{
    public $applicationName = null;

    /**
     * The email address of a delegated admin in the Google Apps instance where
     * the users exist that you will be retrieving/modifying.
     *
     * @var string
     */
    public $delegatedAdminEmail = null;

    /**
     * The name of the field on the $userActiveRecordClass where the email
     * address is stored.
     *
     * @var string
     */
    public $emailFieldName = 'email';

    /**
     * The name of the field on the $userActiveRecordClass where the Employee ID
     * is stored.
     *
     * @var string
     */
    public $employeeIdFieldName = 'employee_id';

    public $jsonAuthConfigBase64 = null;
    public $jsonAuthFilePath = null;

    protected $authConfig = null;

    /**
     * Full class path for a user model (which must be a subclass of
     * \yii\db\ActiveRecord) which we can use to look up the email address for
     * the given employeeId.
     *
     * @var string
     */
    public $userActiveRecordClass = '\common\models\User';

    private $googleClient = null;

    public $displayName = 'Google';

    /**
     * @var bool $findByExternalId If `true`, when retrieving a user by employee_id,
     * a call will be made to `Users: list` to retrieve the user by the Google user
     * property `externalId`.
     */
    public $findByExternalId = false;

    /**
     * @var string $searchDomain Domain name in which to search for a matching user
     * when `findByExternalId` is `true`.
     */
    public $searchDomain = '';

    public function init()
    {
        if ( ! empty($this->jsonAuthConfigBase64)) {
            $jsonAuthConfig = \base64_decode($this->jsonAuthConfigBase64);
        } elseif ( ! empty($this->jsonAuthFilePath)) {
            if ( ! file_exists($this->jsonAuthFilePath)) {
                throw new InvalidArgumentException(sprintf(
                    'JSON auth file path of %s provided, but no such file exists.',
                    var_export($this->jsonAuthFilePath, true)
                ), 1497897359);
            }
            $jsonAuthConfig = \file_get_contents($this->jsonAuthFilePath);
        }

        if (empty($jsonAuthConfig)) {
            throw new InvalidArgumentException(
                'No JSON auth config was provided. Please provide either a '
                . 'jsonAuthFilePath or a jsonAuthConfigBase64.',
                1498056435
            );
        } else {
            $this->authConfig = Json::decode($jsonAuthConfig);
        }

        $requiredProperties = [
            'applicationName',
            'delegatedAdminEmail',
            'emailFieldName',
            'employeeIdFieldName',
            'userActiveRecordClass',
        ];
        foreach ($requiredProperties as $requiredProperty) {
            if (empty($this->$requiredProperty)) {
                throw new InvalidArgumentException(sprintf(
                    'You must provide a value for %s (found %s).',
                    $requiredProperty,
                    var_export($this->$requiredProperty, true)
                ), 1497896922);
            }
        }

        parent::init();
    }

    /**
     * @return Google_Client
     */
    protected function getClient()
    {
        if ($this->googleClient === null) {
            $googleClient = new Google_Client();
            $googleClient->setApplicationName($this->applicationName);
            $googleClient->setAuthConfig($this->authConfig);
            $googleClient->addScope(
                Google_Service_Directory::ADMIN_DIRECTORY_USER
            );
            $googleClient->setSubject($this->delegatedAdminEmail);
            $this->googleClient = $googleClient;
        }
        return $this->googleClient;
    }

    /**
     * @param string $employeeId
     * @return string
     * @throws UserNotFoundException
     */
    protected function getEmailFromLocalStore($employeeId)
    {
        $userActiveRecord = $this->userActiveRecordClass::findOne([
            $this->employeeIdFieldName => $employeeId,
        ]);

        if ($userActiveRecord === null) {
            throw new UserNotFoundException();
        }

        $emailFieldName = $this->emailFieldName;
        if (empty($userActiveRecord->$emailFieldName)) {
            throw new Exception(sprintf(
                'No email address found for user %s, and without that we '
                . 'cannot retrieve the user\'s record from Google.',
                var_export($employeeId, true)
            ), 1497980234);
        }

        return $userActiveRecord->$emailFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta($employeeId): UserPasswordMeta
    {
        $this->getUser($employeeId);

        /* Note: Google doesn't tell us when the user's password expires, so
         * simply return an "empty" UserPasswordMeta object.  */
        return UserPasswordMeta::create('', '');
    }

    /**
     * Get the user record (from Google) that corresponds to the given
     * Employee ID.
     *
     * @param string $employeeId The Employee ID of the desired user.
     * @return Google_Service_Directory_User The user record from Google.
     */
    protected function getUser($employeeId)
    {
        if ($this->findByExternalId === true) {
            return $this->getUserByEmployeeId($employeeId);
        } else {
            $email = $this->getEmailFromLocalStore($employeeId);
            return $this->getUserByEmail($email);
        }
    }

    /**
     * Get the user record from Google that has the given email address.
     *
     * @param string $email The email address.
     * @return Google_Service_Directory_User The user record from Google.
     * @throws UserNotFoundException
     * @throws Exception
     */
    protected function getUserByEmail($email)
    {
        try {
            $usersResource = $this->getUsersResource();
            $googleUser = $usersResource->get($email);
            if ($googleUser->suspended) {
                throw new AccountLockedException();
            }
            return $googleUser;
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                throw new UserNotFoundException();
            }
            throw $e;
        }
    }

    /**
     * Get the object we will use for interacting with user records on Google.
     *
     * @return Google_Service_Directory_Resource_Users
     */
    protected function getUsersResource()
    {
        $directory = new Google_Service_Directory($this->getClient());
        return $directory->users;
    }

    /**
     * Save the changes (on the given Google user record) back to Google.
     *
     * @param Google_Service_Directory_User $googleUser A Google user record.
     */
    protected function saveChangesTo($googleUser)
    {
        $usersResource = $this->getUsersResource();
        $usersResource->update($googleUser->primaryEmail, $googleUser);
    }

    /**
     * {@inheritdoc}
     */
    public function set($employeeId, $password): UserPasswordMeta
    {
        $googleUser = $this->getUser($employeeId);
        $googleUser->setPassword($password);
        $this->saveChangesTo($googleUser);

        /* Note: Google doesn't tell use when the user's password expires, so
         * simply return an "empty" UserPasswordMeta object.  */
        return UserPasswordMeta::create('', '');
    }

    public function isLocked(string $employeeId): bool
    {
        return false;
    }

    /**
     * Assess a potential new password for a user
     * @param string $employeeId
     * @param string $password
     * @return bool
     */
    public function assess($employeeId, $password)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $employeeId
     * @return Google_Service_Directory_User The user record from Google.
     * @throws UserNotFoundException
     */
    protected function getUserByEmployeeId($employeeId)
    {
        $usersResource = $this->getUsersResource();
        $response = $usersResource->listUsers([
                'domain' => $this->searchDomain,
                'maxResults' => 2,
                'query' => 'externalId=' . $employeeId,
        ]);

        if ($response === null || count($response['users']) === 0) {
            \Yii::warning([
                'action' => 'getEmailFromGoogle',
                'status' => 'not found',
                'employee_id' => $employeeId,
            ]);
            throw new UserNotFoundException(\Yii::t('app', 'Google.EmployeeIdNotFound'), 1560370495);
        }

        if (count($response['users']) > 1) {
            \Yii::error([
                'action' => 'getEmailFromGoogle',
                'status' => 'too many results',
                'employee_id' => $employeeId,
                'email1' => $response['users'][0]['primaryEmail'],
                'email2' => $response['users'][1]['primaryEmail'],
            ]);
            throw new \Exception(\Yii::t('app', 'Google.MultipleEmailsFound'), 1560875143);
        }

        return $response['users'][0];
    }
}
