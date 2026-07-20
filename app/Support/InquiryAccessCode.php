<?php

namespace App\Support;

final class InquiryAccessCode
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const LENGTH = 12;

    public static function generate(): string
    {
        $code = '';
        $lastIndex = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < self::LENGTH; $index++) {
            $code .= self::ALPHABET[random_int(0, $lastIndex)];
        }

        return $code;
    }

    public static function normalize(string $code): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim($code))) ?? '';
    }

    public static function format(string $code): string
    {
        return implode('-', str_split(self::normalize($code), 4));
    }

    public static function hash(string $code): string
    {
        return hash_hmac('sha256', self::normalize($code), (string) config('app.key'));
    }
}
