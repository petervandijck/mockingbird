<?php

namespace PeterVanDijck\Mockingbird\Console;

use PeterVanDijck\Mockingbird\Actions\UploadDocument;
use PeterVanDijck\Mockingbird\LlamaParseService;
use Illuminate\Console\Command;

class TestLlamaParseCommand extends Command
{
    protected $signature = 'llamaparse:test {file=test.pdf} {--webhook}';
    protected $description = 'Test LlamaParse service with a document';

    public function handle(UploadDocument $uploadDocument, LlamaParseService $llamaParseService): int
    {
        $filePath = $this->argument('file');
        $useWebhook = $this->option('webhook');

        $this->info("Testing LlamaParse with file: {$filePath}");

        try {
            $webhookUrl = $useWebhook ? url(config('llamaparse.webhook_path', '/llamaparse/webhook')) : null;

            $this->info('Uploading document...');
            $result = $uploadDocument->handle($filePath, $webhookUrl);

            $jobId = $result['job_id'] ?? $result['id'];
            $this->info("Upload successful! Job ID: {$jobId}");

            if ($useWebhook) {
                $this->info('Webhook URL provided. Check your logs for webhook notifications.');
                $this->info('You can also check job status manually with: php artisan llamaparse:status ' . $jobId);
            } else {
                $this->info('Waiting for job completion...');
                $status = $llamaParseService->waitForCompletion($jobId, config('llamaparse.default_timeout', 300));

                $this->info('Job completed! Getting markdown result...');
                $markdown = $llamaParseService->getMarkdownResult($jobId);

                $this->line('--- MARKDOWN RESULT ---');
                $this->line($markdown);
                $this->line('--- END RESULT ---');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}