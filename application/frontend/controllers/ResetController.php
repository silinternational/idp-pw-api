<?php
namespace frontend\controllers;

use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

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
         * Validate $verificationToken before proceeding
         */
        /*
         * integrate with reCAPTCHA to validate token
         */

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
     * @param string $uid
     * @return void
     * @throws NotFoundHttpException
     */
    public function actionResend($uid)
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException();
        }

        $reset->send();

        \Yii::$app->response->statusCode = 204;
        return;
    }
}