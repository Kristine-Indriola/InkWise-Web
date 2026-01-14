<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivedSalesReport extends Model
{
    protected $table = 'sales_report_archives';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
