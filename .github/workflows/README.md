# 🚀 Workflows GitHub Actions

Ce dossier contient plusieurs workflows GitHub Actions pour gérer le build, le déploiement et les tests de l'application Acti'Vibe.

## 📋 Workflows Disponibles

### 1. 🚀 Déploiement Production Modulaire (`deploy-production-modular.yml`)

**Workflow principal** qui combine build et déploiement avec des jobs séparés.

**Déclencheurs :**
- Push sur la branche `main` (automatique)
- Déclenchement manuel avec options

**Options de déclenchement manuel :**
- `skip_build` : Ignorer le build (déployer seulement)
- `skip_deploy` : Ignorer le déploiement (build seulement)
- `force_rebuild` : Forcer la reconstruction des images

**Jobs :**
1. **🔍 Préparation & Validation** : Vérifie les conditions et prépare les tags
2. **🏗️ Build Images Docker** : Construit les images backend et frontend
3. **⚙️ Génération Configuration** : Génère les fichiers de configuration
4. **🚀 Déploiement Serveur** : Déploie sur le serveur de production
5. **🧪 Tests Post-Déploiement** : Vérifie le bon fonctionnement
6. **📧 Notifications** : Envoie les notifications de succès/échec

### 2. 🏗️ Build Images Docker (`build-only.yml`)

**Workflow dédié** uniquement au build des images Docker.

**Déclencheurs :**
- Déclenchement manuel uniquement (pas de push automatique)

**Options :**
- `force_rebuild` : Forcer la reconstruction complète (sans cache)
- `build_backend` : Builder le backend Laravel
- `build_frontend` : Builder le frontend Nuxt.js

**Jobs :**
1. **🏗️ Build Backend Laravel** : Construit l'image backend
2. **🎨 Build Frontend Nuxt.js** : Construit l'image frontend
3. **📊 Résumé du Build** : Affiche le résumé des builds

### 3. 🚀 Déploiement Serveur (`deploy-only.yml`)

**Workflow dédié** uniquement au déploiement sur le serveur.

**Déclencheurs :**
- Déclenchement manuel uniquement (pas de push automatique)

**Options :**
- `image_tag` : Tag de l'image à déployer (par défaut: latest)
- `skip_tests` : Ignorer les tests post-déploiement

**Jobs :**
1. **🔍 Préparation** : Prépare les tags d'images
2. **⚙️ Génération Configuration** : Génère les fichiers de configuration
3. **🚀 Déploiement Serveur** : Déploie sur le serveur
4. **🧪 Tests Post-Déploiement** : Vérifie le bon fonctionnement
5. **📧 Notifications** : Envoie les notifications

### 4. 🧪 Tests Serveur (`test-only.yml`)

**Workflow dédié** uniquement aux tests du serveur.

**Déclencheurs :**
- Déclenchement manuel uniquement (pas de push automatique)

**Options :**
- `test_type` : Type de tests à exécuter (all, connectivity, api, containers)
- `verbose` : Mode verbeux (plus de détails)

**Jobs :**
1. **🔌 Tests de Connectivité** : Vérifie l'accessibilité des services
2. **🧪 Tests API** : Teste les endpoints de l'API Laravel
3. **🐳 Tests des Conteneurs** : Vérifie l'état des conteneurs Docker
4. **📊 Résumé des Tests** : Affiche le résumé des tests

## 🎯 Cas d'Usage

### Scénario 1 : Déploiement Complet (Automatique)
```bash
# Push sur la branche main
git push origin main
# Le workflow modulaire se déclenche automatiquement
```

### Scénario 2 : Build Seulement (Manuel)
```bash
# Déclencher build-only.yml manuellement
# Ou déclencher deploy-production-modular.yml avec skip_deploy=true
```

### Scénario 3 : Déploiement Seulement (Manuel)
```bash
# Déclencher deploy-only.yml manuellement
# Ou déclencher deploy-production-modular.yml avec skip_build=true
```

### Scénario 4 : Tests Seulement (Manuel)
```bash
# Déclencher test-only.yml manuellement
# Utile pour vérifier l'état du serveur sans redéployer
```

### Scénario 5 : Build Forcé (Manuel)
```bash
# Déclencher build-only.yml avec force_rebuild=true
# Utile après des changements majeurs
```

## 🔧 Configuration Requise

### Variables GitHub (`Settings > Secrets and variables > Actions`)

**Variables :**
- `DOCKERHUB_USERNAME` : Nom d'utilisateur DockerHub
- `SERVER_HOST` : Adresse IP du serveur de production
- `SERVER_USERNAME` : Nom d'utilisateur SSH
- `SERVER_PORT` : Port SSH (généralement 22)

**Secrets :**
- `DOCKERHUB_PASSWORD` : Mot de passe DockerHub
- `SERVER_SSH_KEY` : Clé privée SSH pour le serveur

### Fichiers Requis sur le Serveur

Le serveur de production doit avoir :
- Docker et Docker Compose installés
- Fichier `.env` dans `/srv/activibe/`
- Certificat SSL dans `/srv/activibe/cert.pem`

## 🚨 Gestion des Erreurs

### Cache frontend et déploiement

Le build frontend utilise un **cache Docker dont le scope dépend du hash du code** (`frontend/**`). Ainsi, dès qu’un fichier du frontend change (ex. `planning.vue`), le cache est invalidé et l’image est reconstruite entièrement. Cela évite de servir en production un ancien bundle JS (ex. erreur « formatDate is not a function » alors que le correctif est dans le code).

- **Déploiement automatique (push sur `main`)** : le workflow **🚀 Déploiement GCP Cloud Run** (`deploy-production-security.yml`) build puis déploie ; avec le nouveau scope de cache, chaque modification du frontend reconstruit le bundle (`npm run build`).
  - Seule exception : la couche `npm ci` est reprise d’un second scope indexé sur `frontend/package-lock.json` tant que ce fichier ne change pas. Le cache BuildKit étant adressé par contenu, le bundle n’est jamais repris d’un autre code que celui du commit.
  - Ordre du workflow : préparation, contrôle de sécurité et **PHPUnit** en parallèle ; images API et web construites en parallèle ; le déploiement attend PHPUnit, le contrôle de sécurité et les deux images. Un seul déploiement à la fois (groupe de concurrence `deploy-production-gcp`).
- **Si la prod affiche encore l’ancien comportement** : faire un **hard refresh** (Ctrl+Shift+R) ou vider le cache navigateur ; si le problème persiste, lancer le workflow en manuel avec **Forcer la reconstruction des images** (`force_rebuild=true`) pour ignorer tout cache.

### En cas d'échec de build :
1. Vérifier les logs du job de build
2. Vérifier la configuration Docker
3. Relancer avec `force_rebuild=true`

### En cas d'échec de déploiement :
1. Vérifier la connexion SSH
2. Vérifier les variables d'environnement
3. Se connecter manuellement au serveur pour diagnostiquer

### En cas d'échec de tests :
1. Vérifier l'état des conteneurs
2. Vérifier les logs des conteneurs
3. Vérifier la configuration réseau

## 📊 Monitoring

### Logs des Workflows
- Accessibles dans l'onglet "Actions" de GitHub
- Chaque job affiche des logs détaillés
- Notifications en cas de succès/échec

### État des Services
- Tests automatiques post-déploiement
- Vérification de l'accessibilité des ports
- Tests fonctionnels de l'API

## 🔄 Maintenance

### Nettoyage des Images
```bash
# Sur le serveur de production
docker image prune -f
docker system prune -f
```

### Mise à Jour des Workflows
- Modifier les fichiers `.yml` dans ce dossier
- Tester sur une branche de développement
- Merger sur `main` pour activer

### Sauvegarde
- Les workflows génèrent automatiquement les fichiers de configuration
- Sauvegarder régulièrement le fichier `.env` du serveur
- Documenter les changements de configuration
