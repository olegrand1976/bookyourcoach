# 💰 DASHBOARD FINANCIER COMPLET - CLUBS MULTI-ACTIVITÉS

## 🎯 Fonctionnalités Financières Demandées

### **1. Chiffres Clés Financiers**
- **CA Total** : Chiffre d'affaires global du club
- **CA par Discipline** : Revenus par discipline (Dressage, Aquagym, etc.)
- **CA par Type d'Activité** : Équitation vs Natation
- **CA par Période** : Mensuel, trimestriel, annuel
- **Évolution** : Comparaison avec périodes précédentes

### **2. Activités Annexes**
- **Snack/Restauration** : Vente de boissons, snacks, repas
- **Vente de Matériel** : Équipements, vêtements, accessoires
- **Services Supplémentaires** : Soins, transport, hébergement
- **Abonnements** : Adhésions, cartes de fidélité

### **3. Système de Caisse En Ligne**
- **Enregistrement des Ventes** : Interface de caisse moderne
- **Paiements Multiples** : Espèces, CB, virement, chèque
- **Gestion des Stocks** : Inventaire des produits
- **Facturation** : Génération automatique de factures
- **Rapports de Caisse** : Clôture quotidienne/mensuelle

### **4. Visualisations Avancées**
- **Graphiques de CA** : Évolution temporelle
- **Répartition par Source** : Disciplines, annexes, abonnements
- **Analyse de Rentabilité** : Coûts vs Revenus par activité
- **Prévisions** : Projections basées sur l'historique

---

## 🏗️ Architecture Technique à Implémenter

### **1. Nouvelles Tables de Base de Données**

#### **Table : `cash_registers` (Caisses)**
```sql
- id (PK)
- club_id (FK)
- name (varchar) : "Caisse Principale", "Caisse Snack"
- location (varchar) : "Accueil", "Snack Bar"
- is_active (boolean)
- current_balance (decimal) : Solde actuel
- last_closing_at (datetime) : Dernière clôture
- created_at, updated_at
```

#### **Table : `product_categories` (Catégories de Produits)**
```sql
- id (PK)
- name (varchar) : "Snack", "Matériel", "Vêtements"
- slug (varchar) : "snack", "equipment", "clothing"
- description (text)
- icon (varchar) : "🍔", "🏇", "👕"
- is_active (boolean)
- created_at, updated_at
```

#### **Table : `products` (Produits/Services)**
```sql
- id (PK)
- club_id (FK)
- category_id (FK)
- name (varchar) : "Café", "Casque d'équitation"
- description (text)
- price (decimal) : Prix de vente
- cost_price (decimal) : Prix d'achat
- stock_quantity (integer) : Stock disponible
- min_stock (integer) : Stock minimum
- sku (varchar) : Code produit
- is_active (boolean)
- created_at, updated_at
```

#### **Table : `transactions` (Transactions de Caisse)**
```sql
- id (PK)
- club_id (FK)
- cash_register_id (FK)
- user_id (FK) : Utilisateur qui a effectué la transaction
- type (enum) : "sale", "refund", "expense", "deposit"
- amount (decimal) : Montant
- payment_method (enum) : "cash", "card", "transfer", "check"
- description (text) : Description de la transaction
- reference (varchar) : Référence (facture, etc.)
- metadata (json) : Données supplémentaires
- processed_at (datetime) : Date/heure de traitement
- created_at, updated_at
```

#### **Table : `transaction_items` (Articles des Transactions)**
```sql
- id (PK)
- transaction_id (FK)
- product_id (FK) : Peut être null pour services
- quantity (integer) : Quantité
- unit_price (decimal) : Prix unitaire
- total_price (decimal) : Prix total
- discount (decimal) : Remise appliquée
- created_at, updated_at
```

#### **Table : `financial_reports` (Rapports Financiers)**
```sql
- id (PK)
- club_id (FK)
- report_type (enum) : "daily", "monthly", "yearly"
- period_start (date)
- period_end (date)
- total_revenue (decimal)
- total_expenses (decimal)
- net_profit (decimal)
- revenue_by_discipline (json)
- revenue_by_category (json)
- transaction_count (integer)
- generated_at (datetime)
- created_at, updated_at
```

### **2. Modèles Laravel**

#### **CashRegister Model**
```php
- relationships: club(), transactions(), dailyReports()
- scopes: active(), byClub()
- methods: getCurrentBalance(), getDailyTotal(), closeRegister()
```

#### **Product Model**
```php
- relationships: club(), category(), transactionItems()
- scopes: active(), byCategory(), lowStock()
- methods: updateStock(), getProfitMargin(), isLowStock()
```

#### **Transaction Model**
```php
- relationships: club(), cashRegister(), user(), items()
- scopes: byDate(), byType(), byPaymentMethod()
- methods: calculateTotal(), generateReceipt(), refund()
```

### **3. Contrôleurs API**

#### **FinancialDashboardController**
- `getOverview()` : Vue d'ensemble financière
- `getRevenueByDiscipline()` : CA par discipline
- `getRevenueByPeriod()` : CA par période
- `getAncillaryRevenue()` : Revenus annexes
- `getProfitabilityAnalysis()` : Analyse de rentabilité

#### **CashRegisterController**
- `index()` : Liste des caisses
- `show($id)` : Détails d'une caisse
- `processTransaction()` : Traiter une transaction
- `closeRegister($id)` : Clôturer une caisse
- `getDailyReport($id)` : Rapport quotidien

#### **ProductController**
- `index()` : Liste des produits
- `store()` : Créer un produit
- `update($id)` : Modifier un produit
- `updateStock($id)` : Mettre à jour le stock
- `getLowStock()` : Produits en rupture

### **4. Interface Frontend**

#### **Dashboard Financier**
- **Vue d'ensemble** : CA total, évolution, comparaisons
- **Graphiques** : Évolution temporelle, répartition par source
- **Tableaux** : CA par discipline, revenus annexes
- **Indicateurs** : KPIs financiers, alertes

#### **Interface de Caisse**
- **Sélection de produits** : Interface tactile moderne
- **Calcul automatique** : Totaux, taxes, remises
- **Paiements multiples** : Espèces, CB, virement
- **Impression de tickets** : Reçus automatiques
- **Gestion des stocks** : Mise à jour en temps réel

#### **Gestion des Produits**
- **Catalogue** : Liste des produits avec images
- **Gestion des stocks** : Alertes de rupture
- **Prix et marges** : Calcul automatique des profits
- **Catégories** : Organisation par type de produit

### **5. Fonctionnalités Avancées**

#### **Analyse de Rentabilité**
- **Coûts par discipline** : Calcul des coûts directs/indirects
- **Marge par produit** : Profitabilité des ventes
- **ROI des activités** : Retour sur investissement
- **Prévisions** : Projections basées sur l'historique

#### **Gestion des Stocks**
- **Alertes automatiques** : Rupture de stock
- **Commandes automatiques** : Réapprovisionnement
- **Inventaire** : Comptage périodique
- **Pertes** : Gestion des invendus/cassés

#### **Rapports et Exports**
- **Rapports quotidiens** : Clôture de caisse
- **Rapports mensuels** : Synthèse financière
- **Exports** : CSV, PDF, Excel
- **Tableaux de bord** : Visualisations interactives

---

## 🚀 Plan d'Implémentation

### **Phase 1 : Structure de Base Financière**
1. Créer les migrations pour les tables financières
2. Créer les modèles avec relations
3. Créer les contrôleurs API de base

### **Phase 2 : Système de Caisse**
1. Interface de caisse en ligne
2. Gestion des transactions
3. Calculs automatiques et totaux

### **Phase 3 : Dashboard Financier**
1. Visualisations des chiffres clés
2. Graphiques d'évolution
3. Analyse par discipline et période

### **Phase 4 : Gestion des Produits**
1. Catalogue de produits
2. Gestion des stocks
3. Alertes et réapprovisionnement

### **Phase 5 : Rapports Avancés**
1. Rapports automatisés
2. Exports et impressions
3. Prévisions et analyses

---

**Prochaine étape** : Commencer par la Phase 1 avec la création de la structure financière de base.
