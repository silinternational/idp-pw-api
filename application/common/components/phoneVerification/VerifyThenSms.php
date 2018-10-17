<?php
namespace common\components\phoneVerification;

use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class Verify
 * @package common\components\phoneVerification
 * @link https://docs.nexmo.com/api-ref/verify Nexmo Verify API documentation
 */
class VerifyThenSms extends Base implements PhoneVerificationInterface
{

    /**
     * Attempt to use Verify first, but if network not supported or fails, attempt SMS
     * @param string $phoneNumber The mobile or landline phone number to verify. Unless you are setting country
     *                            explicitly, this number must be in E.164 format. For example, 4478342080934.
     * @param string $code This is ignored in Nexmo Verify implementation
     * @return string The verification code used, or another identifier to be used with self::verify() later
     * @throws \Exception
     */
    public function send($phoneNumber, $code)
    {
        if (empty($code)) {
            throw new \Exception('Code cannot be empty', 1469712310);
        } elseif (empty($this->apiKey)) {
            throw new \Exception('API Key required for Nexmo', 1469712301);
        } elseif (empty($this->apiSecret)) {
            throw new \Exception('API Secret required for Nexmo', 1469712311);
        } elseif (empty($this->brand)) {
            throw new \Exception('Brand required for Nexmo', 1469712312);
        } elseif (empty($this->from)) {
            throw new \Exception('From is required for Nexmo', 1469712313);
        }

        $verify = $this->getVerifyClient();

        try {
            /*
             * Returns the Verify ID if successful
             */
            return $verify->send($phoneNumber, $code);
        } catch (\Exception $e) {

            if ($e instanceof NexmoException) {
                /*
                 * If concurrent request, throw exception letting users know to check phone
                 */
                if (strval($e->getCode()) === '10') {
                    throw new BadRequestHttpException(
                        \Yii::t('app', 'Verification currently in progress, please check your phone.'),
                        1470317050
                    );
                }
                
                /*
                 * If Nexmo complains about the format of the number, log it so
                 * we can see what the format is.
                 */
                if (strval($e->getCode()) === '3') {
                    \Yii::error([
                        'action' => 'phone verification',
                        'number' => $phoneNumber,
                        'type' => 'verify',
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                    throw new BadRequestHttpException(
                        \Yii::t(
                            'app',
                            'We had trouble understanding that phone number. '
                            . 'Would you mind retyping it, perhaps using only numbers?'
                        ),
                        1513088476
                    );
                }
            }

            /*
             * Don't log error if Nexmo code in list:
             * 15 - The destination number is not in a supported network
             * 16 - The code inserted does not match the expected value
             * 17 - A wrong code was provided too many times
             */
            if ( ! in_array(strval($e->getCode()), ['15', '16', '17'])) {
                \Yii::error([
                    'action' => 'phone verification',
                    'type' => 'verify',
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
            }
        }

        /*
         * Don't send Verify ID as verification code
         */
        if (strlen($code) !== $this->codeLength) {
            \Yii::error([
                'action' => 'verify phone',
                'status' => 'error',
                'error' => 'Call to Nexmo Verify failed, $code is a Verify ID so not falling back to SMS',
                'code' => $code,
            ]);
            throw new ServerErrorHttpException(
                'There was an internal problem verifying your number. Please wait a minute and try again.',
                1470317786
            );
        }

        $sms = $this->getSmsClient();
        try {
            return $sms->send($phoneNumber, $code);
        } catch (\Exception $e) {
            /*
             * SMS also failed, just throw the exception back
             */
            throw $e;
        }
    }

    /**
     * Verify that previously stored $resetCode matches the code provided by the user, $userProvided
     * Component may use $resetCode as a key for it's own purposes, as is the case with the Nexmo Verify service.
     * Return true on success or throw NotMatchException when match fails.
     * Throw \Exception when other exception occurs like network issue with service provider
     * @param string $resetCode
     * @param string $userProvided
     * @return boolean
     * @throws \Exception
     * @throws \common\components\phoneVerification\NotMatchException
     */
    public function verify($resetCode, $userProvided)
    {
        if (empty($resetCode) || empty($userProvided)) {
            throw new \Exception('Reset code and user provided code cannot be empty', 1469713857);
        }

        /*
         * Verify codes are long IDs for the Verify service, so if $resetCode is same length as
         * $this->codeLength then SMS was used to send code
         */
        if (strlen($resetCode) === $this->codeLength) {
            $sms = $this->getSmsClient();
            return $sms->verify($resetCode, $userProvided);
        }

        $verify = $this->getVerifyClient();

        return $verify->verify($resetCode, $userProvided);
    }

    /**
     * @return Verify
     */
    private function getVerifyClient()
    {
        $verify = new Verify();
        $verify->apiKey = $this->apiKey;
        $verify->apiSecret = $this->apiSecret;
        $verify->brand = $this->brand;
        $verify->codeLength = $this->codeLength;
        $verify->country = $this->country;
        $verify->language = $this->language;
        $verify->pinExpiry = $this->pinExpiry;
        $verify->nextEventWait = $this->nextEventWait;
        $verify->senderId = $this->senderId;

        return $verify;
    }

    /**
     * @return Sms
     */
    private function getSmsClient()
    {
        $sms = new Sms();
        $sms->apiKey = $this->apiKey;
        $sms->apiSecret = $this->apiSecret;
        $sms->from = $this->from;

        return $sms;
    }

}
