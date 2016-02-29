<?php
namespace common\models;

use Yii;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use yii\web\NotFoundHttpException;
use Sil\IdpPw\Common\Personnel\PersonnelInterface;
use Sil\IdpPw\Common\Personnel\PersonnelUser;
use Sil\IdpPw\Common\Personnel\NotFoundException;

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
     * @return array
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
     * Find or create local user. Fetch/update user data from personnel.
     * @param string $username
     * @return User
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws NotFoundException
     */
    public static function findOrCreate($username)
    {
        /*
         * If username looks like an email address, search by email
         */
        /** @var PersonnelUser $personnelUser */
        if (substr_count($username, '@') > 0) {
            $personnelUser = \Yii::$app->personnel->findByEmail($username);
        } else {
            $personnelUser = \Yii::$app->personnel->findByUsername($username);
        }

        $user = self::findOne(['employee_id' => $personnelUser->employeeId]);
        if ( ! $user) {
            $user = new User();
            $user->employee_id = $personnelUser->employeeId;
            $user->first_name = $personnelUser->firstName;
            $user->last_name = $personnelUser->lastName;
            $user->idp_username = $personnelUser->username;
            $user->email = $personnelUser->email;
            if ( ! $user->save()) {
                /**
                 * @todo add logging with model validation errors
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
     * @param string $firstName
     * @param string $lastName
     * @param string $username
     * @param string $email
     * @return bool
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
            if($this->$property != $value) {
                $dirty = true;
                $this->$property = $value;
            }
        }

        if ($dirty) {
            if ($this->save()) {
                return true;
            } else {
                /**
                 * @todo add logging with model validation errors
                 */
                throw new \Exception("Unable to update profile", 1456760819);
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
        $methods = [];
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

        /*
         * If session is available, cache data in session
         */
        try {
            $sessionAvailable = (\Yii::$app->user->identity == $this);
        } catch (\Exception $e) {
            $sessionAvailable = false;
        }

        if ($sessionAvailable && is_array(\Yii::$app->session->get('personnelUser'))) {
            return \Yii::$app->session->get('personnelUser');
        }

        /*
         * Fetch data from Personnel system and cache it
         */
        $this->fetchPersonnelUser();
        \Yii::$app->session->set('personnelUser', $this->personnelUser);

        return $this->personnelUser;
    }

    /**
     * @return bool
     */
    public function hasSupervisor()
    {
        $personnelUser = $this->getPersonnelUser();
        return $personnelUser->supervisorEmail !== null;
    }

    /**
     * @return bool
     */
    public function hasSpouse()
    {
        $personnelUser = $this->getPersonnelUser();
        return $personnelUser->spouseEmail !== null;
    }

    /**
     * @return null|PersonnelUser
     */
    public function getSupervisorEmail()
    {
        $personnelUser = $this->getPersonnelUser();
        return $personnelUser->supervisorEmail;
    }
    /**
     * @return null|PersonnelUser
     */
    public function getSpouseEmail()
    {
        $personnelUser = $this->getPersonnelUser();
        return $personnelUser->spouseEmail;
    }

    /**
     * @throws \Exception
     */
    public function fetchPersonnelUser()
    {
        /** @var PersonnelInterface $personnel */
        $personnel = \Yii::$app->personnel;

        if ($this->employee_id) {
            $this->personnelUser = $personnel->findByEmployeeId($this->employee_id);
        } elseif ($this->idp_username) {
            $this->personnelUser = $personnel->findByUsername($this->idp_username);
        } elseif ($this->email) {
            $this->personnelUser = $personnel->findByEmail($this->email);
        } else {
            throw new \Exception('Not enough information to find personnel data', 1456690741);
        }
    }

    /**
     * Finds an identity by the given ID.
     *
     * @param string|integer $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * This method is not supported in this app right now but is required by Yii IdentityInterface
     *
     * @param string $token the token to be looked for
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
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
}