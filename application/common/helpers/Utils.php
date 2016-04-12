<?php
namespace common\helpers;

use yii\base\Security;
use yii\web\ServerErrorHttpException;

class Utils
{

    const DT_FORMAT = 'Y-m-d H:i:s';
    const UID_REGEX = '[a-zA-Z0-9_\-]{32}';

    /**
     * @param integer|null $timestamp
     * @return string
     */
    public static function getDatetime($timestamp = null)
    {
        $timestamp = $timestamp ?: time();

        return date(self::DT_FORMAT, $timestamp);
    }

    /**
     * @param integer|null $timestamp
     * @return string
     */
    public static function getIso8601($timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        return date('c', strtotime($timestamp));
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 32)
    {
        $security = new Security();
        return $security->generateRandomString($length);
    }

    /**
     * Utility function to extract attribute values from SAML attributes and
     * return as a simple array
     * @param $attributes array the SAML attributes returned
     * @param $map array configuration map of attribute names with field and element values
     * @return array
     */
    public static function extractSamlAttributes($attributes, $map)
    {
        $attrs = [];

        foreach ($map as $attr => $details) {
            if (isset($details['element'])) {
                if (isset($attributes[$details['field']][$details['element']])) {
                    $attrs[$attr] = $attributes[$details['field']][$details['element']];
                }
            } else {
                if (isset($attributes[$details['field']])) {
                    $attrs[$attr] = $attributes[$details['field']];
                }
            }
        }

        return $attrs;
    }

    /**
     * Check if given array of $attributes includes all keys from $map
     * @param array $attributes
     * @param array $map
     * @throws \Exception
     */
    public static function assertHasRequiredSamlAttributes($attributes, $map)
    {
        foreach ($map as $key => $value) {
            if ( ! array_key_exists($key, $attributes)) {
                throw new \Exception(sprintf('SAML attributes missing attribute: %s', $key), 1454436522);
            }
        }
    }

    /**
     * @param array $array
     * @param string $key
     * @return bool
     */
    public static function isArrayEntryTruthy($array, $key)
    {
        return (is_array($array) && isset($array[$key]) && $array[$key]);
    }

    /**
     * Check if user is logged in and if so return the identity model
     * @return null|\common\models\User
     */
    public static function getCurrentUser()
    {
        if (\Yii::$app->user && ! \Yii::$app->user->isGuest) {
            return \Yii::$app->user->identity;
        }
        return null;
    }

    /**
     * @param string $phone
     * @return string
     */
    public static function maskPhone($phone)
    {
        /*
         * $phone may be formatted with country code followed by a comma followed by the rest of the phone number
         * Example: 1,4085551212 or 77,8588923456
         */
        if (substr_count($phone, ',') > 0) {
            list($countryCode, $number) = explode(',', $phone);
        } else {
            $countryCode = null;
            $number = $phone;
        }

        $string = '';

        /*
         * If country code is present, prepend string with + followed by country code
         */
        if ( ! is_null($countryCode)) {
            $string .= '+' . $countryCode . ' ';
        }

        $string .= self::maskString($number);

        return $string;
    }

    /**
     * @param string $email an email address (it doesn't verify it)
     * @return string with most letters changed to asterisks
     */
    public static function maskEmail($email)
    {
        list($part1, $domain) = explode('@', $email);
        $newEmail = '';
        $useRealChar = true;

        /*
         * Replace all characters with '*', except
         * the first one, the last one, underscores and each
         * character that follows and underscore.
         */
        foreach (str_split($part1) as $nextChar) {
            if ($useRealChar) {
                $newEmail .= $nextChar;
                $useRealChar = false;
            } else if ($nextChar === '_') {
                $newEmail .= $nextChar;
                $useRealChar = true;
            } else {
                $newEmail .= '*';
            }
        }

        // replace the last * with the last real character
        $newEmail = substr($newEmail, 0, -1);
        $newEmail .= substr($part1, -1);
        $newEmail .= '@';

        /*
         * Add an '*' for each of the characters of the domain, except
         * for the first character of each part and the .
         */
        list($domainA, $domainB) = explode('.', $domain);

        $newEmail .= substr($domainA, 0, 1);
        $newEmail .= str_repeat('*', strlen($domainA) - 1);
        $newEmail .= '.';

        $newEmail .= substr($domainB, 0, 1);
        $newEmail .= str_repeat('*', strlen($domainB) - 1);
        return $newEmail;
    }

    /**
     * Replaces all the characters with asterisks, except for the last X characters.
     *
     * @param string $inString
     * @param string $maskChar [optional, default #]
     * @param int $goodCount [optional, default 3]
     * @return string
     */
    public static function maskString($inString, $maskChar = '#', $goodCount = 3)
    {
        $newString = str_repeat($maskChar, strlen($inString) - $goodCount);
        $newString .= substr($inString, - $goodCount);
        return $newString;
    }

    /**
     * @return array
     * @throws ServerErrorHttpException
     */
    public static function getFrontendConfig()
    {
        $params = \Yii::$app->params;

        $config = [];

        $config['gaTrackingId'] = $params['gaTrackingId'];
        $config['support'] = $params['support'];
        $config['recaptchaKey'] = $params['recaptcha']['siteKey'];
        $config['password'] = [];

        $passwordRuleFields = [
            'minLength', 'maxLength', 'minNum', 'minUpper', 'minSpecial'
        ];

        foreach ($passwordRuleFields as $rule) {
            if (empty($params['password'][$rule])) {
                throw new ServerErrorHttpException('Missing configuration for ' . $rule);
            }
            $config['password'][$rule]['value'] = $params['password'][$rule]['value'];
            $config['password'][$rule]['regex'] = $params['password'][$rule]['jsRegex'];
        }

        $config['password']['blacklist'] = $params['password']['blacklist'];
        $config['password']['zxcvbn'] = $params['password']['zxcvbn'];

        return $config;
    }

    /**
     * Check if user session is available
     * @return boolean
     */
    public static function isSessionAvailable()
    {
        try {
            $sessionAvailable = ! \Yii::$app->user->isGuest;
        } catch (\Exception $e) {
            $sessionAvailable = false;
        }

        return $sessionAvailable;
    }

    /**
     * Return a random string of numbers
     * @param int $length [default=4]
     * @return string
     * @throws \Exception
     */
    public static function getRandomDigits($length = 4)
    {
        $result = '';
        while (strlen($result) < $length) {
            $randomString = openssl_random_pseudo_bytes(16, $cryptoStrong);
            if ($cryptoStrong !== true) {
                throw new \Exception('Unable to generate cryptographically strong number', 1460385230);
            } else if ( ! $randomString) {
                throw new \Exception('Unable to generate random number', 1460385231);
            }

            $hex = bin2hex($randomString);
            $digits = preg_replace('/[^0-9]/', '', $hex);
            $result .= $digits;
        }

        $randomDigits = substr($result, 0, $length);

        return $randomDigits;
    }

}