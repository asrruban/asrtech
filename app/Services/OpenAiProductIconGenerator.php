<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProductIconGenerator
{
    public function __construct(
        private readonly ProductNameBrandExtractor $brands,
        private readonly StorageService $storage,
    ) {}

    /** @return array{url: string, brand: string} */
    public function generate(string $productName): array
    {
        $apiKey = trim((string) config('services.openai.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('OpenAI is not configured. Add OPENAI_API_KEY to the application environment.');
        }

        $brand = $this->brands->extract($productName);

        try {
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(180)
                ->retry(1, 500)
                ->post('/images/generations', [
                    'model' => (string) config('services.openai.image_model', 'gpt-image-2'),
                    'prompt' => implode(' ', [
                        'Create a polished square software marketplace icon.',
                        "The only product or brand context is \"{$brand}\".",
                        'Use an original visual concept and do not reproduce an official trademark logo.',
                        'Do not include descriptions, platform names, payment terms, module names, badges, or any other words.',
                        "If the design contains text, the only permitted text is exactly \"{$brand}\".",
                        'Use a centered symbol, crisp edges, balanced spacing, a professional modern color palette, and no mockup background.',
                    ]),
                    'size' => '1024x1024',
                    'quality' => 'medium',
                    'background' => 'opaque',
                    'output_format' => 'png',
                    'n' => 1,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException(
                'OpenAI could not generate the product icon right now. Check the API key, image-model access, organization verification, and network connection.',
                previous: $exception,
            );
        }

        $encoded = $response->json('data.0.b64_json');
        $image = is_string($encoded) ? base64_decode($encoded, true) : false;

        if (! is_string($image) || $image === '') {
            throw new RuntimeException('OpenAI returned an invalid product icon. Please try again.');
        }

        return [
            'url' => $this->storage->storeContents($image, 'products', 'png'),
            'brand' => $brand,
        ];
    }
}
