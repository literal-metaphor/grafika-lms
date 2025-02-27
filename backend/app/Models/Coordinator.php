<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
    use HasUlids;

    protected $guarded = [];
}
