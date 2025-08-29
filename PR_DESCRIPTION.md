# PR: Configuration Vitest + Mocks et Vérification Interface Élève

## Contexte
Mise en place d'une configuration de tests plus robuste pour le frontend (Nuxt 3 + Vue 3 + TypeScript) afin de faciliter la validation de l'interface élève et des pages clés sans dépendance forte au backend.

## Changements
- Configuré Vitest pour supporter `.vue`, utiliser `happy-dom`, charger `tests/setup.ts` et exclure les tests E2E
  - `frontend/vitest.config.ts`
- Ajout d'un setup de tests complet:
  - Mocks `useI18n` + `$t`, `useSettings`, `$api`, `NuxtLink`, icônes Heroicons, et stubs globaux
  - `frontend/tests/setup.ts`
- Documentation PR: `.github/PULL_REQUEST_TEMPLATE.md` et `PR_DESCRIPTION.md`

## Impact
- Les tests unitaires frontend s'exécutent localement (sans Docker)
- Réduction des faux négatifs liés aux dépendances Nuxt/i18n/API

## Comment tester
```bash
cd frontend
npm ci
npm run test:unit
```

## Vérifications interface élève (manuelles)
- Pages concernées: `frontend/pages/index.vue`, `login.vue`, `register.vue`, `dashboard.vue`
- Backend (routes): `GET /api/student/dashboard/stats`

## Étapes suivantes
- Ajouter un test unitaire pour `pages/dashboard.vue` (mock `/lessons` et `/student/dashboard/stats`)
- Mettre à jour quelques assertions de tests pour coller au libellé actuel FR (ex: “Commencer l’aventure”)