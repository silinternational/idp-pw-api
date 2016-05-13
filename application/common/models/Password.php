<?php
namespace common\models;

use d3th\validators\ZxcvbnPasswordValidator;
use yii\base\Model;

class Password extends Model
{
    public $user;

    public $password;

    public $passwordStore;

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
}