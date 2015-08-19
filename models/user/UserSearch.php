<?php

namespace wmc\models\user;

use wmc\models\Person;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UserSearch represents the model behind the search form about `wmc\models\user\User`.
 */
class UserSearch extends User
{
    public $fullName;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'group_id', 'status'], 'integer'],
            [['username', 'email', 'fullName'], 'string'],
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

    public function beforeValidate() {
        return true;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $this->load($params);

        $userGroupWhere = ['<=', 'user_group.id', Yii::$app->user->identity->group_id];
        if ($this->status == User::STATUS_DELETED) {
            $query = User::find()->where($userGroupWhere)->deleted();
        } else {
            $query = User::find()->where($userGroupWhere)->notDeleted();
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]]
        ]);


        $dataProvider->sort->attributes['fullName'] = [
            'asc' => [Person::getTableSchema()->fullName . '.' . 'last_name' => SORT_ASC, Person::getTableSchema()->fullName . '.' . 'first_name' => SORT_ASC],
            'desc' => [Person::getTableSchema()->fullName . '.' . 'last_name' => SORT_DESC, Person::getTableSchema()->fullName . '.' . 'first_name' => SORT_DESC],
        ];

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            self::getTableSchema()->fullName . '.' .  'id' => $this->id,
            'group_id' => $this->group_id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', Person::getTableSchema()->fullName . '.first_name', $this->fullName])
            ->orFilterWhere(['like', Person::getTableSchema()->fullName . '.last_name', $this->fullName]);

        return $dataProvider;
    }
}