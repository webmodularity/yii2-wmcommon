<?php
use yii\helpers\Html;
use yii\helpers\Url;

$resetLink = Url::to(["/user/reset-email", 'key' => $userKey], true);
$this->title = "Change Email Request";
?>
<?= $user->person->fullName ?>,
<p>To complete your email change you will need to confirm your new email address by clicking the link below.</p>

<p>Confirm Email Address: <?= Html::a($resetLink, $resetLink) ?></p>

<p>If you have any questions please feel free to <?= Html::a('Contact Us', Url::to(['/contact'], true)) ?>.</p>

<p><?= Yii::$app->params['siteName'] ?></p>