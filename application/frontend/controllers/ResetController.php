<?php
namespace frontend\controllers;

use common\helpers\Utils;
use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class ResetController extends BaseRestController
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
                        'roles' => ['?'],
                    ],
                ]
            ]
        ]);
    }

    /**
     * Create new reset process
     * @return Reset
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionCreate()
    {
        $username = \Yii::$app->request->post('username');
        $verificationToken = \Yii::$app->request->post('verification_token');

        if ( ! $username || ! $verificationToken) {
            throw new BadRequestHttpException('Missing username or verification_token');
        }

        /*
         * Validate reCaptcha $verificationToken before proceeding.
         * This will throw an exception if not successful, checking response to
         * be double sure an exception is thrown.
         */
        $clientIp = Utils::getClientIp(\Yii::$app->request);
        if ( ! Utils::isRecaptchaResponseValid($verificationToken, $clientIp)) {
            throw new BadRequestHttpException('reCaptcha failed verification');
        }

        /*
         * Find or create user
         */
        $user = User::findOrCreate($username);

        /*
         * Find or create a reset
         */
        $reset = Reset::findOrCreate($user);

        /*
         * Send reset notification
         */
        $reset->send();

        return $reset;
    }

    /**
     * Update reset type/method and send verification
     * @param string $uid
     * @return Reset
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \yii\web\ServerErrorHttpException
     * @throws \yii\web\TooManyRequestsHttpException
     */
    public function actionUpdate($uid)
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException('Reset not found', 1462989590);
        }

        $type = \Yii::$app->request->getBodyParam('type', null);
        $methodId = \Yii::$app->request->getBodyParam('method_id', null);

        if ($type === null) {
            throw new BadRequestHttpException('Invalid reset type', 1462989664);
        }

        /*
         * Update type
         */
        $reset->setType($type, $methodId);

        /*
         * Send verification
         */
        $reset->send();

        return $reset;
    }

    /**
     * @param string $uid
     * @return Reset
     * @throws NotFoundHttpException
     */
    public function actionResend($uid)
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException();
        }

        /*
         * Resend verification
         */
        $reset->send();

        return $reset;
    }

    /**
     * Validate reset code. Logs user in if successful
     * @param string $uid
     * @return Reset
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function actionValidate($uid)
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException();
        }

        $code = \Yii::$app->request->getBodyParam('code', null);
        if ($code === null) {
            throw new BadRequestHttpException('Code is required', 1462989866);
        }

        $log = [
            'action' => 'Validate reset',
            'reset_id' => $reset->id,
        ];

        $isValid = $reset->isUserProvidedCodeCorrect($code);
        if ($isValid === true) {
            /*
             * Reset verified successfully, log user in
             */
            if (\Yii::$app->user->login($reset->user, \Yii::$app->params['sessionDuration'])) {
                $log['status'] = 'success';
                \Yii::warning($log);

                /*
                 * Delete reset record, log errors, but let user proceed
                 */
                if ( ! $reset->delete()) {
                    \Yii::error([
                        'action' => 'delete reset after validation',
                        'reset_id' => $reset->id,
                        'status' => 'error',
                        'error' => Json::encode($reset->getFirstErrors()),
                    ]);
                }

                return new \stdClass();
            }

            $log['status'] = 'error';
            $log['error'] = 'Unable to log user in after successful reset verification';
            \Yii::error($log);
            throw new ServerErrorHttpException(
                $log['error'],
                1462990877
            );
        }

        $log['status'] = 'error';
        $log['error'] = 'Reset code verification failed';
        \Yii::error($log);
        throw new BadRequestHttpException('Invalid verification code', 1462991098);
    }
}