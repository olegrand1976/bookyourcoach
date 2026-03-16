<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class AiConfigService
{
    /**
     * Liste des modèles texte par ordre de préférence (Stratégie > Rapidité).
     */
    public function getTextModels(): array
    {
        return config('services.gemini.text_models', [
            'gemini-1.5-pro',
            'gemini-1.5-flash',
            'gemini-2.5-flash',
        ]);
    }

    /**
     * Liste des modèles image par ordre de préférence (Imagen 4 standard > fast).
     */
    public function getImageModels(): array
    {
        return config('services.gemini.image_models', [
            'imagen-4.0-generate-001',
            'imagen-4.0-fast-generate-001',
        ]);
    }

    /**
     * Premier modèle texte (par défaut).
     */
    public function getDefaultTextModel(): string
    {
        $models = $this->getTextModels();
        return $models[0] ?? config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Premier modèle image (par défaut).
     */
    public function getDefaultImageModel(): string
    {
        $models = $this->getImageModels();
        return $models[0] ?? 'imagen-4.0-generate-001';
    }

    /**
     * Retourne un modèle texte qui répond (test léger), sinon null.
     */
    public function getWorkingTextModel(GeminiService $gemini): ?string
    {
        foreach ($this->getTextModels() as $model) {
            $response = $gemini->generateContent('Réponds par OK.', ['model' => $model, 'maxTokens' => 10]);
            if ($response !== null && trim($response) !== '') {
                return $model;
            }
            Log::warning('AiConfigService: text model failed', ['model' => $model]);
        }
        return null;
    }
}
