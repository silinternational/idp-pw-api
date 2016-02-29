<?php
namespace common\models;

use Yii;
use common\components\PersonnelInterface;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use yii\web\NotFoundHttpException;

/**
 * Class User
 * @package common\models
 * @method User self::findOne([])
 */
class User extends UserBase implements IdentityInterface
{
    /**
     * Holds personnelData
     * @var array
     */
    public $personnelData;

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
     */
    public static function findOrCreate($username)
    {
        /*
         * If username looks like an email address, search by email
         */
        if (substr_count($username, '@') > 0) {
            $criteria = ['email' => $username];
        } else {
            $criteria = ['username' => $username];
        }

        $user = self::findOne($criteria);
        if ( ! $user) {
            /**
             * @todo integrate with personnel backend to find user and create local user
             *       throw NotFoundHttpException if not found
             */
        } else {
            /**
             * @todo user found, but call personnel to verify they still exist
             *       update local user info if found?
             *       If not found throw NotFoundHttpException
             */
        }

        return $user;
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
     * @return array
     * @throws \Exception
     */
    public function getPersonnelData()
    {
        if ($this->personnelData) {
            return $this->personnelData;
        }

        /*
         * If session is available, cache data in session
         */
        try {
            $sessionAvailable = (\Yii::$app->user->identity == $this);
        } catch (\Exception $e) {
            $sessionAvailable = false;
        }

        if ($sessionAvailable && is_array(\Yii::$app->session->get('personnelData'))) {
            return \Yii::$app->session->get('personnelData');
        }

        /*
         * Fetch data from Personnel system and cache it
         */
        $this->fetchPersonnelData();
        \Yii::$app->session->set('personnelData', $this->personnelData);

        return $this->personnelData;
    }

    /**
     * @return bool
     */
    public function hasSupervisor()
    {
        $personnelData = $this->getPersonnelData();
        if ($personnelData['supervisor']) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasSpouse()
    {
        $personnelData = $this->getPersonnelData();
        if ($personnelData['spouse']) {
            return true;
        }

        return false;
    }

    /**
     * @return null|array
     */
    public function getSupervisor()
    {
        $personnelData = $this->getPersonnelData();
        return $personnelData['supervisor'] ?: null;
    }
    /**
     * @return null|array
     */
    public function getSpouse()
    {
        $personnelData = $this->getPersonnelData();
        return $personnelData['spouse'] ?: null;
    }

    /**
     * @throws \Exception
     */
    public function fetchPersonnelData()
    {
        /** @var PersonnelInterface $personnel */
        $personnel = \Yii::$app->personnel;

        if ($this->employee_id) {
            $this->personnelData = $personnel->findByEmployeeId($this->employee_id);
        } elseif ($this->idp_username) {
            $this->personnelData = $personnel->findByUsername($this->idp_username);
        } elseif ($this->email) {
            $this->personnelData = $personnel->findByEmail($this->email);
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