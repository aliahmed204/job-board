<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'slug',
        'company', 'location','logo',
        'is_highlighted','is_active','content','apply_link'
    ];
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function booted()
    {
        static::creating(function (Listing $listing){
            $listing->slug = Str::slug($listing->title) . '-' . rand(1111, 9999);
        });
    }

    public function scopeIsActive(Builder $builder)
    {
        return $builder->where('is_active', true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clicks()
    {
        return $this->hasMany(Click::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
