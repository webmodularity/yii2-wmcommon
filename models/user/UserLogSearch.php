<?php

namespace wmc\models\user;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UserLogSearch represents the model behind the search form about `wmu\models\UserLog`.
 */
class UserLogSearch extends UserLog
{
    public $created_at = "P1M";

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'action_type', 'result_type', 'app'], 'integer'],
            [['created_at'], 'safe'],
            [
                'ip',
                'match',
                'pattern' => '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/',
                'message' => 'Supports only exact IPv4 addresses!'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params) {
        $query = UserLog::find()
            ->joinWith('path')
            ->where(['user_id' => $this->user_id])
            ->orderBy(['created_at' => SORT_DESC]);

        $this->load($params);

        if (!empty($this->created_at)) {
            $query->recent($this->created_at);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'action_type' => $this->action_type,
            'result_type' => $this->result_type,
            'app' => $this->app,
            'ip' => $this->ipAsBinary()
        ]);

        return $dataProvider;
    }

    protected function ipAsBinary() {
        if (empty($this->ip)) {
            return null;
        }
        return Yii::$app->formatter->asBinaryIp($this->ip);
    }
}