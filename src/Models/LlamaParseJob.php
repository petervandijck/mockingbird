<?php

namespace PeterVanDijck\Mockingbird\Models;

use Illuminate\Database\Eloquent\Model;

class LlamaParseJob extends Model
{
    protected $fillable = [
        'job_id',
        'filename',
        'status',
        'result',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        $this->setTable(config('llamaparse.table_name', 'llama_parse_jobs'));
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['success', 'error']);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}