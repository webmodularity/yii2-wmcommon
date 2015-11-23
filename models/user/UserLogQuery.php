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

    public function lastLogin($userId, $successOnly = true) {
        $this->andWhere(['user_id' => $userId, 'action_type' => UserLog::ACTION_LOGIN]);
        if ($successOnly) {
            $this->andWhere(['result_type' => UserLog::RESULT_SUCCESS]);
        }
        $this->orderBy(['created_at' => SORT_DESC]);
        return $this;
    }

    public function frontend() {
        $this->andWhere(['app' => UserLog::APP_FRONTEND]);
        return $this;
    }

    public function backend() {
        $this->andWhere(['app' => UserLog::APP_BACKEND]);
        return $this;
    }

    public function console() {
        $this->andWhere(['app' => UserLog::APP_CONSOLE]);
        return $this;
    }
}