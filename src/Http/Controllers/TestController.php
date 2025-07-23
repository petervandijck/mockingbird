<?php

namespace PeterVanDijck\Mockingbird\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PeterVanDijck\Mockingbird\Models\LlamaParseJob;
use PeterVanDijck\Mockingbird\LlamaParseService;

class TestController extends Controller
{
    public function test(LlamaParseService $llamaParseService)
    {
        try {
            // Upload the test file with webhook
            $webhookUrl = url(config('llamaparse.webhook_path', '/llamaparse/webhook'));
            $result = $llamaParseService->uploadDocument('test.pdf', $webhookUrl);
            
            $jobId = $result['job_id'] ?? $result['id'];
            
            $output = '<h1>LlamaParse Test</h1>';
            $output .= '<p><strong>Upload Status:</strong> Success</p>';
            $output .= '<p><strong>Job ID:</strong> ' . $jobId . '</p>';
            $output .= '<p><strong>Webhook URL:</strong> ' . $webhookUrl . '</p>';
            
            // Wait for completion (with shorter timeout for web request)
            $output .= '<p>Waiting for job completion...</p>';
            
            try {
                $status = $llamaParseService->waitForCompletion($jobId, 120); // 2 min timeout
                
                $output .= '<p><strong>Job Status:</strong> ' . $status['status'] . '</p>';
                
                if ($status['status'] === 'SUCCESS') {
                    $markdown = $llamaParseService->getMarkdownResult($jobId);
                    
                    $output .= '<h2>Markdown Result:</h2>';
                    $output .= '<pre style="background: #f5f5f5; padding: 20px; border-radius: 5px; overflow: auto; white-space: pre-wrap;">';
                    $output .= htmlspecialchars($markdown);
                    $output .= '</pre>';
                }
                
            } catch (\Exception $e) {
                $output .= '<p><strong>Timeout/Error waiting for completion:</strong> ' . $e->getMessage() . '</p>';
                $output .= '<p>You can check the status manually at: <a href="/llamaparse-status/' . $jobId . '">/llamaparse-status/' . $jobId . '</a></p>';
            }
            
            return $output;
            
        } catch (\Exception $e) {
            return '<h1>LlamaParse Test Failed</h1><p><strong>Error:</strong> ' . $e->getMessage() . '</p>';
        }
    }

    public function status($jobId, LlamaParseService $llamaParseService)
    {
        $job = LlamaParseJob::where('job_id', $jobId)->first();
        
        if (!$job) {
            return '<h1>Error</h1><p>Job not found</p>';
        }
        
        // Refresh status from API
        try {
            $llamaParseService->getJobStatus($jobId);
            $job->refresh();
        } catch (\Exception $e) {
            // Continue with stored status if API fails
        }
        
        $output = '<h1>Job Status</h1>';
        $output .= '<p><strong>Job ID:</strong> ' . $jobId . '</p>';
        $output .= '<p><strong>Filename:</strong> ' . $job->filename . '</p>';
        $output .= '<p><strong>Status:</strong> ' . $job->status . '</p>';
        $output .= '<p><strong>Created:</strong> ' . $job->created_at . '</p>';
        
        if ($job->status === 'success') {
            $output .= '<p><a href="/llamaparse-result/' . $jobId . '">View Result</a></p>';
        } else {
            $output .= '<p><a href="javascript:location.reload()">Refresh Status</a></p>';
        }
        
        return $output;
    }

    public function result($jobId, LlamaParseService $llamaParseService)
    {
        $job = LlamaParseJob::where('job_id', $jobId)->first();
        
        if (!$job) {
            return '<h1>Error</h1><p>Job not found</p>';
        }
        
        // If result not stored, fetch it
        if (!$job->result && $job->status === 'success') {
            try {
                $markdown = $llamaParseService->getMarkdownResult($jobId);
                $job->refresh();
            } catch (\Exception $e) {
                return '<h1>Error</h1><p>' . $e->getMessage() . '</p>';
            }
        }
        
        if (!$job->result) {
            return '<h1>Error</h1><p>No result available yet. Job status: ' . $job->status . '</p>';
        }
        
        $output = '<h1>Markdown Result</h1>';
        $output .= '<p><strong>Job ID:</strong> ' . $jobId . '</p>';
        $output .= '<p><strong>Filename:</strong> ' . $job->filename . '</p>';
        $output .= '<p><strong>Status:</strong> ' . $job->status . '</p>';
        $output .= '<pre style="background: #f5f5f5; padding: 20px; border-radius: 5px; overflow: auto; white-space: pre-wrap;">';
        $output .= htmlspecialchars($job->result);
        $output .= '</pre>';
        
        return $output;
    }
}