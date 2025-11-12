# 🐳 Playwright avec Docker - BookYourCoach

Guide complet pour exécuter les tests E2E Playwright dans un environnement Docker.

---

## 🎯 Vue d'ensemble

Deux approches pour lancer Playwright avec Docker :

1. **Approche simple** : Installer dans le conteneur existant (temporaire)
2. **Approche permanente** : Service dédié avec `docker-compose.e2e.yml` (recommandé pour CI/CD)

---

## ⚡ Approche 1 : Installation temporaire dans le conteneur

### **1. Entrer dans le conteneur frontend**

```bash
docker compose exec -it frontend sh
```

### **2. Installer les dépendances Playwright**

```bash
# Installer les packages npm (si pas déjà fait)
npm install

# Installer les navigateurs Playwright + dépendances système
# ⚠️ Cela peut prendre 5-10 minutes
apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont
```

### **3. Lancer les tests**

```bash
# Tests headless (sans interface)
npm run test:e2e

# Voir le rapport
npm run test:e2e:report
```

### **⚠️ Limitations**

- ❌ L'installation est **perdue au redémarrage** du conteneur
- ❌ L'image `node:20-alpine` n'est pas optimisée pour Playwright
- ❌ Mode `--headed` ne fonctionne pas (pas de X server)

---

## 🚀 Approche 2 : Service Docker dédié (Recommandé)

### **Architecture**

```
┌─────────────────────────────────────┐
│  docker-compose.e2e.yml             │
│  ├─ e2e-tests (headless)            │
│  └─ e2e-ui (mode UI)                │
└─────────────────────────────────────┘
         │
         ├─ Utilise: Dockerfile.e2e
         │  (Image officielle Playwright)
         │
         └─ Dépend de:
            ├─ frontend:3000
            └─ backend:8080
```

### **1. Construire l'image E2E**

```bash
# Construire uniquement le service E2E
docker compose -f docker-compose.yml -f docker-compose.e2e.yml build e2e-tests
```

### **2. Lancer les tests E2E**

#### **Option A : Tests headless (CI/CD)**

```bash
# Lancer tous les tests
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests

# Lancer une catégorie spécifique
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests npm run test:e2e:auth

# Lancer un fichier spécifique
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests npx playwright test tests/e2e/club/students.spec.ts
```

#### **Option B : Mode UI (Développement)**

```bash
# Lancer l'interface Playwright UI
docker compose -f docker-compose.yml -f docker-compose.e2e.yml up e2e-ui

# Puis ouvrir dans le navigateur
open http://localhost:9323
```

### **3. Voir les rapports**

Les rapports sont automatiquement générés dans `frontend/playwright-report/` :

```bash
# Ouvrir le rapport HTML
cd frontend
npm run test:e2e:report
```

---

## 📁 Fichiers créés

```
.
├── frontend/
│   ├── Dockerfile.e2e                  # Dockerfile pour tests E2E
│   ├── playwright.config.ts            # Configuration Playwright
│   └── tests/e2e/                      # Tests E2E
│       ├── auth/
│       ├── club/
│       └── utils/
├── docker-compose.e2e.yml              # Services E2E
└── PLAYWRIGHT_DOCKER.md                # Ce fichier
```

---

## ⚙️ Configuration

### **Variables d'environnement**

Dans `docker-compose.e2e.yml` :

```yaml
environment:
  # URL de l'application à tester
  - PLAYWRIGHT_BASE_URL=http://frontend:3000
  
  # Mode CI (désactive l'interface)
  - CI=true
```

### **Personnalisation**

Pour modifier les credentials de test, créer `.env.test` :

```bash
TEST_USER_EMAIL=test.club@example.com
TEST_USER_PASSWORD=password_test_secure
```

Puis dans `docker-compose.e2e.yml` :

```yaml
env_file:
  - ./frontend/.env.test
```

---

## 🔧 Debugging

### **1. Voir les logs des tests**

```bash
docker compose -f docker-compose.yml -f docker-compose.e2e.yml logs e2e-tests
```

### **2. Accéder au conteneur E2E**

```bash
# Lancer un shell dans le conteneur
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests sh

# Puis lancer les tests manuellement
npm run test:e2e:debug
```

### **3. Voir les screenshots/vidéos**

En cas d'échec, les artifacts sont dans :

```
frontend/
├── playwright-report/       # Rapport HTML
└── test-results/            # Screenshots, vidéos, traces
    └── auth-auth-spec-ts-... /
        ├── test-failed-1.png
        ├── video.webm
        └── trace.zip
```

**Ouvrir la trace :**

```bash
cd frontend
npx playwright show-trace test-results/.../trace.zip
```

---

## 🤖 Intégration CI/CD

### **GitHub Actions**

Créer `.github/workflows/e2e-tests.yml` :

```yaml
name: Tests E2E Docker

on:
  push:
    branches: [ main, develop ]
  pull_request:

jobs:
  e2e-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Build Docker images
        run: |
          docker compose -f docker-compose.yml build
          docker compose -f docker-compose.yml -f docker-compose.e2e.yml build e2e-tests
      
      - name: Start services
        run: docker compose -f docker-compose.yml up -d
      
      - name: Wait for services
        run: |
          timeout 120 sh -c 'until curl -f http://localhost:3000; do sleep 2; done'
          timeout 120 sh -c 'until curl -f http://localhost:8080/api/health; do sleep 2; done'
      
      - name: Run E2E tests
        run: docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests
      
      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: frontend/playwright-report/
      
      - name: Stop services
        if: always()
        run: docker compose -f docker-compose.yml down
```

---

## 📊 Comparaison des approches

| Aspect | Temporaire (conteneur) | Service dédié (E2E) |
|--------|------------------------|---------------------|
| **Installation** | Manuelle dans conteneur | Automatique (Dockerfile) |
| **Persistance** | ❌ Perdue au redémarrage | ✅ Permanente |
| **Performance** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **CI/CD** | ❌ Difficile | ✅ Facile |
| **Debugging** | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Image officielle** | ❌ Non (Alpine) | ✅ Oui (Playwright) |
| **Mode UI** | ❌ Non supporté | ✅ Supporté |

**Recommandation :** Utiliser le **service dédié** pour une meilleure expérience.

---

## 🎯 Commandes utiles

### **Construire et tester**

```bash
# Build + tests en une commande
docker compose -f docker-compose.yml -f docker-compose.e2e.yml up --build e2e-tests

# Tests uniquement (sans rebuild)
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests
```

### **Nettoyage**

```bash
# Supprimer les conteneurs E2E
docker compose -f docker-compose.e2e.yml down

# Supprimer les images E2E
docker compose -f docker-compose.e2e.yml down --rmi all

# Nettoyer les rapports
rm -rf frontend/playwright-report frontend/test-results
```

### **Alias pratiques** (optionnel)

Ajouter à votre `~/.bashrc` ou `~/.zshrc` :

```bash
alias e2e="docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests"
alias e2e-ui="docker compose -f docker-compose.yml -f docker-compose.e2e.yml up e2e-ui"
```

Puis :

```bash
# Lancer les tests
e2e

# Lancer l'UI
e2e-ui
```

---

## ⚠️ Troubleshooting

### **Erreur : "Cannot find module '@playwright/test'"**

**Cause :** Les dépendances npm ne sont pas installées dans le conteneur.

**Solution :**

```bash
# Option 1 : Rebuild l'image
docker compose -f docker-compose.yml -f docker-compose.e2e.yml build e2e-tests

# Option 2 : Installer manuellement
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests npm install
```

### **Erreur : "Connection refused" sur http://frontend:3000**

**Cause :** Le service frontend n'est pas démarré ou pas encore prêt.

**Solution :**

```bash
# Démarrer les services d'abord
docker compose -f docker-compose.yml up -d

# Attendre que le frontend soit prêt
curl -f http://localhost:3000

# Puis lancer les tests
docker compose -f docker-compose.yml -f docker-compose.e2e.yml run --rm e2e-tests
```

### **Erreur : "Timeout waiting for page to load"**

**Cause :** Le backend ou frontend met trop de temps à répondre.

**Solution :** Augmenter le timeout dans `playwright.config.ts` :

```typescript
use: {
  navigationTimeout: 60000, // 60 secondes au lieu de 30
}
```

---

## 📚 Ressources

- 📖 **Documentation Playwright** : https://playwright.dev/
- 🐳 **Images Docker Playwright** : https://playwright.dev/docs/docker
- 📝 **Documentation complète des tests** : `frontend/tests/e2e/README.md`
- 📊 **Vue d'ensemble de l'intégration** : `PLAYWRIGHT_INTEGRATION.md`

---

**Date :** 5 novembre 2025  
**Auteur :** BookYourCoach Team  
**Branche :** `feature/playwright-testing`

