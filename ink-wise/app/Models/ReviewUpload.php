<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewUpload extends Model
{
    use HasFactory;

    protected $table = 'review_uploads';

    protected $guarded = ['id'];

    public function review()
    {
        return $this->belongsTo(CustomerReview::class, 'customer_review_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
