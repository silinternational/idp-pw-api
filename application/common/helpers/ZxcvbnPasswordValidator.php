<?php
namespace common\helpers;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\validators\Validator;
use yii\base\InvalidConfigException;

/**
 * Class ZxcvbnPasswordValidator
 * @package common\helpers
 * @codeCoverageIgnore
 */
class ZxcvbnPasswordValidator extends Validator
{
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to false.
     * If this is true, it means the attribute is considered valid when it is empty.
     */
    public $allowEmpty = false;

    /**
     * @var int Minimal score value (1-4)
     */
    public $minScore = 2;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        if ( ! in_array($this->minScore, [1, 2, 3, 4])) {
            throw new InvalidConfigException('The "minScore" property must be in range 1-4.');
        }

        $value = $model->$attribute;

        if ($this->allowEmpty && $this->isEmpty($value)) {
            return null;
        }

        $strength = Utils::getZxcvbnScore($value);
        $score = ArrayHelper::getValue($strength, 'score');

        if ($score < $this->minScore) {
            $message = $this->message !== null ? $this->message : \Yii::t(
                    'app',
                    'Password did not meet minimum strength of {minScore}.' .
                    'Try adding some words but avoid common phrases.',
                    ['minScore' => $this->minScore]
                );
            $this->addError($model, $attribute, $message);
        }
    }
}
