<?php
namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;

use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;

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

        $do_not_disclose = \Yii::$app->request->getBodyParam('do_not_disclose');

        if ($do_not_disclose !== null) {
            $user->do_not_disclose = $do_not_disclose ? 1 : 0;
            $user->save();
        }

        return $user;
    }
}
