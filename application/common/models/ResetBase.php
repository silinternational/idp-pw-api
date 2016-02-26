<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "reset".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $type
 * @property integer $method_id
 * @property string $code
 * @property integer $attempts
 * @property string $expires
 * @property string $disable_until
 * @property string $created
 *
 * @property Method $method
 * @property User $user
 */
class ResetBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'reset';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'code', 'expires', 'created'], 'required'],
            [['user_id', 'method_id', 'attempts'], 'integer'],
            [['type'], 'string'],
            [['expires', 'disable_until', 'created'], 'safe'],
            [['code'], 'string', 'max' => 64],
            [['user_id'], 'unique'],
            [['code'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'method_id' => Yii::t('app', 'Method ID'),
            'code' => Yii::t('app', 'Code'),
            'attempts' => Yii::t('app', 'Attempts'),
            'expires' => Yii::t('app', 'Expires'),
            'disable_until' => Yii::t('app', 'Disable Until'),
            'created' => Yii::t('app', 'Created'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMethod()
    {
        return $this->hasOne(Method::className(), ['id' => 'method_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
