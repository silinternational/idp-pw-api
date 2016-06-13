<?php
namespace frontend\controllers;

use common\helpers\Utils;
use common\models\User;
use frontend\components\BaseRestController;
use Sil\IdpPw\Common\Auth\RedirectException;
use Sil\IdpPw\Common\Auth\User as AuthUser;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
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
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['me'],
                        'roles' => ['?'],
                    ],
                ]
            ],
            'authenticator' => [
                'except' => ['login'] // bypass authentication for /config
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

        if ( ! \Yii::$app->user->isGuest) {
            $afterLogin = $this->getAfterLoginUrl($returnTo);
            return $this->redirect($afterLogin);
        }

        /*
         * Initialize $log variable for logging
         */
        $log = ['action' => 'login'];

        try {
            $returnTo = Url::to(['auth/login', 'ReturnTo' => $returnTo], true);
            /** @var AuthUser $authUser */
            $authUser = \Yii::$app->auth->login($returnTo, \Yii::$app->request);

            $log['email'] = $authUser->email;

            /*
             * Get local user instance or create one.
             * Use employeeId since username or email could change.
             */
            $user = User::findOrCreate(null, null, $authUser->employeeId);

            /*
             * Create access_token and update user
             */
            $user->access_token = Utils::generateRandomString(32);
            $user->access_token_expiration = Utils::getDatetime(
                time() + \Yii::$app->user->absoluteAuthTimeout
            );
            if ( ! $user->save()) {
                throw new ServerErrorHttpException('Unable to create access token', 1465833228);
            }

            $state = \Yii::$app->request->get('state');
            $afterLogin = $this->getAfterLoginUrl($returnTo);
            $url = $afterLogin . sprintf(
                '?state=%s&token_type=Bearer&expires_in=%s&acces_token=%s',
                Html::encode($state), \Yii::$app->user->absoluteAuthTimeout, $user->access_token
            );

            $log['status'] = 'success';
            \Yii::warning($log, 'application');

            return $this->redirect($url);

            // Initialize session for user
//            if (\Yii::$app->user->login($user)) {
//                $log['status'] = 'success';
//                \Yii::warning($log, 'application');
//
//                $afterLogin = $this->getAfterLoginUrl($returnTo);
//                return $this->redirect($afterLogin);
//            } else {
//                throw new UnauthorizedHttpException('Unable to perform user login', 1459966846);
//            }
        } catch (RedirectException $e) {
            /*
             * Login triggered redirect to IdP to login, so return a redirect to it
             */
            return $this->redirect($e->getUrl());
        } catch (\Exception $e) {
            $log['status'] = 'error';
            $log['error'] = $e->getMessage();
            $log['code'] = $e->getCode();
            \Yii::error($log, 'application');
            throw new UnauthorizedHttpException($e->getMessage(), $e->getCode());
        }

    }

    public function actionLogout()
    {
        if (\Yii::$app->user->isGuest) {
            /*
             * User not logged in, but lets kill session anyway and redirect to UI
             */
            \Yii::$app->user->logout(true);

            return $this->redirect(\Yii::$app->params['ui_url']);
        }

        /*
         * Clear access_token
         */
        /** @var User $user */
        $user = \Yii::$app->user->identity;
        $user->access_token = null;
        $user->access_token_expiration = null;
        if ( ! $user->save()) {
            throw new ServerErrorHttpException('Unable to log user out', 1465838419);
        }

        /*
         * Get AuthUser for call to auth component
         */
        $authUser = $user->getAuthUser();

        /*
         * Kill local session
         */
        \Yii::$app->user->logout(true);

        /*
         * Log user out of IdP
         */
        try {
            \Yii::$app->auth->logout(\Yii::$app->params['ui_url'], $authUser);
        } catch (RedirectException $e) {
            return $this->redirect($e->getUrl());
        }

        return $this->redirect(\Yii::$app->params['ui_url']);
    }

    public function getAfterLoginUrl($returnTo)
    {
        /*
         * Only keep $returnTo if it is a path on the frontend as a safety measure
         * to help prevent CSRF
         */
        if (substr($returnTo, 0, 1) == '/') {
            $path = $returnTo;
        } else {
            $path = '';
        }
        return \Yii::$app->params['ui_url'] . $path;
    }
}