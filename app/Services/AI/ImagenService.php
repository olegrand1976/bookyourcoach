<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImagenService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(
        protected AiConfigService $aiConfig
    ) {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Génère une image à partir d'un prompt. Essaie les modèles dans l'ordre (fallback).
     * Retourne le chemin relatif stocké (storage) ou null en cas d'échec.
     */
    public function generateImage(string $prompt, array $options = []): ?string
    {
        $sampleCount = $options['sampleCount'] ?? 1;
        $subDir = $options['subDir'] ?? 'social-posts';

        foreach ($this->aiConfig->getImageModels() as $model) {
            $result = $this->callPredict($model, $prompt, $sampleCount);
            if ($result !== null) {
                return $this->storeImage($result, $subDir);
            }
            Log::warning('ImagenService: model failed', ['model' => $model]);
        }

        return null;
    }

    /**
     * Appel REST :predict
     */
    protected function callPredict(string $model, string $prompt, int $sampleCount): ?string
    {
        try {
            $response = Http::timeout(60)->post(
                "{$this->baseUrl}/models/{$model}:predict?key={$this->apiKey}",
                [
                    'instances' => [
                        ['prompt' => $prompt],
                    ],
                    'parameters' => [
                        'sampleCount' => $sampleCount,
                    ],
                ]
            );

            if (!$response->successful()) {
                Log::error('Imagen API Error', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            return $this->extractImageBase64($data);
        } catch (\Exception $e) {
            Log::error('Imagen Service Exception', [
                'model' => $model,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extrait le base64 de la réponse (format variable selon l'API).
     */
    protected function extractImageBase64(array $data): ?string
    {
        $predictions = $data['predictions'] ?? null;
        if (!is_array($predictions) || empty($predictions)) {
            return null;
        }
        $first = $predictions[0];
        if (isset($first['bytesBase64Encoded'])) {
            return $first['bytesBase64Encoded'];
        }
        if (isset($first['image']['bytesBase64Encoded'])) {
            return $first['image']['bytesBase64Encoded'];
        }
        if (isset($first['image']['imageBytes'])) {
            return $first['image']['imageBytes'];
        }
        return null;
    }

    /**
     * Stocke l'image base64 sur disque public et retourne le chemin relatif.
     */
    protected function storeImage(string $base64, string $subDir): string
    {
        $disk = Storage::disk('public');
        $filename = uniqid('img_', true) . '.png';
        $path = "{$subDir}/{$filename}";
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }
        $disk->put($path, $decoded);
        return $path;
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
