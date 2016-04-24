<?php
namespace common\models;

use common\models\User;
use Sil\IdpPw\Common\PhoneVerification\NotMatchException;
use yii\base\Model;

class Verification extends Model
{
    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';

    /**
     * Send email for verification
     * @param string $toAddress
     * @param string $subject
     * @param string $view Full view path with alias, ex: @common/mail/reset/self
     * @param string $code
     * @param User $forUser
     * @param null|string $ccAddress
     * @param null|integer $eventLogUserId
     * @param null|string $eventLogTopic
     * @param null|string $eventLogDetails
     */
    public static function sendEmail(
        $toAddress,
        $subject,
        $view,
        $code,
        $forUser,
        $ccAddress = null,
        $eventLogUserId = null,
        $eventLogTopic = null,
        $eventLogDetails = null
    ) {
        $body = \Yii::$app->mailer->render(
            $view,
            [
                'idpName' => \Yii::$app->params['idpName'],
                'name' => $forUser->first_name,
                'code' => $code,
                'toAddress' => $toAddress,
            ]
        );

        EmailQueue::sendOrQueue(
            $toAddress,
            $subject,
            $body,
            $body,
            $ccAddress,
            $eventLogUserId,
            $eventLogTopic,
            $eventLogDetails
        );
    }

    /**
     * Send code to phone for verification
     * @param string $phoneNumber
     * @param string $code
     * @param null|integer $eventLogUserId
     * @param null|string $eventLogTopic
     * @param null|string $eventLogDetails
     * @return string
     * @throws \Exception
     */
    public static function sendPhone(
        $phoneNumber,
        $code,
        $eventLogUserId = null,
        $eventLogTopic = null,
        $eventLogDetails = null
    ) {
        $result = \Yii::$app->phone->send($phoneNumber, $code);

        if ($eventLogUserId !== null && $eventLogTopic !== null && $eventLogDetails !== null) {
            EventLog::log($eventLogTopic, $eventLogDetails, $eventLogUserId);
        }

        return $result;
    }

    /**
     * Check if user submitted code matches the code emailed to them
     * @param string $code
     * @param string $userProvided
     * @return bool
     */
    public static function isEmailCodeValid($code, $userProvided)
    {
        return $code === $userProvided;
    }

    /**
     * Check if user submitted code matches the code sent to their phone
     * @param string $code
     * @param string $userProvided
     * @return boolean
     * @throws \Exception
     * @throws \Sil\IdpPw\Common\PhoneVerification\NotMatchException
     */
    public static function isPhoneCodeValid($code, $userProvided)
    {
        try {
            return \Yii::$app->phone->verify($code, $userProvided);
        } catch (NotMatchException $e) {
            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}