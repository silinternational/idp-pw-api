<?php
namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

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
            'hasSupervisor' => $this->user->hasSupervisor(),
            'hasSpouse' => $this->user->hasSpouse(),
            'methods' => $this->user->getMaskedMethods(),
        ];
    }

    /**
     * @param User $user
     * @param string [default=self::TYPE_PRIMARY] $type
     * @param integer|null [default=null] $method_id
     * @return Reset
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public static function findOrCreate($user, $type = self::TYPE_PRIMARY, $methodId = null)
    {
        /*
         * Find existing or create new Reset
         */
        $reset = $user->reset;
        if ($reset === null) {
            $reset = new Reset();
            $reset->user_id = $user->id;
            /*
             * Only set type/method if creating so that on subsequent restarts of process
             * it does not reset method to primary
             */
            $reset->type = $type;
            /*
             * If $method_id is provided, make sure user owns it
             */
            if ($type == self::TYPE_METHOD && $methodId !== null) {
                $method = Method::findOne(['user_id' => $user->id, 'id' => $methodId, 'verified' => 1]);
                if ( ! $method) {
                    throw new NotFoundHttpException('Requested method not found', 1456608142);
                }
                $reset->method_id = $methodId;
            }
            /*
             * Save new Reset
             */
            if ( ! $reset->save()) {
                throw new \Exception('Unable to create new reset', 1456608028);
            }
        }

        return $reset;
    }

    /**
     * Send reset notification to appropriate method
     * @throws \Exception
     */
    public function send()
    {
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

    public function sendPrimary()
    {
        $subject = \Yii::t(
            'app',
            '{{idpName}} password reset request',
            [
                'idpName' => \Yii::$app->params['idpName'],
            ]
        );

        $this->sendEmail($this->user->email, $subject, 'self');
    }

    public function sendSupervisor()
    {
        if ($this->user->hasSupervisor()) {
            $supervisor = $this->user->getSupervisorEmail();
            $this->sendOnBehalf($supervisor);
        } else {
            throw new \Exception('User does not have supervisor on record', 1461173406);
        }
    }

    public function sendSpouse()
    {
        if ($this->user->hasSpouse()) {
            $spouse = $this->user->getSpouseEmail();
            $this->sendOnBehalf($spouse);
        } else {
            throw new \Exception('User does not have spouse on record', 1461173477);
        }
    }

    public function sendOnBehalf($toAddress)
    {
        $subject = \Yii::t(
            'app',
            '{idpName} password reset request for {name}',
            [
                'idpName' => \Yii::$app->params['idpName'],
                'name' => $this->user->first_name,
            ]
        );

        $this->sendEmail($toAddress, $subject, 'on-behalf', $this->user->email);
    }

    /**
     * Determine if type is phone or email and send accordingly
     * @throws \Exception
     */
    public function sendMethod()
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
                '{{idpName}} password reset request',
                [
                    'idpName' => \Yii::$app->params['idpName'],
                ]
            );
            $this->sendEmail($this->method->value, $subject, 'self');
        } elseif ($this->method->type == Method::TYPE_PHONE) {
            $this->sendPhone();
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
    public function sendEmail($toAddress, $subject, $view, $ccAddress = null)
    {
        /*
         * Generate code if needed, update attempt counter, save record, and send email
         */
        if ($this->code === null) {
            $this->code = Utils::getRandomDigits(\Yii::$app->params['reset']['codeLength']);
        }
        $this->attempts += 1;
        if ($this->save()) {
            // Send email verification
            Verification::sendEmail(
                $toAddress,
                $subject,
                '@common/mail/reset/' . $view,
                $this->code,
                $this->user,
                $ccAddress,
                $this->user->id,
                self::TOPIC_RESET_EMAIL_SENT,
                'Password reset email for ' . $this->user->getDisplayName() .
                'sent to ' . $toAddress
            );

        } else {
            throw new \Exception('Unable to update reset in database, email not sent', 1461098651);
        }
    }

    /**
     * Send phone verification and store resulting code
     * @throws \Exception
     */
    public function sendPhone()
    {
        // Initialize log
        $log = [
            'action' => 'reset',
            'method' => 'phone',
            'method_id' => $this->method_id,
            'previous_attempts' => $this->attempts,
        ];

        // Get phone number without comma
        $number = $this->method->getRawPhoneNumber();

        // Generate random code for potential use
        $code = Utils::getRandomDigits(\Yii::$app->phone->codeLength);

        // Send phone verification
        $result = Verification::sendPhone(
            $number,
            $code,
            $this->user->getId(),
            self::TOPIC_RESET_PHONE_SENT,
            'Password reset for ' . $this->user->getDisplayName() .
            'sent to phone ' . $this->method->getMaskedValue()
        );

        // Update db with code and increased attempts count
        $this->code = $result;
        $this->attempts++;
        if ( ! $this->save()) {
            $log['status'] = 'error';
            $log['error'] = 'Unable to update Reset in database';
            $log['model_error'] = Json::encode($this->getFirstErrors());
            \Yii::error($log, 'application');
            throw new \Exception($log['error'], 1460388532);
        }
        $log['status'] = 'success';
        \Yii::warning($log, 'application');
    }

    /**
     * Check if user provided code is valid
     * @param string $userProvided code submitted by user
     * @return boolean
     * @throws \Exception
     * @throws \Sil\IdpPw\Common\PhoneVerification\NotMatchException
     */
    public function isUserProvidedCodeCorrect($userProvided)
    {
        if ($this->type == self::TYPE_METHOD && $this->method->type == Method::TYPE_PHONE) {
            return Verification::isPhoneCodeValid($this->code, $userProvided);
        } else {
            return Verification::isEmailCodeValid($this->code, $userProvided);
        }
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
}