## Résumé

- Configuration Vitest pour supporter les fichiers `.vue` et exclure les tests E2E
- Ajout d'un setup de tests robuste (mocks i18n, `useSettings`, `$api`, stubs de composants)
- Vérification de l'interface élève (pages `index`, `login`, `register`, `dashboard`), routes backend étudiant (`/api/student/dashboard/stats`)

## Détails des changements

- `frontend/vitest.config.ts` : configuration Vitest (plugins, env, setupFiles, include/exclude)
- `frontend/tests/setup.ts` : mocks i18n (`useI18n` + `$t`), `useSettings`, `$api`, `NuxtLink`, icônes, et stubs globaux
- Ajustements mineurs pour permettre l'exécution locale des tests unitaires frontend

## Pourquoi

- Assurer une base de tests stable pour les composants Vue/Nuxt
- Permettre la validation de l'interface élève sans dépendre totalement du backend

## Comment tester

1. Installer les dépendances frontend
```bash
cd frontend && npm ci
```
2. Lancer les tests unitaires
```bash
npm run test:unit
```
3. (Optionnel) Démarrer le frontend
```bash
NUXT_PUBLIC_API_BASE=http://localhost:8081/api npm run dev
```

## Points d'attention

- Les tests E2E Playwright restent exclus côté Vitest; utilisez `npm run test:e2e` si Playwright est configuré
- Quelques assertions peuvent nécessiter une mise à jour si le wording UI change

## Suivi

- Ajouter un test unitaire simple pour `pages/dashboard.vue` (stats élève) avec API mockée
- Ajuster les tests pour refléter les libellés actuels (FR)