<?php

namespace PeterVanDijck\Mockingbird\Console;

use PeterVanDijck\Mockingbird\Actions\RetrieveResult;
use Illuminate\Console\Command;

class LlamaParseResultCommand extends Command
{
    protected $signature = 'llamaparse:result {job_id}';
    protected $description = 'Get the markdown result of a completed LlamaParse job';

    public function handle(RetrieveResult $retrieveResult): int
    {
        $jobId = $this->argument('job_id');
        
        try {
            $markdown = $retrieveResult->handle($jobId);
            
            $this->line('--- MARKDOWN RESULT ---');
            $this->line($markdown);
            $this->line('--- END RESULT ---');
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}