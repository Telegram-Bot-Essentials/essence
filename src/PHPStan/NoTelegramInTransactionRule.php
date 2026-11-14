<?php

namespace TelegramBotEssentials\Essence\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Telegram must not be called directly inside DB::transaction(): a failed send
 * rolls back work that had nothing wrong with it, and a slow one holds its row
 * locks. Commit, then send, or use background(), which waits for the commit.
 *
 * Sees the closure itself, not what it calls: it catches the mistake where it
 * is usually made, and proves nothing about code further down.
 *
 * @implements Rule<StaticCall>
 */
class NoTelegramInTransactionRule implements Rule
{
    private const API_METHODS = '/^(send[A-Z]|edit[A-Z]|answer[A-Z]|delete[A-Z]|forward[A-Z]|copy[A-Z])/';

    private const HELPERS = ['notifyUser', 'ownerAlert'];

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->class instanceof Name || $node->class->getLast() !== 'DB'
            || ! $node->name instanceof Identifier || $node->name->toString() !== 'transaction') {
            return [];
        }

        $work = $node->getArgs()[0]->value ?? null;

        if (! $work instanceof Closure && ! $work instanceof ArrowFunction) {
            return [];
        }

        $errors = [];

        foreach ((new NodeFinder)->find($work, fn (Node $inner) => $this->isTelegramCall($inner)) as $call) {
            $errors[] = RuleErrorBuilder::message('Telegram is called inside DB::transaction(): a failed send would roll back the work. Send after the commit, or use background().')
                ->identifier('tbe.telegramInTransaction')
                ->line($call->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isTelegramCall(Node $node): bool
    {
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            return in_array($node->name->getLast(), self::HELPERS, true);
        }

        return $node instanceof MethodCall
            && $node->name instanceof Identifier
            && preg_match(self::API_METHODS, $node->name->toString()) === 1
            && $node->var instanceof MethodCall
            && $node->var->name instanceof Identifier
            && $node->var->name->toString() === 'api';
    }
}
