<?php
namespace frontend\components;

use yii\filters\AccessControl;
use yii\filters\Cors;
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
            'corsFilter' => [
                'class' => Cors::className(),
                'actions' => ['index', 'view', 'create', 'update', 'delete', 'options'],
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => [
                        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'
                    ],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 86400,
                    //'Access-Control-Expose-Headers' => []
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
     * Default rule is forbidden
     * @throws \yii\web\ForbiddenHttpException
     * @param string $action
     */
    protected function checkForForbidden($action)
    {
        throw new ForbiddenHttpException;
    }

    /**
     * Checks the privilege of the current user.
     *
     * This method should be overridden to check whether the current user has the privilege
     * to run the specified action against the specified data model.
     * If the user must be logged in, a [[ForbiddenHttpException]] should be thrown.
     * If the user is logged in but not allowed, a [[UnauthorizedHttpException]] should be thrown.
     *
     * @param string $action the ID of the action to be executed
     * @param \yii\base\Model|null $model the model to be accessed.
     *        If null, it means no specific model is being accessed.
     * @param array $params additional parameters
     * @throws UnauthorizedHttpException if the user is not logged in
     * @throws ForbiddenHttpException if the user is logged in but not authorized for the call
     */
    public function checkAccess($action, $model = null, $params = [])
    {

        $appUser = \Yii::$app->user;

        // Not logged in
        if ( ! $appUser || $appUser->isGuest) {
            throw new UnauthorizedHttpException;
        }

        $this->checkForForbidden($action);

    }

    /**
     * @return array
     */
    public function actionOptions()
    {
        return [];
    }
}