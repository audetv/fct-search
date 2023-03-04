<?php

declare(strict_types=1);

namespace App\Auth\Http\Action\V1\Auth\Join;

use App\Auth\Command\JoinByEmail\Confirm\Command;
use App\Auth\Command\JoinByEmail\Confirm\Handler;
use common\auth\Identity;
use Exception;
use Yii;
use yii\base\Action;
use yii\web\Response;

class ConfirmAction extends Action
{
    private Handler $handler;

    public function __construct($id, $controller, Handler $handler, $config = [])
    {
        parent::__construct($id, $controller, $config);
        $this->handler = $handler;
    }

    public function run(string $token): Response
    {
        $command = new Command();
        $command->token = $token ?? '';

        try {
            $user = $this->handler->handle($command);
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            Yii::$app->user->login(new Identity($user));
            return $this->controller->goHome();
        } catch (Exception $e) {
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        }
        return $this->controller->goHome();
    }
}
