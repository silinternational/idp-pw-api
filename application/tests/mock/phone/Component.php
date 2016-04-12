<?php
namespace tests\mock\phone;

use Sil\IdpPw\Common\PhoneVerification\PhoneVerificationInterface;
use Sil\IdpPw\Common\PhoneVerification\NotMatchException;
use yii\base\Component as YiiComponent;

class Component extends YiiComponent implements PhoneVerificationInterface
{

    public $codeLength;

    /**
     * Initiate phone verification, returns $code or newly generated code/identifier to be stored in Reset model
     * @param string $phoneNumber
     * @param string $code
     * @return string The verification code used, or another identifier to be used with self::verify() later
     * @throws \Exception
     */
    public function send($phoneNumber, $code)
    {
        // For testing, if code starts with zero, just return the code
        if (substr($code, 0, 1) == '0') {
            return $code;
        }

        // Look up code by phone number to support "generated" numbers too
        $data = include __DIR__ . '/data.php';
        foreach ($data as $phone) {
            if ($phone['number'] == $phoneNumber) {
                return $phone['code'];
            }
        }

        throw new \Exception('Not found', 1460486422);
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
     * @throws \Sil\IdpPw\Common\PhoneVerification\NotMatchException
     */
    public function verify($resetCode, $userProvided)
    {
        // If codes match, return true
        if ($resetCode == $userProvided) {
            return true;
        }

        // Look up code by id to simulate verifying with a service like Nexmo Verify
        $data = include __DIR__ . '/data.php';
        foreach ($data as $phone) {
            if($phone['id'] == $resetCode && $phone['code'] == $userProvided) {
                return true;
            }
        }

        throw new NotMatchException();
    }
}