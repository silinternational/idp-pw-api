<?php
namespace frontend\controllers;

use common\models\User;
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
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ]
            ],
        ]);
    }

    /**
     * @return null|\yii\web\IdentityInterface
     */
    public function actionMe()
    {
        return \Yii::$app->user->identity;
    }

    /**
     * @return null|\yii\web\IdentityInterface
     */
    public function actionUpdate()
    {
        /**
         * @var User $user
         */
        $user = \Yii::$app->user->identity;

        $doNotDisclose = \Yii::$app->request->getBodyParam('do_not_disclose');

        if ($doNotDisclose !== null) {
            $user->do_not_disclose = (int)$doNotDisclose;
            $user->save();
        }

        return $user;
    }
}
