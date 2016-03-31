<?php
namespace frontend\controllers;

use frontend\components\BaseRestController;

use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class UserController extends BaseRestController
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
            ]
        ]);
    }

    /**
     * @return null|\yii\web\IdentityInterface
     */
    public function actionMe()
    {
        return \Yii::$app->user->identity;
    }
}