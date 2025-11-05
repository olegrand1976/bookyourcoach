# 🎭 Intégration Playwright - Tests E2E pour BookYourCoach

**Date d'intégration :** 5 novembre 2025  
**Branche :** `feature/playwright-testing`  
**Statut :** ✅ Implémenté et prêt à l'emploi

---

## 📊 Vue d'ensemble

**Playwright** est un framework de tests end-to-end moderne développé par Microsoft. Il permet de tester l'application complète en simulant le comportement d'un utilisateur réel dans un vrai navigateur.

### **Pourquoi Playwright ?**

| Critère | Détails |
|---------|---------|
| 🌐 **Multi-navigateurs** | Chrome, Firefox, Safari (WebKit) |
| ⚡ **Rapide** | Exécution parallèle des tests |
| 🎯 **Fiable** | Auto-wait, retry automatique, moins de flaky tests |
| 🛠️ **Debugging** | Trace viewer, screenshots, vidéos |
| 📝 **TypeScript** | API moderne avec types |
| 🤖 **CI/CD ready** | Intégration GitHub Actions, GitLab CI |

---

## 🎯 Couverture des tests

### **Tests d'authentification** (`tests/e2e/auth/auth.spec.ts`)

| Test | Description |
|------|-------------|
| ✅ Connexion réussie | Identifiants valides → Dashboard |
| ✅ Échec mot de passe incorrect | Message d'erreur affiché |
| ✅ Échec email inexistant | Message d'erreur affiché |
| ✅ Déconnexion | Retour à la page login |
| ✅ Redirection si non authentifié | Pages protégées → Login |
| ✅ Validation formulaire | Champs requis vérifiés |

**Total : 6 tests**

---

### **Tests Dashboard Club** (`tests/e2e/club/dashboard.spec.ts`)

| Test | Description |
|------|-------------|
| ✅ Indicateurs principaux | Total élèves, abonnements, cours, revenus |
| ✅ Élèves récents | Liste des derniers inscrits |
| ✅ Données incomplètes | Liste élèves manquant infos |
| ✅ Navigation vers élèves | Redirection liste complète |
| ✅ Navigation vers abonnements | Redirection gestion |
| ✅ Navigation vers planning | Redirection planning |
| ✅ Rafraîchissement | Données rechargées correctement |
| ✅ Responsive mobile | Adaptation viewport 375px |

**Total : 8 tests**

---

### **Tests Gestion Élèves** (`tests/e2e/club/students.spec.ts`)

| Test | Description |
|------|-------------|
| ✅ Affichage liste | Tableau/cartes élèves |
| ✅ Pagination | 20 élèves par page |
| ✅ Recherche par nom | Filtrage en temps réel |
| ✅ Filtre statut | Actif/Inactif |
| ✅ Modal ajout | Formulaire ouverture |
| ✅ Ajout complet | Tous champs remplis |
| ✅ Ajout sans email | Champs optionnels |
| ✅ Modal modification | Pré-remplissage données |
| ✅ Modification infos | Mise à jour élève |
| ✅ Désactivation | Soft delete |
| ✅ Historique (œil) | Vue abonnements/cours |
| ✅ Export liste | Téléchargement fichier |
| ✅ Responsive mobile | Adaptation viewport |

**Total : 13 tests**

---

### **Tests Gestion Abonnements** (`tests/e2e/club/subscriptions.spec.ts`)

| Test | Description |
|------|-------------|
| ✅ Affichage liste | Cartes abonnements |
| ✅ Compteurs utilisés/total | Format "X / Y cours" |
| ✅ Code couleur | Vert/Orange/Rouge selon % |
| ✅ Période validité | Dates affichées |
| ✅ Filtre statut utilisation | Par couleur |
| ✅ Modal création | Formulaire ouverture |
| ✅ Création + assignation | Nouvel abonnement élève |
| ✅ Recalcul compteurs | Bouton réinitialisation |
| ✅ Assignation élève | Attribution abonnement |
| ✅ Renouvellement | Pré-remplissage type |
| ✅ Archivage auto | 100% utilisés archivés |
| ✅ Affichage détails | Modal/page détails |
| ✅ Modification | Date expiration, etc. |
| ✅ Responsive mobile | Adaptation viewport |

**Total : 14 tests**

---

### **Tests Planning** (`tests/e2e/club/planning.spec.ts`)

| Test | Description |
|------|-------------|
| ✅ Affichage planning | Calendrier/liste créneaux |
| ✅ Créneaux ouverts | Liste disponible |
| ✅ Sélection créneau | Affichage cours programmés |
| ✅ Navigation par date | Flèches précédent/suivant |
| ✅ Bouton "Aujourd'hui" | Si jour correspond |
| ✅ Modal création cours | Formulaire ouverture |
| ✅ Autocomplete enseignant | Suggestions |
| ✅ Autocomplete élève | Suggestions |
| ✅ Durée/prix auto | Selon type cours |
| ✅ Création cours abonnement | Récurrence auto |
| ✅ Vérification récurrences | Conflits détectés |
| ✅ Modification cours | Changement statut |
| ✅ Annulation cours | Confirmation |
| ✅ Gestion créneaux création | Ajout nouveau créneau |
| ✅ Activation/désactivation | Toggle statut |
| ✅ Prix affiché correct | Pas 0.00 € |
| ✅ Responsive mobile | Adaptation viewport |

**Total : 17 tests**

---

## 📊 Statistiques globales

| Catégorie | Nombre de tests |
|-----------|----------------|
| Authentification | 6 |
| Dashboard | 8 |
| Élèves | 13 |
| Abonnements | 14 |
| Planning | 17 |
| **TOTAL** | **58 tests** |

---

## 🚀 Utilisation

### **1. Lancer tous les tests (headless)**

```bash
cd frontend
npm run test:e2e
```

### **2. Lancer avec interface visible**

```bash
npm run test:e2e:headed
```

### **3. Mode debug interactif**

```bash
npm run test:e2e:debug
```

### **4. Mode UI Playwright**

```bash
npm run test:e2e:ui
```

### **5. Voir le rapport HTML**

```bash
npm run test:e2e:report
```

### **6. Lancer une catégorie spécifique**

```bash
# Tests d'authentification uniquement
npm run test:e2e:auth

# Tests club uniquement
npm run test:e2e:club

# Un fichier spécifique
npx playwright test tests/e2e/club/students.spec.ts
```

### **7. Lancer un test spécifique**

```bash
npx playwright test -g "Connexion réussie"
```

---

## 🛠️ Configuration

### **Fichiers importants**

| Fichier | Description |
|---------|-------------|
| `playwright.config.ts` | Configuration globale |
| `tests/e2e/utils/auth.ts` | Utilitaires authentification |
| `tests/e2e/README.md` | Documentation complète |

### **Variables d'environnement**

Créer `.env.test` (optionnel) :

```bash
PLAYWRIGHT_BASE_URL=http://localhost:3000
TEST_USER_EMAIL=b.murgo1976@gmail.com
TEST_USER_PASSWORD=votre_mot_de_passe_test
```

### **Credentials de test**

Dans `tests/e2e/utils/auth.ts` :

```typescript
export const TEST_CREDENTIALS = {
  club: {
    email: 'b.murgo1976@gmail.com',
    password: 'password123', // À adapter
  },
};
```

⚠️ **Important :** Utilisez des comptes de test dédiés, pas de comptes de production !

---

## 🎭 Comment ça fonctionne ?

### **Architecture Playwright**

```
┌─────────────────────────────────────────────┐
│         Playwright Test Runner               │
│  (Gère l'exécution, parallélisation, retry) │
└──────────────────┬──────────────────────────┘
                   │
         ┌─────────┴─────────┐
         │                   │
┌────────▼───────┐  ┌────────▼───────┐
│  Browser       │  │  Browser       │
│  Context 1     │  │  Context 2     │
│  (Isolated)    │  │  (Isolated)    │
└────────┬───────┘  └────────┬───────┘
         │                   │
    ┌────▼────┐         ┌────▼────┐
    │  Page   │         │  Page   │
    │  (Tab)  │         │  (Tab)  │
    └─────────┘         └─────────┘
         │                   │
    ┌────▼────────────┬──────▼──────┐
    │  Votre App      │  Votre App  │
    │  localhost:3000 │  ...        │
    └─────────────────┴─────────────┘
```

### **Cycle d'exécution d'un test**

```
1. 🚀 Démarrage
   ├─ Playwright démarre le serveur Nuxt (npm run dev)
   ├─ Attend que http://localhost:3000 soit accessible
   └─ Lance le navigateur Chromium
   
2. 🔐 Authentification (beforeEach)
   ├─ Navigue vers /login
   ├─ Remplit email + password
   ├─ Clique sur "Connexion"
   └─ Attend redirection vers /club/dashboard
   
3. 🎯 Exécution du test
   ├─ Navigue vers la page testée
   ├─ Interagit avec les éléments (clic, remplissage)
   ├─ Attend les résultats (auto-wait)
   └─ Vérifie les assertions (expect)
   
4. 📸 Captures (si échec)
   ├─ Screenshot de l'écran
   ├─ Vidéo de l'exécution
   └─ Trace complète (DOM, réseau, logs)
   
5. 🧹 Nettoyage
   ├─ Ferme le browser context
   └─ Libère les ressources
```

### **Auto-wait : La magie de Playwright**

Playwright **attend automatiquement** que les éléments soient prêts avant d'interagir :

```typescript
// ❌ Autres frameworks (Selenium, Cypress)
await driver.findElement(By.id('button'));
await driver.sleep(2000); // 😢 Timeout arbitraire
await driver.click();

// ✅ Playwright
await page.click('button'); // Attend automatiquement que le bouton soit :
                            // - Visible
                            // - Enabled
                            // - Stable (pas d'animation)
                            // - Attaché au DOM
```

### **Isolation complète**

Chaque test a son propre **Browser Context** (= profil navigateur isolé) :

```typescript
test('Test 1', async ({ page }) => {
  // Context 1 : cookies, localStorage, session indépendants
});

test('Test 2', async ({ page }) => {
  // Context 2 : Totalement isolé du test 1
  // Pas de contamination entre tests !
});
```

### **Retry automatique**

```typescript
// Si un élément met du temps à apparaître, Playwright retry :
await expect(page.locator('text=Succès')).toBeVisible();

// Retry pendant 5 secondes par défaut
// Réduit drastiquement les tests flaky
```

---

## 🐛 Debugging

### **1. Mode Debug Visuel**

```bash
npm run test:e2e:debug
```

**Fonctionnalités :**
- ⏯️ Exécution pas à pas
- 🔍 Inspection du DOM en direct
- 🎯 Sélecteurs interactifs
- 📝 Logs en temps réel

### **2. Trace Viewer**

En cas d'échec, Playwright génère automatiquement une **trace complète** :

```bash
npx playwright show-trace trace.zip
```

**Contenu :**
- 📹 Vidéo de chaque action
- 📸 Screenshot avant/après
- 🌐 Snapshot du DOM
- 🔍 Logs réseau (API calls)
- ⏱️ Timeline des événements

### **3. Screenshots de debug**

Ajouter dans un test :

```typescript
test('Debug', async ({ page }) => {
  await page.goto('/club/students');
  await page.screenshot({ path: 'debug.png', fullPage: true });
});
```

### **4. Logs du navigateur**

```typescript
test('Debug logs', async ({ page }) => {
  page.on('console', msg => console.log('🌐', msg.text()));
  page.on('pageerror', err => console.error('❌', err));
  
  await page.goto('/club/dashboard');
});
```

---

## 🔄 CI/CD

### **GitHub Actions**

Créer `.github/workflows/playwright.yml` :

```yaml
name: Tests E2E

on:
  push:
    branches: [ main, develop ]
  pull_request:

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        working-directory: ./frontend
        run: npm ci
      
      - name: Install Playwright
        working-directory: ./frontend
        run: npx playwright install --with-deps chromium
      
      - name: Run E2E tests
        working-directory: ./frontend
        run: npm run test:e2e
      
      - name: Upload report
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: frontend/playwright-report/
```

---

## ✅ Bonnes pratiques

### **1. Utiliser `data-testid`**

```vue
<!-- Dans vos composants -->
<button data-testid="submit-button" @click="handleSubmit">
  Enregistrer
</button>
```

```typescript
// Dans vos tests
await page.click('[data-testid="submit-button"]');
```

### **2. Tests indépendants**

✅ **BON** : Chaque test crée ses propres données
❌ **MAUVAIS** : Test 2 dépend des données créées par Test 1

### **3. Pas de `waitForTimeout()`**

✅ **BON** :
```typescript
await expect(page.locator('text=Succès')).toBeVisible();
```

❌ **MAUVAIS** :
```typescript
await page.waitForTimeout(2000); // Timeout arbitraire
```

### **4. Sélecteurs robustes**

| Priorité | Sélecteur | Exemple |
|----------|-----------|---------|
| 1️⃣ | `data-testid` | `[data-testid="submit"]` |
| 2️⃣ | Texte visible | `button:has-text("Connexion")` |
| 3️⃣ | Rôle ARIA | `button[role="submit"]` |
| 4️⃣ | Classe CSS | `.btn-primary` |

---

## 📚 Ressources

- 📖 **Doc officielle** : https://playwright.dev/
- 🎥 **Vidéos** : https://playwright.dev/docs/videos
- 💬 **Discord** : https://discord.gg/playwright
- 📝 **README tests** : `frontend/tests/e2e/README.md`

---

## 🎊 Résumé

✅ **58 tests E2E** couvrant les écrans critiques  
✅ **Auto-wait** : pas de tests flaky  
✅ **Debugging puissant** : Trace viewer, screenshots, vidéos  
✅ **CI/CD ready** : Intégration GitHub Actions  
✅ **Documentation complète** : README + exemples  
✅ **Scripts npm** : `test:e2e`, `test:e2e:debug`, etc.  

**La suite de tests E2E est prête à l'emploi !** 🚀

---

**Date :** 5 novembre 2025  
**Auteur :** BookYourCoach Team  
**Branche :** `feature/playwright-testing`

