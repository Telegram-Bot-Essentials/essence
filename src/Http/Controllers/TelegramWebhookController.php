<?php

namespace TelegramBotEssentials\Essence\Http\Controllers;

use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Exceptions\TbeLogicException;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;
use TelegramBotEssentials\Essence\Traits\CanCancelOldProcess;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramWebhookController extends Controller
{
    use CanCancelOldProcess;

    public function __invoke(Request $request)
    {
        $request->headers->set('Accept', 'application/json');
        App::setLocale(wHook()->bot()->settings->language);

        try {
            dependsOn(!wHook()->bot()->suspended && (is_null(wHook()->bot()->activated_until) || wHook()->bot()->activated_until->isFuture()), __('tbe::general.alerts.botIsOff'));
            if (!hasAccess()) dependsOn(wHook()->bot()->settings->bot_status, __('tbe::general.alerts.botIsOff'));
            $this->initializeOptions();
            $this->processUpdate();
        } catch (Exception $e) {
            exceptionHandler()->handle($e);
        }
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     */
    private function initializeOptions()
    {
        $commands = config('telegram-bot-essentials.commands') ?? [];

        foreach ($commands as $command) {
            if (!is_subclass_of($command, Command::class))
                throw new LogicException("ReplyKey {$command} is not a subclass of namespace Telegram\Bot\Commands\Command");
            wHook()->api()->addCommand($command);
        }

        $adminQueries = base_path('app/Telegram/CallbackQueries/Admin');
        $memberQueries = base_path('app/Telegram/CallbackQueries/Member');
        $adminStateAnswers = base_path('app/Telegram/StateAnswers/Admin');
        $memberStateAnswers = base_path('app/Telegram/StateAnswers/Member');
//        $adminReplyKeys = base_path('app/Telegram/ReplyKeys/Admin');
//        $memberReplyKeys = base_path('app/Telegram/ReplyKeys/Member');
        if (is_dir($adminQueries)) $this->autoLoadCallbackQueries($adminQueries);
        if (is_dir($memberQueries)) $this->autoLoadCallbackQueries($memberQueries);
        $this->autoLoadCallbackQueries(realpath(__DIR__ . '/../../Telegram/CallbackQueries/Member'));
        $this->autoLoadCallbackQueries(realpath(__DIR__ . '/../../Telegram/CallbackQueries/Admin'));

        if (is_dir($adminStateAnswers)) $this->autoLoadStateAnswers($adminStateAnswers);
        if (is_dir($memberStateAnswers)) $this->autoLoadStateAnswers($memberStateAnswers);
        $this->autoLoadStateAnswers(realpath(__DIR__ . '/../../Telegram/StateAnswers/Member'));
        $this->autoLoadStateAnswers(realpath(__DIR__ . '/../../Telegram/StateAnswers/Admin'));

        $this->addUserReplyKeys(config('telegram-bot-essentials.keyboard.admin') ?? []);
        $this->addUserReplyKeys(config('telegram-bot-essentials.keyboard.member') ?? []);
        $this->autoLoadReplyKeys(realpath(__DIR__ . '/../../Telegram/ReplyKeys/Member'));
        $this->autoLoadReplyKeys(realpath(__DIR__ . '/../../Telegram/ReplyKeys/Admin'));
    }

    /**
     * @param string $path
     * @return void
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadCallbackQueries(string $path): void
    {
        $namespace = $this->resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, CallbackQuery::class)) {
                callbackQueryBus()->addCallbackQuery($fqcn);
            }
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function resolveNamespace(string $path): string
    {
        if (str_starts_with($path, base_path('app'))) {
            $basePath = base_path('app');
            $baseNamespace = app()->getNamespace();
        } else {
            $basePath = realpath(__DIR__ . '/../../');
            $baseNamespace = 'TelegramBotEssentials\\Essence';
        }

        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $path);
        $relativeNamespace = str_replace('/', '\\', $relativePath);

        return trim(rtrim($baseNamespace, '\\') . '\\' . $relativeNamespace, '\\');
    }

    /**
     * @param string $path
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadStateAnswers(string $path): void
    {
        $namespace = $this->resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, StateAnswer::class)) {
                stateAnswerBus()->addStateAnswer($fqcn);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function addUserReplyKeys(array $replyKeys): void
    {
        foreach ($replyKeys as $replyKeyRow) {
            foreach ($replyKeyRow as $replyKey) {
                if (!is_subclass_of($replyKey, ReplyKey::class))
                    throw new LogicException("ReplyKey {$replyKey} is not a subclass of TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey");
                replyKeyBus()->addReplyKey($replyKey);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws LogicException
     */
    private function autoLoadReplyKeys(string $path): void
    {
        $namespace = $this->resolveNamespace($path);

        foreach (File::allFiles($path) as $file) {
            $fqcn = $namespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($fqcn) && is_subclass_of($fqcn, ReplyKey::class)) {
                replyKeyBus()->addReplyKey($fqcn);
            }
        }
    }

    /**
     * @throws LogicException
     * @throws TbeLogicException
     * @throws BindingResolutionException
     * @throws TelegramSDKException
     */
    private function processUpdate()
    {
        $commandProcessed = false;
        $keyProcessed = false;
        $answerProcessed = false;

        if (wHook()->update()->message) {
            if (str_starts_with(wHook()->update()->message->text, '/')) {
                $this->cancelOldProcess();
                wHook()->api()->processCommand(wHook()->update());
                $commandProcessed = true;
            } else {
                $keyProcessed = replyKeyBus()->processReplyKey();
                if (wHook()->user()->state)
                    $answerProcessed = stateAnswerBus()->processStateAnswers();
            }

            $requestIsInvalid = !($commandProcessed || $keyProcessed || $answerProcessed);
            if ($requestIsInvalid) {
                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => __('tbe::general.alerts.requestIsInvalid'),
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
            }
        } elseif (wHook()->update()->callbackQuery) {
            callbackQueryBus()->processCallbackQueries();
        }
    }
}
