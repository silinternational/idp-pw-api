<?php
namespace frontend\controllers;

use common\helpers\Utils;
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
        return Utils::getFrontendConfig();
    }
}