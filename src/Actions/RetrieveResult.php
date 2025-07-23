<?php

namespace PeterVanDijck\Mockingbird\Actions;

use PeterVanDijck\Mockingbird\Models\LlamaParseJob;
use PeterVanDijck\Mockingbird\LlamaParseService;

class RetrieveResult
{
    public function __construct(
        private LlamaParseService $llamaParseService
    ) {}

    public function handle(string $jobId): string
    {
        // Get markdown result from API
        $markdown = $this->llamaParseService->getMarkdownFromApi($jobId);
        
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
}