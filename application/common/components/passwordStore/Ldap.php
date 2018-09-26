<?php
namespace common\components\passwordStore;

use Adldap\Adldap;
use Adldap\Connections\Provider;
use Adldap\Exceptions\Auth\BindException;
use Adldap\Schemas\OpenLDAP;
use yii\base\Component;

class Ldap extends Component implements PasswordStoreInterface
{
    /** @var string */
    public $baseDn;

    /** @var string */
    public $host;

    /** @var integer default=636 */
    public $port = 636;

    /** @var string */
    public $adminUsername;

    /** @var string */
    public $adminPassword;

    /** @var boolean default=false */
    public $useSsl = false;

    /** @var boolean default=true*/
    public $useTls = true;

    /** @var string */
    public $employeeIdAttribute;

    /** @var string */
    public $passwordLastChangeDateAttribute;

    /** @var string */
    public $passwordExpireDateAttribute;

    /** @var string */
    public $userPasswordAttribute;

    /** @var string */
    public $userAccountDisabledAttribute;

    /** @var string */
    public $userAccountDisabledValue;

    /**
     * If set, only update password if given attribute is present and value matches
     * @var array attributeName => value
     */
    public $updatePasswordIfAttributeAndValue;

    /**
     * Single dimension array of attribute names to be removed after password is changed.
     * This is helpful when certain flags may be set like lock status.
     * Example: ['pwdPolicySubentry']
     * @var array
     */
    public $removeAttributesOnSetPassword = [];

    /**
     * Associative array of attribute names and values to be set when password is changed.
     * This is helpful with certain flags need to be set after password is changed.
     * Example: ['pwdChangeEvent' => 'Yes']
     * @var array
     */
    public $updateAttributesOnSetPassword = [];

    /** @var \Adldap\Connections\Provider */
    public $ldapProvider;

    /** @var \Adldap\Adldap LDAP client*/
    public $ldapClient;

    /**
     * Connect and bind to ldap server
     * @throws \Adldap\Exceptions\Auth\BindException
     */
    public function connect()
    {
        if ($this->useSsl && $this->useTls) {
            // Prefer TLS over SSL
            $this->useSsl = false;
        }

        /*
         * Initialize provider with configuration
         */
        $schema = new OpenLDAP();
        $this->ldapProvider = new Provider([
            'base_dn' => $this->baseDn,
            'domain_controllers' => [$this->host],
            'port' => $this->port,
            'admin_username' => $this->adminUsername,
            'admin_password' => $this->adminPassword,
            'use_ssl' => $this->useSsl,
            'use_tls' => $this->useTls,
        ], null, $schema);

        $this->ldapClient = new Adldap();
        $this->ldapClient->addProvider('default', $this->ldapProvider);

        try {
            $this->ldapClient->connect('default');
            $this->ldapProvider->auth()->bindAsAdministrator();
        } catch (BindException $e) {
            throw $e;
        }
    }

    /**
     * @param string $employeeId
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throws \Exception
     * @throws \common\components\passwordStore\UserNotFoundException
     */
    public function getMeta($employeeId)
    {
        $user = $this->findUser($employeeId);

        /*
         * Make sure user is not disabled
         */
        $this->assertUserNotDisabled($user);

        /*
         * Get Password expires value
         */
        $pwdExpires = $user->getAttribute($this->passwordExpireDateAttribute);
        if (is_array($pwdExpires)) {
            $pwdExpires = $pwdExpires[0];
        }

        /*
         * Get password last changed value
         */
        $pwdChanged = $user->getAttribute($this->passwordLastChangeDateAttribute);
        if (is_array($pwdChanged)) {
            $pwdChanged = $pwdChanged[0];
        }

        return UserPasswordMeta::create(
            $pwdExpires,
            $pwdChanged
        );
    }

    /**
     * @param string $employeeId
     * @param string $password
     * @return \common\components\passwordStore\UserPasswordMeta
     * @throws \Exception
     */
    public function set($employeeId, $password)
    {
        $user = $this->findUser($employeeId);
        $logIdentifier = $user->hasAttribute('mail') ? $user->getAttribute('mail')[0] : $employeeId;

        /*
         * If $this->updatePasswordIfAttributeAndValue is defined and is an array,
         * check that user has given attribute and value matches. If not, return now
         * and consider change successful
         */
        if ( ! $this->matchesRequiredAttributes($user)) {
            try {
                \Yii::warning(
                    [
                        'action' => 'set password in ldap store',
                        'message' => sprintf(
                            "Skipping password update in LDAP for user %s because they do not match required attributes: %s",
                            $logIdentifier,
                            json_encode($this->updatePasswordIfAttributeAndValue)
                        ),
                    ]
                );
            } catch (\Throwable $e) {
                // ignoring
            }
            return $this->getMeta($employeeId);
        }

        /*
         * Make sure user is not disabled
         */
        $this->assertUserNotDisabled($user);


        /*
         * Update password
         */
        try{
            $user->updateAttribute($this->userPasswordAttribute, $password);
        } catch (\Exception $e) {
            /*
             * Check if failure is due to constraint violation
             */
            $error = strtolower($e->getMessage());
            if (substr_count($error, 'constraint violation') > 0) {
                throw new PasswordReuseException(
                    'Unable to change password. If this password has been used before please use something different.',
                    1464018255,
                    $e
                );
            }
        }

        /*
         * Reload user after password change
         */
        $user = $this->findUser($employeeId);

        /*
         * Remove any attributes that should be removed after changing password
         */
        foreach ($this->removeAttributesOnSetPassword as $removeAttr) {
            if($user->hasAttribute($removeAttr) || $user->hasAttribute(strtolower($removeAttr))) {
                $user->deleteAttribute($removeAttr);
            }
        }

        /*
         * Update flag attributes after changing password
         */
        foreach ($this->updateAttributesOnSetPassword as $key => $value) {
            if ($user->hasAttribute($key) || $user->hasAttribute(strtolower($key))) {
                $user->updateAttribute($key, $value);
            } else {
                $user->createAttribute($key, $value);
            }
        }

        /*
         * Save changes
         */
        try {
            if ( ! $user->save()) {
                throw new \Exception('Unable to change password.', 1464018238);
            }
        } catch (\Exception $e) {
            /*
             * throw generic failure exception
             */
            throw new \Exception('Unable to change password, server error.', 1464018242, $e);
        }

        try {
            \Yii::warning([
                'action' => 'set password in ldap store',
                'status' => 'success',
                'user' => $logIdentifier,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        return $this->getMeta($employeeId);
    }

    public function matchesRequiredAttributes($user)
    {
        // If not defined, just return true to continue processing as normal
        if ( ! is_array($this->updatePasswordIfAttributeAndValue)) {
            return true;
        }

        // Ensure each attribute is present and value matches, else return false
        foreach ($this->updatePasswordIfAttributeAndValue as $key => $value) {
            if ($user->hasAttribute($key)) {
                $userValue = $user->getAttribute($key);
                if (is_array($userValue)) {
                    $userValue = $userValue[0] ?? null;
                }
                if (strtolower($value) !== strtolower($userValue)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Adldap\Models\Entry $user
     * @throws AccountLockedException
     */
    public function assertUserNotDisabled($user)
    {
        if ($user->hasAttribute($this->userAccountDisabledAttribute)) {
            $value = $user->getAttribute($this->userAccountDisabledAttribute);
            if (is_array($value)) {
                $value = $value[0];
            }
            if (strtolower($value) === strtolower($this->userAccountDisabledValue)) {
                throw new AccountLockedException('User account is disabled', 1472740480);
            }
        }
    }

    /**
     * conditionally build ldap search criteria
     * @return array
     */
    public function getSearchCriteria()
    {
        $criteria = [
            $this->passwordExpireDateAttribute,
            $this->passwordLastChangeDateAttribute,
            'mail',
        ];
        if ( ! empty($this->userAccountDisabledAttribute)) {
            $criteria[] = $this->userAccountDisabledAttribute;
        }
        if ( is_array($this->updateAttributesOnSetPassword)) {
            $criteria = array_merge(
                $criteria,
                array_keys($this->updateAttributesOnSetPassword)
            );
        }
        if ( is_array($this->removeAttributesOnSetPassword)) {
            $criteria = array_merge($criteria, $this->removeAttributesOnSetPassword);
        }
        if ( is_array($this->updatePasswordIfAttributeAndValue)) {
            foreach ($this->updatePasswordIfAttributeAndValue as $key => $value) {
                $criteria[] = $key;
            }
        }

        return $criteria;
    }

    /**
     * @param string $employeeId
     * @return \Adldap\Models\Entry
     * @throws UserNotFoundException
     */
    public function findUser($employeeId)
    {
        $this->connect();
        $criteria = $this->getSearchCriteria();

        try {
            /** @var \Adldap\Models\Entry $user */
            $user = $this->ldapProvider->search()
                ->select($criteria)
                ->findByOrFail($this->employeeIdAttribute, $employeeId);
        } catch (\Exception $e) {
            throw new UserNotFoundException('User not found', 1463493653, $e);
        }

        return $user;
    }

}