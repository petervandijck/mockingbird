<?php

namespace PeterVanDijck\Mockingbird\Actions;

use PeterVanDijck\Mockingbird\Models\LlamaParseJob;
use PeterVanDijck\Mockingbird\LlamaParseService;

class CheckJobStatus
{
    public function __construct(
        private LlamaParseService $llamaParseService
    ) {}

    public function handle(string $jobId): array
    {
        // Get status from API
        $result = $this->llamaParseService->getJobStatusFromApi($jobId);
        
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
}