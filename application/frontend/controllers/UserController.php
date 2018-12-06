<?php
namespace frontend\controllers;

use common\helpers\Utils;
use common\models\User;
use frontend\components\BaseRestController;
use Sil\Idp\IdBroker\Client\IdBrokerClient;
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
                    [
                        'allow' => true,
                        'actions' => ['create'],
                        'roles' => ['?'],
                    ],
                ]
            ],
            'authenticator' => [
                'except' => ['create'] // bypass authentication for POST /user
            ]
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

        $hide = \Yii::$app->request->getBodyParam('hide');

        if ($hide !== null) {
            $user->hide = $hide;
            $user->save();
        }

        return $user;
    }

    public function actionCreate()
    {
        $magicCode = \Yii::$app->request->getBodyParam('magic');

        /**
         * @var IdBrokerClient
         */
        $client = \Yii::$app->passwordStore->getClient();

        // TODO: Replace with actual call to get user info from magic code
        $response = $client->getUser('25921');

        if ($response['employee_id'] ?? null) {
            $user = User::findOrCreate(null, null, $response['employee_id']);
            $clientId = \Yii::$app->request->getBodyParam('client_id');
            $accessToken = $user->createAccessToken($clientId, User::AUTH_TYPE_LOGIN);
            return [
                'access_token' => $accessToken,
            ];
        }
    }
}
