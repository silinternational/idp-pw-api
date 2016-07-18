<?php
namespace common\helpers;

use yii\base\Security;
use yii\helpers\Json;
use yii\validators\EmailValidator;
use yii\web\BadRequestHttpException;
use yii\web\Request;
use yii\web\ServerErrorHttpException;

class Utils
{

    const DT_FORMAT = 'Y-m-d H:i:s';
    const FRIENDLY_DT_FORMAT = 'l F j, Y g:iA T';
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
     * @param integer|string|null $timestamp time as unix timestamp, mysql datetime, or null for now
     * @return string
     * @throws \Exception
     */
    public static function getIso8601($timestamp = null)
    {
        $timestamp = $timestamp !== null ? $timestamp : time();
        $timestamp = is_int($timestamp) ? $timestamp : strtotime($timestamp);
        if ($timestamp === false) {
            throw new \Exception('Unable to parse date to timestamp', 1468865840);
        }
        return date('c', $timestamp);
    }

    /**
     * Return human readable date time
     * @param int|string $timestamp Either a unix timestamp or a date in string format
     * @return bool|string
     * @throws \Exception
     */
    public static function getFriendlyDate($timestamp = null)
    {
        $timestamp = $timestamp !== null ? $timestamp : time();
        $timestamp = is_int($timestamp) ? $timestamp : strtotime($timestamp);
        if ($timestamp === false) {
            throw new \Exception('Unable to parse date to timestamp', 1468865838);
        }
        return date(self::FRIENDLY_DT_FORMAT, $timestamp);
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
     * @codeCoverageIgnore
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
     * @param string $email an email address
     * @return string with most letters changed to asterisks
     * @throws \Exception
     */
    public static function maskEmail($email)
    {
        $validator = new EmailValidator();
        if ( ! $validator->validate($email)) {
            throw new \Exception('Invalid email address provided', 1461459797);
        }

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

        $config['idpName'] = $params['idpName'];
        $config['idpUsernameHint'] = $params['idpUsernameHint'];
        $config['recaptchaKey'] = $params['recaptcha']['siteKey'];

        $config['support'] = [];
        foreach ($params['support'] as $supportOption => $value) {
            if ( ! empty($value)) {
                $config['support'][$supportOption] = $value;
            }
        }

        $config['password'] = [];
        $passwordRuleFields = [
            'minLength', 'maxLength', 'minNum', 'minUpper', 'minSpecial'
        ];

        foreach ($passwordRuleFields as $rule) {
            if (empty($params['password'][$rule])) {
                throw new ServerErrorHttpException('Missing configuration for ' . $rule);
            }
            $config['password'][$rule]['value'] = $params['password'][$rule]['value'];
            $config['password'][$rule]['pattern'] = $params['password'][$rule]['jsRegex'];
            $config['password'][$rule]['enabled'] = $params['password'][$rule]['enabled'];
        }

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

    /**
     * Call reCaptcha API to verify response token
     * @param string $verificationToken
     * @param string $ipAddress
     * @return bool
     * @throws \Exception
     * @codeCoverageIgnore
     */
    public static function isRecaptchaResponseValid($verificationToken, $ipAddress)
    {
        $recaptcha = new \ReCaptcha\ReCaptcha(\Yii::$app->params['recaptcha']['secretKey']);
        $response = $recaptcha->verify($verificationToken, $ipAddress);

        if ($response->isSuccess()) {
            return true;
        }

        \Yii::error([
            'action' => __METHOD__,
            'status' => 'error',
            'error' => Json::encode($response->getErrorCodes()),
        ]);
        throw new BadRequestHttpException('Unable to verify recaptcha', 1462904023);
    }

    /**
     * Get Client IP address by looking through headers for proxied requests
     * @param Request $request
     * @return string
     * @codeCoverageIgnore
     */
    public static function getClientIp(Request $request)
    {
        $checkHeaders = [
            'X-Forwarded-For',
            'X-Forwarded',
            'X-Cluster-Client-Ip',
            'Client-Ip',
        ];

        $ipAddress = $request->userIP;

        $requestHeaders = $request->getHeaders();
        foreach ($checkHeaders as $header) {
            if ($requestHeaders->has($header)) {
                $ip = trim(current(explode(',', $requestHeaders->get($header))));
                if (self::isValidIpAddress($ip)) {
                    $ipAddress = $ip;
                    break;
                }
            }
        }

        return $ipAddress;
    }

    /**
     * Check that a given string is a valid IP address
     *
     * @param  string  $ip
     * @return boolean
     */
    public static function isValidIpAddress($ip)
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        return (filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false);
    }

    /**
     * Call Zxcvbn API and return full score object array
     * @param string $password
     * @return array
     * @throws \Exception
     * @codeCoverageIgnore
     */
    public static function getZxcvbnScore($password)
    {
        try {
            $zxcvbn = new \Zxcvbn\Score([
                'description_override' => [
                    'baseUrl' => \Yii::$app->params['password']['zxcvbn']['apiBaseUrl'],
                ]
            ]);
            return $zxcvbn->getFull(['password' => $password]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get client_id from request or session and then store in session
     * @return string
     * @throws BadRequestHttpException
     */
    public static function getClientIdOrFail()
    {
        if (\Yii::$app->request->isPut) {
            $clientId = \Yii::$app->request->getBodyParam('client_id');
        } else {
            $clientId = \Yii::$app->request->get('client_id');
        }

        if ($clientId === null) {
            $clientId = \Yii::$app->session->get('clientId');
            if ($clientId === null) {
                throw new BadRequestHttpException('Missing client_id');
            }
        }
        \Yii::$app->session->set('clientId', $clientId);

        return $clientId;
    }

    /**
     * Return HMAC SHA256 of access token
     * @param string $accessToken
     * @return string
     */
    public static function getAccessTokenHash($accessToken)
    {
        return hash_hmac('sha256', $accessToken, \Yii::$app->params['accessTokenHashKey']);
    }

}