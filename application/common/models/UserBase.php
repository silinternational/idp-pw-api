<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $uid
 * @property string $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string $idp_username
 * @property string $email
 * @property string $created
 * @property string $last_login
 * @property string $pw_last_changed
 * @property string $pw_expires
 * @property string $access_token
 * @property string $access_token_expiration
 * @property string $auth_type
 *
 * @property EventLog[] $eventLogs
 * @property Method[] $methods
 * @property PasswordChangeLog[] $passwordChangeLogs
 * @property Reset $reset
 */
class UserBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'employee_id', 'first_name', 'last_name', 'idp_username', 'email', 'created'], 'required'],
            [['created', 'last_login', 'pw_last_changed', 'pw_expires', 'access_token_expiration'], 'safe'],
            [['auth_type'], 'string'],
            [['uid', 'employee_id'], 'string', 'max' => 32],
            [['first_name', 'last_name', 'idp_username', 'email'], 'string', 'max' => 255],
            [['access_token'], 'string', 'max' => 64],
            [['uid'], 'unique'],
            [['employee_id'], 'unique'],
            [['email'], 'unique'],
            [['access_token'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uid' => Yii::t('app', 'Uid'),
            'employee_id' => Yii::t('app', 'Employee ID'),
            'first_name' => Yii::t('app', 'First Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'idp_username' => Yii::t('app', 'Idp Username'),
            'email' => Yii::t('app', 'Email'),
            'created' => Yii::t('app', 'Created'),
            'last_login' => Yii::t('app', 'Last Login'),
            'pw_last_changed' => Yii::t('app', 'Pw Last Changed'),
            'pw_expires' => Yii::t('app', 'Pw Expires'),
            'access_token' => Yii::t('app', 'Access Token'),
            'access_token_expiration' => Yii::t('app', 'Access Token Expiration'),
            'auth_type' => Yii::t('app', 'Auth Type'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventLogs()
    {
        return $this->hasMany(EventLog::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMethods()
    {
        return $this->hasMany(Method::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPasswordChangeLogs()
    {
        return $this->hasMany(PasswordChangeLog::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReset()
    {
        return $this->hasOne(Reset::className(), ['user_id' => 'id']);
    }
}
