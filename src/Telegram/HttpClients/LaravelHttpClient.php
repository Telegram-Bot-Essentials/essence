<?php

namespace TelegramBotEssentials\Essence\Telegram\HttpClients;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\HttpClients\HttpClientInterface;
use TelegramBotEssentials\Essence\Support\GroupMessageOwners;
use Throwable;

class LaravelHttpClient implements HttpClientInterface
{
    protected int $timeOut = 30;

    protected int $connectTimeOut = 10;

    /** An ordinary call has no reason to hold a webhook request longer than this. */
    private const CALL_TIMEOUT = 15;

    private const CONNECT_TIMEOUT = 5;

    /** Sending a request again is only safe this many times, and only as allowed by shouldRetry(). */
    private const ATTEMPTS = 3;

    /** The longest rate limit worth waiting out in place, in seconds. */
    private const MAX_RETRY_AFTER = 3;

    public function send(
        string $url,
        string $method,
        array $headers = [],
        array $options = [],
        bool $isAsyncRequest = false
    ): ResponseInterface|PromiseInterface|null {
        try {
            $pending = $this->pendingRequest($headers, $options);
            $response = $this->dispatch($pending, $url, strtoupper($method), $options);

            GroupMessageOwners::capture($url, $options, $response);

            return $this->toPsrResponse($response);
        } catch (ConnectionException $e) {
            throw new TelegramSDKException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function pendingRequest(array $headers, array $options): PendingRequest
    {
        // Uploads and downloads may really take a while, and an upload's
        // stream is not safe to read twice.
        $transfersFile = isset($options['multipart']) || isset($options['sink']);

        $pending = Http::withHeaders($headers)
            ->timeout($transfersFile ? $this->timeOut : min($this->timeOut, self::CALL_TIMEOUT))
            ->connectTimeout(min($this->connectTimeOut, self::CONNECT_TIMEOUT));

        if (! $transfersFile) {
            $pending = $pending->retry(
                self::ATTEMPTS,
                fn (int $attempt, mixed $e) => $this->retryDelay($e),
                fn (Throwable $e) => $this->shouldRetry($e),
                throw: false,
            );
        }

        if (isset($options['sink'])) {
            $pending = $pending->sink($options['sink']);
        }

        return $pending;
    }

    /**
     * Only what Telegram cannot have acted on. A read timeout is not here: the
     * message may have arrived, and sending it again would deliver it twice.
     */
    private function shouldRetry(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return (bool) preg_match('/cURL error (6|7):|Connection timed out|Connection timeout/i', $e->getMessage());
        }

        $retryAfter = $this->retryAfter($e);

        return $retryAfter !== null && $retryAfter <= self::MAX_RETRY_AFTER;
    }

    private function retryDelay(mixed $e): int
    {
        $retryAfter = $this->retryAfter($e);

        return $retryAfter === null ? 200 : $retryAfter * 1000;
    }

    /** Seconds a 429 asks to wait; null for anything else. */
    private function retryAfter(mixed $e): ?int
    {
        if (! $e instanceof RequestException || $e->response->status() !== 429) {
            return null;
        }

        $seconds = $e->response->json('parameters.retry_after');

        return is_numeric($seconds) ? (int) $seconds : null;
    }

    private function dispatch(PendingRequest $pending, string $url, string $method, array $options): LaravelResponse
    {
        if (isset($options['multipart'])) {
            return $pending->asMultipart()->post($url, $options['multipart']);
        }

        if ($method === 'GET') {
            return $pending->get($url, $options['query'] ?? []);
        }

        if ($method === 'POST' && isset($options['form_params'])) {
            return $pending->asForm()->post($url, $options['form_params']);
        }

        if ($method === 'POST') {
            return $pending->asForm()->post($url, $options['form_params'] ?? []);
        }

        return $pending->send($method, $url, $options);
    }

    private function toPsrResponse(LaravelResponse $response): ResponseInterface
    {
        return new Response(
            $response->status(),
            $response->headers(),
            $response->body()
        );
    }

    public function getTimeOut(): int
    {
        return $this->timeOut;
    }

    public function setTimeOut(int $timeOut): static
    {
        $this->timeOut = $timeOut;

        return $this;
    }

    public function getConnectTimeOut(): int
    {
        return $this->connectTimeOut;
    }

    public function setConnectTimeOut(int $connectTimeOut): static
    {
        $this->connectTimeOut = $connectTimeOut;

        return $this;
    }
}
