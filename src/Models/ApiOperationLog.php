<?php

namespace NexusPlugin\TpOpenConnect\Models;

use Illuminate\Database\Eloquent\Model;

class ApiOperationLog extends Model
{
    protected $table = 'api_operation_logs';
    public $timestamps = false;
    protected $guarded = [];
}
