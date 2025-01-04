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
        'age',
        'birth_date',
        'address',
        'origin_city_id',
        'origin_campus_id',
        'phone_number',
        'room_number_id',
        'status',
    ];

    public function originCities()
    {
        return $this->belongsTo(OriginCity::class);
    }

    public function originCampuses()
    {
        return $this->belongsTo(OriginCampus::class);
    }

    public function roomNumbers()
    {
        return $this->belongsTo(RoomNumber::class);
    }

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

    public function payments()
    {
        return $this->hasMany(Payment::class);
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
