<?php

namespace App\Models;

use Database\Factories\InquirySettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $number_prefix
 * @property int $sequence_padding
 * @property bool $ai_screening_enabled
 * @property string|null $ai_screening_instructions
 */
#[Fillable([
    'number_prefix',
    'sequence_padding',
    'ai_screening_enabled',
    'ai_screening_instructions',
])]
class InquirySetting extends Model
{
    /** @use HasFactory<InquirySettingFactory> */
    use HasFactory;

    public const DEFAULT_SCREENING_INSTRUCTIONS = <<<'TEXT'
Accept good-faith reports connected to the company, its employees, contractors, or business activities concerning ethics, fraud, corruption, conflicts of interest, discrimination, harassment, retaliation, human rights, safety, environmental harm, theft, data misuse, or other misconduct.

Reject only clear spam, advertising, meaningless text, threats unrelated to reporting misconduct, and everyday household or personal service requests that have no connection to the company, such as a broken house key or inability to open a private apartment door.

When relevance is uncertain, accept the inquiry for human review.
TEXT;

    /** @var array<string, mixed> */
    protected $attributes = [
        'number_prefix' => 'KAZM',
        'sequence_padding' => 4,
        'ai_screening_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'sequence_padding' => 'integer',
            'ai_screening_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::query()->first() ?? new self([
            'ai_screening_instructions' => self::DEFAULT_SCREENING_INSTRUCTIONS,
        ]);
    }
}
