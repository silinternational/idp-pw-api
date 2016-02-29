<?php
namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;

use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use common\models\Method;

class MethodController extends BaseRestController
{
    /**
     * Access Control Filter
     * NEEDS TO BE UPDATED FOR EVERY ACTION
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
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => ['?'],
                    ],
                ]
            ]
        ]);
    }

    /**
     * Return list of available reset methods for user.
     * If user is not authenticated they should be masked.
     * @return Method[]
     */
    public function actionIndex()
    {
        /** @var User $user */
        $user = \Yii::$app->user->identity;
        return $user->methods;
    }
}