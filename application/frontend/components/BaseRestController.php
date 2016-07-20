<?php
namespace frontend\components;

use yii\filters\AccessControl;
use yii\filters\Cors;
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
                'class' => CompositeAuth::className(),
                'authMethods' => [
                    HttpBearerAuth::className(), // Use header ... Authorization: Bearer abc123
                ],
                'except' => ['options'],
            ],
            'corsFilter' => [
                'class' => Cors::className(),
                'actions' => ['index', 'view', 'create', 'update', 'delete', 'options'],
                'cors' => [
                    'Origin' => [\Yii::$app->params['uiCorsOrigin']],
                    'Access-Control-Request-Method' => [
                        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'
                    ],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 86400,
                ]
            ],
            'access' => [
                'class' => AccessControl::className(),
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