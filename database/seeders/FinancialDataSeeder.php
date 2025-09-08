<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Club;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;

class FinancialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clubs = Club::all();
        
        foreach ($clubs as $club) {
            // Créer des caisses pour chaque club
            $mainCashRegister = CashRegister::create([
                'club_id' => $club->id,
                'name' => 'Caisse Principale',
                'location' => 'Accueil',
                'is_active' => true,
                'current_balance' => 0.00
            ]);

            $snackCashRegister = CashRegister::create([
                'club_id' => $club->id,
                'name' => 'Caisse Snack',
                'location' => 'Snack Bar',
                'is_active' => true,
                'current_balance' => 0.00
            ]);

            // Créer des produits selon le type d'activité
            $this->createProductsForClub($club, $mainCashRegister, $snackCashRegister);
        }

        $this->command->info('✅ Données financières créées avec succès !');
    }

    private function createProductsForClub($club, $mainCashRegister, $snackCashRegister)
    {
        $categories = ProductCategory::all();
        
        // Produits communs (snack)
        $snackCategory = $categories->where('slug', 'snack')->first();
        if ($snackCategory) {
            $snackProducts = [
                ['name' => 'Café', 'price' => 2.50, 'cost_price' => 0.80, 'stock_quantity' => 100],
                ['name' => 'Thé', 'price' => 2.00, 'cost_price' => 0.60, 'stock_quantity' => 50],
                ['name' => 'Coca-Cola', 'price' => 3.00, 'cost_price' => 1.20, 'stock_quantity' => 80],
                ['name' => 'Eau', 'price' => 1.50, 'cost_price' => 0.50, 'stock_quantity' => 120],
                ['name' => 'Sandwich Jambon', 'price' => 5.50, 'cost_price' => 2.80, 'stock_quantity' => 30],
                ['name' => 'Salade César', 'price' => 7.50, 'cost_price' => 4.20, 'stock_quantity' => 20],
                ['name' => 'Chips', 'price' => 2.00, 'cost_price' => 1.00, 'stock_quantity' => 60],
                ['name' => 'Chocolat', 'price' => 1.80, 'cost_price' => 0.90, 'stock_quantity' => 40]
            ];

            foreach ($snackProducts as $productData) {
                Product::create(array_merge($productData, [
                    'club_id' => $club->id,
                    'category_id' => $snackCategory->id,
                    'description' => 'Produit de snack',
                    'min_stock' => 10,
                    'sku' => 'SNK-' . strtoupper(substr($productData['name'], 0, 3)),
                    'is_active' => true
                ]));
            }
        }

        // Produits spécifiques selon le type d'activité
        if ($club->activity_type_id === 1) { // Équitation
            $equipmentCategory = $categories->where('slug', 'equipment-equestrian')->first();
            if ($equipmentCategory) {
                $equestrianProducts = [
                    ['name' => 'Casque d\'équitation', 'price' => 89.90, 'cost_price' => 45.00, 'stock_quantity' => 15],
                    ['name' => 'Bottes d\'équitation', 'price' => 129.90, 'cost_price' => 65.00, 'stock_quantity' => 12],
                    ['name' => 'Gants d\'équitation', 'price' => 24.90, 'cost_price' => 12.00, 'stock_quantity' => 25],
                    ['name' => 'Bombe', 'price' => 45.90, 'cost_price' => 23.00, 'stock_quantity' => 20],
                    ['name' => 'Cravache', 'price' => 15.90, 'cost_price' => 8.00, 'stock_quantity' => 30],
                    ['name' => 'Éperons', 'price' => 35.90, 'cost_price' => 18.00, 'stock_quantity' => 18]
                ];

                foreach ($equestrianProducts as $productData) {
                    Product::create(array_merge($productData, [
                        'club_id' => $club->id,
                        'category_id' => $equipmentCategory->id,
                        'description' => 'Équipement équestre',
                        'min_stock' => 5,
                        'sku' => 'EQT-' . strtoupper(substr($productData['name'], 0, 3)),
                        'is_active' => true
                    ]));
                }
            }
        } else { // Natation
            $equipmentCategory = $categories->where('slug', 'equipment-swimming')->first();
            if ($equipmentCategory) {
                $swimmingProducts = [
                    ['name' => 'Lunettes de natation', 'price' => 19.90, 'cost_price' => 10.00, 'stock_quantity' => 30],
                    ['name' => 'Bonnet de bain', 'price' => 8.90, 'cost_price' => 4.50, 'stock_quantity' => 50],
                    ['name' => 'Maillot de bain', 'price' => 39.90, 'cost_price' => 20.00, 'stock_quantity' => 25],
                    ['name' => 'Serviette', 'price' => 24.90, 'cost_price' => 12.50, 'stock_quantity' => 20],
                    ['name' => 'Planche de natation', 'price' => 15.90, 'cost_price' => 8.00, 'stock_quantity' => 15],
                    ['name' => 'Palmes', 'price' => 29.90, 'cost_price' => 15.00, 'stock_quantity' => 12]
                ];

                foreach ($swimmingProducts as $productData) {
                    Product::create(array_merge($productData, [
                        'club_id' => $club->id,
                        'category_id' => $equipmentCategory->id,
                        'description' => 'Équipement aquatique',
                        'min_stock' => 5,
                        'sku' => 'SWM-' . strtoupper(substr($productData['name'], 0, 3)),
                        'is_active' => true
                    ]));
                }
            }
        }

        // Créer des transactions de démonstration
        $this->createSampleTransactions($club, $mainCashRegister, $snackCashRegister);
    }

    private function createSampleTransactions($club, $mainCashRegister, $snackCashRegister)
    {
        $user = User::where('role', 'club')->first();
        $products = Product::where('club_id', $club->id)->get();

        // Transactions des 30 derniers jours
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);
            
            // 2-5 transactions par jour
            $transactionsCount = rand(2, 5);
            
            for ($j = 0; $j < $transactionsCount; $j++) {
                $cashRegister = rand(0, 1) ? $mainCashRegister : $snackCashRegister;
                $productsInTransaction = $products->random(rand(1, 3));
                
                $totalAmount = 0;
                $transaction = Transaction::create([
                    'club_id' => $club->id,
                    'cash_register_id' => $cashRegister->id,
                    'user_id' => $user->id,
                    'type' => 'sale',
                    'amount' => 0, // Sera calculé après
                    'payment_method' => ['cash', 'card', 'transfer'][rand(0, 2)],
                    'description' => 'Vente de produits',
                    'reference' => 'TXN-' . str_pad($i * 10 + $j, 6, '0', STR_PAD_LEFT),
                    'processed_at' => $date->copy()->addHours(rand(8, 18))->addMinutes(rand(0, 59))
                ]);

                foreach ($productsInTransaction as $product) {
                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;
                    $discount = rand(0, 1) ? rand(0, 10) / 100 * $unitPrice : 0;
                    $totalPrice = ($unitPrice * $quantity) - $discount;
                    
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'item_name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'discount' => $discount
                    ]);
                    
                    $totalAmount += $totalPrice;
                    
                    // Mettre à jour le stock
                    $product->update(['stock_quantity' => $product->stock_quantity - $quantity]);
                }

                $transaction->update(['amount' => $totalAmount]);
            }
        }
    }
}