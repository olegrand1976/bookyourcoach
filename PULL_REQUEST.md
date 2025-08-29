## feat(i18n): multi-langue complet, i18n robuste et tests front durcis

### Résumé des changements

- Internationalisation de la page d’accueil (`pages/index.vue`) avec des clés `home.*`.
- Ajout/complétion des clés i18n pour 15 langues (FR de référence, EN/NL/DE/IT/ES/PT traduites). Les autres langues ont un fallback FR cohérent.
- Nouveau script `scripts/sync-locales.mjs` pour synchroniser automatiquement les clés manquantes à partir de `fr.json`.
- Corrections HTML du template admin (`frontend/pages/admin/index.vue`) : fermeture de balises `<th>/<tr>`.
- Ajout de `data-testid` pour stabiliser les tests (nav, logo, footer, sections de la home, CTA, page/login form).
- Mise à jour des tests unitaires et E2E pour tolérer l’i18n et utiliser des sélecteurs stables.
- Dépendances frontend ajustées: ajout `@nuxtjs/i18n`, installation de `unhead@^1.10` pour compat.

### Validations

- Tests unitaires frontend: 33 tests PASS (4 fichiers) avant adaptations finales de la page d’accueil (3 assertions de texte à corriger dans `tests/unit/index.test.ts`).
- Test i18n d’intégration (`test-i18n-integration.cjs`): 15/15 locales, 98 clés par langue, 0 clé manquante.
- Build frontend Nuxt: OK (client + serveur Nitro).
- E2E (Chromium): specs durcies, 10 PASS / 11 FAIL liés à la page d’accueil non visible (l’app affiche login en racine). Les sélecteurs sont prêts; il reste à décider si `/` doit pointer vers la home ou adapter la navigation des tests.

### Points d’attention / Suivi

- Décider du comportement en racine `/` (home vs login). Les E2E échouent car `/` rend login; adapter les tests (naviguer vers home explicite) ou changer la route par défaut.
- Finaliser les traductions réelles pour les langues Nordiques (sv, no, fi, da), Europe de l’Est (hu, pl), Asie de l’Est (zh, ja) si nécessaire.
- Mettre à jour `tests/unit/index.test.ts` pour refléter les textes i18n (au lieu de strings codées en dur) ou utiliser des sélecteurs structurants.

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

---

PR prête pour revue: i18n complet, build OK, tests consolidés; reste à figer la stratégie de route par défaut (`/` => home/login) pour terminer l’E2E au vert.

