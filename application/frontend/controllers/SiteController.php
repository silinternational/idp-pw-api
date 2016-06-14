<?php
namespace frontend\controllers;

use common\models\Reset;
use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Site controller
 */
class SiteController extends Controller
{

    public $layout = false;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                    'application/xml'  => Response::FORMAT_XML,
                ]
            ]
        ]);
    }

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
