<?php

namespace wmc\web;

use Yii;
use wmc\models\File;
use wmc\models\FileLog;
use yii\web\NotFoundHttpException;

class FileAction extends \yii\base\Action
{
    public function run($filename) {
        $file = File::findFileFromFilename($filename);

        if (is_null($file)) {
            Yii::warning("FileAction 404 on (".$filename.")");
            throw new NotFoundHttpException("File not found.");
        }

        if (Yii::$app->user->isGuest) {
            $groupId = $userId = null;
        } else {
            $groupId = Yii::$app->user->identity->group_id;
            $userId = Yii::$app->user->id;
        }

        $sourcePath = Yii::getAlias($file->filePath->path . DIRECTORY_SEPARATOR . $file->fullName);

        if (!is_file($sourcePath)) {
            FileLog::add($file->id, FileLog::RESULT_FILE_NOT_FOUND, $userId);
            throw new NotFoundHttpException("File not found.");
        }

        if ($file->groupHasAccess($groupId) === false) {
            FileLog::add($file->id, FileLog::RESULT_PERMISSIONS, $userId);
            throw new NotFoundHttpException("File not found.");
        }

        // Serve File
        FileLog::add($file->id, FileLog::RESULT_SUCCESS, $userId);
        $options = [];
        if ($file->inline) {
            $options['inline'] = true;
        }

        return Yii::$app->response->sendFile($sourcePath, $file->fullAlias, $options);
    }
}