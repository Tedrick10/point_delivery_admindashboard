<?php

namespace App\Services;

use App\Support\MyanmarTools\ZawgyiDetector;
use App\Support\Rabbit;
use Throwable;

/**
 * Canonical store = Unicode. Detect Zawgyi and convert so writers & readers see correct Myanmar text.
 */
class MyanmarTextService
{
    protected static ?ZawgyiDetector $detector = null;

    /** Skip keys that must never be font-converted. */
    protected static array $skipKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'old_password',
        'new_password',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'remember_token',
        'email',
        'contact_number',
        'phone',
        'username',
        'otp',
        'pin',
        'latitude',
        'longitude',
        'lat',
        'lng',
        'fcm_token',
        'player_id',
        'device_token',
        'image',
        'photo',
        'avatar',
        'file',
        'base64',
        'signature',
        'qr_code',
        'barcode',
        '_token',
        '_method',
    ];

    public function toUnicode(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        if (! $this->containsMyanmar($text)) {
            return $text;
        }

        if (! $this->looksLikeZawgyi($text)) {
            return $text;
        }

        try {
            return Rabbit::zg2uni($text);
        } catch (Throwable) {
            return $text;
        }
    }

    public function containsMyanmar(string $text): bool
    {
        return (bool) preg_match('/[\x{1000}-\x{109F}\x{AA60}-\x{AA7F}\x{A9E0}-\x{A9FF}]/u', $text);
    }

    public function looksLikeZawgyi(string $text): bool
    {
        // Strong Zawgyi-only code points / stacks.
        if (preg_match('/[\x{1033}\x{1034}\x{105A}\x{1060}-\x{1097}\x{1099}-\x{109D}]/u', $text)) {
            return true;
        }

        try {
            $score = $this->detector()->getZawgyiProbability($text);
            if (! is_finite($score)) {
                return false;
            }
            // Convert aggressively enough for short Zawgyi phrases (e.g. မဂၤလာပါ ≈ 0.54).
            if ($score >= 0.5) {
                return true;
            }
            if ($score <= 0.05) {
                return false;
            }
        } catch (Throwable) {
            // Fall through to regex scoring.
        }

        return $this->regexPrefersZawgyi($text);
    }

    /**
     * Recursively normalize string leaves in arrays (request / JSON payloads).
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function normalizeValue(mixed $value, ?string $key = null): mixed
    {
        if (is_string($value)) {
            if ($key !== null && $this->shouldSkipKey($key)) {
                return $value;
            }

            return $this->toUnicode($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $k => $v) {
            $keyStr = is_string($k) ? $k : null;
            if ($keyStr !== null && $this->shouldSkipKey($keyStr)) {
                $out[$k] = $v;
                continue;
            }
            $out[$k] = $this->normalizeValue($v, $keyStr);
        }

        return $out;
    }

    protected function shouldSkipKey(string $key): bool
    {
        $key = strtolower($key);
        if (in_array($key, self::$skipKeys, true)) {
            return true;
        }

        foreach (['password', 'token', 'secret', 'base64', '_token'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function detector(): ZawgyiDetector
    {
        return self::$detector ??= new ZawgyiDetector;
    }

    /**
     * Knayi / MyanFont-style regex scoring for ambiguous mid-range ML scores.
     */
    protected function regexPrefersZawgyi(string $content): bool
    {
        $ws = '[\x{0020}\t\r\n\f]';
        $unicodeRules = [
            '[ဃငဆဇဈဉညဋဌဍဎဏဒဓနဘရဝဟဠအ]်',
            'ျ[က-အ]ါ',
            'ျ[ါ-း]',
            '\x{1031}[^\x{1000}-\x{1021}\x{103b}\x{1040}\x{106a}\x{106b}\x{107e}-\x{1084}\x{108f}\x{1090}]',
            '\x{1031}$',
            '\x{1025}\x{102f}',
            '\x{103e}',
            '\x{103f}',
            '\x{100a}\x{103a}',
            '\x{1014}\x{103a}',
            '\x{1031}\x{1038}',
            '\x{1031}\x{102c}',
            '\x{103a}\x{1038}',
            '\x{1035}',
            '[\x{1050}-\x{1059}]',
            '^([\x{1000}-\x{1021}]\x{103c}|[\x{1000}-\x{1021}]\x{1031})',
        ];
        $zawgyiRules = [
            '\x{102c}\x{1039}',
            '\x{103a}\x{102c}',
            $ws.'(\x{103b}|\x{1031}|[\x{107e}-\x{1084}])[\x{1000}-\x{1021}]',
            '^(\x{103b}|\x{1031}|[\x{107e}-\x{1084}])[\x{1000}-\x{1021}]',
            '[\x{1000}-\x{1021}]\x{1039}[^\x{1000}-\x{1021}]',
            '\x{1025}\x{1039}',
            '\x{1039}\x{1038}',
            '[\x{102b}-\x{1030}\x{1031}\x{103a}\x{1038}](\x{103b}|[\x{107e}-\x{1084}])[\x{1000}-\x{1021}]',
            '\x{1036}\x{102f}',
            '[\x{1000}-\x{1021}]\x{1039}\x{1031}',
            '\x{1064}',
            '\x{1039}'.$ws,
            '\x{102c}\x{1031}',
            '[\x{102b}-\x{1030}\x{103a}\x{1038}]\x{1031}[\x{1000}-\x{1021}]',
            '\x{1031}\x{1031}',
            '\x{102f}\x{102d}',
            '\x{1039}$',
        ];

        $unicode = 0;
        $zawgyi = 0;
        foreach ($unicodeRules as $rule) {
            $unicode += preg_match('~'.$rule.'~u', $content) ? 1 : 0;
        }
        foreach ($zawgyiRules as $rule) {
            $zawgyi += preg_match('~'.$rule.'~u', $content) ? 1 : 0;
        }

        return $zawgyi > $unicode;
    }
}
