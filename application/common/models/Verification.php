<?php
namespace common\models;

use Sil\EmailService\Client\EmailServiceClient;
use common\components\phoneVerification\NotMatchException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

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
     * @param string $expireTime
     * @param User $forUser
     * @param null|string $ccAddress
     * @param null|integer $eventLogUserId
     * @param null|string $eventLogTopic
     * @param null|string $eventLogDetails
     * @param array $additionalEmailParameters
     * @throws ServerErrorHttpException
     */
    public static function sendEmail(
        $toAddress,
        $subject,
        $view,
        $code,
        $expireTime,
        $forUser,
        $ccAddress = null,
        $eventLogUserId = null,
        $eventLogTopic = null,
        $eventLogDetails = null,
        $additionalEmailParameters = []
    ) {

        $parameters = ArrayHelper::merge(
            [
                'idpDisplayName' => \Yii::$app->params['idpDisplayName'],
                'name' => $forUser->first_name,
                'code' => $code,
                'expireTime' => $expireTime,
                'toAddress' => $toAddress,
                'helpCenterUrl' => \Yii::$app->params['helpCenterUrl'],
                'fromName' => \Yii::$app->params['fromName'],
            ],
            $additionalEmailParameters
        );


        $body = \Yii::$app->mailer->render(
            $view,
            $parameters
        );

        /*
         * If configured to use external email service send through that instead of using
         * local EmailQueue service
         */
        if (\Yii::$app->params['emailVerification']['useEmailService']) {

            $serviceConfig = \Yii::$app->params['emailVerification'];
            $requiredParams = ['baseUrl', 'accessToken', 'assertValidIp', 'validIpRanges'];

            foreach ($requiredParams as $param) {
                if ( ! isset($serviceConfig[$param])) {
                    throw new ServerErrorHttpException(
                        'Missing email service configuration for ' . $param,
                        1500916751
                    );
                }
            }

            $emailService = new EmailServiceClient(
                $serviceConfig['baseUrl'],
                $serviceConfig['accessToken'],
                [
                    EmailServiceClient::ASSERT_VALID_IP_CONFIG => $serviceConfig['assertValidIp'],
                    EmailServiceClient::TRUSTED_IPS_CONFIG => $serviceConfig['validIpRanges'],
                ]
            );

            $emailService->email([
                'to_address' => $toAddress,
                'cc_address' => $ccAddress,
                'subject' => $subject,
                'text_body' => $body,
                'html_body' => $body,
            ]);

            if ($eventLogTopic !== null && $eventLogDetails !== null && $eventLogUserId !== null) {
                EventLog::log($eventLogTopic, $eventLogDetails, $eventLogUserId);
            }
        } else {
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
        return strval($code) === strval($userProvided);
    }

    /**
     * Check if user submitted code matches the code sent to their phone
     * @param string $code
     * @param string $userProvided
     * @return boolean
     * @throws \Exception
     * @throws \common\components\phoneVerification\NotMatchException
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