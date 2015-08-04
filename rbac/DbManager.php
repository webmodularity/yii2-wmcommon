<?php

namespace wmc\rbac;

use Yii;

class DbManager extends \yii\rbac\DbManager
{
    public $assignmentTable = '{{%user_auth_assignment}}';
    public $itemChildTable = '{{%user_auth_item_child}}';
    public $itemTable = '{{%user_auth_item}}';
    public $ruleTable = '{{%user_auth_rule}}';
}