<?php
namespace common\helpers;

use \DateTime;
use yii\base\Security;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\validators\EmailValidator;
use yii\web\BadRequestHttpException;
use yii\web\Request;
use yii\web\ServerErrorHttpException;

class Utils
{

    const DT_FORMAT = 'Y-m-d H:i:s';
    const FRIENDLY_DT_FORMAT = 'l F j, Y g:iA T';
    const DT_ISO8601 = 'Y-m-d\TH:i:s\Z';
    const UID_REGEX = '[a-zA-Z0-9_\-]{32}';

    /**
     * @param integer|string|null $time time as unix timestamp or mysql datetime. If omitted,
     *        the current time is used.
     * @return bool|DateTime
     * @throws \Exception
     */
    public static function convertToDateTime($time)
    {
        $time = $time ?? time();
        $time = is_int($time) ? $time : strtotime($time);
        if ($time === false) {
            throw new \Exception('Unable to parse date to timestamp', 1468865840);
        }
        return DateTime::createFromFormat('U', $time);
    }

    /**
     * @param integer|string|null $time time as unix timestamp or mysql datetime. If omitted,
     *        the current time is used.
     * @return string
     */
    public static function getDatetime($time = null)
    {
        return self::convertToDateTime($time)->format(self::DT_FORMAT);
    }

    /**
     * @param integer|string|null $time time as unix timestamp, mysql datetime, or null for now
     * @return string
     * @throws \Exception
     */
    public static function getIso8601($time = null)
    {
        return self::convertToDateTime($time)->format(self::DT_ISO8601);
    }

    /**
     * Return human readable date time
     * @param int|string|null $time Either a unix timestamp or a date in string format
     * @return string
     * @throws \Exception
     */
    public static function getFriendlyDate($time = null)
    {
        return self::convertToDateTime($time)->format(self::FRIENDLY_DT_FORMAT);
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
     * @param string $email an email address
     * @return string with most letters changed to asterisks
     * @throws BadRequestHttpException
     */
    public static function maskEmail($email)
    {
        $validator = new EmailValidator();
        if ( ! $validator->validate($email)) {
            \Yii::warning([
                'action' => 'mask email',
                'status' => 'error',
                'error' => 'Invalid email address provided: ' . Html::encode($email),
            ]);
            throw new BadRequestHttpException(\Yii::t('app', 'Invalid email address provided'), 1461459797);
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

        $config['idpName'] = $params['idpDisplayName'];
        $config['idpUsernameHint'] = $params['idpUsernameHint'];
        $config['recaptchaKey'] = $params['recaptcha']['siteKey'];
        $config['logoUrl'] = $params['logoUrl'];

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

            if ($params['password'][$rule]['enabled']) {
                $config['password'][$rule]['value'] = $params['password'][$rule]['value'];
                $config['password'][$rule]['pattern'] = $params['password'][$rule]['jsRegex'];
            }
        }

        $config['password']['zxcvbn'] = [
            'minScore' => $params['password']['zxcvbn']['minScore'],
        ];

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
        throw new BadRequestHttpException(\Yii::t('app', 'Unable to verify reCAPTCHA'), 1462904023);
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
            return $zxcvbn->getFull(['password' => $password])->toArray();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get client_id from request or session and then store in session
     * @return string
     * @throws \Exception
     */
    public static function getClientIdOrFail()
    {
        $request = \Yii::$app->request;
        if (\Yii::$app->request->isPut) {
            $clientId = $request->getBodyParam('client_id');
        } else {
            $clientId = $request->get('client_id');
        }

        if ($clientId === null) {
            $clientId = \Yii::$app->session->get('clientId');
            if ($clientId === null) {
                \Yii::warning([
                    'action' => 'login - get client id or fail',
                    'status' => 'error',
                    'request_method' => $request->getMethod(),
                    'request_url' => $request->getAbsoluteUrl(),
                    'body_params' => $request->getBodyParams(),
                    'user_agent' => $request->getUserAgent(),
                ]);
                throw new \Exception('Missing client_id');
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

    /**
     * Calculate expiration date based on
     * @param string $changeDate
     * @return string
     */
    public static function calculatePasswordExpirationDate($changeDate)
    {
        $passwordLifetime = \Yii::$app->params['passwordLifetime'];
        $dateInterval = new \DateInterval($passwordLifetime);
        $dateTime = new \DateTime($changeDate);
        $expireDate = $dateTime->add($dateInterval);

        return $expireDate->format(self::DT_FORMAT);
    }

    /**
     * Remove all non-numeric characters
     * @param string $value
     * @return string
     */
    public static function stripNonNumbers($value)
    {
        return preg_replace('/[^0-9]/', '', $value);
    }
}
