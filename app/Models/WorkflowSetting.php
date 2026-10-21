<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowSetting extends Model
{
    protected $fillable = ['confirmation_hours'];

    public static function current(): self
    {
        return static::findOrFail(1);
    }
}
