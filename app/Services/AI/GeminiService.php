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
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Générer du contenu avec Gemini
     * Options: temperature, topK, topP, maxTokens, model (override du modèle par appel)
     */
    public function generateContent(string $prompt, array $options = []): ?string
    {
        $model = $options['model'] ?? $this->model;
        try {
            $response = Http::timeout(30)->post(
                "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}",
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
     * Analyse économique et suggestions de déplacement pour le planning club (lendemain).
     * Retourne un tableau décodé depuis JSON ou null si indisponible / parse impossible.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function analyzeClubDailyPlanning(array $payload): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return null;
        }

        $prompt = <<<PROMPT
Tu es un conseiller opérationnel pour un club sportif. On te fournit le planning JSON d'une journée (demain) : cours, prix, élèves, la clé "teacher_parallel_conflicts" (sens interdit : même enseignant, plages qui se chevauchent — erreur bloquante à corriger), et des groupes "family_constraint_groups" d'élèves qui doivent rester PROCHES dans la journée (liens famille explicites, même nom normalisé parmi les présents, ou même instance d'abonnement partagée).

Tâches :
1) Si "teacher_parallel_conflicts" n'est pas vide : signale-le en premier dans le résumé et dans economic_inconsistencies (severity high), sans en inventer d'autres que ceux fournis.
2) Repère les incohérences ou fragilités ÉCONOMIQUES (revenu vs remplissage, cours presque vides, surcharge moniteur, durées/prix incohérents, déductions abonnement vs prix affiché, risques de no-show groupés, etc.) — uniquement à partir des données fournies.
3) Propose des déplacements de cours (changement de créneau le même jour ou suggestion de fusion/split si pertinent) pour OPTIMISER, en respectant STRICTEMENT : ne jamais éloigner deux élèves du même groupe family_constraint_groups (même groupe_id) : leurs cours doivent rester dans une fenêtre courte le même jour (idéalement même bloc 2–3h ou créneaux contigus). Si une solution éloignerait des membres d'un groupe, rejette-la ou propose une alternative safe.
4) Sois prudent : indique quand une suggestion nécessite validation humaine (conflit salle, dispo prof, niveau).

Réponds en FRANÇAIS avec UN SEUL objet JSON valide (pas de markdown, pas de ```), forme :
{
  "summary": "2-4 phrases",
  "economic_inconsistencies": [
    {
      "severity": "high|medium|low",
      "title": "string",
      "description": "string",
      "related_lesson_ids": [1,2]
    }
  ],
  "move_suggestions": [
    {
      "lesson_id": 0,
      "suggested_start_local": "YYYY-MM-DD HH:MM ou null si inconnu",
      "suggested_end_local": "YYYY-MM-DD HH:MM ou null",
      "rationale": "string",
      "family_constraint_safe": true,
      "human_role_required": ["club","teacher","parent"]
    }
  ],
  "limitations": ["ce que tu ne peux pas vérifier sans plus de données"]
}

Données :
{$json}
PROMPT;

        $response = $this->generateContent($prompt, [
            'temperature' => 0.25,
            'maxTokens' => 4096,
        ]);

        if (! $response) {
            return null;
        }

        return $this->extractJsonFromResponse($response);
    }

    /**
     * Court résumé pour le responsable club : conflit récurrence + créneaux alternatifs.
     *
     * @param  array<string, mixed>  $brief
     */
    public function summarizeRecurringPlanningAdvice(array $brief): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $json = json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return null;
        }

        $prompt = <<<PROMPT
Tu t'adresses à un responsable de club sportif. En 2 à 4 phrases en français, explique pourquoi le créneau récurrent demandé est refusé (sur la base de l'échantillon de conflits) et comment utiliser les créneaux alternatifs proposés (même enseignant et même élève, autre jour ou heure dans une plage du club). Ne invente rien qui n'apparaît pas dans le JSON. Pas de markdown.

JSON :
{$json}
PROMPT;

        return $this->generateContent($prompt, [
            'temperature' => 0.35,
            'maxTokens' => 500,
        ]);
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
