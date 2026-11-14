<?php

namespace TelegramBotEssentials\Essence\Web;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Where the web account model and its guard come from: the app's config. */
final class WebAccounts
{
    /** The guard essence defines itself when the app keeps the default. */
    public const OWN_GUARD = 'tbe-web';

    /** @return class-string<Model&TelegramAccount> */
    public static function model(): string
    {
        $model = config('tbe-essence.web.account_model');

        if (! is_string($model) || ! class_exists($model) || ! is_subclass_of($model, Model::class) || ! is_subclass_of($model, TelegramAccount::class)) {
            $shown = is_string($model) ? $model : get_debug_type($model);

            throw new LogicException(
                "The web panel signs in with tbe-essence.web.account_model ($shown), which must be an Eloquent model "
                .'implementing '.TelegramAccount::class.' (use the HasTelegramAccount trait).'
            );
        }

        return $model;
    }

    public static function guard(): string
    {
        $guard = config('tbe-essence.web.guard', self::OWN_GUARD);

        return is_string($guard) && $guard !== '' ? $guard : self::OWN_GUARD;
    }
}
