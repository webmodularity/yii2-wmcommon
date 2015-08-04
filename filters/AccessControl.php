<?php
namespace wmc\filters;

use Yii;
use yii\web\ForbiddenHttpException;
use wmc\models\user\UserCooldownLog;
use wmc\models\user\UserLog;
use wmc\widgets\Alert;

class AccessControl extends \yii\filters\AccessControl
{
    /**
     * Denies the access of the user.
     * The default implementation will redirect the user to the login page if he is a guest;
     * if the user is already logged, a 403 HTTP exception will be thrown.
     * @param \yii\web\User $user the current user
     * @throws ForbiddenHttpException if the user is already logged in.
     */
    protected function denyAccess($user) {
        if ($user->isGuest) {
            UserCooldownLog::add(UserCooldownLog::ACTION_ACCESS, UserCooldownLog::RESULT_FAIL);
            Yii::$app->alertManager->add(Alert::widget([
                'heading' => 'Permission Denied!',
                'message' => 'You must log in to access the requested resource!',
                'style' => 'danger',
                'icon' => 'times-circle-o'
            ]));
            $user->loginRequired();
        } else {
            UserLog::add(UserLog::ACTION_ACCESS, UserLog::RESULT_FAIL, $user->id);
            throw new ForbiddenHttpException('You are trying to access a protected area without proper permission.');
        }
    }
}