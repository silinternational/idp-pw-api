<?php
namespace frontend\controllers;

use common\models\Reset;
use frontend\components\BaseRestController;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Site controller
 */
class SiteController extends BaseRestController
{

    public $layout = false;

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
                    [
                        'allow' => true,
                        'actions' => ['system-status'],
                        'roles' => ['?'],
                    ],
                ]
            ],
            'authenticator' => [
                'except' => ['system-status'] // bypass authentication for /auth/login
            ]
        ]);
    }

//    public function behaviors()
//    {
//        return ArrayHelper::merge(parent::behaviors(), [
//            [
//                'class' => 'yii\filters\ContentNegotiator',
//                'formats' => [
//                    'application/json' => Response::FORMAT_JSON,
//                    'application/xml'  => Response::FORMAT_XML,
//                ]
//            ]
//        ]);
//    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'frontend\components\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        if (\Yii::$app->user->isGuest) {
            throw new UnauthorizedHttpException();
        }
        throw new MethodNotAllowedHttpException();
        /**
         * Redirect to Doorman UI
         */
        return $this->redirect(\Yii::$app->params['uiUrl'], 301);
    }

    public function actionSystemStatus()
    {
        /**
         * Check for DB connection
         */
        try {
            Reset::find()->all();
            return [];
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(
                'Unable to connect to db, error code ' . $e->getCode(),
                $e->getCode()
            );
        }

    }

}
