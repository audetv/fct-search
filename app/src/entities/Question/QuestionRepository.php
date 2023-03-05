<?php

declare(strict_types=1);

namespace App\entities\Question;

use App\Question\Entity\Question\Question;
use DomainException;
use yii\db\ActiveRecord;

class QuestionRepository
{
    public function get(int $id): array|ActiveRecord|Question
    {
        if (($question = Question::find()->andWhere(['id' => $id])->limit(1)->one()) === null) {
            throw new DomainException('Question is not found.');
        }
        return $question;
    }

    /**
     * @param int $data_id
     * @return array|ActiveRecord|Question
     */
    public function getByDataId(int $data_id): array|ActiveRecord|Question
    {
        if (($question = Question::find()->andWhere(['data_id' => $data_id])->limit(1)->one()) === null) {
            throw new DomainException('Question is not found.');
        }
        return $question;
    }

    public function save(Question $question): void
    {
        if (!$question->save()) {
            throw new \RuntimeException('Saving error.');
        }
    }
}
