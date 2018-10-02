<?php
namespace common\components\phoneVerification;

/**
 * Interface PhoneVerificationInterface
 * @package common\components\phoneVerification
 */
interface PhoneVerificationInterface
{
    /**
     * Initiate phone verification, returns $code or newly generated code/identifier to be stored in Reset model
     * @param string $phoneNumber
     * @param string $code
     * @return string The verification code used, or another identifier to be used with self::verify() later
     * @throws \Exception
     */
    public function send($phoneNumber, $code);

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
    public function verify($resetCode, $userProvided);

    /**
     * Apply any special formatting to phone number. May use service provider like Nexmo Insights API to
     * get proper international formatting. This is used for more friendly display to users.
     * @param string $phoneNumber
     * @return string
     */
    public function format($phoneNumber);
}