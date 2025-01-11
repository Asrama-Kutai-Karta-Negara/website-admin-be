<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Gallery extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'galleries';

    protected $fillable = [
        'category_id',
        'title',
        'type',
        'kategori',
        'file',
        'file_name',
        'url'
    ];

    public function category()
    {
        return $this->belongsTo(CategoryGallery::class);
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

    public function scopeByTitle($query, $name)
    {
        return $query->where('title', 'like', "%{$name}%");
    }

    public function scopeFilterByCategoryId($query, $categoryId)
    {
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query;
    }
}
