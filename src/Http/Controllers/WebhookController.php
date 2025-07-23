<?php

namespace PeterVanDijck\Mockingbird\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use PeterVanDijck\Mockingbird\Models\LlamaParseJob;
use PeterVanDijck\Mockingbird\LlamaParseService;

class WebhookController extends Controller
{
    public function handle(Request $request, LlamaParseService $llamaParseService): JsonResponse
    {
        $payload = $request->all();
        
        Log::info('LlamaParse webhook received:', $payload);
        
        $jobId = $payload['job_id'] ?? null;
        $status = $payload['status'] ?? null;
        
        if ($jobId) {
            $job = LlamaParseJob::where('job_id', $jobId)->first();
            
            if ($job) {
                $dbStatus = match($status) {
                    'SUCCESS' => 'success',
                    'ERROR' => 'error',
                    'PROCESSING' => 'processing',
                    default => 'pending'
                };
                
                $job->update([
                    'status' => $dbStatus,
                    'metadata' => array_merge($job->metadata ?? [], $payload)
                ]);
                
                // If successful, fetch the result
                if ($status === 'SUCCESS') {
                    try {
                        $markdown = $llamaParseService->getMarkdownResult($jobId);
                        Log::info("Markdown result fetched for job {$jobId}");
                    } catch (\Exception $e) {
                        Log::error("Failed to fetch markdown for job {$jobId}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return response()->json(['status' => 'success']);
    }
}