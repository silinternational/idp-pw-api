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
        /*
         * Check for given number that should trigger exception
         */
        if ($phoneNumber == '14044044044') {
            throw new \Exception();
        }

        // Look up code by phone number to support "generated" numbers
        $data = include __DIR__ . '/data.php';
        foreach ($data as $phone) {
            if ($phone['number'] == $phoneNumber) {
                return $phone['code'];
            }
        }

        return $code;
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
        // Look up code by id to simulate verifying with a service like Nexmo Verify
        $data = include __DIR__ . '/data.php';
        foreach ($data as $phone) {
            if ($phone['id'] == $resetCode && $phone['code'] == $userProvided) {
                return true;
            }
        }

        // Check if codes match and return true
        if ($resetCode == $userProvided) {
            return true;
        }

        throw new NotMatchException();
    }

    public function format($phoneNumber)
    {
        return $phoneNumber;
    }
}