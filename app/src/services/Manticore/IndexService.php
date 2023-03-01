<?php
declare(strict_types=1);

namespace App\services\Manticore;


use App\forms\Manticore\IndexCreateForm;
use App\forms\Manticore\IndexDeleteForm;
use JsonException;
use Manticoresearch\Client;
use Manticoresearch\Index;
use Manticoresearch\Query\BoolQuery;
use Manticoresearch\Query\In;

/**
 * Class IndexService
 * @packaage App\services\Manticore
 * @author Aleksey Gusev <audetv@gmail.com>
 */
class IndexService
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(IndexCreateForm $form): void
    {
        $name = $form->name;
        if ($name === '') {
            $name = 'questions';
        }
        $index = new Index($this->client);
        $index->setName($name);
        $index->drop(true);

        $index->create(
            [
                'username' => ['type' => 'text'],
                'role' => ['type' => 'text'],
                'text' => ['type' => 'text'],
                'datetime' => ['type' => 'text'],
                'data_id' => ['type' => 'integer'],
                'parent_id' => ['type' => 'integer'],
                'type' => ['type' => 'integer'],
                'position' => ['type' => 'integer']
            ],
            [
                'morphology' => 'stem_ru'
            ]
        );
    }

    public function delete(IndexDeleteForm $form): void
    {
        $params = [
            'index' => $form->name,
            'body' => ['silent' => true]
        ];

        $this->client->indices()->drop($params);
    }

    public function index(): void
    {
        $params = ['index' => 'questions'];
        $this->client->indices()->truncate($params);

        $index = new Index($this->client);
        $index->setName('questions');

        $files = $this->readDir();
        foreach ($files as $file) {
            $doc = $this->readFile($file);
            $this->addQuestion($doc, $index);
        }
    }

    private function readFile(string $file): bool|string
    {
        return file_get_contents(__DIR__ . "/../../../data/$file");
    }

    private function readDir(): array
    {
        $arrFiles = array();

        $handle = opendir(__DIR__ . '/../../../data');
        if ($handle) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && $entry != ".gitignore") {
                    $arrFiles[] = $entry;
                }
            }
        }
        closedir($handle);

        return $arrFiles;
    }

    public function updateQuestionComments(mixed $id)
    {
        $index = $this->client->index('questions');
        $query = new BoolQuery();
        $query->should(new In('parent_id', [$id]));
        $query->should(new In('data_id', [$id]));

        $doc = $this->readFile(\Yii::$app->params['questions']['current']['file']);


        try {
            $topic = json_decode($doc, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo $file . ": " . $e->getMessage() . "\n";
        }

        $comments = [];
        foreach ($topic->comments as $key => $comment) {
            $comment->position = $key + 1;
            $comments[] = $comment;
            var_dump($comment);
        }


        $index->updateDocuments($topic->comments, $query);
    }

    public function updateQuestion(mixed $id): void
    {
        $this->deleteQuestion($id);

        $index = $this->client->index('questions');

        $doc = $this->readFile(\Yii::$app->params['questions']['current']['file']);
        $this->addQuestion($doc, $index);

    }

    public function deleteQuestion($id): void
    {
        $index = $this->client->index('questions');
        $query = new BoolQuery();
        $query->should(new In('parent_id', [$id]));
        $query->should(new In('data_id', [$id]));

        $index->deleteDocuments($query);
    }

    private function addQuestion($doc, Index $index): void
    {
        try {
            $topic = json_decode($doc, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo $file . ": " . $e->getMessage() . "\n";
        }

        $index->addDocument($topic->question);

        if ($topic->linked_question) {
            foreach ($topic->linked_question as $key => $linkedQuestion) {
                $linkedQuestion->position = $key + 1;
                $index->addDocument($linkedQuestion);
            }
        }

        foreach ($topic->comments as $key => $comment) {
            $comment->position = $key + 1;
            $index->addDocument($comment);
        }
    }
}
