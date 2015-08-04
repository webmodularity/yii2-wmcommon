<?php
use yii\helpers\Html;
use yii\helpers\Url;
$this->title = "Password Reset Successful";
?>
<?= $user->person->fullName ?>,
<p>The password for the account associated with <strong><?= $user->email ?></strong> has been updated.
    If you did not request this change please contact us so we can restore access to your account.</p>


<p>If you have any questions please feel free to <?= Html::a('Contact Us', Url::to(['/contact'], true)) ?>.</p>

<p><?= Yii::$app->params['siteName'] ?></p>