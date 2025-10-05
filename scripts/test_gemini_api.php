<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use Illuminate\Support\Facades\Http;

echo "🤖 Test de l'API Google Gemini\n";
echo "=====================================\n\n";

// Récupérer la clé API
$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
$model = $_ENV['GEMINI_MODEL'] ?? 'gemini-1.5-flash';

if (!$apiKey || $apiKey === 'your_gemini_api_key_here') {
    echo "❌ ERREUR: Clé API Gemini non configurée!\n";
    echo "   Veuillez ajouter GEMINI_API_KEY dans votre fichier .env\n\n";
    echo "   Pour obtenir une clé API:\n";
    echo "   1. Rendez-vous sur https://makersuite.google.com/app/apikey\n";
    echo "   2. Connectez-vous avec votre compte Google\n";
    echo "   3. Cliquez sur 'Get API Key'\n";
    echo "   4. Copiez la clé et ajoutez-la dans .env\n\n";
    exit(1);
}

echo "✅ Clé API trouvée: " . substr($apiKey, 0, 10) . "...\n";
echo "📦 Modèle: $model\n\n";

echo "🔄 Test de connexion à l'API Gemini...\n";

try {
    $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    $prompt = "Dis bonjour en une phrase et confirme que tu es opérationnel pour BookYourCoach!";
    
    $response = Http::timeout(15)->post(
        "$baseUrl/models/$model:generateContent?key=$apiKey",
        [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 100,
            ],
        ]
    );

    if ($response->successful()) {
        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Pas de réponse';
        
        echo "\n✅ SUCCÈS! L'API Gemini répond correctement.\n\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🤖 Réponse de Gemini:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo wordwrap($text, 70) . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "📊 Statistiques de la requête:\n";
        echo "   - Status: " . $response->status() . "\n";
        echo "   - Temps de réponse: ~" . round($response->handlerStats()['total_time'] ?? 0, 2) . "s\n";
        echo "   - Tokens utilisés: Estimation ~50-100\n\n";
        
        echo "🎉 L'analyse prédictive est prête à être utilisée!\n";
        echo "   Rendez-vous sur /club/dashboard pour voir l'analyse.\n\n";
        
        exit(0);
    } else {
        echo "\n❌ ERREUR: L'API a retourné un code " . $response->status() . "\n";
        echo "   Message: " . $response->body() . "\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "\n❌ ERREUR lors de la connexion:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
}
