<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string $idp_username
 * @property string $email
 * @property string $created
 * @property string $pw_last_changed
 * @property string $pw_expires
 * @property string $access_token
 * @property string $access_token_expiration
 * @property string $auth_type
 * @property string $hide
 * @property string $uuid
 *
 * @property EventLog[] $eventLogs
 * @property Method[] $methods
 * @property Reset $reset
 */
class UserBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['employee_id', 'first_name', 'last_name', 'idp_username', 'email', 'created', 'hide'], 'required'],
            [['created', 'pw_last_changed', 'pw_expires', 'access_token_expiration'], 'safe'],
            [['auth_type', 'hide'], 'string'],
            [['employee_id'], 'string', 'max' => 32],
            [['first_name', 'last_name', 'idp_username', 'email'], 'string', 'max' => 255],
            [['access_token', 'uuid'], 'string', 'max' => 64],
            [['employee_id'], 'unique'],
            [['email'], 'unique'],
            [['access_token'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'employee_id' => Yii::t('app', 'Employee ID'),
            'first_name' => Yii::t('app', 'First Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'idp_username' => Yii::t('app', 'Idp Username'),
            'email' => Yii::t('app', 'Email'),
            'created' => Yii::t('app', 'Created'),
            'pw_last_changed' => Yii::t('app', 'Pw Last Changed'),
            'pw_expires' => Yii::t('app', 'Pw Expires'),
            'access_token' => Yii::t('app', 'Access Token'),
            'access_token_expiration' => Yii::t('app', 'Access Token Expiration'),
            'auth_type' => Yii::t('app', 'Auth Type'),
            'hide' => Yii::t('app', 'Hide'),
            'uuid' => Yii::t('app', 'Uuid'),
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
    public function getReset()
    {
        return $this->hasOne(Reset::className(), ['user_id' => 'id']);
    }
}
