<?php

namespace wmc\models;

/**
 * This is the ActiveQuery class for [[File]].
 *
 * @see File
 */
class FileQuery extends \yii\db\ActiveQuery
{

    public function active() {
        return $this->andWhere(['status' => 1]);
    }

    public function fromUrl($alias, $extension, $pathAlias = '') {
        return $this->andWhere(
            [
                'alias' => $alias,
                'extension' => $extension,
                'path_alias' => $pathAlias
            ]
        )->active();
    }
    
}