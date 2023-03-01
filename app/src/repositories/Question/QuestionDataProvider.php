<?php

namespace App\repositories\Question;

use App\models\Comment;
use Manticoresearch\Search;
use yii\data\BaseDataProvider;

class QuestionDataProvider extends BaseDataProvider
{

    /**
     * @var string|callable Имя столбца с ключом или callback-функция, возвращающие его
     */
    public $key;
    /**
     * @var Search
     */
    public Search $query;

    protected function prepareModels(): array
    {
        $models = [];
        $pagination = $this->getPagination();
        $sort = $this->getSort();

        // сортировка по умолчанию
        if ($sort->defaultOrder) {
            foreach ($sort->defaultOrder as $attribute => $value) {
                $direction = $value === SORT_ASC ? 'asc' : 'desc';
                $this->query->sort($attribute, $direction);
            }
        }

        if ($pagination === false) {
            // в случае отсутствия разбивки на страницы - прочитать все строки
            foreach ($this->query->get() as $hit) {
                $models[] = new Comment($hit->getData());
            }
        } else {
            // в случае, если разбивка на страницы есть - прочитать только одну страницу
            $pagination->totalCount = $this->getTotalCount();

            $this->query->limit($pagination->pageSize);
            $this->query->offset($pagination->getOffset());

            $limit = $pagination->getLimit();

            $data = $this->query->get();

            // Если количество записей меньше чем лимит,
            // то переписываем лимит, чтобы избежать ошибки Undefined array key при вызове $data->current()
            if ($data->count() < $limit) {
                $limit = $data->count();
            }

            for ($count = 0; $count < $limit; ++$count) {
                $models[] = new Comment($data->current()->getData());
                $data->next();
            }
        }

        return $models;
    }

    protected function prepareKeys($models): array
    {
        if ($this->key !== null) {
            $keys = [];

            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }

            return $keys;
        } else {
            return array_keys($models);
        }
    }

    protected function prepareTotalCount()
    {
        $count = $this->query->get()->getTotal();
        if ($count > 1000) {
            $this->query->maxMatches($count);
        }
        return $count;
    }
}
