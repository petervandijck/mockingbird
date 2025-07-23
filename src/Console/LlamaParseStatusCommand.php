<?php

namespace PeterVanDijck\Mockingbird\Console;

use PeterVanDijck\Mockingbird\Actions\CheckJobStatus;
use Illuminate\Console\Command;

class LlamaParseStatusCommand extends Command
{
    protected $signature = 'llamaparse:status {job_id}';
    protected $description = 'Check the status of a LlamaParse job';

    public function handle(CheckJobStatus $checkJobStatus): int
    {
        $jobId = $this->argument('job_id');
        
        try {
            $status = $checkJobStatus->handle($jobId);
            
            $this->info("Job ID: {$jobId}");
            $this->info("Status: " . $status['status']);
            
            if (isset($status['progress'])) {
                $this->info("Progress: " . $status['progress']);
            }
            
            if ($status['status'] === 'SUCCESS') {
                $this->info('Job completed successfully! You can get the result with:');
                $this->line("php artisan llamaparse:result {$jobId}");
            } elseif ($status['status'] === 'ERROR') {
                $this->error('Job failed with error: ' . json_encode($status));
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}