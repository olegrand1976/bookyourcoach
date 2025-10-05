<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Générer du contenu avec Gemini
     */
    public function generateContent(string $prompt, array $options = []): ?string
    {
        try {
            $response = Http::timeout(30)->post(
                "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $options['temperature'] ?? 0.7,
                        'topK' => $options['topK'] ?? 40,
                        'topP' => $options['topP'] ?? 0.95,
                        'maxOutputTokens' => $options['maxTokens'] ?? 2048,
                    ],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Analyser des données avec contexte structuré
     */
    public function analyzeData(array $data, string $analysisType, string $instructions = ''): ?array
    {
        $prompt = $this->buildAnalysisPrompt($data, $analysisType, $instructions);
        
        $response = $this->generateContent($prompt, [
            'temperature' => 0.3, // Plus déterministe pour l'analyse
            'maxTokens' => 4096
        ]);

        if (!$response) {
            return null;
        }

        // Extraire le JSON de la réponse
        return $this->extractJsonFromResponse($response);
    }

    /**
     * Construire un prompt pour l'analyse
     */
    protected function buildAnalysisPrompt(array $data, string $analysisType, string $instructions): string
    {
        $dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return <<<PROMPT
Tu es un expert en analyse de données pour une plateforme de réservation de cours sportifs (natation, fitness, équitation, etc.).

Type d'analyse demandé : {$analysisType}

Instructions spécifiques :
{$instructions}

Données à analyser :
```json
{$dataJson}
```

Analyse ces données et retourne UNIQUEMENT un objet JSON valide (sans markdown, sans ```json) avec la structure suivante :
{
  "insights": [
    {
      "type": "prediction|recommendation|alert|opportunity",
      "priority": "high|medium|low",
      "title": "Titre court",
      "description": "Description détaillée",
      "impact": "Description de l'impact attendu",
      "confidence": 0-100 (pourcentage de confiance),
      "data": { objets de données supportant l'insight }
    }
  ],
  "summary": "Résumé général en 2-3 phrases",
  "nextActions": ["Action 1", "Action 2", "Action 3"]
}

Sois précis, factuel et actionnable. Base-toi uniquement sur les données fournies.
PROMPT;
    }

    /**
     * Extraire le JSON d'une réponse texte
     */
    protected function extractJsonFromResponse(string $response): ?array
    {
        // Nettoyer la réponse (enlever markdown, espaces, etc.)
        $cleaned = trim($response);
        $cleaned = preg_replace('/```json\s*/i', '', $cleaned);
        $cleaned = preg_replace('/```\s*$/i', '', $cleaned);
        $cleaned = trim($cleaned);

        try {
            $decoded = json_decode($cleaned, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse JSON from Gemini response', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Vérifier si le service est disponible
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Chat conversationnel avec historique
     */
    public function chat(array $messages, array $options = []): ?string
    {
        try {
            $contents = array_map(function ($msg) {
                return [
                    'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]]
                ];
            }, $messages);

            $response = Http::timeout(30)->post(
                "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $options['temperature'] ?? 0.9,
                        'maxOutputTokens' => $options['maxTokens'] ?? 2048,
                    ],
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini Chat Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
