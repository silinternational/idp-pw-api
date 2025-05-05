<?php

namespace common\components\passwordStore;

use LdapRecord\Auth\BindException;
use LdapRecord\Connection;
use LdapRecord\Models\OpenLDAP\Entry as LDAP_Entry;
use yii\base\Component;

class Ldap extends Component implements PasswordStoreInterface
{
    /** @var string */
    public $baseDn;

    /** @var string|string[] */
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

    /** @var \LdapRecord\Connection|null */
    public ?Connection $ldapClient = null;

    public $displayName = 'LDAP';

    /**
     * Connect and bind to ldap server
     * @throws \Exception
     */
    public function connect()
    {
        // Connection has already been established
        if ($this->ldapClient !== null) {
            return;
        }

        // Prefer TLS over SSL
        if ($this->useSsl && $this->useTls) {
            $this->useSsl = false;
        }

        // ensure the `host` property is an array
        $this->host = is_array($this->host) ? $this->host : [$this->host];

        // iterate over the list of hosts to find the first one that is good
        foreach ($this->host as $host) {
            $connection = $this->connectHost($host);
            if ($connection !== null) {
                $this->ldapClient = $connection;
                return;
            }
        }

        // Wasn't able to connect to any of the provided LDAP hosts
        if ($this->ldapClient === null) {
            throw new \Exception(
                "failed to connect to " . $this->displayName . " host",
                1611157472
            );
        }
    }

    /**
     * @param string $host
     * @return Connection|null
     */
    private function connectHost(string $host): ?Connection
    {
        $connection = new Connection([
            'base_dn' => $this->baseDn,
            'hosts' => [$host],
            'port' => $this->port,
            'username' => $this->adminUsername,
            'password' => $this->adminPassword,
            'use_ssl' => $this->useSsl,
            'use_tls' => $this->useTls,
            'timeout' => 3, // set connection timeout to 3 seconds, default is 5 seconds
        ]);

        try {
            $connection->connect();
            $this->ldapClient = $connection;
        } catch (BindException $e) {
            $err = $e->getDetailedError();
            \Yii::warning([
                'action' => 'ldap connect host',
                'status' => 'warning',
                'host' => $host,
                'ldap_code' => $err->getErrorCode(),
                'diagnostic' => $err->getDiagnosticMessage(),
                'message' => $err->getErrorMessage(),
            ]);
            return null;
        }
        return $connection;
    }

    /**
     * @param string $employeeId
     * @return \common\components\passwordStore\UserPasswordMeta
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
        if (! $this->matchesRequiredAttributes($user)) {
            try {
                \Yii::warning(
                    [
                        'action' => 'set password in ldap store',
                        'message' => sprintf(
                            'Skipping password update in LDAP for user %s because they do not match ' .
                                'required attributes: %s',
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

        $this->assertUserNotDisabled($user);

        if ($this->userPasswordAttribute === 'unicodePwd') {
            $password = $this->encodeForUnicodePwdField($password);
        }

        $this->updatePassword($user, $password);

        /*
         * Reload user after password change
         */
        $user = $this->findUser($employeeId);

        $this->removeAttributesAfterNewPassword($user);

        $this->updateAttributesAfterNewPassword($user);

        /*
         * Save changes
         */
        try {
            if (! $user->save()) {
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

    /**
     * Encode the given password as necessary for use in the `unicodePwd` field.
     *
     * For details, see
     * https://docs.microsoft.com/en-us/openspecs/windows_protocols/ms-adts/6e803168-f140-4d23-b2d3-c3a8ab5917d2
     *
     * @param string $password
     * @return string
     * @throws \Exception
     */
    protected function encodeForUnicodePwdField(string $password): string
    {
        $encodedPassword = iconv('UTF-8', 'UTF-16LE', '"' . $password . '"');
        if ($encodedPassword === false) {
            throw new \Exception(
                'Did not set password: Cannot encode it properly.',
                1554296385
            );
        }
        return $encodedPassword;
    }

    /**
     * @param LDAP_Entry|array $user
     * @param string $password
     * @throws \common\components\passwordStore\PasswordReuseException
     */
    protected function updatePassword(LDAP_Entry|array $user, string $password): void
    {
        try {
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
    }

    /**
     * @param LDAP_Entry|array $user
     */
    protected function removeAttributesAfterNewPassword(LDAP_Entry|array $user): void
    {
        foreach ($this->removeAttributesOnSetPassword as $removeAttr) {
            if ($user->hasAttribute($removeAttr) || $user->hasAttribute(strtolower($removeAttr))) {
                $user->deleteAttribute($removeAttr);
            }
        }
    }

    /**
     * @param LDAP_Entry|array $user
     */
    protected function updateAttributesAfterNewPassword(LDAP_Entry|array $user): void
    {
        foreach ($this->updateAttributesOnSetPassword as $key => $value) {
            if ($user->hasAttribute($key) || $user->hasAttribute(strtolower($key))) {
                $user->updateAttribute($key, $value);
            } else {
                $user->createAttribute($key, $value);
            }
        }
    }

    /**
     * @param LDAP_Entry|array $user
     * @return bool
     */
    public function matchesRequiredAttributes(LDAP_Entry|array $user): bool
    {
        // If not defined, just return true to continue processing as normal
        if (! is_array($this->updatePasswordIfAttributeAndValue)) {
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
     * @param LDAP_Entry|array $user
     * @throws \common\components\passwordStore\AccountLockedException
     */
    public function assertUserNotDisabled(LDAP_Entry|array $user): void
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
        if (! empty($this->userAccountDisabledAttribute)) {
            $criteria[] = $this->userAccountDisabledAttribute;
        }
        if (is_array($this->updateAttributesOnSetPassword)) {
            $criteria = array_merge(
                $criteria,
                array_keys($this->updateAttributesOnSetPassword)
            );
        }
        if (is_array($this->removeAttributesOnSetPassword)) {
            $criteria = array_merge($criteria, $this->removeAttributesOnSetPassword);
        }
        if (is_array($this->updatePasswordIfAttributeAndValue)) {
            foreach ($this->updatePasswordIfAttributeAndValue as $key => $value) {
                $criteria[] = $key;
            }
        }

        return $criteria;
    }

    /**
     * @param string $employeeId
     * @return LDAP_Entry|array
     * @throws \common\components\passwordStore\UserNotFoundException
     */
    public function findUser(string $employeeId): LDAP_Entry|array
    {
        $this->connect();
        $criteria = $this->getSearchCriteria();
        $client = $this->ldapClient;

        try {
            /** @var LDAP_Entry $user */
            $user = $client->query()
                ->select($criteria)
                ->findByOrFail($this->employeeIdAttribute, $employeeId);
        } catch (\Exception $e) {
            throw new UserNotFoundException(
                sprintf('User %s not found in %s', $employeeId, $this->employeeIdAttribute),
                1463493653,
                $e
            );
        }

        return $user;
    }

    public function isLocked(string $employeeId): bool
    {
        $user = $this->findUser($employeeId);

        try {
            $this->assertUserNotDisabled($user);
        } catch (AccountLockedException $e) {
            return true;
        }
        return false;
    }

    /**
     * Assess a potential new password for a user
     * @param string $employeeId
     * @param string $password
     * @return bool
     */
    public function assess($employeeId, $password)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
