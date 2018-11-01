<?php
namespace frontend\components;

use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;

class BaseRestController extends Controller
{

    /**
     * Enable CORS support
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'class' => CompositeAuth::class,
                'authMethods' => [
                    HttpBearerAuth::class, // Use header ... Authorization: Bearer abc123
                ],
                'except' => ['options'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['options']
                    ],
                ],
                'denyCallback' => function($rule, $action) {
                    if (\Yii::$app->user->isGuest) {
                        throw new UnauthorizedHttpException();
                    } else {
                        throw new ForbiddenHttpException();
                    }
                },
            ]
        ]);
    }

    /**
     * @return array
     */
    public function actionOptions()
    {
        return [];
    }
}
