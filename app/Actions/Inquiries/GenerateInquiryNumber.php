<?php

namespace App\Actions\Inquiries;

use App\Models\InquiryNumberSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class GenerateInquiryNumber
{
    public const DEFAULT_FORMAT = '{prefix}-{month}{year}-{sequence}';

    /**
     * @return array{
     *     number: string,
     *     prefix: string,
     *     period: string,
     *     sequence: int,
     *     format: string
     * }
     */
    public function handle(
        ?CarbonInterface $submittedAt = null,
        string $prefix = 'KAZM',
        string $format = self::DEFAULT_FORMAT,
        int $sequencePadding = 4,
    ): array {
        $submittedAt ??= now();
        $period = $submittedAt->format('my');
        $prefix = mb_strtoupper(trim($prefix));

        return DB::transaction(function () use ($format, $period, $prefix, $sequencePadding): array {
            $timestamp = now();

            DB::table('inquiry_number_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'period' => $period,
                'last_number' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $sequence = InquiryNumberSequence::query()
                ->where('prefix', $prefix)
                ->where('period', $period)
                ->lockForUpdate()
                ->firstOrFail();

            $nextNumber = $sequence->last_number + 1;
            $sequence->forceFill(['last_number' => $nextNumber])->save();
            $sequenceValue = str_pad((string) $nextNumber, $sequencePadding, '0', STR_PAD_LEFT);

            return [
                'number' => strtr($format, [
                    '{prefix}' => $prefix,
                    '{month}' => substr($period, 0, 2),
                    '{year}' => substr($period, 2, 2),
                    '{sequence}' => $sequenceValue,
                ]),
                'prefix' => $prefix,
                'period' => $period,
                'sequence' => $nextNumber,
                'format' => $format,
            ];
        }, attempts: 3);
    }

    public function __invoke(
        ?CarbonInterface $submittedAt = null,
        string $prefix = 'KAZM',
        string $format = self::DEFAULT_FORMAT,
        int $sequencePadding = 4,
    ): array {
        return $this->handle($submittedAt, $prefix, $format, $sequencePadding);
    }
}
