<?php
namespace tests\features;

use Exception;
use common\components\passwordStore\PasswordStoreInterface;
use common\components\passwordStore\UserPasswordMeta;
use yii\base\Component;

class DummyPasswordStore extends Component implements PasswordStoreInterface
{
    /** @var string */
    public $uniqueDate;

    /**
     * Whether this dummy password store should pretend to be online.
     * @var boolean
     */
    public $isOnline = true;

    /**
     * Whether this dummy password store will fail (throw an exception) when
     * it tries to set a password.
     * @var boolean
     */
    public $willFailToSetPassword = false;

    /**
     * {@inheritdoc}
     */
    public function getMeta($employeeId): UserPasswordMeta
    {
        if ( ! $this->isOnline) {
            throw new Exception('Failed to get metadata for ' . $employeeId);
        }
        return UserPasswordMeta::create($this->uniqueDate, $this->uniqueDate);
    }

    /**
     * {@inheritdoc}
     */
    public function set($employeeId, $password): UserPasswordMeta
    {
        if ($this->willFailToSetPassword || !$this->isOnline) {
            throw new Exception('Failed to set password for ' . $employeeId);
        }
        return UserPasswordMeta::create($this->uniqueDate, $this->uniqueDate);
    }
}
