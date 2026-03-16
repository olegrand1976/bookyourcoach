<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Services\AI\ImagenService;
use App\Services\AI\SocialGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClubSocialGeneratorController extends Controller
{
    public function __construct(
        protected SocialGeneratorService $socialGenerator,
        protected ImagenService $imagen
    ) {
    }

    /**
     * Liste des posts du mois (planning). Scope par club si l'utilisateur a un club.
     */
    public function planning(Request $request): JsonResponse
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        $clubId = $club?->id;

        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $posts = SocialPost::query()
            ->forClub($clubId)
            ->forMonth($year, $month)
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (SocialPost $p) => $this->postToArray($p));

        return response()->json([
            'success' => true,
            'data' => ['posts' => $posts],
            'message' => 'Planning récupéré',
        ]);
    }

    /**
     * Génère le planning du mois (8 posts) et remplace les existants.
     */
    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        $clubId = $club?->id;

        $posts = $this->socialGenerator->generateMonthlyPlanning($clubId);
        $data = array_map(fn (SocialPost $p) => $this->postToArray($p), $posts);

        return response()->json([
            'success' => true,
            'data' => ['posts' => $data],
            'message' => count($posts) . ' posts générés',
        ]);
    }

    /**
     * Met à jour un post (texte, statut).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        $clubId = $club?->id;

        $post = SocialPost::query()->forClub($clubId)->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        $validated = $request->validate([
            'text' => 'sometimes|string|max:10000',
            'status' => 'sometimes|in:draft,validated',
        ]);

        if (array_key_exists('text', $validated)) {
            $post->text = $validated['text'];
        }
        if (array_key_exists('status', $validated)) {
            $post->status = $validated['status'];
        }
        $post->save();

        return response()->json([
            'success' => true,
            'data' => $this->postToArray($post->fresh()),
            'message' => 'Post mis à jour',
        ]);
    }

    /**
     * Régénère l'image du post avec le prompt stocké.
     */
    public function regenerateImage(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $club = $user->getFirstClub();
        $clubId = $club?->id;

        $post = SocialPost::query()->forClub($clubId)->find($id);
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post non trouvé'], 404);
        }

        $prompt = $post->image_prompt ?: 'Children in a friendly sports activity. ' . \App\Services\AI\ActivibeCmPrompt::IMAGE_STYLE_SUFFIX;
        $newPath = $this->imagen->generateImage($prompt, ['subDir' => 'social-posts']);
        if ($newPath === null) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de générer l\'image',
            ], 502);
        }

        $post->image_path = $newPath;
        $post->save();

        return response()->json([
            'success' => true,
            'data' => $this->postToArray($post->fresh()),
            'message' => 'Image régénérée',
        ]);
    }

    protected function postToArray(SocialPost $post): array
    {
        $imageUrl = null;
        if ($post->image_path) {
            $imageUrl = Storage::disk('public')->url($post->image_path);
        }
        return [
            'id' => $post->id,
            'club_id' => $post->club_id,
            'scheduled_at' => $post->scheduled_at->format('Y-m-d'),
            'type' => $post->type,
            'text' => $post->text,
            'image_prompt' => $post->image_prompt,
            'image_path' => $post->image_path,
            'image_url' => $imageUrl,
            'status' => $post->status,
            'created_at' => $post->created_at->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
        ];
    }
}
