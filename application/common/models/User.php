<?php
namespace common\models;

use common\components\auth\User as AuthUser;
use common\components\passwordStore\UserPasswordMeta;
use common\components\personnel\NotFoundException;
use common\components\personnel\PersonnelInterface;
use common\components\personnel\PersonnelUser;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class User
 * @package common\models
 * @method User self::findOne([])
 */
class User extends UserBase implements IdentityInterface
{

    const AUTH_TYPE_LOGIN = 'login';
    const AUTH_TYPE_RESET = 'reset';

    /**
     * Holds personnelUser
     * @var PersonnelUser
     */
    public $personnelUser;

    /**
     * Validation rules, applies User rules before UserBase rules
     * @return string[]
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['uid'], 'default', 'value' => Utils::generateRandomString(),
                ],

                [
                    ['created'], 'default', 'value' => Utils::getDatetime(),
                ],

                [
                    ['email'], 'email'
                ],
            ],
            parent::rules()
        );
    }

    /**
     * Limit what fields are returned from api calls
     * @return array
     */
    public function fields()
    {
        /** @var User $model */
        return [
            'first_name',
            'last_name',
            'idp_username',
            'email',
            'password_meta' => function($model) {
                return $model->getPasswordMeta();
            },
            'auth_type'
        ];
    }

    /**
     * Find or create local user. Fetch/update user data from personnel.
     * @param string|null $username [default=null]
     * @param string|null $email [default=null]
     * @param string|null $employeeId [default=null]
     * @return User
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws NotFoundException
     */
    public static function findOrCreate($username = null, $email = null, $employeeId = null)
    {
        /*
         * Make sure at least one method was provided
         */
        if (is_null($username) && is_null($email) && is_null($employeeId)) {
            throw new \Exception(
                'You must provide a username, email address, or employee id to find or create a user',
                1459974492
            );
        }

        /*
         * Always call Personnel system in case employee is no longer employed
         */
        try {
            /** @var PersonnelUser $personnelUser */
            if ( ! is_null($employeeId)) {
                $personnelUser = \Yii::$app->personnel->findByEmployeeId($employeeId);
            } elseif ( ! is_null($username)) {
                $personnelUser = \Yii::$app->personnel->findByUsername($username);
            } else {
                $personnelUser = \Yii::$app->personnel->findByEmail($email);
            }
        } catch (\Exception $e) {
            /*
             * If user was not found just re-throw exception
             */
            if ($e instanceof NotFoundException) {
                throw $e;
            }

            \Yii::error([
                'action' => 'personnel find user',
                'status' => 'error',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new ServerErrorHttpException(
                'There was a problem retrieving your information from the personnel system. Please wait a few ' .
                'minutes and try again.',
                1470164077
            );
        }


        $user = self::findOne(['employee_id' => $personnelUser->employeeId]);
        if ( ! $user) {
            $user = new User();
            $user->employee_id = (string)$personnelUser->employeeId;
            $user->first_name = $personnelUser->firstName;
            $user->last_name = $personnelUser->lastName;
            $user->idp_username = $personnelUser->username;
            $user->email = $personnelUser->email;
            if ( ! $user->save()) {
                \Yii::error([
                    'action' => 'create new user',
                    'status' => 'error',
                    'error' => $user->getFirstErrors(),
                ]);
                throw new \Exception('Unable to create new user', 1456760294);
            }
        } else {
            $user->updateProfileIfNeeded(
                $personnelUser->firstName,
                $personnelUser->lastName,
                $personnelUser->username,
                $personnelUser->email
            );
        }

        return $user;
    }


    /**
     * Update local user record if given properties are different than currently stored
     * @param string $firstName
     * @param string $lastName
     * @param string $username
     * @param string $email
     * @return bool True if profile was updated, false if no updates were needed
     * @throws \Exception
     */
    public function updateProfileIfNeeded($firstName, $lastName, $username, $email)
    {
        $dirty = false;
        $properties = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'idp_username' => $username,
            'email' => $email,
        ];

        foreach ($properties as $property => $value) {
            if ($this->$property != $value) {
                $dirty = true;
                $this->$property = $value;
            }
        }

        if ($dirty) {

            /*
             * Check that $email is not already in use by another user
             * If it is, refresh that user's profile from personnel in
             * case their email address has also changed
             */
            if ($this->isEmailInUseByOtherUser($email)) {
                self::refreshPersonnelDataForUserWithSpecificEmail($email);
            }

            /*
             * Save updated profile
             */
            if ($this->save()) {
                return true;
            } else {
                \Yii::error([
                    'action' => 'update user profile',
                    'status' => 'error',
                    'error' => $this->getFirstErrors(),
                ]);
                throw new \Exception('Unable to update profile', 1456760819);
            }
        }
        return false;
    }

    /**
     * In case where email address has been reassigned to $this user, refresh profile for user
     * with email address currently in case their email address has changed too
     * @param string $email
     * @return bool Whether or not the profile was updated in the database
     * @throws \Exception
     */
    public static function refreshPersonnelDataForUserWithSpecificEmail($email)
    {
        $user = self::findOne(['email' => $email]);
        if ($user === null) {
            throw new \Exception(
                sprintf('User with email %s not found', $email),
                1499817281
            );
        }

        try {
            /** @var PersonnelUser $personnelUser */
            $personnelUser = \Yii::$app->personnel->findByEmployeeId($user->employee_id);
        } catch (NotFoundException $e) {
            /*
             * User no longer exists in personnel system, so update their email to release for use by other users
             */
            $personnelUser = new PersonnelUser();
            $personnelUser->firstName = $user->first_name;
            $personnelUser->lastName = $user->last_name;
            $personnelUser->username = $user->idp_username;
            $personnelUser->email = sprintf('notfound-%s-%s', $user->email, time());

            \Yii::error([
                'action' => 'updateProfileForExistingUserWithEmailFromPersonnel',
                'message' => sprintf(
                    'When updating profile for existing user with email address % they could not be ' .
                    'found in personnel so their email was updated to %s',
                    $user->email,
                    $personnelUser->email
                 )
            ]);
        }

        return $user->updateProfileIfNeeded(
            $personnelUser->firstName,
            $personnelUser->lastName,
            $personnelUser->username,
            $personnelUser->email
        );
    }

    /**
     * Check if email address is in use by a different user already
     * @param string $email
     * @return bool
     */
    public function isEmailInUseByOtherUser($email)
    {
        $user = self::findOne(['email' => $email]);

        return $user !== null && $user->id != $this->id;
    }

    /**
     * Return array of arrays of masked out methods
     * @return array<Method|array>
     */
    public function getMaskedMethods()
    {
        $methods = $this->getMethodsAndPersonnelEmails();
        foreach ($methods as $key => $method) {
            if ($method['verified'] ?? true) {
                $methods[$key]['value'] = Utils::maskEmail($method['value']);
            } else {
                unset($methods[$key]);
            }
        }
        return $methods;
    }

    /**
     * @return PersonnelUser
     * @throws \Exception
     */
    public function getPersonnelUser()
    {
        if ( ! empty($this->personnelUser)) {
            return $this->personnelUser;
        }

        $sessionAvailable = Utils::isSessionAvailable();

        if ($sessionAvailable && is_array(\Yii::$app->session->get('personnelUser'))) {
            $this->personnelUser = \Yii::$app->session->get('personnelUser');
            return $this->personnelUser;
        }

        /*
         * Fetch data from Personnel system and cache it
         */
        $this->personnelUser = $this->getPersonnelUserFromInterface();
        \Yii::$app->session->set('personnelUser', $this->personnelUser);

        return $this->personnelUser;
    }

    /**
     * @return bool
     */
    public function hasSupervisor()
    {
        return $this->getSupervisorEmail() !== null;
    }

    /**
     * @return bool
     */
    public function hasSpouse()
    {
        return $this->getSpouseEmail() !== null;
    }

    /**
     * @return null|string
     */
    public function getSupervisorEmail()
    {
        $personnelUser = $this->getPersonnelUser();
        return $personnelUser->supervisorEmail;
    }
    /**
     * @return null|string
     */
    public function getSpouseEmail()
    {
        $personnelUser = $this->getPersonnelUser();
        return $personnelUser->spouseEmail;
    }

    /**
     * @return PersonnelUser
     * @throws \Exception
     */
    public function getPersonnelUserFromInterface()
    {
        /** @var PersonnelInterface $personnel */
        $personnel = \Yii::$app->personnel;

        if ($this->employee_id) {
            return $personnel->findByEmployeeId($this->employee_id);
        } elseif ($this->idp_username) {
            return $personnel->findByUsername($this->idp_username);
        } elseif ($this->email) {
            return $personnel->findByEmail($this->email);
        } else {
            throw new \Exception('Not enough information to find personnel data', 1456690741);
        }
    }

    /**
     * Finds an identity by the given ID.
     *
     * @param string|integer $id the ID to be looked for
     * @return User|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     *
     * @param string $token the token to be looked for
     * @return User|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $accessTokenHash = Utils::getAccessTokenHash($token);
        return static::find()->where(['access_token' => $accessTokenHash])
            ->andWhere(['>', 'access_token_expiration', Utils::getDatetime()])
            ->one();
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returning null to explicitly disable this
     * @return string|null current user auth key
     */
    public function getAuthKey()
    {
        return null;
    }

    /**
     * Returning false to explicitly disable this
     * @param string $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return false;
    }

    /**
     * Get this user as an AuthUser object
     * @return \common\components\auth\User
     */
    public function getAuthUser()
    {
        $authUser = new AuthUser();
        $authUser->firstName = $this->first_name;
        $authUser->lastName = $this->last_name;
        $authUser->email = $this->email;
        $authUser->employeeId = $this->employee_id;
        $authUser->idpUsername = $this->idp_username;

        return $authUser;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return array<Method|array>
     */
    public function getMethodsAndPersonnelEmails()
    {
        $verifiedMethods = Method::getMethods($this->employee_id);

        foreach ($verifiedMethods as $key => $method) {
            $verifiedMethods[$key]['type'] = 'email';
        }

        $verifiedMethods[] = [
            'type' => Reset::TYPE_PRIMARY,
            'value' => $this->email,
        ];

        if ($this->hasSpouse()) {
            $verifiedMethods[] = [
                'type' => Reset::TYPE_SPOUSE,
                'value' => $this->getSpouseEmail(),
            ];
        }

        if ($this->hasSupervisor()) {
            $verifiedMethods[] = [
                'type' => Reset::TYPE_SUPERVISOR,
                'value' => $this->getSupervisorEmail(),
            ];
        }

        return $verifiedMethods;
    }

    /**
     * @return array
     * @throws ServerErrorHttpException
     */
    public function getPasswordMeta()
    {
        /*
         * If password metadata is missing, fetch from passwordStore and update
         */
        /** @var UserPasswordMeta $pwMeta */
        $pwMeta = \Yii::$app->passwordStore->getMeta($this->employee_id);

        return [
            'last_changed' => Utils::getIso8601($pwMeta->passwordLastChangeDate),
            'expires' => Utils::getIso8601($pwMeta->passwordExpireDate),
        ];
    }

    /**
     * @param string $newPassword
     * @throws ServerErrorHttpException
     * @throws \yii\web\BadRequestHttpException
     */
    public function setPassword($newPassword)
    {
        $password = Password::create($this->employee_id, $newPassword);
        $password->user = $this;
        $password->save();

        $this->pw_last_changed = Utils::getDatetime();
        $this->pw_expires = Utils::calculatePasswordExpirationDate($this->pw_last_changed);
        
        if ( ! $this->save()) {
            \Yii::error([
                'action' => 'set password for user',
                'status' => 'error',
                'error' => $this->getFirstErrors(),
            ]);
            throw new ServerErrorHttpException('Unable to save user profile after password change', 1466104537);
        }

        /*
         * Check for request to get user's IP address for logging
         */
        $ipAddress = Utils::getClientIp(\Yii::$app->request);
        if (empty($ipAddress)) {
            $ipAddress = 'Not web request';
        }

        /*
         * Log password change
         */
        $scenario = ($this->auth_type === self::AUTH_TYPE_RESET) ?
            PasswordChangeLog::SCENARIO_RESET : PasswordChangeLog::SCENARIO_CHANGE;
        PasswordChangeLog::log(
            $this->id,
            $scenario,
            $ipAddress
        );

        /*
         * Log event
         */
        EventLog::log(
            'PasswordChanged',
            [
                'Authentication method' => $this->auth_type,
                'IP Address' => $ipAddress,
            ],
            $this->id
        );
    }

    /**
     * @param string $clientId
     * @return string
     * @throws ServerErrorHttpException
     */
    public function createAccessToken($clientId, $authType)
    {
        /*
         * Create access_token and update user
         */
        $accessToken = Utils::generateRandomString(32);
        /*
         * Store combination of clientId and accessToken for bearer auth
         */
        $this->auth_type = $authType;
        $this->access_token = Utils::getAccessTokenHash($clientId . $accessToken);
        $this->access_token_expiration = Utils::getDatetime(
            time() + \Yii::$app->params['accessTokenLifetime']
        );
        if ( ! $this->save()) {
            \Yii::error([
                'action' => 'create access token for user',
                'status' => 'error',
                'error' => $this->getFirstErrors(),
            ]);
            throw new ServerErrorHttpException('Unable to create access token', 1465833228);
        }

        return $accessToken;
    }

    public function isAuthScopeFull()
    {
        return $this->auth_type === self::AUTH_TYPE_LOGIN;
    }
}
