<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinancialReport extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'financial_reports';

    protected $fillable = [
        'title',
        'report_evidence',
        'report_file_name',
        'report_date',
        'report_amount',
        'report_categories'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function scopeByReportCategories($query, $category)
    {
        return $query->where('report_categories', $category);
    }

    public function scopeByTitle($query, $title)
    {
        return $query->where('title', 'like', "%{$title}%");
    }
}
