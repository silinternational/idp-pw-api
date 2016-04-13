<?php
namespace tests\mock\auth;

use Sil\IdpPw\Common\Auth\AuthnInterface;
use Sil\IdpPw\Common\Auth\InvalidLoginException;
use Sil\IdpPw\Common\Auth\RedirectException;
use Sil\IdpPw\Common\Auth\User as AuthUser;
use yii\base\Component as YiiComponent;
use yii\web\Request;

class Component extends YiiComponent implements AuthnInterface
{
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
     * @param \Sil\IdpPw\Common\Auth\User|null $user
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
     * @return \Sil\IdpPw\Common\Auth\User
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