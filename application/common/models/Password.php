<?php
namespace common\models;

use d3th\validators\ZxcvbnPasswordValidator;
use yii\base\Model;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

class Password extends Model
{
    /** @var string */
    public $password;

    /** @var \Sil\IdpPw\Common\PasswordStore\PasswordStoreInterface */
    public $passwordStore;

    /** @var array */
    public $config;

    public function init()
    {
        $this->passwordStore = \Yii::$app->passwordStore;
        $this->config = \Yii::$app->params['password'];
    }

    public function rules()
    {
        return [
            [
                'password', 'match', 'pattern' => $this->config['minLength']['phpRegex'],
                'message' => \Yii::t(
                    'app',
                    'Your password does not meet the minimum length of {minLength}',
                    ['minScore' => $this->config['minLength']['value']]
                ),
                'when' => function() { return $this->config['minLength']['enabled']; }
            ],
            [
                'password', 'match', 'pattern' => $this->config['maxLength']['phpRegex'],
                'message' => \Yii::t(
                    'app',
                    'Your password exceeds the maximum length of {maxLength}',
                    ['maxLength' => $this->config['maxLength']['value']]
                ),
                'when' => function() { return $this->config['maxLength']['enabled']; }
            ],
            [
                'password', 'match', 'pattern' => $this->config['minNum']['phpRegex'],
                'message' => \Yii::t(
                    'app',
                    'Your password must contain at least {minNum} numbers',
                    ['minNum' => $this->config['minNum']['value']]
                ),
                'when' => function() { return $this->config['minNum']['enabled']; }
            ],
            [
                'password', 'match', 'pattern' => $this->config['minUpper']['phpRegex'],
                'message' => \Yii::t(
                    'app',
                    'Your password must contain at least {minUpper} upper case letters',
                    ['minUpper' => $this->config['minUpper']['value']]
                ),
                'when' => function() { return $this->config['minUpper']['enabled']; }
            ],
            [
                'password', 'match', 'pattern' => $this->config['minSpecial']['phpRegex'],
                'message' => \Yii::t(
                    'app',
                    'Your password must contain at least {minSpecial} special characters',
                    ['minSpecial' => $this->config['minSpecial']['value']]
                ),
                'when' => function() { return $this->config['minSpecial']['enabled']; }
            ],
            [
                'password',
                ZxcvbnPasswordValidator::className(),
                'minScore' => $this->config['zxcvbn']['minScore'],
                'message' => \Yii::t(
                    'app',
                    'Your password does not meet the minimum strength of {minScore}',
                    ['minScore' => $this->config['zxcvbn']['minScore']]
                ),
            ],
        ];
    }

    /**
     * Shortcut method to initialize a Password object
     * @param string $newPassword
     * @return Password
     */
    public static function create($newPassword)
    {
        $password = new Password();
        $password->password = $newPassword;

        return $password;
    }

    public function save($userId, $username)
    {
        $log = [
            'action' => 'save password',
            'user_id' => $userId,
        ];

        /*
         * If validation fails, return just the first error
         */
        if ( ! $this->validate()) {
            $errors = $this->getFirstErrors();
            $log['status'] = 'error';
            $log['error'] = Json::encode($errors);
            \Yii::error($log);
            throw new BadRequestHttpException($errors[0], 1463164336);
        }

        /*
         * Update password
         */
        try {
            $this->passwordStore->set($username, $this->password);
            $log['status'] = 'success';
            \Yii::warning($log);
        } catch (\Exception $e) {
            $log['status'] = 'error';
            $log['error'] = $e->getMessage();
            \Yii::error($log);
            throw new ServerErrorHttpException(\Yii::t('app', 'Unable to update password'), 1463165209);
        }

        return [];
    }


}