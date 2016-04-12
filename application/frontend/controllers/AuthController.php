<?php
namespace frontend\controllers;

use Sil\IdpPw\Common\Auth\RedirectException;
use Sil\IdpPw\Common\Auth\User as AuthUser;
use common\models\User;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;

class AuthController extends Controller
{
    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        /*
         * Disable CSRF validation for login since user is redirected to an IdP for logging in
         */
        if ($action->id == 'login') {
            // can this be changed to use a URL parameter for the
            // token for this action and we can pass to idp and back?
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
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
            $authUser = \Yii::$app->auth->login(\Yii::$app->request, $returnTo);

            $log['email'] = $authUser->email;

            /*
             * Get local user instance or create one.
             * Use employeeId since username or email could change.
             */
            $user = User::findOrCreate(null, null, $authUser->employeeId);
            // Initialize session for user
            if (\Yii::$app->user->login($user, \Yii::$app->params['sessionDuration'])) {
                $log['status'] = 'success';
                \Yii::warning($log, 'application');

                $afterLogin = $this->getAfterLoginUrl($returnTo);
                return $this->redirect($afterLogin);
            } else {
                throw new UnauthorizedHttpException('Unable to perform user login', 1459966846);
            }
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
         * Get AuthUser for call to auth component
         */
        $authUser = \Yii::$app->user->identity->getAuthUser();

        /*
         * Kill local session
         */
        \Yii::$app->user->logout(true);

        /*
         * Log user out of IdP
         */
        try {
            \Yii::$app->auth->logout($authUser, \Yii::$app->params['ui_url']);
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