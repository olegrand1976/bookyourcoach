# activibe 🏊‍♀️

activibe est une plateforme de coaching moderne et multilingue qui permet aux utilisateurs de trouver et de réserver des sessions avec des instructeurs professionnels certifiés.

## ✨ Fonctionnalités

-   **🌍 Support Multilingue**: Interface disponible en 15 langues (Français, Anglais, Néerlandais, Allemand, Italien, Espagnol, Portugais, Hongrois, Polonais, Chinois, Japonais, Suédois, Norvégien, Finlandais, Danois)
-   **👥 Système Multi-Rôles**: Interfaces distinctes et capacités croisées pour administrateurs, enseignants et étudiants
-   **🔐 Authentification Avancée**: Construit avec Laravel Sanctum avec gestion des capacités utilisateur
-   **📱 Interface Responsive**: Design moderne adaptatif avec thème équestre
-   **⚙️ Configuration Dynamique**: Paramètres système configurables à la volée par les administrateurs
-   **🐳 Déploiement Docker**: Environnement de développement et production containerisé

## 🚀 Stack Technique & Architecture

Architecture frontend/backend découplée avec support multilingue complet.

-   **Backend**:

    -   **Framework**: Laravel 11 (PHP 8.3)
    -   **API**: API JSON RESTful avec capacités utilisateur
    -   **Authentification**: Laravel Sanctum avec système multi-rôles
    -   **Base de données**: SQLite (dev), MySQL (prod)
    -   **Tests**: PHPUnit (127 tests passants)

-   **Frontend**:

    -   **Framework**: Nuxt 3.17.7 (Vue.js 3 + TypeScript)
    -   **Internationalisation**: @nuxtjs/i18n (15 langues)
    -   **Styling**: Tailwind CSS avec thème équestre personnalisé
    -   **Gestion d'état**: Pinia avec stores authentification
    -   **Composants**: Système de composants Vue réutilisables

-   **Infrastructure**:
    -   **Conteneurisation**: Docker Compose (5 services)
    -   **Serveur Web**: Nginx
    -   **Cache**: Redis
    -   **Monitoring**: Logs structurés

## 📦 Installation & Démarrage Rapide

1.  **Cloner le dépôt**:

    ```bash
    git clone https://github.com/olegrand1976/bookyourcoach.git
    cd bookyourcoach
    ```

2.  **Démarrage automatique**:

    ```bash
    # Démarrage complet avec tous les services
    ./start-full-stack.sh

    # Ou démarrage manuel
    docker-compose up -d
    ```

3.  **Vérification du système**:

    ```bash
    # Test complet automatisé
    ./test_complete_system.sh
    ```

4.  **Accès aux services**:
    -   **🎨 Frontend**: [http://localhost:3000](http://localhost:3000)
    -   **🔧 API Backend**: [http://localhost:8081](http://localhost:8081)
    -   **🗄️ Base de données**: Port 3307
    -   **📊 Redis**: Port 6380

## 🧪 Tests et Validation

-   **Tests Backend** (127 tests):

    ```bash
    docker exec bookyourcoach_app php artisan test
    ```

-   **Tests Frontend**:

    ```bash
    docker exec bookyourcoach_frontend npm run test
    ```

-   **Test système complet**:
    ```bash
    ./test_complete_system.sh
    ```

## 👤 Comptes de Test

-   **🔑 Administrateur**:

    -   Email: `admin@bookyourcoach.com`
    -   Mot de passe: `password123`
    -   Capacités: Admin + Enseignant + Étudiant

-   **👨‍🏫 Enseignant**:

    -   Email: `sophie.martin@bookyourcoach.com`
    -   Mot de passe: `password123`
    -   Capacités: Enseignant + Étudiant

-   **👨‍🎓 Étudiant**:
    -   Email: `alice.durand@email.com`
    -   Mot de passe: `password123`
    -   Capacités: Étudiant uniquement

## 🌍 Support Multilingue

L'application supporte actuellement **15 langues** :

| Langue     | Code | Drapeau | Statut     |
| ---------- | ---- | ------- | ---------- |
| Français   | `fr` | 🇫🇷      | ✅ Défaut  |
| English    | `en` | 🇺🇸      | ✅ Complet |
| Nederlands | `nl` | 🇳🇱      | ✅ Complet |
| Deutsch    | `de` | 🇩🇪      | ✅ Complet |
| Italiano   | `it` | 🇮🇹      | ✅ Complet |
| Español    | `es` | 🇪🇸      | ✅ Complet |
| Português  | `pt` | 🇵🇹      | ✅ Complet |
| Magyar     | `hu` | 🇭🇺      | ✅ Complet |
| Polski     | `pl` | 🇵🇱      | ✅ Complet |
| 中文       | `zh` | 🇨🇳      | ✅ Complet |
| 日本語     | `ja` | 🇯🇵      | ✅ Complet |
| Svenska    | `sv` | 🇸🇪      | ✅ Complet |
| Norsk      | `no` | 🇳🇴      | ✅ Complet |
| Suomi      | `fi` | 🇫🇮      | ✅ Complet |
| Dansk      | `da` | 🇩🇰      | ✅ Complet |

### Ajout d'une nouvelle langue

1. Créer le fichier de traduction : `frontend/locales/{code}.json`
2. Ajouter la locale dans `nuxt.config.ts`
3. Mettre à jour le composant `LanguageSelector.vue`

## 🔧 Développement

### Architecture des rôles

Le système implémente une architecture de rôles flexible :

```php
// Un admin peut agir comme enseignant et étudiant
$admin->canActAsTeacher(); // true
$admin->canActAsStudent(); // true

// Un enseignant peut aussi être étudiant
$teacher->canActAsStudent(); // true

// Capacités vérifiées côté frontend
authStore.canActAsTeacher // Getter Pinia
```

### Scripts utilitaires

-   `./start-full-stack.sh` - Démarrage complet
-   `./test_complete_system.sh` - Test système complet
-   `./test_teacher_access.sh` - Test accès enseignant
-   `./cleanup.sh` - Nettoyage des containers

## 📁 Structure du Projet

```
bookyourcoach/
├── frontend/                 # Nuxt.js + i18n
│   ├── locales/             # 15 fichiers de traduction
│   ├── stores/auth.ts       # Store avec capacités utilisateur
│   ├── components/          # LanguageSelector, etc.
│   └── layouts/default.vue  # Navigation multilingue
├── app/                     # Laravel 11
│   ├── Models/User.php      # Modèle avec multi-rôles
│   └── Http/Controllers/Api/AuthController.php # API capacités
├── database/                # 39 utilisateurs de test
├── docker-compose.yml       # 5 services Docker
└── tests/                   # 127 tests passants
```

## 🚀 État du Projet

### ✅ Fonctionnalités Implémentées (Août 2025)

1. **Système Multi-Rôles Complet**

    - Administrateurs avec capacités étendues
    - Interface enseignant accessible aux admins
    - Gestion des permissions granulaire

2. **Support Multilingue Total**

    - 15 langues supportées
    - Sélecteur de langue dynamique
    - Interface entièrement traduite

3. **Infrastructure Stable**
    - Docker optimisé et testé
    - API robuste avec 127 tests
    - Frontend responsive et performant

### 🔄 Prochaines Étapes

-   Ajout de nouvelles fonctionnalités enseignant
-   Extension du système de réservation
-   Intégration calendrier et notifications
-   Tests end-to-end automatisés

## 📞 Support

Pour toute question ou contribution :

-   **Repository**: [github.com/olegrand1976/bookyourcoach](https://github.com/olegrand1976/bookyourcoach)
-   **Issues**: Utiliser le système d'issues GitHub
-   **Documentation**: Consulter `copilot/Agents.md` pour les détails techniques

---

**BookYourCoach** - Votre plateforme équestre multilingue 🏇
