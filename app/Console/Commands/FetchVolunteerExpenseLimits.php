<?php

namespace App\Console\Commands;

use App\Models\VolunteerExpenseLimit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchVolunteerExpenseLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'volunteer:fetch-expense-limits {year?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Récupère les plafonds de défraiement des volontaires depuis le site officiel belge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->argument('year') ?? now()->year;
        $url = 'https://conseilsuperieurvolontaires.belgium.be/fr/defraiements/plafonds-limites-indexes.htm';

        $this->info("Récupération des plafonds de défraiement pour l'année {$year}...");

        try {
            // Récupérer le contenu de la page
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                $this->error("Erreur lors de la récupération de la page : " . $response->status());
                Log::error('FetchVolunteerExpenseLimits: HTTP error', [
                    'status' => $response->status(),
                    'url' => $url
                ]);
                return 1;
            }

            $html = $response->body();

            // Parser le HTML
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);

            // Trouver le tableau
            $tables = $xpath->query('//table');

            if ($tables->length === 0) {
                $this->error("Aucun tableau trouvé sur la page");
                return 1;
            }

            $table = $tables->item(0);
            $rows = $xpath->query('.//tr', $table);

            $yearData = null;

            // Parcourir les lignes (en sautant l'en-tête)
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Sauter l'en-tête

                $cells = $xpath->query('.//td', $row);
                
                if ($cells->length < 3) continue;

                // Extraire le texte et chercher l'année
                $yearText = trim($cells->item(0)->textContent);
                
                // Vérifier si cette ligne contient l'année recherchée
                if (strpos($yearText, (string)$year) !== false) {
                    // Extraire les montants
                    $dailyAmount = $this->parseAmount($cells->item(1)->textContent);
                    $yearlyAmount = $this->parseAmount($cells->item(2)->textContent);
                    $yearlySpecial = $cells->length > 3 ? $this->parseAmount($cells->item(3)->textContent) : null;
                    $yearlyHealth = $cells->length > 4 ? $this->parseAmount($cells->item(4)->textContent) : null;

                    $yearData = [
                        'daily_amount' => $dailyAmount,
                        'yearly_amount' => $yearlyAmount,
                        'yearly_special_categories' => $yearlySpecial,
                        'yearly_health_sector' => $yearlyHealth,
                    ];

                    break;
                }
            }

            if (!$yearData) {
                $this->warn("Aucune donnée trouvée pour l'année {$year}");
                Log::warning('FetchVolunteerExpenseLimits: Année non trouvée', ['year' => $year]);
                return 1;
            }

            // Vérifier si les données existent déjà
            $existing = VolunteerExpenseLimit::where('year', $year)->first();

            if ($existing) {
                // Mettre à jour
                $existing->update([
                    'daily_amount' => $yearData['daily_amount'],
                    'yearly_amount' => $yearData['yearly_amount'],
                    'yearly_special_categories' => $yearData['yearly_special_categories'],
                    'yearly_health_sector' => $yearData['yearly_health_sector'],
                    'source_url' => $url,
                    'fetched_at' => now(),
                ]);

                $this->info("✓ Plafonds mis à jour pour {$year}");
            } else {
                // Créer
                VolunteerExpenseLimit::create([
                    'year' => $year,
                    'daily_amount' => $yearData['daily_amount'],
                    'yearly_amount' => $yearData['yearly_amount'],
                    'yearly_special_categories' => $yearData['yearly_special_categories'],
                    'yearly_health_sector' => $yearData['yearly_health_sector'],
                    'source_url' => $url,
                    'fetched_at' => now(),
                ]);

                $this->info("✓ Plafonds créés pour {$year}");
            }

            // Afficher les montants
            $this->table(
                ['Type', 'Montant'],
                [
                    ['Par jour', '€ ' . number_format($yearData['daily_amount'], 2, ',', ' ')],
                    ['Par an', '€ ' . number_format($yearData['yearly_amount'], 2, ',', ' ')],
                    ['Par an (catégories spéciales)', $yearData['yearly_special_categories'] ? '€ ' . number_format($yearData['yearly_special_categories'], 2, ',', ' ') : '-'],
                    ['Par an (secteur santé)', $yearData['yearly_health_sector'] ? '€ ' . number_format($yearData['yearly_health_sector'], 2, ',', ' ') : '-'],
                ]
            );

            Log::info('FetchVolunteerExpenseLimits: Plafonds récupérés avec succès', [
                'year' => $year,
                'data' => $yearData
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("Erreur : " . $e->getMessage());
            Log::error('FetchVolunteerExpenseLimits: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Parser un montant depuis le texte HTML (€ 42,31 -> 42.31)
     */
    private function parseAmount(?string $text): ?float
    {
        if (!$text || trim($text) === '-' || trim($text) === '') {
            return null;
        }

        // Supprimer le symbole €, les espaces, et remplacer la virgule par un point
        $cleaned = str_replace(['€', ' ', "\xc2\xa0"], '', trim($text));
        $cleaned = str_replace(',', '.', $cleaned);

        return floatval($cleaned);
    }
}
