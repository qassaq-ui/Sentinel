<?php

namespace App\Models;

use Database\Factories\InquiryOutcomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InquiryOutcome extends Model
{
    /** @use HasFactory<InquiryOutcomeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'fallback_name',
        'fallback_description',
        'ai_instruction',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InquiryOutcome $outcome): void {
            $outcome->name_key ??= "inquiry_outcomes.{$outcome->code}.name";
            $outcome->description_key ??= "inquiry_outcomes.{$outcome->code}.description";
        });
    }
}
