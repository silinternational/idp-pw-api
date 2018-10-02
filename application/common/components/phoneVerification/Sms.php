<?php
namespace common\components\phoneVerification;

use GuzzleHttp\Exception\RequestException;
use Nexmo\Sms as NexmoClient;

/**
 * Class SMS
 * @package common\components\phoneVerification
 * @link https://docs.nexmo.com/messaging/sms-api/api-reference Nexmo SMS API documentation
 */
class Sms extends Base implements PhoneVerificationInterface
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
            throw new \Exception('Code cannot be empty', 1463510967);
        }

        $client = $this->getClient();

        /*
         * Parameters for API call
         */
        $requestData = [
            'number' => $phoneNumber,
            'from' => $this->from,
            'text' => sprintf('Verification code: %s', $code),
            'to' => $phoneNumber,
        ];

        try {
            $results = $client->send($requestData);
            $results = $results['messages'][0];
            if ((string)$results['status'] == '0') {
                return $code;
            }

            throw new \Exception(
                sprintf('Error: [%s]  %s', $results['status'], $results['error-text']),
                1460146367
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = json_decode($response->getBody());
                throw new \Exception(
                    sprintf('Error: [%s] %s', $body['status'], $body['error-text']),
                    1460146928
                );
            }
            throw $e;
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
            throw new \Exception('Reset code and user provided code cannot be empty', 1463510988);
        }

        if ($resetCode === $userProvided) {
            return true;
        }

        throw new NotMatchException();
    }

    /**
     * @return \Nexmo\Sms
     * @throws \Exception
     */
    private function getClient()
    {
        if (empty($this->apiKey)) {
            throw new \Exception('API Key required for Nexmo', 1469715093);
        } elseif (empty($this->apiSecret)) {
            throw new \Exception('API Secret required for Nexmo', 1469715094);
        } elseif (empty($this->from)) {
            throw new \Exception('From is required for Nexmo', 1469715095);
        }

        return new NexmoClient([
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
        ]);
    }

}
