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

        $code = $this->getCode($exception);
        $name = $this->getName($exception);
        $message = $this->getMessage($exception);

        $name .= ($code ? ' (#' . $code . ')' : '');

        $response = [
            'name' => $name,
            'message' => $message,
            'code' => $code,
        ];

        if (YII_DEBUG) {
            $response['exception'] = $exception;
        }

        return $response;
    }

    /**
     * @param \Exception $exception
     * @return int
     */
    public function getCode($exception)
    {
        if ($exception instanceof HttpException) {
            return $exception->statusCode;
        } else {
            return $exception->getCode();
        }
    }

    /**
     * @param \Exception $exception
     * @return string
     */
    public function getName($exception)
    {
        if ($exception instanceof Exception) {
            return $exception->getName();
        } else {
            return  $this->defaultName ?: \Yii::t('yii', 'Error');
        }
    }

    /**
     * @param \Exception $exception
     * @return string
     */
    public function getMessage($exception)
    {
        if ($exception instanceof UserException) {
            return $exception->getMessage();
        } else {
            return $this->defaultMessage ?: \Yii::t('yii', 'An internal server error occurred.');
        }
    }
}
