<?php
namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

/**
 * Class Reset
 * @package common\models
 * @method Reset self::findOne([])
 */
class Reset extends ResetBase
{

    const TYPE_PRIMARY = 'primary'; // Used for primary email address
    const TYPE_METHOD = 'method';
    const TYPE_SUPERVISOR = 'supervisor';
    const TYPE_SPOUSE = 'spouse';

    const TOPIC_RESET_EMAIL_SENT = 'Reset Email Sent';
    const TOPIC_RESET_PHONE_SENT = 'Reset Phone Sent';

    /**
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
                    ['expires'], 'default', 'value' => Utils::getDatetime(self::getExpireTimestamp()),
                ],

                [
                    ['type'], 'default', 'value' => self::TYPE_PRIMARY,
                ],

                [
                    ['attempts'], 'default', 'value' => 0,
                ],

                [
                    ['type'], 'in', 'range' => [
                        self::TYPE_PRIMARY, self::TYPE_METHOD, self::TYPE_SUPERVISOR, self::TYPE_SPOUSE
                    ],
                    'message' => 'Reset type must be either ' . self::TYPE_PRIMARY . ' or ' . self::TYPE_METHOD .
                        ' or ' . self::TYPE_SUPERVISOR . ' or ' . self::TYPE_SPOUSE . ' .',
                ],
            ],
            parent::rules()
        );
    }

    /**
     * @return array
     */
    public function fields()
    {
        return [
            'uid',
            'methods' => function($model) {
                return $model->user->getMaskedMethods();
            },
        ];
    }

    /**
     * @param User $user
     * @return Reset
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public static function findOrCreate($user)
    {
        /*
         * Clean up expired resets
         */
        self::deleteExpired();

        $log = [
            'action' => 'findOrCreate reset',
            'user_id' => $user->id,
            'user' => $user->email,
            'type' => self::TYPE_PRIMARY,
            'method_id' => null,
            'ip_address' => 'initiated outside web request context',
        ];

        if (\Yii::$app->request) {
            $log['ip_address'] = Utils::getClientIp(\Yii::$app->request);
        }

        /*
         * Find existing or create new Reset
         */
        $reset = $user->reset;
        if ($reset === null) {
            $log['existing reset'] = 'no';
            /*
             * Create new reset
             */
            $reset = new Reset();
            $reset->user_id = $user->id;
            /*
             * Only set type/method if creating so that on subsequent restarts of process
             * it does not reset method to primary
             */
            $reset->type = self::TYPE_PRIMARY;

            /*
             * Save new Reset
             */
            $reset->saveOrError('create new reset', 'Unable to create new reset.');

            EventLog::log(
                'ResetCreated',
                [
                    'reset_id' => $reset->id,
                    'type' => $reset->type
                ],
                $user->id
            );
        } else {
            $log['existing reset'] = 'yes';
            /*
             * change method back to primary if they are requesting to start reset again
             */
            $reset->setType(self::TYPE_PRIMARY);
        }

        $log['status'] = 'success';
        $log['attempts_count'] = $reset->attempts;
        $log['expires'] = $reset->expires;
        $log['disable_until'] = $reset->disable_until;
        \Yii::warning($log);

        return $reset;
    }

    /**
     * Make sure reset is not disabled, track attempt, and then send.
     * Send reset notification to appropriate method
     * @throws \Exception
     */
    public function send()
    {
        /*
         * Track attempt and throw error if disabled or limit is reached
         */
        $this->trackAttempt('send');

        /*
         * Based on type/method send reset verification and update
         * model with reset code
         */
        switch ($this->type) {
            case self::TYPE_PRIMARY:
                $this->sendPrimary();
                break;
            case self::TYPE_SUPERVISOR:
                $this->sendSupervisor();
                break;
            case self::TYPE_SPOUSE:
                $this->sendSpouse();
                break;
            case self::TYPE_METHOD:
                $this->sendMethod();
                break;
            default:
                throw new \Exception('Reset is configured with unknown type.', 1456784825);
        }
    }

    private function sendPrimary()
    {
        $subject = \Yii::t(
            'app',
            '{idpDisplayName} password reset request',
            [
                'idpDisplayName' => \Yii::$app->params['idpDisplayName'],
            ]
        );

        $this->sendEmail($this->user->email, $subject, 'self');
    }

    private function sendSupervisor()
    {
        if ($this->user->hasSupervisor()) {
            $supervisor = $this->user->getSupervisorEmail();
            $this->sendOnBehalf($supervisor);
        } else {
            throw new \Exception('User does not have supervisor on record', 1461173406);
        }
    }

    private function sendSpouse()
    {
        if ($this->user->hasSpouse()) {
            $spouse = $this->user->getSpouseEmail();
            $this->sendOnBehalf($spouse);
        } else {
            throw new \Exception('User does not have spouse on record', 1461173477);
        }
    }

    private function sendOnBehalf($toAddress)
    {
        $subject = \Yii::t(
            'app',
            '{idpDisplayName} password reset request for {name}',
            [
                'idpDisplayName' => \Yii::$app->params['idpDisplayName'],
                'name' => $this->user->first_name,
            ]
        );

        $this->sendEmail($toAddress, $subject, 'on-behalf', $this->user->email);
    }

    /**
     * Send reset appropriate for its type
     * @throws \Exception
     */
    private function sendMethod()
    {
        if ( ! ($this->method instanceof Method)) {
            throw new \Exception('Method not initialized on Reset', 1456608512);
        }

        if ($this->method->type == Method::TYPE_EMAIL) {
            /*
             * Send email to 'self' with verified email address
             */
            $subject = \Yii::t(
                'app',
                '{idpDisplayName} password reset request',
                [
                    'idpDisplayName' => \Yii::$app->params['idpDisplayName'],
                ]
            );
            $this->sendEmail($this->method->value, $subject, 'on-behalf');
        } else {
            throw new \Exception('Method using unknown type', 1456608781);
        }
    }

    /**
     * @param string $toAddress
     * @param string $subject
     * @param string $view
     * @param string|null $ccAddress
     * @throws \Exception
     */
    private function sendEmail($toAddress, $subject, $view, $ccAddress = null)
    {
        /*
         * Generate code if needed, update attempt counter, save record, and send email
         */
        if ($this->code === null) {
            $this->code = Utils::getRandomDigits(\Yii::$app->params['reset']['codeLength']);
            $this->saveOrError('send email', 'Unable to update reset in database, email not sent.');
        }

        $resetUrl = sprintf('%s/reset/%s/verify/%s', \Yii::$app->params['uiUrl'], $this->uid, $this->code);

        // Send email verification
        $friendlyExpiration = Utils::getFriendlyDate($this->expires);
        Verification::sendEmail(
            $toAddress,
            $subject,
            '@common/mail/reset/' . $view,
            $this->code,
            $friendlyExpiration,
            $this->user,
            $ccAddress,
            $this->user->id,
            self::TOPIC_RESET_EMAIL_SENT,
            'Password reset email for ' . $this->user->getDisplayName() .
            ' sent to ' . $toAddress,
            ['resetUrl' => $resetUrl]
        );
        
        \Yii::warning([
            'action' => 'reset send email',
            'user' => $this->user->email,
            'to_address' => $toAddress,
            'subject' => $subject,
        ]);
    }

    /**
     * Check if user provided code is valid
     * @param string $userProvided code submitted by user
     * @return boolean
     * @throws \Exception
     * @throws ServerErrorHttpException
     * @throws TooManyRequestsHttpException
     */
    public function isUserProvidedCodeCorrect($userProvided)
    {
        /*
         * Track attempt and throw error if disabled or limit is reached
         */
        $this->trackAttempt('verify');

        if ($this->isTypeEmail()) {
            return Verification::isEmailCodeValid($this->code, $userProvided);
        } else {
            throw new \Exception('Unable to verify code because method type is invalid', 1462543005);
        }
    }

    /**
     * Check if reset is using an email type of verification
     * @return bool
     */
    public function isTypeEmail()
    {
        return ($this->type === self::TYPE_PRIMARY
            || $this->type === self::TYPE_SUPERVISOR
            || $this->type === self::TYPE_SPOUSE
            || ($this->type === self::TYPE_METHOD && $this->method->type === Method::TYPE_EMAIL));
    }

    /**
     * Calculate expiration timestamp based on given timestamp and configured reset lifetime
     * @return integer
     * @throws ServerErrorHttpException
     */
    public static function getExpireTimestamp()
    {
        $params = \Yii::$app->params;
        if ( ! isset($params['reset']) || ! isset($params['reset']['lifetimeSeconds']) ||
            ! is_integer($params['reset']['lifetimeSeconds'])) {
            throw new ServerErrorHttpException('Application configuration for reset lifetime is not set', 1458676224);
        }

        $time = time();

        return $time + $params['reset']['lifetimeSeconds'];
    }

    /**
     * Check if this reset is currently disabled
     * @return bool
     */
    public function isDisabled()
    {
        if ($this->disable_until !== null) {
            $disableUntilTime = strtotime($this->disable_until);
            // Intentionally loose comparison to catch zero
            if ($disableUntilTime == false) {
                return true;
            }
            return $disableUntilTime > time();
        }

        return false;
    }

    /**
     * Mark reset as disabled by setting disable_until date
     * @throws ServerErrorHttpException
     */
    public function disable()
    {
        $log = [
            'action' => 'disable reset',
            'reset_id' => $this->id,
            'attempts' => $this->attempts,
            'user' => $this->user->email,
        ];
        $this->disable_until = Utils::getDatetime(time() + \Yii::$app->params['reset']['disableDuration']);
        $this->saveOrError($log['action'], 'Unable to save reset with disable_until.');

        EventLog::log(
            'ResetDisabled',
            [
                'reset_id' => $this->id,
                'type' => $this->type,
                'attempts' => $this->attempts,
                'disable_until' => $this->disable_until,
            ],
            $this->user_id
        );

        $log['status'] = 'success';
        \Yii::warning($log);
    }

    /**
     * Re-enable reset
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function enable()
    {
        $this->disable_until = null;
        $this->attempts = 0;
        $this->saveOrError('enable reset', 'Unable to enable reset.');

        EventLog::log(
            'ResetEnabled',
            [
                'reset_id' => $this->id,
                'type' => $this->type,
            ],
            $this->user_id
        );

        \Yii::warning([
            'action' => 'enable reset',
            'reset_id' => $this->id,
            'status' => 'success',
            'user' => $this->user->email,            
        ]);
    }

    /**
     * Enable reset if disable until time has past, or check attempts count and disable if it should be
     * @throws ServerErrorHttpException
     */
    public function enableOrDisableIfNeeded()
    {
        if ($this->disable_until !== null) {
            $disableUntilTime = strtotime($this->disable_until);
            if ($disableUntilTime == false) {
                throw new ServerErrorHttpException('Unable to check disable timeout', 1463146757);
            }

            /*
             * Disable until is in the past, so enable reset
             */
            if ($disableUntilTime < time()) {
                $this->enable();
            }
        } else {
            /*
             * If attempts has reached limit, disable reset
             */
            if ($this->attempts >= \Yii::$app->params['reset']['maxAttempts']) {
                $this->disable();
            }
        }
    }

    public function setType($type, $methodUid = null)
    {
        $previousType = $this->type;
        /*
         * If type is not method, update or throw error
         */
        if (in_array($type, [self::TYPE_SPOUSE, self::TYPE_SUPERVISOR, self::TYPE_PRIMARY])) {
            $this->type = $type;
            $this->method_id = null;
        } elseif ($type == self::TYPE_METHOD) {
            /*
             * If type is method but methodId is missing, throw error
             */
            if ($methodUid === null) {
                throw new BadRequestHttpException(
                    \Yii::t('app', 'Method UID required for reset type method'),
                    1462988984
                );
            }

            /*
             * Make sure user owns requested method and it is verified before update
             */
            $method = Method::findOne(['uid' => $methodUid, 'user_id' => $this->user_id, 'verified' => 1]);
            if ($method === null) {
                throw new NotFoundHttpException(
                    \Yii::t('app', 'Method not found'),
                    1462989221
                );
            }
            $this->type = self::TYPE_METHOD;
            $this->method_id = $method->id;
        } else {
            throw new BadRequestHttpException(
                \Yii::t('app', 'Unknown reset type requested'),
                1462989489
            );
        }

        // NOTE: We stopped changing the code here so that, if someone requests
        // a subsequent reset while an existing one is not yet expired, the same
        // code will be used. That way, clicking the link in the first email
        // will still work.

        /*
         * Save changes
         */
        $this->saveOrError('Set type of reset', 'Unable to update reset type.');

        EventLog::log(
            'ResetChangeType',
            [
                'reset_id' => $this->id,
                'previous_type' => $previousType,
                'new_type' => $this->type,
                'attempts' => $this->attempts,
            ],
            $this->user_id
        );
    }

    /**
     * Increments attempts counter and disables account when limit is reached
     * @param string $action Used in logging, either 'send' or 'verify'
     * @throws ServerErrorHttpException
     * @throws TooManyRequestsHttpException
     */
    public function trackAttempt($action)
    {
        /*
         * Increment attempts count first thing
         */
        $this->attempts++;
        $this->saveOrError($action . ' reset', 'Unable to increment attempts count.');

        /*
         * Enable / disable reset as needed
         */
        $this->enableOrDisableIfNeeded();

        /*
         * Check if reset is disabled and throw exception if it is
         */
        if ($this->isDisabled()) {
            \Yii::warning([
                'action' => $action . ' reset',
                'reset_id' => $this->id,
                'attempts' => $this->attempts,
                'status' => 'error',
                'error' => 'Reset is currently disabled until ' . $this->disable_until,
                'user' => $this->user->email,                
            ]);
            throw new TooManyRequestsHttpException();
        }
    }

    /**
     * Save model or throw exception on error
     * @param string $action
     * @param string $errorPrefix This can be displayed to end user, so do not put anything sensitive in it
     * @throws ServerErrorHttpException
     */
    public function saveOrError($action, $errorPrefix = '')
    {
        if ( ! $this->save()) {
            \Yii::error([
                'action' => $action,
                'reset_id' => $this->id,
                'attempts' => $this->attempts,
                'type' => $this->type,
                'status' => 'error',
                'error' => $errorPrefix . ' Error: ' . Json::encode($this->getFirstErrors()),
                'user' => $this->user->email,                
            ]);
            throw new ServerErrorHttpException(\Yii::t('app', $errorPrefix));
        }
    }

    /**
     * Delete all expired Reset records
     */
    public static function deleteExpired()
    {
        try {
            $deleted = self::deleteAll(
                ['<', 'expires', Utils::getDatetime()]
            );

            if ($deleted > 0) {
                \Yii::warning([
                    'action' => 'delete expired resets',
                    'status' => 'success',
                    'deleted count' => $deleted,
                ]);
            }
        } catch (\Exception $e) {
            \Yii::error([
                'action' => 'delete expired resets',
                'status' => 'error',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }

    /**
     * Return the masked value of whatever is used for this reset
     * @return string
     */
    public function getMaskedValue()
    {
        if ($this->type === self::TYPE_METHOD) {
            return $this->method->getMaskedValue();
        } elseif ($this->type === self::TYPE_PRIMARY) {
            return Utils::maskEmail($this->user->email);
        } elseif ($this->type == self::TYPE_SUPERVISOR) {
            return Utils::maskEmail($this->user->getSupervisorEmail());
        } elseif ($this->type == self::TYPE_SPOUSE) {
            return Utils::maskEmail($this->user->getSpouseEmail());
        } else {
            return 'Invalid reset type';
        }
    }
}
