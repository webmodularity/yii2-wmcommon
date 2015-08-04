<?php

namespace wmc\models\user;

use Yii;

class UserLogQuery extends \yii\db\ActiveQuery
{
    public function recent($interval = "PT5M") {
        $timezone = new \DateTimeZone('UTC');
        $time = new \DateTime(NULL, $timezone);
        $time->sub(new \DateInterval($interval));
        $this->andWhere([">=", 'created_at', $time->format('Y-m-d H:i:s')]);
        return $this;
    }

    public function lastLogin($userId) {
        $this->andWhere(['user_id' => $userId]);
        $this->orderBy(['created_at' => SORT_DESC]);
        return $this;
    }
}