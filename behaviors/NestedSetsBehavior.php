<?php

namespace wmc\behaviors;

use yii\validators\Validator;

class NestedSetsBehavior extends \creocoder\nestedsets\NestedSetsBehavior
{
    public $attachTargetId;
    public $attachOperation = self::OPERATION_INSERT_AFTER;

    public function attach($owner) {
        parent::attach($owner);

        // Append some validators
        $validators = $owner->validators;
        $validators->append(Validator::createValidator('integer', $owner, ['attachTargetId']));
        $validators->append(Validator::createValidator('in', $owner, ['attachOperation'], ['range' =>
            [
                self::OPERATION_PREPEND_TO,
                self::OPERATION_APPEND_TO,
                self::OPERATION_INSERT_AFTER,
                self::OPERATION_INSERT_BEFORE
            ]
        ]));
        $reflect = new \ReflectionClass($this->owner);
        $shortName = $reflect->getShortName();
        $validators->append(Validator::createValidator('required', $owner, ['attachTargetId'],
            [
                'on' => 'move',
                'when' => function($model) {
                    return !in_array($model->attachOperation, [self::OPERATION_APPEND_TO, self::OPERATION_PREPEND_TO]);
                },
                'whenClient' => "function (attribute, value) {
                    var validOperations = ['".self::OPERATION_APPEND_TO."', '".self::OPERATION_PREPEND_TO."'];
                    return $.inArray($('input[name=\"".$shortName."[attachOperation]\"]:checked').val(), validOperations) == -1 ? true : false;
                    }"
            ]));
        $validators->append(Validator::createValidator('required', $owner, ['attachOperation'], ['on' => 'move']));
    }

    public function saveNode() {
        if (!$this->owner->validate()) {
            return false;
        }
        $attach = $this->owner->findOne($this->owner->attachTargetId);
        return call_user_func([$this->owner, $this->owner->attachOperation], $attach);
    }
}