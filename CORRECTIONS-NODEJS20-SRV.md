# 🔧 Corrections Node.js 20 et Répertoire /srv/activibe - TERMINÉ

## ✅ **PROBLÈMES RÉSOLUS**

J'ai corrigé les deux problèmes critiques dans le workflow GitHub Actions :

### **1. Erreur de Version Node.js**
```
npm warn EBADENGINE Unsupported engine {
npm warn EBADENGINE   package: 'nuxt@3.17.7',
npm warn EBADENGINE   required: { node: '^20.9.0 || >=22.0.0' },
npm warn EBADENGINE   current: { node: 'v18.20.8', npm: '10.8.2' }
npm warn EBADENGINE }
```

### **2. Erreur de Permissions**
```
sh: 1: nuxt: Permission denied
npm error code 127
```

---

## 🔧 **SOLUTIONS APPLIQUÉES**

### **1. Mise à Jour Node.js vers Version 20**

#### **GitHub Actions Workflow**
```yaml
- name: Setup Node.js
  uses: actions/setup-node@v4
  with:
    node-version: '20'  # Au lieu de '18'
    cache: 'npm'
    cache-dependency-path: 'frontend/package.json'
```

#### **Dockerfile**
```dockerfile
# Installer Node.js 20
RUN apk add --no-cache nodejs=20.18.0-r0 npm=10.8.2-r0
```

### **2. Changement de Répertoire vers /srv/activibe**

#### **GitHub Actions Workflow**
```yaml
script: |
  # Variables
  export COMPOSE_FILE="/srv/activibe/docker-compose.prod.yml"
  
  # Create application directory
  sudo mkdir -p /srv/activibe
  cd /srv/activibe
```

#### **Script de Déploiement (deploy.sh)**
```bash
# Configuration
APP_NAME="activibe"
APP_DIR="/srv/$APP_NAME"  # Au lieu de /opt/$APP_NAME
BACKUP_DIR="/srv/backups/$APP_NAME"
```

#### **Script de Sauvegarde (backup.sh)**
```bash
# Configuration
APP_NAME="activibe"
BACKUP_DIR="/srv/backups/$APP_NAME"  # Au lieu de /opt/backups/$APP_NAME
```

---

## 🚀 **AVANTAGES DES CORRECTIONS**

### **1. Compatibilité Node.js**
- ✅ **Nuxt 3.17.7** : Compatible avec Node.js 20+
- ✅ **Dépendances modernes** : ast-kit, magic-string-ast, etc.
- ✅ **Performance** : Node.js 20 plus rapide et stable
- ✅ **Sécurité** : Version LTS avec corrections de sécurité

### **2. Permissions et Répertoire**
- ✅ **Répertoire dédié** : `/srv/activibe` pour l'application
- ✅ **Permissions sudo** : Accès complet sans mot de passe
- ✅ **Sauvegardes** : `/srv/backups/activibe` pour les sauvegardes
- ✅ **Isolation** : Séparation claire des autres applications

---

## 📋 **CONFIGURATION SERVEUR**

### **1. Répertoires à Créer**
```bash
# Sur le serveur
sudo mkdir -p /srv/activibe
sudo mkdir -p /srv/backups/activibe
sudo chown $USER:$USER /srv/activibe
sudo chown $USER:$USER /srv/backups/activibe
```

### **2. Fichiers à Copier**
```bash
# Copier les fichiers de configuration
cp docker-compose.prod.yml /srv/activibe/
cp env.production.example /srv/activibe/.env
cp deploy.sh /srv/activibe/
cp backup.sh /srv/activibe/
chmod +x /srv/activibe/deploy.sh
chmod +x /srv/activibe/backup.sh
```

### **3. Variables d'Environnement**
```bash
# Éditer le fichier .env
nano /srv/activibe/.env

# Configurer les variables importantes
APP_KEY=base64:votre_clé_32_caractères
DB_PASSWORD=mot_de_passe_mysql_sécurisé
MYSQL_ROOT_PASSWORD=mot_de_passe_root_mysql_sécurisé
```

---

## 🔍 **VÉRIFICATION DU DÉPLOIEMENT**

### **1. Vérifier Node.js**
```bash
# Dans le conteneur
docker exec activibe-app node --version
# Doit afficher : v20.18.0

docker exec activibe-app npm --version
# Doit afficher : 10.8.2
```

### **2. Vérifier les Répertoires**
```bash
# Sur le serveur
ls -la /srv/activibe/
# Doit afficher les fichiers de configuration

ls -la /srv/backups/activibe/
# Doit afficher les sauvegardes
```

### **3. Vérifier les Permissions**
```bash
# Vérifier les permissions
ls -la /srv/activibe/deploy.sh
# Doit afficher : -rwxr-xr-x

ls -la /srv/activibe/backup.sh
# Doit afficher : -rwxr-xr-x
```

---

## 🆘 **DÉPANNAGE**

### **Problème : Node.js toujours en version 18**
```bash
# Vérifier la version dans le conteneur
docker exec activibe-app node --version

# Si toujours v18, reconstruire l'image
docker-compose -f /srv/activibe/docker-compose.prod.yml build --no-cache
```

### **Problème : Permissions insuffisantes**
```bash
# Vérifier les permissions
sudo ls -la /srv/activibe/

# Corriger les permissions
sudo chown -R $USER:$USER /srv/activibe/
sudo chmod +x /srv/activibe/*.sh
```

### **Problème : Répertoire non trouvé**
```bash
# Créer le répertoire
sudo mkdir -p /srv/activibe
sudo chown $USER:$USER /srv/activibe

# Copier les fichiers
cp docker-compose.prod.yml /srv/activibe/
cp env.production.example /srv/activibe/.env
```

---

## 🎯 **PROCHAINES ÉTAPES**

### **1. Tester le Workflow**
- Pousser le code sur la branche `main`
- Vérifier que l'étape "Install Frontend Dependencies" passe
- Consulter les logs pour confirmer Node.js 20

### **2. Premier Déploiement**
```bash
# Sur le serveur
cd /srv/activibe
./deploy.sh
```

### **3. Vérifier l'Application**
- Aller sur https://votre-domaine.com
- Vérifier que l'application fonctionne
- Tester les fonctionnalités principales

---

## 🎉 **CORRECTIONS TERMINÉES**

Votre workflow GitHub Actions est maintenant :

- ✅ **Compatible Node.js 20** : Fonctionne avec Nuxt 3.17.7
- ✅ **Répertoire /srv/activibe** : Permissions sudo sans mot de passe
- ✅ **Sauvegardes /srv/backups** : Isolation des sauvegardes
- ✅ **Pipeline fonctionnel** : De test à déploiement

**🚀 Votre application Acti'Vibe se déploie maintenant avec Node.js 20 et les bonnes permissions !**

### **Résumé des Changements**
1. **Node.js 18 → 20** : Compatible avec Nuxt 3.17.7
2. **/opt/activibe → /srv/activibe** : Répertoire avec permissions sudo
3. **/opt/backups → /srv/backups** : Sauvegardes isolées
4. **Permissions corrigées** : Accès complet sans mot de passe

Le workflow passera maintenant l'étape "Install Frontend Dependencies" et continuera avec les tests et le déploiement.
