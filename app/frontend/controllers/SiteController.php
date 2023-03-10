<?php

namespace frontend\controllers;

use App\Contact\Http\Action\V1\Contact\ContactAction;
use App\forms\SearchForm;
use App\repositories\Question\QuestionStatsRepository;
use App\services\ManticoreService;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Site controller
 */
class SiteController extends Controller
{

    private ManticoreService $service;
    private QuestionStatsRepository $questionStatsRepository;

    public function __construct(
        $id,
        $module,
        ManticoreService $service,
        QuestionStatsRepository $questionStatsRepository,
        $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
        $this->questionStatsRepository = $questionStatsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'contact' => [
                'class' => ContactAction::class,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $results = null;
        $form = new SearchForm();
        $page = Yii::$app->request->get()['page'] ?? 1;

        try {
            if ($form->load(Yii::$app->request->queryParams) && $form->validate()) {
                $results = $this->service->search($form, $page);
            }
        } catch (\DomainException $e) {
            Yii::$app->errorHandler->logException($e);
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        if (!$results) {
            $list = $this->questionStatsRepository->findAllForList();
        }

        return $this->render('index', [
            'results' => $results ?? null,
            'list' => $list ?? null,
            'model' => $form,
        ]);
    }

    public function actionQuestion($id): string
    {
        $question = $this->service->question($id);

        return $this->render('question', [
            'question' => $question,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }
}
