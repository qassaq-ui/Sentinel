<?php

namespace App\Models;

use Database\Factories\InquiryCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InquiryCategory extends Model
{
    /** @use HasFactory<InquiryCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'fallback_name',
        'fallback_description',
        'review_days',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InquiryCategory $category): void {
            $uuid = $category->uuid ?? (string) Str::uuid();

            $category->uuid = $uuid;
            $category->name_key ??= "inquiry_categories.{$uuid}.name";
            $category->description_key ??= "inquiry_categories.{$uuid}.description";
        });
    }
}
