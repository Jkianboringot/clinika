<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Roles extends Model
{
    public $guarded = ['id'];

    public function users(){
        return  $this->BelongsTo(User::class);
    }
}
