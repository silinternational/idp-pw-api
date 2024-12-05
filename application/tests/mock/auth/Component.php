<?php

namespace tests\mock\auth;

use common\components\auth\AuthnInterface;
use common\components\auth\InvalidLoginException;
use common\components\auth\RedirectException;
use common\components\auth\User as AuthUser;
use yii\base\Component as YiiComponent;
use yii\web\Request;

class Component extends YiiComponent implements AuthnInterface
{
    /**
     * Whether or not to sign request
     * @var bool [default=true]
     */
    public $signRequest = true;

    /**
     * Whether or not response should be signed
     * @var bool [default=true]
     */
    public $checkResponseSigning = true;

    /**
     * Whether or not to require response assertion to be encrypted
     * @var bool [default=true]
     */
    public $requireEncryptedAssertion = true;

    /**
     * Certificate contents for remote IdP
     * @var string
     */
    public $idpCertificate;

    /**
     * Certificate contents for this SP
     * @var string|null If null, request will not be signed
     */
    public $spCertificate;

    /**
     * PEM encoded private key file associated with $spCertificate
     * @var string|null If null, request will not be signed
     */
    public $spPrivateKey;

    /**
     * This SP Entity ID as known by the remote IdP
     * @var string
     */
    public $entityId;

    /**
     * Single-Sign-On url for remote IdP
     * @var string
     */
    public $ssoUrl;

    /**
     * Single-Log-Out url for remote IdP
     * @var string
     */
    public $sloUrl;

    /**
     * Mapping configuration for IdP attributes to User
     * @var array
     */
    public $attributeMap;

    /**
     * @param string $returnTo Where to have IdP send user after login
     * @param \yii\web\Request|null $request
     * @return AuthUser
     * @throws InvalidLoginException
     * @throws RedirectException
     */
    public function login($returnTo, Request $request = null)
    {
        $username = null;
        $password = null;

        if ($request instanceof \yii\web\Request) {
            $username = $request->post('username', null);
            $password = $request->post('password', null);
        }

        /*
         * If username or password are missing, "redirect" them
         */
        if (is_null($username) || is_null($password)) {
            throw new RedirectException('https://login');
        }

        /*
         * Loop through mock users to perform "login"
         */
        $data = include __DIR__ . '/data.php';
        foreach ($data as $user) {
            if ($user['username'] == $username) {
                if ($user['password'] == $password) {
                    return $this->toAuthUser($user);
                }

                break;
            }
        }

        throw new InvalidLoginException();
    }

    /**
     * @param string $returnTo Where to have IdP send user after login
     * @param \common\components\auth\User|null $user
     * @return void
     * @throws RedirectException
     */
    public function logout($returnTo, AuthUser $user = null)
    {
        // Allow for redirect potential
        if ($returnTo == 'redirect') {
            throw new RedirectException('https://logout');
        }
    }

    /**
     * Convert mock user array to AuthUser
     * @return \common\components\auth\User
     */
    private function toAuthUser($userArray)
    {
        $authUser = new AuthUser();
        $authUser->firstName = $userArray['firstName'];
        $authUser->lastName = $userArray['lastName'];
        $authUser->email = $userArray['email'];
        $authUser->employeeId = $userArray['employeeId'];
        $authUser->idpUsername = $userArray['idpUsername'];
        return $authUser;
    }
}
