<?php
namespace frontend\controllers;

use frontend\components\BaseRestController;

use common\models\Reset;
use common\models\User;

use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
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
     * @returns array
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
        /**
         * @todo integrate with reCAPTCHA to validate token
         */

        /*
         * Find or create user
         */
        $user = User::findOrCreate($username);
        if ( ! $user->reset) {

        }

        /*
         * Find or initialize reset
         */
        return Reset::findOrCreate($username);
    }
}