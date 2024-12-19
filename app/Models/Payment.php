<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'payments';

    protected $fillable = [
        'resident_id',
        'payment_evidence',
        'billing_date',
        'billing_amount',
        'status',
        'move_to_report'
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
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

    public function scopeFilterByResidentId($query, $residentId)
    {
        if ($residentId) {
            $query->where('resident_id', $residentId);
        }

        return $query;
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('move_to_report', $status);
    }
}
