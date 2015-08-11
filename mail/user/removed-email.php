<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = "Email Address Removed";
?>
<?= $user->person->fullName ?>,
<p>This email address (<strong><?= $user->email ?></strong>) has been removed from your <?= Yii::$app->params['siteName'] ?> account.
    If you did not request this action please <?= Html::a('Contact Us', Url::to(['/contact'], true)) ?> so we can restore access
    to your account.</p>

<p><?= Yii::$app->params['siteName'] ?></p>