<?php

namespace App\Services;

class ProductNameBrandExtractor
{
    /** @var list<string> */
    private const GENERIC_TERMS = [
        'payment',
        'payments',
        'gateway',
        'module',
        'addon',
        'plugin',
        'integration',
        'connector',
        'extension',
        'software',
        'license',
        'template',
        'theme',
        'application',
        'app',
        'tool',
        'tools',
        'solution',
        'service',
        'services',
        'for',
        'with',
    ];

    public function extract(string $productName): string
    {
        $clean = trim((string) preg_replace('/[™®©|:–—-]+/u', ' ', $productName));
        $words = preg_split('/\s+/u', $clean) ?: [];
        $brand = [];

        foreach ($words as $word) {
            if (in_array(mb_strtolower(trim($word, " \t\n\r\0\x0B,.")), self::GENERIC_TERMS, true)) {
                break;
            }

            $brand[] = $word;
        }

        $result = trim(implode(' ', $brand));

        return $result !== '' ? $result : trim((string) ($words[0] ?? $productName));
    }
}
