<?php
namespace common\models;

use common\exception\InvalidCodeException;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class Method
 * @package common\models
 * @method Method self::findOne([])
 */
class Method extends MethodBase
{

    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['uid'], 'default', 'value' => Utils::generateRandomString(),
                ],

                [
                    ['verified', 'verification_attempts'], 'default', 'value' => 0,
                ],

                [
                    'verification_code', 'default', 'when' => function() { return $this->getIsNewRecord(); },
                    'value' => Utils::getRandomDigits(\Yii::$app->params['reset']['codeLength']),
                ],

                [
                    'verification_expires', 'default', 'when' => function() { return $this->getIsNewRecord(); },
                    'value' => Utils::getDatetime(time() + \Yii::$app->params['reset']['lifetimeSeconds']),
                ],

                [
                    ['type'], 'in', 'range' => [self::TYPE_EMAIL, self::TYPE_PHONE],
                    'message' => 'Method type must be either ' . self::TYPE_EMAIL . ' or ' . self::TYPE_PHONE . '.',
                ],

                [// Email validation when type is email
                    'value', 'email', 'when' => function() { return $this->type === self::TYPE_EMAIL; }
                ],

                [// Phone number validation when type is phone
                    'value', 'match', 'pattern' => '/[0-9,]{8,16}/',
                    'when' => function() { return $this->type === self::TYPE_PHONE; }
                ],

                [
                    ['created'], 'default', 'value' => Utils::getDatetime(),
                ],

            ],
            parent::rules()
        );
    }

    public function fields()
    {
        return [
            'id' => function() { return $this->uid; },
            'type',
            'value',
        ];
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMaskedValue()
    {
        if ($this->type == self::TYPE_PHONE) {
            return Utils::maskPhone($this->value);
        } elseif ($this->type == self::TYPE_EMAIL) {
            return Utils::maskEmail($this->value);
        } else {
            throw new \Exception('Method using invalid Type', 1456610497);
        }
    }

    /**
     * If this is a phone method, remove comma from value before returning
     * @return string
     */
    public function getRawPhoneNumber()
    {
        return preg_replace('/,/', '', $this->value);
    }

    /**
     * @param integer $userId
     * @param string $type
     * @param string $value
     * @return Method
     * @throws \Exception
     */
    public static function createAndSendVerification($userId, $type, $value)
    {
        $log = [
            'class' => __CLASS__,
            'method' => __METHOD__,
            'user_id' => $userId,
            'type' => $type,
        ];

        $method = new Method();
        $method->user_id = $userId;
        $method->type = $type;
        $method->value = $value;

        if ($type == self::TYPE_PHONE) {
            $log['value'] = Utils::maskPhone($value);
        } elseif ($type == self::TYPE_EMAIL) {
            $log['value'] = Utils::maskEmail($value);
        } else {
            $log['value'] = 'invalid type';
        }

        if ( ! $method->save()) {
            $log['status'] = 'failed';
            $log['error'] = Json::encode($method->getFirstErrors());
            \Yii::error($log);

            throw new \Exception('Unable to add new method', 1461375342);
        }

        /*
         * Method saved, send verification
         */
        $method->sendVerification();

        $log['status'] = 'success';
        \Yii::warning($log);

        return $method;
    }

    /**
     * Send verification to either email or phone based on $this->type
     * @throws \Exception
     */
    public function sendVerification()
    {
        if ($this->type == self::TYPE_EMAIL) {
            $this->sendVerificationEmail();
        } elseif ($this->type == self::TYPE_PHONE) {
            $this->verification_code = $this->sendVerificationPhone();
            if ( ! $this->save()) {
                throw new \Exception('Unable to save method after sending phone verification', 1461441850);
            }
        } else {
            throw new \Exception('Invalid method type', 1461432437);
        }
    }

    /**
     * @throws \Exception
     */
    public function sendVerificationEmail()
    {
        Verification::sendEmail(
            $this->value,
            'Verification required - New account recovery method added',
            '@common/mail/method/verify',
            $this->verification_code,
            $this->user,
            null,
            $this->user->getId(),
            'New email method',
            'A new email method has been added and verification sent to ' . $this->getMaskedValue()
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function sendVerificationPhone()
    {
        return Verification::sendPhone(
            $this->getRawPhoneNumber(),
            $this->verification_code,
            $this->user->getId(),
            'New phone method',
            'A new phone method has been added and verification sent to ' . $this->getMaskedValue()
        );
    }

    /**
     * @param string $userSubmitted
     * @return bool
     */
    public function isUserProvidedCodeCorrect($userSubmitted)
    {
        if ($this->type === self::TYPE_EMAIL) {
            return Verification::isEmailCodeValid($this->verification_code, $userSubmitted);
        } elseif ($this->type === self::TYPE_PHONE) {
            return Verification::isPhoneCodeValid($this->verification_code, $userSubmitted);
        } else {
            return false;
        }
    }

    /**
     * Validate user submitted code and update record to be verified if valid
     * @param string $userSubmitted
     * @throws \Exception
     */
    public function validateAndSetAsVerified($userSubmitted)
    {
        /*
         * Increase attempts count before verifying code in case verification fails
         * for some reason
         */
        $this->verification_attempts++;
        if ( ! $this->save()) {
            throw new \Exception('Unable to increment verification attempts', 1462903086);
        }

        /*
         * Verify user provided code
         */
        if ( ! $this->isUserProvidedCodeCorrect($userSubmitted)) {
            throw new InvalidCodeException('Invalid verification code', 1461442988);
        }

        /*
         * Update attributes to be verified
         */
        $this->verification_code = null;
        $this->verification_expires = null;
        $this->verification_attempts = null;
        $this->verified = 1;

        if ( ! $this->save()) {
            throw new \Exception('Unable to set method as verified', 1461442990);
        }
    }

    /**
     * Delete all method records that are not verified and verification_expires date is in the past
     * @throws \Exception
     */
    public static function deleteExpiredUnverifiedMethods()
    {
        $methods = self::find()->where(['verified' => 0])
                               ->andWhere(['<', 'verification_expires', Utils::getDatetime()])
                               ->all();

        foreach ($methods as $method) {
            if ( ! $method->delete()) {
                \Yii::error([
                    'action' => 'delete expired unverified methods',
                    'status' => 'failed',
                    'error' => Json::encode($method->getFirstErrors()),
                    'method_id' => $method->id,
                ]);
            }
        }
    }
}