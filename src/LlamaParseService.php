<?php

namespace PeterVanDijck\Mockingbird;

use PeterVanDijck\Mockingbird\Models\LlamaParseJob;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LlamaParseService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('llamaparse.api_key');
        $this->baseUrl = config('llamaparse.api_url', 'https://api.cloud.llamaindex.ai/api/v1/parsing');
    }

    public function uploadDocument(string $filePath, ?string $webhookUrl = null): array
    {
        // Handle full paths by checking if file exists directly
        $fullPath = storage_path("app/{$filePath}");
        
        if (!file_exists($fullPath)) {
            throw new \Exception("File not found: {$fullPath}");
        }

        $fileContent = file_get_contents($fullPath);
        $fileName = basename($filePath);
        $mimeType = mime_content_type($fullPath);

        $result = $this->uploadToApi($fileContent, $fileName, $mimeType, $webhookUrl);
        $jobId = $result['job_id'] ?? $result['id'];

        // Store job in database
        LlamaParseJob::create([
            'job_id' => $jobId,
            'filename' => $fileName,
            'status' => 'pending',
            'metadata' => $result
        ]);

        return $result;
    }

    public function uploadToApi(string $fileContent, string $fileName, string $mimeType, ?string $webhookUrl = null): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'accept' => 'application/json',
        ])->attach('file', $fileContent, $fileName)
          ->post("{$this->baseUrl}/upload", $webhookUrl ? ['webhook_url' => $webhookUrl] : []);

        if (!$response->successful()) {
            throw new \Exception("Upload failed: " . $response->body());
        }

        return $response->json();
    }

    public function getJobStatus(string $jobId): array
    {
        $result = $this->getJobStatusFromApi($jobId);
        
        // Update job status in database
        $job = LlamaParseJob::where('job_id', $jobId)->first();
        if ($job) {
            $status = match($result['status']) {
                'SUCCESS' => 'success',
                'ERROR' => 'error',
                'PROCESSING' => 'processing',
                default => 'pending'
            };
            
            $job->update([
                'status' => $status,
                'metadata' => array_merge($job->metadata ?? [], $result)
            ]);
        }

        return $result;
    }

    public function getJobStatusFromApi(string $jobId): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'accept' => 'application/json',
        ])->get("{$this->baseUrl}/job/{$jobId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to get job status: " . $response->body());
        }

        return $response->json();
    }

    public function getMarkdownResult(string $jobId): string
    {
        $markdown = $this->getMarkdownFromApi($jobId);
        
        // Store result in database
        $job = LlamaParseJob::where('job_id', $jobId)->first();
        if ($job) {
            $job->update([
                'result' => $markdown,
                'status' => 'success'
            ]);
        }

        return $markdown;
    }

    public function getMarkdownFromApi(string $jobId): string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'accept' => 'application/json',
        ])->get("{$this->baseUrl}/job/{$jobId}/result/markdown");

        if (!$response->successful()) {
            throw new \Exception("Failed to get markdown result: " . $response->body());
        }

        return $response->json()['markdown'] ?? $response->body();
    }

    public function waitForCompletion(string $jobId, int $maxWaitSeconds = 300): array
    {
        $startTime = time();
        
        while (time() - $startTime < $maxWaitSeconds) {
            $status = $this->getJobStatus($jobId);
            
            if ($status['status'] === 'SUCCESS') {
                return $status;
            }
            
            if ($status['status'] === 'ERROR') {
                throw new \Exception("Job failed: " . json_encode($status));
            }
            
            sleep(5);
        }
        
        throw new \Exception("Job timed out after {$maxWaitSeconds} seconds");
    }
}