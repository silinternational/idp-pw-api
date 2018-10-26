<?php
namespace tests\features\Context;

use Behat\Behat\Context\Context;
use Exception;
use PHPUnit\Framework\Assert;
use common\components\passwordStore\PasswordStoreInterface;
use common\components\passwordStore\UserPasswordMeta;
use tests\features\DummyUser;
use common\components\passwordStore\Google as GooglePasswordStore;
use Sil\PhpEnv\Env;

class GoogleContext implements Context
{
    /** @var Exception|null */
    protected $exceptionThrown = null;

    /** @var PasswordStoreInterface */
    protected $googlePasswordStore;

    /** @var UserPasswordMeta */
    protected $userPasswordMeta;

    public function __construct()
    {
        require_once __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
    }

    protected function generateSecurePassword()
    {
        return \base64_encode(\random_bytes(33));
    }

    /**
     * @Given I can make authenticated calls to Google
     */
    public function iCanMakeAuthenticatedCallsToGoogle()
    {
        $this->googlePasswordStore = new GooglePasswordStore(array_merge(
            [
                'userActiveRecordClass' => DummyUser::class,
            ],
            Env::getArrayFromPrefix('TEST_GOOGLE_PWSTORE_CONFIG_')
        ));
    }

    /**
     * @When I try to get a specific user's metadata
     */
    public function iTryToGetASpecificUsersMetadata()
    {
        try {
            $this->userPasswordMeta = $this->googlePasswordStore->getMeta(12345);
        } catch (Exception $e) {
            $this->exceptionThrown = $e;
        }
    }

    /**
     * @Then I should get back metadata about that user's password
     */
    public function iShouldGetBackMetadataAboutThatUsersPassword()
    {
        Assert::assertInstanceOf(UserPasswordMeta::class, $this->userPasswordMeta);
    }

    /**
     * @Then an exception should not have been thrown
     */
    public function anExceptionShouldNotHaveBeenThrown()
    {
        Assert::assertNull($this->exceptionThrown);
    }

    /**
     * @When I try to set a specific user's password
     */
    public function iTryToSetASpecificUsersPassword()
    {
        try {
            $newPassword = Env::get('TEST_GOOGLE_USER_NEW_PASSWORD');
            if (empty($newPassword)) {
                $newPassword = $this->generateSecurePassword();
            }
            $this->userPasswordMeta = $this->googlePasswordStore->set(
                12345,
                $newPassword
            );
        } catch (Exception $e) {
            $this->exceptionThrown = $e;
        }
    }
}
