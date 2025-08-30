## feat(mobile+i18n): App mobile Flutter (profils/lessons), i18n front durcie et tests

### Résumé des changements

- Web (frontend):
  - Internationalisation de la page d’accueil (`pages/index.vue`) avec des clés `home.*`.
  - Ajout/complétion des clés i18n pour 15 langues (FR de référence, EN/NL/DE/IT/ES/PT traduites). Les autres langues ont un fallback FR.
  - Script `scripts/sync-locales.mjs` pour synchroniser les clés manquantes.
  - Corrections HTML et `data-testid` pour E2E; tests unitaires/E2E adaptés.
  - Dépendances ajustées (`@nuxtjs/i18n`, `unhead@^1.10`).

- Mobile (Flutter `/mobile`):
  - Architecture Provider + Dio + SharedPreferences + Go-style routing.
  - Auth: login, register, forgot password, storage du token, fetch `/auth/me`.
  - Sélection des rôles (élève/enseignant) avec init via `/profiles/init-roles`.
  - Profils Élève/Enseignant: écrans de consultation/édition (services prêts).
  - Leçons/Réservations: création leçon (enseignant), liste leçons dispo, réservation/annulation (élève), liste participants (enseignant).
  - UI avancée: chips de disciplines, sliders/durations, actions contextuelles, filtres+recherche.
  - i18n mobile: gen_l10n (ARB fr/en), persistance de la langue, écrans core localisés.
  - Tests: unitaires Flutter OK; base `integration_test` + mock API (HttpClientAdapter) pour un flux bout-en-bout simulé.

### Validations

- Frontend: build OK; i18n sync OK; E2E stabilisés via `data-testid` (reste la stratégie de route `/`).
- Mobile: `flutter test` OK; `integration_test` configuré (mock InMemory), prêt pour exécution sur device/Chrome.

### Points d’attention / Suivi

- Décider du comportement `/` (home vs login) pour harmoniser E2E web.
- Traductions supplémentaires (sv/no/fi/da/hu/pl/zh/ja) si nécessaire.
- Étendre l’i18n des écrans mobile (profils/leçons) et compléter les scénarios d’intégration.

### Comment tester

1) Unitaire
```
cd frontend
npm run test:unit
```

2) i18n
```
node test-i18n-integration.cjs
```

3) Build
```
cd frontend
npm run build
```

4) Preview + E2E (Chromium)
```
node .output/server/index.mjs &
npx playwright install chromium
npx playwright test --project=chromium
```

### Screens/Artifacts

- Les rapports Playwright sont disponibles via `playwright-report` si reporter HTML activé.

5) Mobile
```
cd mobile
flutter pub get
flutter test
# Optionnel intégration (requires device/chrome):
# flutter drive --driver=test_driver/integration_test.dart --target=integration_test/full_flow_test.dart -d chrome
```

---

PR prête pour revue: i18n web consolidé + application mobile Flutter initiale complète (auth/roles/profils/leçons), tests unitaires OK, base E2E mobile mockée.

### Checklists

- [ ] Frontend web: build passe (Nuxt/Vite)
- [ ] Frontend web: tests unitaires passent
- [ ] Frontend web: E2E Playwright passent ou adaptés (route `/` décidée)
- [ ] Mobile: `flutter pub get` OK
- [ ] Mobile: tests unitaires `flutter test` passent
- [ ] Mobile: test d’intégration (mock) s’exécute sur device/Chrome (si nécessaire)
- [ ] i18n: gen_l10n OK, clés admin ajoutées (fr/en)
- [ ] Documentation PR revue (résumé, comment tester)

