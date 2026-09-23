# 🎭 Tests E2E avec Playwright - BookYourCoach

Documentation complète pour comprendre et utiliser les tests end-to-end Playwright dans le projet BookYourCoach.

---

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Structure des tests](#structure-des-tests)
3. [Installation et configuration](#installation-et-configuration)
4. [Lancer les tests](#lancer-les-tests)
5. [Écrire de nouveaux tests](#écrire-de-nouveaux-tests)
6. [Debugging](#debugging)
7. [CI/CD](#cicd)
8. [Bonnes pratiques](#bonnes-pratiques)

---

## 🎯 Vue d'ensemble

**Playwright** est un framework de tests E2E (End-to-End) moderne qui permet de tester l'application complète dans un vrai navigateur, simulant le comportement d'un utilisateur réel.

### **Pourquoi Playwright ?**

✅ **Multi-navigateurs** : Chrome, Firefox, Safari (WebKit)  
✅ **Rapide** : Exécution parallèle des tests  
✅ **Fiable** : Auto-wait, retry automatique  
✅ **Debugging puissant** : Trace viewer, screenshots, vidéos  
✅ **Moderne** : API TypeScript, async/await  

### **Que teste-t-on ?**

- ✅ **Authentification** : Login, logout, gestion des sessions
- ✅ **Dashboard** : Indicateurs, navigation
- ✅ **Élèves** : CRUD, recherche, filtres, pagination
- ✅ **Abonnements** : Création, assignation, recalcul
- ✅ **Planning** : Créneaux, cours, récurrences

---

## 📁 Structure des tests

```
frontend/
├── playwright.config.ts          # Configuration globale Playwright
├── tests/
│   └── e2e/
│       ├── utils/
│       │   └── auth.ts          # Utilitaires d'authentification
│       ├── auth/
│       │   └── auth.spec.ts     # Tests d'authentification
│       └── club/
│           ├── dashboard.spec.ts      # Tests du dashboard
│           ├── students.spec.ts       # Tests gestion élèves
│           ├── subscriptions.spec.ts  # Tests abonnements
│           └── planning.spec.ts       # Tests planning
└── playwright-report/           # Rapports HTML (généré)
```

---

## 🛠️ Installation et Configuration

### **1. Installation des dépendances**

```bash
cd frontend
npm install -D @playwright/test @types/node
```

### **2. Installation des navigateurs**

```bash
npx playwright install chromium
# Ou pour tous les navigateurs :
npx playwright install
```

### **3. Configuration**

Le fichier `playwright.config.ts` contient toute la configuration :

```typescript
export default defineConfig({
  testDir: './tests/e2e',           // Répertoire des tests
  baseURL: 'http://localhost:3000', // URL de base
  use: {
    trace: 'on-first-retry',        // Traces en cas d'échec
    screenshot: 'only-on-failure',  // Screenshots en cas d'échec
    video: 'retain-on-failure',     // Vidéos en cas d'échec
  },
  webServer: {
    command: 'npm run dev',         // Lance le serveur auto
    url: 'http://localhost:3000',
  },
});
```

### **4. Variables d'environnement (obligatoire)**

Les identifiants de test ne sont **pas** dans le dépôt : un compte réel y avait été
codé en dur. Les tests échouent explicitement si les variables manquent.

```bash
cp .env.test.example .env.test   # depuis frontend/
```

`.env.test` est ignoré par git :

```bash
PLAYWRIGHT_BASE_URL=http://localhost:3000
E2E_CLUB_EMAIL=e2e-club@example.test
E2E_CLUB_PASSWORD=<votre valeur locale>
```

Puis créer le compte correspondant en base de développement — jamais un compte
existant en production :

```bash
php artisan db:seed --class=E2eClubAccountSeeder
```

En intégration continue, ces variables viennent des secrets du dépôt et le fichier
`.env.test` n'existe pas.

---

## 🚀 Lancer les tests

### **Mode headless (sans interface)**

```bash
cd frontend
npx playwright test
```

### **Mode headed (avec interface visible)**

```bash
npx playwright test --headed
```

### **Mode debug interactif**

```bash
npx playwright test --debug
```

### **Mode UI (interface Playwright)**

```bash
npx playwright test --ui
```

### **Lancer un fichier spécifique**

```bash
npx playwright test tests/e2e/auth/auth.spec.ts
```

### **Lancer un test spécifique**

```bash
npx playwright test -g "Connexion réussie"
```

### **Lancer sur un navigateur spécifique**

```bash
npx playwright test --project=chromium
npx playwright test --project=firefox
```

### **Voir le rapport HTML**

```bash
npx playwright show-report
```

---

## ✍️ Écrire de nouveaux tests

### **Structure de base**

```typescript
import { test, expect } from '@playwright/test';
import { loginAsClub } from '../utils/auth';

test.describe('Nom du groupe de tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Code exécuté avant chaque test
    await loginAsClub(page);
    await page.goto('/ma-page');
  });

  test('Description du test', async ({ page }) => {
    // 1. Interagir avec la page
    await page.click('button:has-text("Cliquer")');
    
    // 2. Vérifier le résultat
    await expect(page.locator('text=Succès')).toBeVisible();
  });
});
```

### **Actions courantes**

```typescript
// Navigation
await page.goto('/club/students');
await page.goBack();
await page.reload();

// Remplir un formulaire
await page.fill('input[type="email"]', 'test@example.com');
await page.fill('input[name="password"]', 'secret');

// Cliquer
await page.click('button:has-text("Envoyer")');
await page.click('[data-testid="submit-button"]');

// Sélectionner dans un <select>
await page.selectOption('select[name="country"]', 'France');

// Attendre un élément
await page.waitForSelector('text=Chargement terminé');
await page.waitForURL(/\/dashboard/);

// Vérifications
await expect(page).toHaveURL(/\/dashboard/);
await expect(page.locator('h1')).toContainText('Bienvenue');
await expect(page.locator('button')).toBeVisible();
await expect(page.locator('button')).toBeDisabled();
```

### **Sélecteurs recommandés**

```typescript
// ✅ BON : Par data-testid (le plus stable)
page.locator('[data-testid="submit-button"]')

// ✅ BON : Par texte visible
page.locator('button:has-text("Connexion")')

// ✅ BON : Par rôle ARIA
page.locator('button[role="submit"]')

// ⚠️ MOYEN : Par classe CSS (peut changer)
page.locator('.btn-primary')

// ❌ ÉVITER : Sélecteurs trop spécifiques
page.locator('div > div > span.text-sm')
```

### **Gérer les modals**

```typescript
// Ouvrir un modal
await page.click('button:has-text("Ajouter")');

// Attendre que le modal soit visible
await expect(page.locator('[role="dialog"]')).toBeVisible();

// Interagir avec le modal
await page.fill('[role="dialog"] input[name="name"]', 'Nouveau');

// Fermer le modal
await page.click('[role="dialog"] button:has-text("Enregistrer")');

// Attendre que le modal se ferme
await expect(page.locator('[role="dialog"]')).not.toBeVisible();
```

---

## 🐛 Debugging

### **1. Mode Debug Interactif**

```bash
npx playwright test --debug
```

**Fonctionnalités :**
- ▶️ Exécution pas à pas
- 🔍 Inspection du DOM en temps réel
- 📝 Console des logs
- ⏸️ Points d'arrêt

### **2. Trace Viewer**

```bash
# Lancer avec trace
npx playwright test --trace on

# Ouvrir le viewer
npx playwright show-trace trace.zip
```

**Fonctionnalités :**
- 📹 Enregistrement vidéo de chaque action
- 📸 Screenshots avant/après
- 🌐 Snapshots du DOM
- 📊 Timeline des événements

### **3. Screenshots manuels**

```typescript
test('Mon test', async ({ page }) => {
  await page.goto('/');
  await page.screenshot({ path: 'debug-screenshot.png' });
});
```

### **4. Logs détaillés**

```typescript
test('Mon test', async ({ page }) => {
  // Log de debug
  console.log('Test démarré');
  
  // Capturer les logs du navigateur
  page.on('console', msg => console.log('BROWSER:', msg.text()));
  
  // Capturer les erreurs
  page.on('pageerror', err => console.error('ERREUR:', err));
});
```

### **5. Ralentir l'exécution**

```typescript
// Dans playwright.config.ts
use: {
  launchOptions: {
    slowMo: 1000, // 1 seconde entre chaque action
  }
}
```

---

## 🔄 CI/CD

### **GitHub Actions**

Créer `.github/workflows/playwright.yml` :

```yaml
name: Tests E2E Playwright

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        working-directory: ./frontend
        run: npm ci
      
      - name: Install Playwright
        working-directory: ./frontend
        run: npx playwright install --with-deps chromium
      
      - name: Run tests
        working-directory: ./frontend
        run: npx playwright test
      
      - name: Upload report
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: frontend/playwright-report/
```

---

## ✅ Bonnes Pratiques

### **1. Tests Indépendants**

❌ **MAUVAIS** :
```typescript
test('Créer un élève', async ({ page }) => {
  // Test 1 crée un élève
});

test('Modifier cet élève', async ({ page }) => {
  // Test 2 dépend du test 1 ❌
});
```

✅ **BON** :
```typescript
test('Créer un élève', async ({ page }) => {
  // Test 1 crée son propre élève
});

test('Modifier un élève', async ({ page }) => {
  // Test 2 crée aussi son propre élève
});
```

### **2. Utiliser `data-testid`**

```html
<!-- Dans vos composants Vue -->
<button data-testid="submit-button">Envoyer</button>
```

```typescript
// Dans vos tests
await page.click('[data-testid="submit-button"]');
```

### **3. Attendre les éléments**

❌ **MAUVAIS** :
```typescript
await page.click('button');
await page.waitForTimeout(2000); // ❌ Timeout arbitraire
```

✅ **BON** :
```typescript
await page.click('button');
await page.waitForSelector('text=Succès'); // ✅ Attend le résultat réel
```

### **4. Grouper les tests liés**

```typescript
test.describe('Gestion des élèves', () => {
  test.describe('Création', () => {
    test('avec email', async ({ page }) => { /* ... */ });
    test('sans email', async ({ page }) => { /* ... */ });
  });
  
  test.describe('Modification', () => {
    test('changer le nom', async ({ page }) => { /* ... */ });
    test('changer le téléphone', async ({ page }) => { /* ... */ });
  });
});
```

### **5. Nettoyer après les tests**

```typescript
test('Créer un élève temporaire', async ({ page }) => {
  // Créer
  const studentId = await createStudent(page);
  
  // Tester
  await expect(page.locator(`#student-${studentId}`)).toBeVisible();
  
  // Nettoyer (si nécessaire)
  await deleteStudent(page, studentId);
});
```

### **6. Tests responsifs**

```typescript
test('Mobile : Liste des élèves', async ({ page }) => {
  // Simuler un iPhone
  await page.setViewportSize({ width: 375, height: 667 });
  
  await page.goto('/club/students');
  
  // Vérifier l'adaptation mobile
  await expect(page.locator('[data-testid="mobile-menu"]')).toBeVisible();
});
```

---

## 📚 Ressources

- 📖 **Documentation officielle** : https://playwright.dev/
- 🎥 **Vidéos Playwright** : https://playwright.dev/docs/videos
- 💬 **Discord Playwright** : https://discord.gg/playwright

---

## 🎯 Checklist avant de pousser

✅ Tous les tests passent en local  
✅ Pas de `test.only()` ou `test.skip()` oublié  
✅ Pas de `console.log()` de debug  
✅ Pas de `waitForTimeout()` arbitraire  
✅ Les sélecteurs sont robustes (`data-testid`)  
✅ Les tests sont indépendants  
✅ Le rapport HTML est propre  

---

**Auteur :** BookYourCoach Team  
**Date :** Novembre 2025  
**Version Playwright :** v1.40+

