<?php
namespace common\models;

use Sil\Idp\IdBroker\Client\IdBrokerClient;
use Sil\Idp\IdBroker\Client\ServiceException;
use common\exception\InvalidCodeException;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
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

    /**
     * @var IdBrokerClient
     */
    public $idBrokerClient;

    public function init()
    {
        parent::init();
        $config = \Yii::$app->params['mfa'];
        $this->idBrokerClient = new IdBrokerClient(
            $config['baseUrl'],
            $config['accessToken'],
            [
                IdBrokerClient::TRUSTED_IPS_CONFIG              => $config['validIpRanges']       ?? [],
                IdBrokerClient::ASSERT_VALID_BROKER_IP_CONFIG   => $config['assertValidBrokerIp']   ?? true,
            ]
        );
    }

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
                    ['type'], 'in', 'range' => [self::TYPE_EMAIL],
                    'message' => 'Method type must be ' . self::TYPE_EMAIL . '.',
                ],

                [// Email validation when type is email
                    'value', 'email', 'when' => function() { return $this->type === self::TYPE_EMAIL; }
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
        if ($this->type == self::TYPE_EMAIL) {
            return Utils::maskEmail($this->value);
        } else {
            throw new \Exception('Method using invalid Type', 1456610497);
        }
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

        if ($type == self::TYPE_EMAIL) {
            $method->value = mb_strtolower($value);
        } else {
            throw new BadRequestHttpException(\Yii::t('app', 'Invalid method type'), 1470169372);
        }

        if ($type == self::TYPE_EMAIL) {
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

        /** @var Method $existMethod */
        foreach ($existing as $existMethod) {
            if ($checkFunction($existMethod->value) === $checkFunction($value)) {
                $existMethod->sendVerification();

                return $existMethod;
            }
        }

        return null;
    }

    /**
     * Send verification to email
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
     * @param string $userSubmitted
     * @return bool
     */
    public function isUserProvidedCodeCorrect($userSubmitted)
    {
        if ($this->type === self::TYPE_EMAIL) {
            return Verification::isEmailCodeValid($this->verification_code, $userSubmitted);
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

    /**
     * Gets all methods for user specified by $employeeId
     * @param string $employeeId
     * @return String[]
     * @throws BadRequestHttpException
     * @throws ServiceException
     */
    public static function getMethods($employeeId)
    {
        $method = new Method;

        try {
            return $method->idBrokerClient->listMethod($employeeId);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 400) {
                throw new BadRequestHttpException(\Yii::t('app', 'Error locating personnel record'), 1542752270);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Gets all verified methods for user specified by $employeeId
     * @param string $employeeId
     * @return string[]
     * @throws BadRequestHttpException
     * @throws ServiceException
     */
    public static function getVerifiedMethods($employeeId)
    {
        $methods = self::getMethods($employeeId);

        $verifiedMethods = [];

        if (is_iterable($methods)) {
            foreach ($methods as $method) {
                if ($method['verified'] ?? false) {
                    $verifiedMethods[] = $method;
                }
            }
        }

        return $verifiedMethods;
    }

    /**
     * Gets a specific verified method for user specified by $employeeId
     * @param string $uid
     * @param string $employeeId
     * @return null|String[]
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public static function getOneVerifiedMethod($uid, $employeeId)
    {
        $method = new Method;
        try {
            return $method->idBrokerClient->getMethod($uid, $employeeId);
        } catch (ServiceException $e) {
            if ($e->httpStatusCode === 404) {
                throw new NotFoundHttpException(
                    \Yii::t('app', 'Method not found'),
                    1462989221
                );
            } else {
                throw new \Exception($e->getMessage());
            }
        }
    }
}
