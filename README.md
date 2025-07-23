# Mockingbird - LlamaParse Laravel Package

A Laravel package for integrating with the LlamaParse document parsing service from LlamaIndex.

## Features

- Upload documents to LlamaParse for parsing
- Track job status and retrieve results
- Webhook support for async processing
- Console commands for testing and management
- Configurable table names and timeouts
- Laravel 11+ and 12+ support

## Installation

```bash
composer require petervandijck/mockingbird
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=llamaparse-config
```

### Publish and Run Migrations

```bash
php artisan vendor:publish --tag=llamaparse-migrations
php artisan migrate
```

## Configuration

Add your LlamaParse API key to your `.env` file:

```env
LLAMAPARSE_API_KEY=your_api_key_here
```

Additional configuration options available in `config/llamaparse.php`:

```php
return [
    'api_key' => env('LLAMAPARSE_API_KEY'),
    'api_url' => env('LLAMAPARSE_API_URL', 'https://api.cloud.llamaindex.ai/api/v1/parsing'),
    'webhook_path' => env('LLAMAPARSE_WEBHOOK_PATH', '/llamaparse/webhook'),
    'default_timeout' => env('LLAMAPARSE_DEFAULT_TIMEOUT', 300),
    'table_name' => env('LLAMAPARSE_TABLE_NAME', 'llama_parse_jobs'),
];
```

## Usage

### Using the Service

```php
use PeterVanDijck\Mockingbird\LlamaParseService;

$service = app(LlamaParseService::class);

// Upload a document
$result = $service->uploadDocument('path/to/document.pdf');
$jobId = $result['job_id'];

// Check job status
$status = $service->getJobStatus($jobId);

// Get markdown result (when job is complete)
$markdown = $service->getMarkdownResult($jobId);

// Wait for completion with timeout
$result = $service->waitForCompletion($jobId, 300);
```

### Using Actions

```php
use PeterVanDijck\Mockingbird\Actions\UploadDocument;
use PeterVanDijck\Mockingbird\Actions\CheckJobStatus;
use PeterVanDijck\Mockingbird\Actions\RetrieveResult;

// Upload document
$uploadAction = app(UploadDocument::class);
$result = $uploadAction->handle('document.pdf', 'https://your-webhook-url.com');

// Check status
$statusAction = app(CheckJobStatus::class);
$status = $statusAction->handle($jobId);

// Retrieve result
$resultAction = app(RetrieveResult::class);
$markdown = $resultAction->handle($jobId);
```

### Console Commands

Test the service:
```bash
php artisan llamaparse:test document.pdf --webhook
```

Check job status:
```bash
php artisan llamaparse:status {job_id}
```

Get job result:
```bash
php artisan llamaparse:result {job_id}
```

### Working with the Model

```php
use PeterVanDijck\Mockingbird\Models\LlamaParseJob;

// Find jobs
$job = LlamaParseJob::where('job_id', $jobId)->first();
$completedJobs = LlamaParseJob::where('status', 'success')->get();

// Check job status
if ($job->isComplete()) {
    // Job is either success or error
}

if ($job->isSuccessful()) {
    // Job completed successfully
    $result = $job->result;
}
```

### Webhook Support

The package includes webhook endpoints for receiving status updates:

- `POST /llamaparse/webhook` - Webhook endpoint for status updates
- `GET /test-llamaparse` - Test endpoint for trying the service
- `GET /llamaparse-status/{jobId}` - View job status
- `GET /llamaparse-result/{jobId}` - View job result

## Testing Routes

Visit `/test-llamaparse` in your browser to test document upload and parsing with a sample PDF file.

## Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+
- LlamaParse API key

## License

MIT License