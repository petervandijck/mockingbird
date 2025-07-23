<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LlamaParse Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the LlamaParse document parsing service.
    |
    */

    'api_key' => env('LLAMAPARSE_API_KEY'),
    
    'api_url' => env('LLAMAPARSE_API_URL', 'https://api.cloud.llamaindex.ai/api/v1/parsing'),
    
    'webhook_path' => env('LLAMAPARSE_WEBHOOK_PATH', '/llamaparse/webhook'),
    
    'default_timeout' => env('LLAMAPARSE_DEFAULT_TIMEOUT', 300),
    
    'table_name' => env('LLAMAPARSE_TABLE_NAME', 'llama_parse_jobs'),
];