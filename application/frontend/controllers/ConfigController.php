<?php
namespace frontend\controllers;

use frontend\components\BaseRestController;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;

class ConfigController extends BaseRestController
{
    /**
     * Access Control Filter
     * NEEDS TO BE UPDATED FOR EVERY ACTION
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(),[
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['?'],
                    ],
                ]
            ]
        ]);
    }

    /**
     * Return array of config information for UI to use
     * @return array
     */
    public function actionIndex()
    {
        $params = \Yii::$app->params;

        $config = [];

        $config['gaTrackingId'] = $params['gaTrackingId'];
        $config['support'] = $params['support'];
        $config['recaptchaKey'] = $params['recaptchaKey'];

        /*
         * Remove phpRegex from password params before adding to config
         */
        foreach ($params['password'] as $key => $value) {
            if(isset($params['password'][$key]['phpRegex'])){
                unset($params['password'][$key]['phpRegex']);
            }
        }
        $config['password'] = $params['password'];

        return $config;
    }
}