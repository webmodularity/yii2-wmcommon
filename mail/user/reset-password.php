<?php
use yii\helpers\Html;
use yii\helpers\Url;

$resetLink = Url::to(["/user/reset-password", 'key' => $userKey->user_key], true);
$this->title = "Password Reset Request";
?>
<?= $user->person->fullName ?>,
<p>We have received a request to reset the password for the account associated with
    <strong><?= $user->email ?></strong>. If you did not request this action you can safely ignore this message,
    otherwise click the link below to set up a new password.</p>

<p>Password Reset: <?= Html::a($resetLink, $resetLink) ?></p>

<p>If you have any questions please feel free to <?= Html::a('Contact Us', Url::to(['/contact'], true)) ?>.</p>

<p><?= Yii::$app->params['siteName'] ?></p>