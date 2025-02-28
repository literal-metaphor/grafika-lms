<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    public function subject() {
        return $this->belongsTo(Subject::class);
    }
}
