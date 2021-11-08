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
 * @property string $access_token
 * @property string $access_token_expiration
 * @property string $auth_type
 * @property string $hide
 * @property string $uuid
 * @property string $display_name
 *
 * @property EventLog[] $eventLogs
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
            [['created', 'access_token_expiration'], 'safe'],
            [['auth_type', 'hide'], 'string'],
            [['employee_id', 'first_name', 'last_name', 'idp_username', 'email', 'display_name'], 'string', 'max' => 255],
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
            'id' => Yii::t('model', 'ID'),
            'employee_id' => Yii::t('model', 'Employee ID'),
            'first_name' => Yii::t('model', 'First Name'),
            'last_name' => Yii::t('model', 'Last Name'),
            'idp_username' => Yii::t('model', 'Idp Username'),
            'email' => Yii::t('model', 'Email'),
            'created' => Yii::t('model', 'Created'),
            'access_token' => Yii::t('model', 'Access Token'),
            'access_token_expiration' => Yii::t('model', 'Access Token Expiration'),
            'auth_type' => Yii::t('model', 'Auth Type'),
            'hide' => Yii::t('model', 'Hide'),
            'uuid' => Yii::t('model', 'Uuid'),
            'display_name' => Yii::t('model', 'Display Name'),
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
    public function getReset()
    {
        return $this->hasOne(Reset::className(), ['user_id' => 'id']);
    }
}
