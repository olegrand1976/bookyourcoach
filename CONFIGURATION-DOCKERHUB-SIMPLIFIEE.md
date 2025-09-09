# 🔧 Configuration Simplifiée DockerHub

## ✅ **CONFIGURATION MISE À JOUR**

J'ai adapté la configuration pour utiliser votre **username** et **password** DockerHub directement, ce qui est plus simple.

---

## 📋 **VARIABLES GITHUB SECRETS À CONFIGURER**

Dans GitHub > Settings > Secrets and variables > Actions, ajoutez :

### **Variables Obligatoires**
```
DOCKERHUB_USERNAME=votre_username_dockerhub
DOCKERHUB_PASSWORD=votre_mot_de_passe_dockerhub
SERVER_HOST=ip_ou_domaine_serveur
SERVER_USERNAME=utilisateur_ssh
SERVER_SSH_KEY=clé_privée_ssh
SERVER_PORT=22
```

### **Variables Optionnelles**
```
SLACK_WEBHOOK=url_webhook_slack (pour notifications)
```

---

## 🐳 **ÉTAPES DOCKERHUB**

### **1. Créer le Repository**
- Aller sur https://hub.docker.com
- Cliquer sur "Create Repository"
- Nom : `activibe/app`
- Visibilité : **Public** (gratuit)
- Description : "Acti'Vibe Application"

### **2. Configurer GitHub Secrets**
- Aller dans votre repository GitHub
- Settings > Secrets and variables > Actions
- Cliquer sur "New repository secret"
- Ajouter chaque variable ci-dessus

---

## 🚀 **DÉPLOIEMENT**

### **1. Premier Déploiement**
```bash
# Sur votre serveur cloud
cd /opt/activibe
./deploy.sh
```

### **2. Déploiement Automatique**
- Pousser le code sur la branche `main`
- GitHub Actions se déclenche automatiquement
- L'image est construite et poussée sur DockerHub
- L'application est déployée sur votre serveur

---

## ✅ **AVANTAGES DE CETTE CONFIGURATION**

- ✅ **Plus simple** : Pas besoin de générer un token
- ✅ **Plus direct** : Utilise directement vos identifiants
- ✅ **Plus rapide** : Configuration en 2 minutes
- ✅ **Plus sûr** : Les mots de passe sont chiffrés dans GitHub

---

## 🔍 **VÉRIFICATION**

### **1. Vérifier DockerHub**
- Aller sur https://hub.docker.com/r/votre_username/activibe-app
- Vérifier que l'image est bien poussée

### **2. Vérifier GitHub Actions**
- Aller dans GitHub > Actions
- Vérifier que le workflow "Deploy to Cloud Server" s'exécute
- Consulter les logs pour voir le déploiement

### **3. Vérifier l'Application**
- Aller sur https://votre-domaine.com
- Vérifier que l'application fonctionne

---

## 🆘 **DÉPANNAGE**

### **Problème : Erreur de connexion DockerHub**
```
Error: Cannot perform an interactive login from a non TTY device
```
**Solution** : Vérifier que `DOCKERHUB_USERNAME` et `DOCKERHUB_PASSWORD` sont correctement configurés dans GitHub Secrets.

### **Problème : Erreur de push**
```
Error: denied: requested access to the resource is denied
```
**Solution** : Vérifier que le repository `activibe/app` existe sur DockerHub et est public.

### **Problème : Erreur SSH**
```
Error: Permission denied (publickey)
```
**Solution** : Vérifier que `SERVER_SSH_KEY` contient la clé privée complète (avec `-----BEGIN OPENSSH PRIVATE KEY-----`).

---

## 🎯 **RÉSUMÉ**

Avec cette configuration simplifiée :

1. **Configurez** les 6 variables GitHub Secrets
2. **Créez** le repository `activibe/app` sur DockerHub
3. **Poussez** le code sur la branche `main`
4. **L'application** se déploie automatiquement !

**🚀 Votre application Acti'Vibe sera en ligne en quelques minutes !**
