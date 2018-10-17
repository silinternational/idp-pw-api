<?php
namespace common\components\phoneVerification;

use Nexmo\Insight;
use yii\base\Component;

class Base extends Component
{
    /*
     * Define all configuration options for both SMS and Verify
     */

    /**
     * Required for Verify and SMS
     * @var string
     */
    public $apiKey;

    /**
     * Required for Verify and SMS
     * @var string
     */
    public $apiSecret;

    /**
     * Required - The Nexmo phone number to send SMS from
     * Required for SMS
     * @var string
     */
    public $from;

    /**
     * Required - The name of the company or App you are using Verify for. This 18 character alphanumeric
     * string is used in the body of Verify message. For example: "Your brand PIN is ..".
     * Required for Verify
     * @var string
     */
    public $brand;

    /**
     * Optional - The length of the PIN. Possible values are 6 or 4 characters. The default value is 4.
     * Optional for both Verify and SMS
     * @var int [default=4]
     */
    public $codeLength = 4;

    /**
     * Optional - If do not set number in international format or you are not sure if number is correctly
     * formatted, set country with the two-character country code. For example, GB, US. Verify
     * works out the international phone number for you.
     * Optional for Verify
     * @var string
     * @link https://docs.nexmo.com/api-ref/voice-api/supported-languages
     */
    public $country;

    /**
     * Optional - By default, TTS are generated in the locale that matches number. For example,
     * the TTS for a 33* number is sent in French. Use this parameter to explicitly control the
     * language, accent and gender used for the Verify request. The default language is en-us.
     * Optional for Verify
     * @var string
     */
    public $language;

    /**
     * Optional - The PIN validity time from generation. This is an integer value between 30 and 3600
     * seconds. The default is 300 seconds. When specified together, pin_expiry must be an integer
     * multiple of next_event_wait. Otherwise, pin_expiry is set to next_event_wait.
     * Optional for Verify
     * @var int
     */
    public $pinExpiry;

    /**
     * Optional - An integer value between 60 and 900 seconds inclusive that specifies the wait
     * time between attempts to deliver the PIN. Verify calculates the default value based on the
     * average time taken by users to complete verification.
     * Optional for Verify
     * @var int
     */
    public $nextEventWait;

    /**
     * Optional - An 11 character alphanumeric string to specify the SenderID for SMS sent by Verify.
     * Depending on the destination of the phone number you are applying, restrictions may apply.
     * By default, sender_id is VERIFY.
     * Optional for Verify
     * @var string
     */
    public $senderId;

    /**
     * Use Nexmo Insights API to get national format version of phone number
     * @param string $phoneNumber
     * @return string
     * @throws \Exception
     */
    public function format($phoneNumber)
    {
        $client = new Insight([
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
        ]);

        try {
            $insights = $client->basic([
                'number' => $phoneNumber,
            ]);

            if ($insights['status'] === 0) {
                return sprintf('%s %s', $insights['country_prefix'], $insights['national_format_number']);
            } else {
                throw new \Exception(
                    \Yii::t(
                        'app',
                        'Unable to verify phone number for formatting, please check the number and try again. ' .
                            'Error code: {code}',
                        ['code' => $insights['status']]
                    ),
                    1469727752
                );
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}