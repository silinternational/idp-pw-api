<?php
namespace frontend\controllers;

use common\helpers\Utils;
use common\models\User;
use frontend\components\BaseRestController;
use common\components\auth\RedirectException;
use common\components\auth\User as AuthUser;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;

class AuthController extends BaseRestController
{
    /**
     * Access Control Filter
     * NEEDS TO BE UPDATED FOR EVERY ACTION
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['login', 'logout'],
                        'roles' => ['?'],
                    ],
                ]
            ],
            'authenticator' => [
                'except' => ['login', 'logout'] // bypass authentication for /auth/login
            ]
        ]);
    }

    public function actionLogin()
    {
        /*
         * Collect return to url of where to send user after successful login
         * Expected as relative url starting with /
         * Before redirecting user after login this will be prefixed with ui_url
         */
        $returnTo = \Yii::$app->request->get('ReturnTo', '');
        if (substr($returnTo, 0, 1) == '/') {
            $returnTo = \Yii::$app->params['uiUrl'] . $returnTo;
        }

        if ( ! \Yii::$app->user->isGuest) {
            $afterLogin = $this->getAfterLoginUrl($returnTo);
            return $this->redirect($afterLogin);
        }

        /*
         * Initialize $log variable for logging
         */
        $log = ['action' => 'login'];

        try {
            /*
             * Grab client_id for use in token after successful login
             */
            $clientId = Utils::getClientIdOrFail();

            /*
             * Grab state for use in response after successful login
             */
            $state = $this->getRequestState();

            /** @var AuthUser $authUser */
            $authUser = \Yii::$app->auth->login($returnTo, \Yii::$app->request);

            $log['email'] = $authUser->email;

            /*
             * Get local user instance or create one.
             * Use employeeId since username or email could change.
             */
            $user = User::findOrCreate(null, null, $authUser->employeeId);
            $accessToken = $user->createAccessToken($clientId, User::AUTH_TYPE_LOGIN);

            $loginSuccessUrl = $this->getLoginSuccessRedirectUrl($state, $accessToken);

            $log['status'] = 'success';
            \Yii::warning($log, 'application');

            /*
             * Kill session
             */
            \Yii::$app->user->logout(true);

            /*
             * Redirect to UI
             */
            return $this->redirect($loginSuccessUrl);

        } catch (RedirectException $e) {
            /*
             * Login triggered redirect to IdP to login, so return a redirect to it
             */
            return $this->redirect($e->getUrl());
        } catch (\Exception $e) {
            /*
             * log exception
             */
            $log['status'] = 'error';
            $log['error'] = $e->getMessage();
            $log['code'] = $e->getCode();
            \Yii::error($log, 'application');

            /*
             * redirect to login error page
             */
            return $this->redirect(\Yii::$app->params['uiUrl'] . '/auth/error');
        }

    }

    public function actionLogout()
    {
        $accessToken = \Yii::$app->request->get('access_token');
        if ($accessToken !== null) {
            /*
             * Clear access_token
             */
            $accessTokenHash = Utils::getAccessTokenHash($accessToken);
            $user = User::findOne(['access_token' => $accessTokenHash]);
            if ($user != null) {
                $user->access_token = null;
                $user->access_token_expiration = null;
                if ( ! $user->save()) {
                    \Yii::error([
                        'action' => 'user logout',
                        'status' => 'error',
                        'error' => Json::encode($user->getFirstErrors()),
                    ]);
                }

                /*
                 * Get AuthUser for call to auth component
                 */
                $authUser = $user->getAuthUser();

                /*
                 * Log user out of IdP
                 */
                try {
                    \Yii::$app->auth->logout(\Yii::$app->params['uiUrl'], $authUser);
                } catch (RedirectException $e) {
                    return $this->redirect($e->getUrl());
                }
            }
        }

        return $this->redirect(\Yii::$app->params['uiUrl']);
    }

    public function getAfterLoginUrl($returnTo)
    {
        /*
         * If $returnTo starts with UI_URL, return it, else relative build absolute
         */
        if (strpos($returnTo, \Yii::$app->params['uiUrl']) === 0) {
            return $returnTo;
        } elseif (substr($returnTo, 0, 1) == '/') {
            $path = $returnTo;
        } else {
            $path = '';
        }
        return \Yii::$app->params['uiUrl'] . $path;
    }

    /**
     * Get state from request or session and then store in session
     * @return string
     */
    public function getRequestState()
    {
        $state = \Yii::$app->request->get('state');
        if ($state === null) {
            $state = \Yii::$app->session->get('state');
        }
        \Yii::$app->session->set('state', $state);

        return $state;
    }

    /**
     * Build URL to redirect user to after successful login
     * @param string $state
     * @param string $accessToken
     * @return string
     */
    public function getLoginSuccessRedirectUrl($state, $accessToken)
    {
        /*
             * Relay state holds the return to path from UI
             */
        $relayState = \Yii::$app->request->post('RelayState', '/');

        /*
         * build url to redirect user to
         */
        $afterLogin = $this->getAfterLoginUrl($relayState);
        if (strpos( $afterLogin, '?')) {
            $joinChar = '&';
        } else {
            $joinChar = '?';
        }
        $url = $afterLogin . sprintf(
                '%sstate=%s&token_type=Bearer&expires_in=%s&access_token=%s',
                $joinChar, Html::encode($state), \Yii::$app->user->absoluteAuthTimeout, $accessToken
            );

        return $url;
    }
}