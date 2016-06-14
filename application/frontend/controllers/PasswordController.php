<?php
namespace frontend\controllers;

use common\models\Password;
use frontend\components\BaseRestController;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;

class PasswordController extends BaseRestController
{
    /**
     * Access Control Filter
     * REMEMBER: NEEDS TO BE UPDATED FOR EVERY ACTION
     * @return array
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
                ]
            ]
        ]);
    }

    /**
     * Return password metadata
     * @return array
     */
    public function actionView()
    {
        return \Yii::$app->user->identity->getPasswordMeta();
    }

    /**
     * Save new password
     * @return array
     * @throws BadRequestHttpException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function actionUpdate()
    {
        $newPassword = \Yii::$app->request->getBodyParam('password');
        if ($newPassword === null) {
            throw new BadRequestHttpException('Password is required');
        }

        $password = Password::create($newPassword);
        $password->save(\Yii::$app->user->getId());

        return \Yii::$app->user->identity->getPasswordMeta();
    }

    

}