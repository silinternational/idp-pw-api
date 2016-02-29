<?php
namespace frontend\components;

use yii\web\HttpException;
use yii\web\ErrorAction as YiiErrorAction;
use yii\base\Exception;
use yii\base\UserException;

class ErrorAction extends YiiErrorAction
{

    public function run()
    {
        if (($exception = \Yii::$app->getErrorHandler()->exception) === null) {
            return '';
        }

        if ($exception instanceof HttpException) {
            $code = $exception->statusCode;
        } else {
            $code = $exception->getCode();
        }
        if ($exception instanceof Exception) {
            $name = $exception->getName();
        } else {
            $name = $this->defaultName ?: \Yii::t('yii', 'Error');
        }
        if ($code) {
            $name .= ' (#' . $code . ')';
        }

        if ($exception instanceof UserException) {
            $message = $exception->getMessage();
        } else {
            $message = $this->defaultMessage ?: \Yii::t('yii', 'An internal server error occurred.');
        }

        $response = [
            'name' => $name,
            'message' => $message,
            'code' => $code,
        ];

        if(YII_DEBUG){
            $response['exception'] = $exception;
        }

        return $response;
    }
}