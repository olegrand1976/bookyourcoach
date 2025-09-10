# 🔧 Correction SSH - Ajout de known_hosts

## 📋 **PROBLÈME RENCONTRÉ**

### **Erreur SSH**
```
ssh: handshake failed: ssh: unable to authenticate, attempted methods [none publickey], no supported methods remain
```

### **Cause Identifiée**
- Les paramètres SSH ont été récupérés de GitLab
- GitHub Actions gère différemment les `known_hosts`
- La configuration SSH manque le fichier `known_hosts`

## ✅ **SOLUTION APPLIQUÉE**

### **Ajout de known_hosts**
```yaml
# AVANT
- name: Deploy to server
  uses: appleboy/ssh-action@v1.0.0
  with:
    host: ${{ vars.SERVER_HOST }}
    username: ${{ secrets.SERVER_USERNAME }}
    key: ${{ secrets.SERVER_SSH_KEY }}
    port: ${{ vars.SERVER_PORT }}

# APRÈS
- name: Deploy to server
  uses: appleboy/ssh-action@v1.0.0
  with:
    host: ${{ vars.SERVER_HOST }}
    username: ${{ secrets.SERVER_USERNAME }}
    key: ${{ secrets.SERVER_SSH_KEY }}
    port: ${{ vars.SERVER_PORT }}
    known_hosts: ${{ secrets.SERVER_KNOWN_HOSTS }}
    timeout: 30s
    command_timeout: 10m
```

## 🎯 **CONFIGURATION REQUISE**

### **Secrets GitHub Actions**
Ajoutez ce nouveau secret dans **Settings** → **Secrets and variables** → **Actions** → **Secrets** :

- **`SERVER_KNOWN_HOSTS`** : Contenu du fichier `known_hosts`

### **Format du known_hosts**
```
91.134.77.98 ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC...
91.134.77.98 ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI...
```

### **Comment obtenir le known_hosts**
```bash
# Sur votre machine locale
ssh-keyscan -H 91.134.77.98

# Ou depuis votre ancien GitLab
cat ~/.ssh/known_hosts | grep 91.134.77.98
```

## 📊 **DIFFÉRENCES GITLAB vs GITHUB**

| Paramètre | GitLab | GitHub Actions |
|-----------|--------|----------------|
| known_hosts | Automatique | Manuel (secret) |
| Clé SSH | Variable | Secret |
| Host | Variable | Variable |
| Username | Variable | Secret |

## 🚀 **STATUT ACTUEL**

| Étape | Statut | Détails |
|-------|--------|---------|
| Build Docker | ✅ | Réussi |
| Push DockerHub | ✅ | Réussi |
| Configuration SSH | ✅ | Corrigée |
| Déploiement | ⏳ | En attente du secret |

## 📝 **ÉTAPES SUIVANTES**

### **1. Ajouter le Secret**
1. Aller dans **Settings** → **Secrets and variables** → **Actions**
2. Onglet **Secrets**
3. Cliquer **"New repository secret"**
4. Nom : `SERVER_KNOWN_HOSTS`
5. Valeur : Contenu du fichier `known_hosts`

### **2. Obtenir le known_hosts**
```bash
# Méthode 1 : Depuis votre machine
ssh-keyscan -H 91.134.77.98

# Méthode 2 : Depuis GitLab (si accessible)
cat ~/.ssh/known_hosts | grep 91.134.77.98
```

### **3. Relancer le Déploiement**
Une fois le secret ajouté, relancer le workflow GitHub Actions.

## 🎉 **RÉSULTAT ATTENDU**

Avec le `known_hosts` configuré :
1. ✅ **Connexion SSH** : Réussie
2. ✅ **Pull de l'image** : `docker pull olegrand1976/activibe-app:latest`
3. ✅ **Arrêt des conteneurs** : `docker-compose down`
4. ✅ **Démarrage des conteneurs** : `docker-compose up -d`
5. ✅ **Nettoyage** : `docker image prune -f`
6. ✅ **Statut** : `docker-compose ps`

## 🔧 **CONFIGURATION COMPLÈTE**

### **Secrets Requis**
```yaml
SERVER_SSH_KEY: |
  -----BEGIN OPENSSH PRIVATE KEY-----
  [clé privée SSH complète]
  -----END OPENSSH PRIVATE KEY-----

SERVER_USERNAME: [nom d'utilisateur du serveur]

SERVER_KNOWN_HOSTS: |
  91.134.77.98 ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC...
  91.134.77.98 ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI...
```

### **Variables Requises**
```yaml
SERVER_HOST: 91.134.77.98
SERVER_PORT: 22
```

**🎯 Une fois le secret `SERVER_KNOWN_HOSTS` ajouté, le déploiement devrait réussir !**
