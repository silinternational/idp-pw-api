<?php
namespace common\components\phoneVerification;

use Nexmo\Verify as NexmoClient;

/**
 * Class Verify
 * @package common\components\phoneVerification
 * @link https://docs.nexmo.com/api-ref/verify Nexmo Verify API documentation
 */
class Verify extends Base implements PhoneVerificationInterface
{
    /**
     * Initiate phone verification
     * @param string $phoneNumber The mobile or landline phone number to verify. Unless you are setting country
     *                            explicitly, this number must be in E.164 format. For example, 4478342080934.
     * @param string $code This is ignored in Nexmo Verify implementation
     * @return string The verification code used, or another identifier to be used with self::verify() later
     * @throws \Exception
     */
    public function send($phoneNumber, $code)
    {
        if (empty($code)) {
            throw new \Exception('Code cannot be empty', 1463510951);
        }

        $client = $this->getClient();

        /*
         * Parameters for API call
         */
        $requestData = [
            'number' => $phoneNumber,
            'brand' => $this->brand,
        ];

        /*
         * Only add optional parameters if we have a value for them
         */
        if ( ! is_null($this->codeLength)){
            $requestData['code_length'] = $this->codeLength;
        }
        if ( ! is_null($this->country)) {
            $requestData['country'] = $this->country;
        }
        if ( ! is_null($this->language)) {
            $requestData['lg'] = $this->language;
        }
        if ( ! is_null($this->pinExpiry)) {
            $requestData['pin_expiry'] = $this->pinExpiry;
        }
        if ( ! is_null($this->nextEventWait)) {
            $requestData['next_event_wait'] = $this->nextEventWait;
        }
        if ( ! is_null($this->senderId)) {
            $requestData['sender_id'] = $this->senderId;
        }

        try {
            $results = $client->verify($requestData);
            if ((string)$results['status'] === '0') {
                if (isset($results['request_id']) && ! empty($results['request_id'])) {
                    return $results['request_id'];
                }
            }

            throw new NexmoException($results['error_text'], $results['status']);
        } catch (\Exception $e) {
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
     * @throws \Sil\IdpPw\Common\PhoneVerification\NotMatchException
     */
    public function verify($resetCode, $userProvided)
    {
        if (empty($resetCode) || empty($userProvided)) {
            throw new \Exception('Reset code and user provided code cannot be empty', 1463510955);
        }

        $client = $this->getClient();

        try {
            $results = $client->check([
                'request_id' => $resetCode,
                'code' => $userProvided,
            ]);

            $resultCode = (string)$results['status'];

            /*
             * Check success
             */
            if ($resultCode == '0') {
                return true;
            }

            /*
             * Check for invalid code and throw NotMatchException
             * 16 - The code inserted does not match the expected value
             * 17 - A wrong code was provided too many times
             */
            if (in_array($resultCode, ['16', '17'])) {
                throw new NotMatchException();
            }

            /*
             * Throw NexmoException
             */
            throw new NexmoException($results['error_text'], $results['status']);

        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * @return \Nexmo\Verify
     * @throws \Exception
     */
    private function getClient()
    {
        if (empty($this->apiKey)) {
            throw new \Exception('API Key required for Nexmo', 1469715138);
        } elseif (empty($this->apiSecret)) {
            throw new \Exception('API Secret required for Nexmo', 1469715139);
        } elseif (empty($this->brand)) {
            throw new \Exception('Brand required for Nexmo', 1469715140);
        }

        return new NexmoClient([
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
        ]);
    }
}
