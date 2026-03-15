# Documentation BookYourCoach

Vue d’ensemble de la documentation du projet.

## Documentation principale

- **[Documentation fonctionnelle](DOCUMENTATION_FONCTIONNELLE.md)** – Rôles, permissions, fonctionnalités par acteur (admin, club, enseignant, étudiant), gestion des cours, abonnements, finances, notifications, intégrations.
- **[Documentation technique](DOCUMENTATION_TECHNIQUE.md)** – Stack, architecture, structure du projet, modèles, API REST, auth, services, BDD, tests, déploiement, frontend.

## Démarrage et scripts

- **[README principal](../README.md)** – Installation, Docker, commandes de base.
- **[Scripts utilitaires](../scripts/README.md)** – `test-all.sh`, `docker-maintenance.sh`, `deploy.sh`.

## Déploiement et configuration

- **[Déploiement production](PRODUCTION_DEPLOYMENT.md)** – Préparation serveur, variables d’environnement, Docker, SSL.
- **[Configuration Sanctum](PRODUCTION_SANCTUM_CONFIG.md)** – Authentification API / SPA.
- **[Template environnement](PRODUCTION_ENV_TEMPLATE.md)** – Variables d’environnement.
- **[GitHub Actions](GITHUB_ACTIONS_CONFIG.md)** – CI/CD.

## Références par thème

- **Récurrence des cours :** [RECURRENCE_COURS_ANALYSE.md](RECURRENCE_COURS_ANALYSE.md) — Flux, validation 26 semaines, erreurs corrigées.
- **Auth / CORS :** [AUTH_SOLUTION.md](AUTH_SOLUTION.md), [config CORS](../config/cors.php)
- **Intégrations :** [Google Calendar](GOOGLE_CALENDAR_INTEGRATION.md), [calendrier étudiant](student-calendar-integration.md)
- **Mobile :** [Démarrage élève](../mobile/DEMARRAGE-ELEVE.md), [Démarrage enseignant](../mobile/DEMARRAGE-ENSEIGNANT.md), [Fonctionnalités élève](../mobile/FONCTIONNALITES-ELEVE.md), [Fonctionnalités enseignant](../mobile/FONCTIONNALITES-ENSEIGNANT.md)

## URLs de développement

- Frontend : http://localhost:3000  
- Backend API : http://localhost:8080  
- phpMyAdmin : http://localhost:8082  
- Neo4j : http://localhost:7474

---

*Dernière mise à jour : Mars 2025*
