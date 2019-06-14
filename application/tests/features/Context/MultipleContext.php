<?php

namespace tests\features\Context;

use Behat\Behat\Context\Context;
use Exception;
use PHPUnit\Framework\Assert;
use common\components\passwordStore\PasswordStoreInterface;
use common\components\passwordStore\UserPasswordMeta;
use tests\features\DummyPasswordStore;
use common\components\passwordStore\Multiple;

class MultipleContext implements Context
{
    /** @var Exception|null */
    protected $exceptionThrown = null;

    /** @var PasswordStoreInterface */
    protected $multiPasswordStore;

    /** @var array */
    protected $passwordStoresConfig;

    /** @var UserPasswordMeta */
    protected $response;

    public function __construct()
    {
        require_once __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
    }

    /**
     * @Given :desiredNumber password stores are configured
     */
    public function passwordStoresAreConfigured($desiredNumber)
    {
        $this->passwordStoresConfig = [];

        for ($pwStoreNumber = 1; $pwStoreNumber <= $desiredNumber; $pwStoreNumber++) {
            $this->passwordStoresConfig[] = [
                'class' => DummyPasswordStore::class,
                'uniqueDate' => gmdate(DATE_ISO8601, ($pwStoreNumber * 86400)),
                'displayName' => 'PasswordStore ' . $pwStoreNumber,
            ];
        }
    }

    /**
     * @When I get metadata about a user
     */
    public function iGetMetadataAboutAUser()
    {
        try {
            $this->multiPasswordStore = new Multiple([
                'passwordStoresConfig' => $this->passwordStoresConfig,
            ]);

            $this->response = $this->multiPasswordStore->getMeta(12345);
        } catch (Exception $e) {
            $this->exceptionThrown = $e;
        }
    }

    /**
     * @Then I should receive the response from the first password store
     */
    public function iShouldReceiveTheResponseFromTheFirstPasswordStore()
    {
        Assert::assertInstanceOf(UserPasswordMeta::class, $this->response);
        $isFirst = true;
        foreach ($this->passwordStoresConfig as $passwordStoreConfig) {
            if ($isFirst) {
                Assert::assertSame(
                    $passwordStoreConfig['uniqueDate'],
                    $this->response->passwordExpireDate,
                    'The response did not seem to come from the first password store.'
                );
                $isFirst = false;
            } else {
                Assert::assertNotEquals(
                    $passwordStoreConfig['uniqueDate'],
                    $this->response->passwordExpireDate,
                    'The response seemed to come from the wrong password store.'
                );
            }
        }
    }

    /**
     * @When I set a user's password
     */
    public function iSetAUsersPassword()
    {
        try {
            $this->multiPasswordStore = new Multiple([
                'passwordStoresConfig' => $this->passwordStoresConfig,
            ]);

            $this->response = $this->multiPasswordStore->set(
                12345,
                \base64_encode(\random_bytes(33)) // Random password
            );
        } catch (Exception $e) {
            $this->exceptionThrown = $e;
        }
    }

    /**
     * @Then an exception should NOT have been thrown
     */
    public function anExceptionShouldNotHaveBeenThrown()
    {
        Assert::assertNull($this->exceptionThrown);
    }

    /**
     * @Given password store :pwStoreNumber will fail when I try to set a user's password
     */
    public function passwordStoreWillFailWhenITryToSetAUsersPassword($pwStoreNumber)
    {
        $this->passwordStoresConfig[$pwStoreNumber]['willFailToSetPassword'] = true;
    }

    /**
     * @Then an exception SHOULD have been thrown
     */
    public function anExceptionShouldHaveBeenThrown()
    {
        Assert::assertNotNull($this->exceptionThrown);
    }

    /**
     * @Then the exception should indicate which password store failed
     */
    public function theExceptionShouldIndicateWhichPasswordStoreFailed()
    {
        $errorMessage = $this->exceptionThrown->getMessage();
        $foundClassName = false;
        foreach ($this->passwordStoresConfig as $passwordStoreConfig) {
            if (strpos($errorMessage, $passwordStoreConfig['displayName']) !== false) {
                $foundClassName = true;
                break;
            }
        }
        Assert::assertTrue($foundClassName, sprintf(
            'Failed to see any indicator of which password store failed '
            . 'in this error message: %s',
            $errorMessage
        ));
    }

    /**
     * @Given password store :pwStoreNumber will fail our status precheck
     */
    public function passwordStoreWillFailOurStatusPrecheck($pwStoreNumber)
    {
        $this->passwordStoresConfig[$pwStoreNumber]['isOnline'] = false;
    }
}
