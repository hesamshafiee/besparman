<?php
namespace App\Services\V1\ElasticSearch;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class SearchClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $index;
    private int $timeout;
    private int $retries;
    private int $retryMs;

    public function __construct(Config $config) // ← فقط Config تزریق می‌شود
    {
        $cfg = $config->get('search');

        $this->baseUrl = rtrim($cfg['base_url'], '/');
        $this->apiKey  = $cfg['api_key'];
        $this->index   = $cfg['index'];
        $this->timeout = (int) ($cfg['timeout'] ?? 10);
        $this->retries = (int) ($cfg['retries'] ?? 2);
        $this->retryMs = (int) ($cfg['retry_ms'] ?? 200);
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry($this->retries, $this->retryMs);
    }

    protected function indexPath(?string $index = null): string
    {
        return "/indexes/" . ($index ?: $this->index);
    }

    public function upsertDocument(array $document, ?string $index = null): array
    {
        $resp = $this->http()->post($this->indexPath($index) . '/documents', $document);
        if ($resp->failed()) throw new RequestException($resp);
        return $resp->json();
    }

    public function upsertMany(array $documents, ?string $index = null): array
    {
        $resp = $this->http()->post($this->indexPath($index) . '/documents', $documents);
        if ($resp->failed()) throw new RequestException($resp);
        return $resp->json();
    }

    public function search(array $body, ?string $index = null): array
    {
        $resp = $this->http()->post($this->indexPath($index) . '/_search', $body);
        if ($resp->failed()) throw new RequestException($resp);
        return $resp->json();
    }

    public function searchByContent(string $term, int $size = 10, ?string $index = null): array
    {
        return $this->search([
            'query' => ['match' => ['content' => $term]],
            'size'  => $size,
        ], $index);
    }

    public function delete(string $id, ?string $index = null): ?array
    {
        $resp = $this->http()->delete($this->indexPath($index) . '/documents/' . urlencode($id));
        if ($resp->status() === 404) return null;
        if ($resp->failed()) throw new RequestException($resp);
        return $resp->json();
    }
}
