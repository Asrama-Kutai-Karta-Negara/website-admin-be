<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Resident extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'residents';

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'room_number',
        'status',
    ];

    public function scopeByStatus($query, $status)
    {
        if ($status === 'active') {
            return $query->where('status', 'active');
        } else {
            return $query->where('status', 'inactive');
        }

        return $query;
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
