<?php

namespace wmc\filters;

use Yii;
use wmc\models\user\UserCooldown;
use yii\web\ForbiddenHttpException;

class IpCooldownFilter extends \yii\base\ActionFilter
{
    public function beforeAction($action) {
        if (UserCooldown::IPOnCooldown(Yii::$app->request->userIP) === true) {
            $this->denyAccess();
            return false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Throws 403 if IP is on Cooldown
     * @throws ForbiddenHttpException
     */
    protected function denyAccess() {
        throw new ForbiddenHttpException('This location is not currently allowed to access this resource.');
    }
}