<?php
namespace common\models;

use common\exception\InvalidCodeException;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

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
                    'value', 'match', 'pattern' => '/^[\-0-9,\(\) \.#*\+]{8,32}$/',
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
        return Utils::stripNonNumbers($this->value);
    }

    /**
     * @param integer $userId
     * @param string $type
     * @param string $value
     * @return Method
     * @throws \Exception
     * @throws BadRequestHttpException
     */
    public static function createAndSendVerification($userId, $type, $value)
    {
        /*
         * Check for existing unverified method first. To be safe, getting all of same type
         * and comparing sanitized value. If found, resend rather than create new.
         */
        $existing = self::checkForExistingAndResend($userId, $type, $value);
        if ($existing !== null) {
            return $existing;
        }

        $log = [
            'class' => __CLASS__,
            'method' => __METHOD__,
            'user_id' => $userId,
            'type' => $type,
        ];

        $method = new Method();
        $method->user_id = $userId;
        $method->type = $type;

        /*
         * Try to get national formatted version of number if phone
         */
        if ($type == self::TYPE_PHONE) {
            try {
                $method->value = \Yii::$app->phone->format(Utils::stripNonNumbers($value));
            } catch (\Exception $e) {
                $log['status'] ='error';
                $log['error'] = $e->getMessage();
                $log['value'] = Utils::stripNonNumbers($value);
                \Yii::warning($log);

                throw new BadRequestHttpException(\Yii::t('app', $e->getMessage()), $e->getCode());
            }
        } elseif ($type == self::TYPE_EMAIL) {
            $method->value = mb_strtolower($value);
        } else {
            throw new BadRequestHttpException(\Yii::t('app', 'Invalid method type'), 1470169372);
        }

        if ($type == self::TYPE_PHONE) {
            $log['value'] = Utils::maskPhone($value);
        } elseif ($type == self::TYPE_EMAIL) {
            $log['value'] = Utils::maskEmail($value);
        } else {
            $log['value'] = 'invalid type';
        }

        if ( ! $method->save()) {
            $log['status'] = 'failed';
            $log['error'] = $method->getFirstErrors();
            \Yii::warning($log);

            throw new \Exception('Unable to add new method', 1461375342);
        }

        /*
         * Method saved, send verification
         * If sending fails, delete method, log it, and return error to user
         */
        try {
            $method->sendVerification();
        } catch (\Exception $e) {
            $methodDeleted = $method->delete();
            $log['status'] = 'error';
            $log['error'] = $e->getMessage();
            $log['code'] = $e->getCode();
            $log['method deleted'] = $methodDeleted ? 'yes' : 'no';
            \Yii::error($log);

            throw new ServerErrorHttpException(
                'Unable to create new verification method. Please check the value you entered and try again. ' .
                sprintf('Error code:  %s', $e->getCode()),
                1469736442,
                $e
            );
        }

        $log['status'] = 'success';
        \Yii::warning($log);

        return $method;
    }

    /**
     * Checks for an existing unverified method and resends verification code if found
     * @param integer $userId
     * @param string $type
     * @param string $value
     * @return Method|null
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    public static function checkForExistingAndResend($userId, $type, $value)
    {
        $existing = self::find()->where([
            'user_id' => $userId,
            'type' => $type,
            'verified' => 0,
        ])->andWhere([
            '>=', 'verification_expires', Utils::getDatetime()
        ])->all();


        /*
         * Email check function
         */
        $checkFunction = function($value) {
            return mb_strtolower($value);
        };

        /*
         * If type is phone use different check function
         */
        if ($type == self::TYPE_PHONE) {
            $checkFunction = function($value) {
                return Utils::stripNonNumbers($value);
            };
        }
        /** @var Method $existMethod */
        foreach ($existing as $existMethod) {
            if ($checkFunction($existMethod->value) === $checkFunction($value)) {
                try {
                    $existMethod->sendVerification();
                } catch (\Exception $e) {
                    /*
                     * If phone verification in process, treat as success so user can provide code
                     */
                    if ($e->getCode() != 1470317050) {
                        throw $e;
                    }
                }

                return $existMethod;
            }
        }

        return null;
    }

    /**
     * Send verification to either email or phone based on $this->type
     * @throws \Exception
     */
    public function sendVerification()
    {
        /*
         * Count as verification attempt and send verification code
         */
        $this->verification_attempts++;
        if ( ! $this->save()) {
            throw new ServerErrorHttpException(
                'Unable to save method after incrementing attempts',
                1461441850
            );
        }

        if ($this->type == self::TYPE_EMAIL) {
            $this->sendVerificationEmail();
        } elseif ($this->type == self::TYPE_PHONE) {
            /*
             * Save verification code to db again in case phone provider generated a new one
             */
            $this->verification_code = $this->sendVerificationPhone();
            if ( ! $this->save()) {
                throw new ServerErrorHttpException(
                    sprintf('Unable to save method after sending %s verification', $this->type),
                    1461441851
                );
            }
        } else {
            throw new BadRequestHttpException(\Yii::t('app', 'Invalid method type'), 1461432437);
        }

    }

    /**
     * @throws \Exception
     */
    public function sendVerificationEmail()
    {
        $friendlyExpireTime = Utils::getFriendlyDate($this->verification_expires);
        Verification::sendEmail(
            $this->value,
            'Verification required - New account recovery method added',
            '@common/mail/method/verify',
            $this->verification_code,
            $friendlyExpireTime,
            $this->user,
            null,
            $this->user->getId(),
            'New email method',
            'A new email method has been added and verification sent to ' . $this->getMaskedValue(),
            []
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
            \Yii::error([
                'action' => 'validate and set method as verified',
                'status' => 'error',
                'error' => $this->getFirstErrors(),
            ]);
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
            try {
                $deleted = $method->delete();
                if ($deleted === 0 || $deleted === false) {
                    throw new \Exception('Expired method delete call failed', 1470324506);
                }
            } catch (\Exception $e) {
                \Yii::error([
                    'action' => 'delete expired unverified methods',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'method_id' => $method->id,
                ]);
            }
        }
    }

}