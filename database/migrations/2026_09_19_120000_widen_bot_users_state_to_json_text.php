<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widens bot_users.state from varchar(255) to TEXT, and rewrites every
 * state stored in the old `TYPE#method?query` format as the JSON payload
 * encodeAnswerState() now writes, so a user mid-flow at deploy time keeps
 * working. The conversion is self-contained (it does not call the helpers)
 * because it must keep reading the old format after the helpers stop
 * writing it, and it is idempotent: rows already holding JSON are skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->text('state')->nullable()->change();
        });

        DB::table('bot_users')
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $json = is_string($row->state) ? $this->legacyToJson($row->state) : null;

                    if ($json !== null) {
                        DB::table('bot_users')->where('id', $row->id)->update(['state' => $json]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('bot_users')
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    if (is_string($row->state)) {
                        DB::table('bot_users')->where('id', $row->id)->update(['state' => $this->jsonToLegacy($row->state)]);
                    }
                }
            });

        Schema::table('bot_users', function (Blueprint $table) {
            $table->string('state')->nullable()->change();
        });
    }

    /** Null when the state is not in the old format (already converted). */
    private function legacyToJson(string $state): ?string
    {
        if (str_starts_with($state, '{')) {
            return null;
        }

        $parts = explode('#', $state);
        $methodAndParams = explode('?', $parts[1] ?? '');
        parse_str($methodAndParams[1] ?? '', $params);

        $payload = ['t' => $parts[0], 'm' => $methodAndParams[0]];

        if ($params !== []) {
            $payload['p'] = $params;
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: null;
    }

    /**
     * Back to `TYPE#method?query`. A state whose params do not fit a flat
     * query string, or that would not fit varchar(255), is dropped: losing
     * an in-flight flow beats failing the rollback.
     */
    private function jsonToLegacy(string $state): ?string
    {
        $payload = str_starts_with($state, '{') ? json_decode($state, true) : null;

        if (! is_array($payload) || ! isset($payload['t'])) {
            return strlen($state) <= 255 ? $state : null;
        }

        $params = $payload['p'] ?? [];
        $query = http_build_query(is_array($params) ? $params : []);
        $type = is_scalar($payload['t']) ? (string) $payload['t'] : '';
        $method = is_scalar($payload['m'] ?? null) ? (string) $payload['m'] : '';
        $legacy = $type.'#'.$method.($query !== '' ? '?'.$query : '');

        return strlen($legacy) <= 255 ? $legacy : null;
    }
};
