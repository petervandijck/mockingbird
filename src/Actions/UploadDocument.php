<?php

namespace PeterVanDijck\Mockingbird\Actions;

use PeterVanDijck\Mockingbird\Models\LlamaParseJob;
use PeterVanDijck\Mockingbird\LlamaParseService;

class UploadDocument
{
    public function __construct(
        private LlamaParseService $llamaParseService
    ) {}

    public function handle(string $filePath, ?string $webhookUrl = null): array
    {
        // Handle full paths by checking if file exists directly
        $fullPath = storage_path("app/{$filePath}");
        
        if (!file_exists($fullPath)) {
            throw new \Exception("File not found: {$fullPath}");
        }

        $fileContent = file_get_contents($fullPath);
        $fileName = basename($filePath);
        $mimeType = mime_content_type($fullPath);

        // Use the service to make the API call
        $result = $this->llamaParseService->uploadToApi($fileContent, $fileName, $mimeType, $webhookUrl);
        
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
}