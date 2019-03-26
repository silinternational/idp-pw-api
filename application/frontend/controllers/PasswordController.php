<?php
namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

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
                'class' => AccessControl::class,
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
     * @throws ServerErrorHttpException
     */
    public function actionView()
    {
        /** @var User $user */
        $user = \Yii::$app->user->identity;

        $pwMeta = $user->getPasswordMeta();

        if ($pwMeta === null) {
            throw new ServerErrorHttpException('A system error has occurred.', 1553606573);
        }

        return $pwMeta;
    }

    /**
     * Save new password
     * @return array<string,string>
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionUpdate()
    {
        $newPassword = \Yii::$app->request->getBodyParam('password');
        if ($newPassword === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Password is required'));
        }

        /** @var User $user */
        $user = \Yii::$app->user->identity;
        $user->setPassword($newPassword);

        $pwMeta = $user->getPasswordMeta();

        if ($pwMeta === null) {
            throw new ServerErrorHttpException('A system error has occurred.', 1553606574);
        }

        return $pwMeta;
    }
}
