<?php

namespace wmc\behaviors;

use Yii;
use yii\base\Behavior;
use yii\helpers\Inflector;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

class TagLikeBehavior extends Behavior
{
    public $_tags = [];

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave'
        ];
    }

    public function setTags($tags) {
        if (!is_array($tags)) {
            throw new InvalidConfigException("Tags property needs to be an array!");
        }

        foreach ($tags as $tagName => $tagConfig) {
            $this->_tags[$tagName] = [];

            // List Column - If not set will Inflector::singularize $tagName and concat _list {{$tagName}}_list
            $this->_tags[$tagName]['list'] = isset($tagConfig['list']) ? $tagConfig['list']
                : Inflector::singularize($tagName) . '_list';
            // Data Column - Defaults to data
            $this->_tags[$tagName]['data'] = isset($tagConfig['data']) ? $tagConfig['data'] : 'data';
            // Separator - Used to separate lists - defaults to a comma
            $this->_tags[$tagName]['separator'] = isset($tagConfig['separator']) ? $tagConfig['separator'] : ',';
            // Trim Values - Bool
            $this->_tags[$tagName]['trimValues'] = isset($tagConfig['trimValues']) && $tagConfig['trimValues'] === false ? false : true;
        }
    }

    public function afterSave() {
        foreach ($this->_tags as $tagName => $tagConfig) {
            $list = $tagConfig['list'];
            $tags = static::splitTags($this->owner->$list, $tagConfig['separator'], $tagConfig['trimValues']);
            $methodName = "get" . Inflector::camelize($tagName);
            $links = $this->owner->$methodName()->link;
            // Getting link column name while assuming we are pointing to column named id
            $link = array_shift(array_keys($links));
            $modelClass = $this->owner->$methodName()->modelClass;
            $existingTags = ArrayHelper::getColumn($modelClass::find()->select([$tagConfig['data']])->where([$link => $this->owner->id])->asArray()->all(), $tagConfig['data']);
            $tagsAdd = array_udiff($tags, $existingTags, 'strcasecmp');
            $tagsDelete = array_udiff($existingTags, $tags, 'strcasecmp');

            foreach ($tagsDelete as $tagDelete) {
                $unlinkTag = $modelClass::find()->where([$link => $this->owner->id, $tagConfig['data'] => $tagDelete])->one();
                if (!empty($unlinkTag)) {
                    $unlinkTag->delete();
                }
            }

            foreach ($tagsAdd as $tagAdd) {
                if (!in_array(strtolower($tagAdd), array_map('strtolower', $existingTags))) {
                    $tag = new $modelClass([$link => $this->owner->id, $tagConfig['data'] => $tagAdd]);
                    if (!$tag->save()) {
                        Yii::error("Failed to save new Tag on tagsAdd! Tag: (".$tagAdd.") with errors: (".VarDumper::dumpAsString($tag->getErrors()).")", 'tagLike');
                    }
                }
            }

            $this->owner->$list = implode($tagConfig['separator'], $tagsAdd);
        }
    }

    /**
     * Returns an array of tags from string using specified separator
     * @param $tagList string Tags split by separator
     * @param $separator string String used to split tags, defaults to comma
     * @param $trimValues bool If set to true (default) will trim spaces off front and back of tags
     * @return array Array of tags
     */

    public static function splitTags($tagList, $separator = ',', $trimValues = true) {
        $tags = empty($tagList) ? [] : explode($separator, $tagList);
        if (count($tags) > 0 && $trimValues) {
            return array_map('trim', $tags);
        } else {
            return $tags;
        }
    }

}