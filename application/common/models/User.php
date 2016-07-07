<?php
namespace common\models;

use Sil\IdpPw\Common\Auth\User as AuthUser;
use Sil\IdpPw\Common\PasswordStore\UserPasswordMeta;
use Sil\IdpPw\Common\Personnel\NotFoundException;
use Sil\IdpPw\Common\Personnel\PersonnelInterface;
use Sil\IdpPw\Common\Personnel\PersonnelUser;
use Yii;
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
        return [
            'first_name',
            'last_name',
            'idp_username',
            'email',
            'password_meta' => function($model) {
                return $model->getPasswordMeta();
            }
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
        /** @var PersonnelUser $personnelUser */
        if ( ! is_null($employeeId)) {
            $personnelUser = \Yii::$app->personnel->findByEmployeeId($employeeId);
        } elseif ( ! is_null($username)) {
            $personnelUser = \Yii::$app->personnel->findByUsername($username);
        } else {
            $personnelUser = \Yii::$app->personnel->findByEmail($email);
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
                /*
                 * add logging with model validation errors
                 */
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
            if ($this->save()) {
                return true;
            } else {
                /*
                 * add logging with model validation errors
                 */
                throw new \Exception('Unable to update profile', 1456760819);
            }
        }
        return false;
    }

    /**
     * Return array of arrays of masked out methods
     * @return array
     */
    public function getMaskedMethods()
    {
        /*
         * Include primary email address
         */
        $methods = [
            [
                'type' => Reset::TYPE_PRIMARY,
                'value' => Utils::maskEmail($this->email),
            ],
        ];

        /*
         * Add spouse if available
         */
        if ($this->hasSpouse()) {
            $methods[] = [
                'type' => Reset::TYPE_SPOUSE,
                'value' => Utils::maskEmail($this->getSpouseEmail()),
            ];
        }

        /*
         * Add supervisor if available
         */
        if ($this->hasSupervisor()) {
            $methods[] = [
                'type' => Reset::TYPE_SUPERVISOR,
                'value' => Utils::maskEmail($this->getSupervisorEmail()),
            ];
        }
        
        /*
         * Then get all other methods
         */
        foreach ($this->methods as $method) {
            $methods[] = [
                'uid' => $method->uid,
                'type' => $method->type,
                'value' => $method->getMaskedValue(),
            ];
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
     * @return \Sil\IdpPw\Common\Auth\User
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
     * @return Method[]
     */
    public function getVerifiedMethods()
    {
        return Method::findAll(['user_id' => $this->id, 'verified' => 1]);
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
        if ($this->pw_last_changed === null) {
            /** @var UserPasswordMeta $pwMeta */
            $pwMeta = \Yii::$app->passwordStore->getMeta($this->employee_id);

            $lastChangedTimestamp = strtotime($pwMeta->passwordLastChangeDate);
            $this->pw_last_changed = Utils::getDatetime($lastChangedTimestamp);
            $expiresTimestamp = strtotime($this->pw_last_changed) + \Yii::$app->params['passwordLifetime'];
            $this->pw_expires = Utils::getDatetime($expiresTimestamp);

            if ( ! $this->save()) {
                throw new ServerErrorHttpException('Unable to update user record with password metadata', 1467297721);
            }
        }

        return [
            'last_changed' => Utils::getIso8601($this->pw_last_changed),
            'expires' => Utils::getIso8601($this->pw_expires),
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
        $password->save();

        $this->pw_last_changed = Utils::getDatetime();
        $this->pw_expires = Utils::getDatetime(time() + \Yii::$app->params['passwordLifetime']);
        
        if ( ! $this->save()) {
            throw new ServerErrorHttpException('Unable to save user profile after password change', 1466104537);
        }
    }

    /**
     * @param string $clientId
     * @return string
     * @throws ServerErrorHttpException
     */
    public function createAccessToken($clientId)
    {
        /*
             * Create access_token and update user
             */
        $accessToken = Utils::generateRandomString(32);
        /*
         * Store combination of clientId and accessToken for bearer auth
         */
        $this->access_token = Utils::getAccessTokenHash($clientId . $accessToken);
        $this->access_token_expiration = Utils::getDatetime(
            time() + \Yii::$app->params['accessTokenLifetime']
        );
        if ( ! $this->save()) {
            throw new ServerErrorHttpException('Unable to create access token', 1465833228);
        }

        return $accessToken;
    }
}