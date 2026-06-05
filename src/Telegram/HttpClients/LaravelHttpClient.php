<?php

namespace TelegramBotEssentials\Essence\Telegram\HttpClients;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as LaravelResponse;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\HttpClients\HttpClientInterface;

class LaravelHttpClient implements HttpClientInterface
{
    protected int $timeOut = 30;

    protected int $connectTimeOut = 10;

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

            return $this->toPsrResponse($response);
        } catch (ConnectionException $e) {
            throw new TelegramSDKException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function pendingRequest(array $headers, array $options): PendingRequest
    {
        $pending = Http::withHeaders($headers)
            ->timeout($this->timeOut)
            ->connectTimeout($this->connectTimeOut);

        if (isset($options['sink'])) {
            $pending = $pending->sink($options['sink']);
        }

        return $pending;
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
