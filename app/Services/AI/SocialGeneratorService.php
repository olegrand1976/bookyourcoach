<?php

namespace App\Services\AI;

use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SocialGeneratorService
{
    public const TRANSPARENCY_SUFFIX = "\n\nGénéré avec l'IA Activibe";

    public function __construct(
        protected GeminiService $gemini,
        protected ImagenService $imagen,
        protected AiConfigService $aiConfig
    ) {
    }

    /**
     * Greffe les mots-clés du style cartoon enfant sur tout prompt image.
     */
    public function logicUpdateImagePrompt(string $basePrompt): string
    {
        $style = ActivibeCmPrompt::IMAGE_STYLE_SUFFIX;
        $trimmed = trim($basePrompt);
        if (str_ends_with($trimmed, '.')) {
            return $trimmed . ' ' . $style;
        }
        return $trimmed . '. ' . $style;
    }

    /**
     * Génère 8 posts pour le mois en cours (alternance Conseil, Promo, Fun Fact).
     * Retourne les posts créés. Remplace les posts existants du mois pour le club.
     */
    public function generateMonthlyPlanning(?int $clubId = null): array
    {
        $year = (int) now()->year;
        $month = (int) now()->month;
        $monthStart = Carbon::createFromDate($year, $month, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $model = $this->aiConfig->getWorkingTextModel($this->gemini)
            ?? $this->aiConfig->getDefaultTextModel();

        $systemPrompt = ActivibeCmPrompt::getSystemPrompt();
        $userPrompt = $this->buildMonthlyPlanningPrompt($monthStart, $monthEnd);

        $fullPrompt = $systemPrompt . "\n\n---\n\n" . $userPrompt;
        $response = $this->gemini->generateContent($fullPrompt, [
            'model' => $model,
            'temperature' => 0.8,
            'maxTokens' => 8192,
        ]);

        if ($response === null) {
            Log::error('SocialGeneratorService: Gemini returned null');
            return [];
        }

        $postsData = $this->parsePlanningResponse($response);
        if (empty($postsData)) {
            return [];
        }

        $this->deleteExistingForMonth($clubId, $year, $month);
        $created = [];
        $suffix = self::TRANSPARENCY_SUFFIX;

        foreach ($postsData as $i => $item) {
            $text = $item['text'] ?? '';
            if ($text !== '' && !str_contains($text, 'Généré avec l\'IA Activibe')) {
                $text .= $suffix;
            }
            $imagePrompt = $this->logicUpdateImagePrompt($item['image_prompt'] ?? 'Children in a friendly sports activity.');
            $scheduledAt = $item['scheduled_at'] ?? $monthStart->copy()->addDays($i * 3)->format('Y-m-d');
            $type = $this->normalizeType($item['type'] ?? 'Conseil');

            $imagePath = $this->imagen->generateImage($imagePrompt, ['subDir' => 'social-posts']);
            if ($imagePath === null) {
                $imagePath = '';
            }

            $post = SocialPost::create([
                'club_id' => $clubId,
                'scheduled_at' => $scheduledAt,
                'type' => $type,
                'text' => $text,
                'image_prompt' => $imagePrompt,
                'image_path' => $imagePath,
                'status' => 'draft',
            ]);
            $created[] = $post;
        }

        return $created;
    }

    protected function buildMonthlyPlanningPrompt(Carbon $monthStart, Carbon $monthEnd): string
    {
        $monthName = $monthStart->locale('fr')->monthName;
        $year = $monthStart->year;

        return <<<PROMPT
Génère exactement 8 posts pour le mois de {$monthName} {$year} (du {$monthStart->format('d/m/Y')} au {$monthEnd->format('d/m/Y')}).

Répartition : 4 posts "Conseil", 2 posts "Promo", 2 posts "Fun Fact". Alterne les types (ex. Conseil, Promo, Conseil, Fun Fact, Conseil, Promo, Conseil, Fun Fact).
Chaque post doit avoir une date prévue (scheduled_at) répartie dans le mois, un texte (text) respectant la règle 80/20, et un prompt image (image_prompt) pour illustrer le post — le prompt image doit décrire la scène (piscine, gymnase, enfants, etc.) en anglais de préférence, le style cartoon sera ajouté automatiquement.

Retourne UNIQUEMENT un JSON valide (sans markdown, sans \`\`\`json) sous la forme d'un tableau d'objets :
[
  { "type": "Conseil", "scheduled_at": "YYYY-MM-DD", "text": "Texte du post...", "image_prompt": "Description de l'image en anglais..." },
  ...
]

Pas de conseils médicaux. Ton bienveillant et sécurisant pour les parents.
PROMPT;
    }

    protected function parsePlanningResponse(string $response): array
    {
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```json\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```\s*$/i', '', $cleaned);
        $cleaned = trim($cleaned);

        $decoded = json_decode($cleaned, true);
        if (!is_array($decoded)) {
            Log::warning('SocialGeneratorService: invalid JSON from Gemini', ['response' => substr($response, 0, 500)]);
            return [];
        }

        $out = [];
        foreach (array_slice($decoded, 0, 8) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $out[] = [
                'type' => $item['type'] ?? 'Conseil',
                'scheduled_at' => $item['scheduled_at'] ?? null,
                'text' => $item['text'] ?? '',
                'image_prompt' => $item['image_prompt'] ?? '',
            ];
        }
        return $out;
    }

    protected function normalizeType(string $type): string
    {
        $allowed = ['Conseil', 'Promo', 'Fun Fact'];
        $v = ucfirst(strtolower(trim($type)));
        if (in_array($v, $allowed, true)) {
            return $v;
        }
        if (str_contains($type, 'Promo')) {
            return 'Promo';
        }
        if (str_contains($type, 'Fun')) {
            return 'Fun Fact';
        }
        return 'Conseil';
    }

    protected function deleteExistingForMonth(?int $clubId, int $year, int $month): void
    {
        $query = SocialPost::query()
            ->whereYear('scheduled_at', $year)
            ->whereMonth('scheduled_at', $month);
        if ($clubId !== null) {
            $query->where('club_id', $clubId);
        } else {
            $query->whereNull('club_id');
        }
        $query->delete();
    }
}
