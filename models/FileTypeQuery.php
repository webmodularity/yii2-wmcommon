<?php

namespace wmc\models;

/**
 * This is the ActiveQuery class for [[FileType]].
 *
 * @see FileType
 */
class FileTypeQuery extends \yii\db\ActiveQuery
{

    public function iconName($iconSetId) {
        return $this->joinWith('icons')->andOnCondition(['icon_set_id' => $iconSetId]);
    }

    public function inName($names) {
        return $this->alias('t')->joinWith('primaryExtension primaryExtension')->andWhere(['in', 't.name', $names]);
    }

    public function fromMimeType($mimeType) {
        return $this->joinWith(['mimeTypes', 'primaryExtension primaryExtension'])->where(['mime_type' => $mimeType]);
    }

}