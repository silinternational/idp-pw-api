<?php
namespace frontend\controllers;

use common\components\Emailer;
use Exception;
use frontend\components\BaseRestController;
use Sil\EmailService\Client\EmailServiceClient;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\MethodNotAllowedHttpException;
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
                'class' => AccessControl::class,
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
                'except' => ['system-status'] // bypass authentication for /site/system-status
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
        if (\Yii::$app->user->isGuest) {
            throw new UnauthorizedHttpException();
        }
        throw new MethodNotAllowedHttpException();
    }

    public function actionSystemStatus()
    {
        /**
         * Check for DB connection
         */
        try {
            \Yii::$app->db->open();
        } catch (Exception $e) {
            throw new ServerErrorHttpException(
                'Unable to connect to db, error code ' . $e->getCode(),
                $e->getCode()
            );
        }
        
        try {
            /**
             * @var $emailer Emailer
             */
            $emailer = \Yii::$app->emailer;
            $emailer->getSiteStatus();
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());
            throw new ServerErrorHttpException('Problem with email service.');
        }
    }
}
