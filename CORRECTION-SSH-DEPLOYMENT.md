# 🔧 Correction de la Connexion SSH - Déploiement Serveur

## 📋 **PROBLÈME RENCONTRÉ**

### **Erreur SSH**
```
ssh: handshake failed: ssh: unable to authenticate, attempted methods [none publickey], no supported methods remain
```

### **Cause**
- L'authentification SSH a échoué
- Les méthodes d'authentification disponibles ne sont pas supportées
- Problème avec la clé SSH ou les credentials

## ✅ **SOLUTIONS À VÉRIFIER**

### **1. Secrets GitHub Actions**

Vérifiez que ces secrets sont définis dans GitHub :
- **`SERVER_SSH_KEY`** : Clé privée SSH complète
- **`SERVER_USERNAME`** : Nom d'utilisateur du serveur

### **2. Variables GitHub Actions**

Vérifiez que ces variables sont définies :
- **`SERVER_HOST`** : 91.134.77.98
- **`SERVER_PORT`** : 22

### **3. Format de la Clé SSH**

La clé SSH doit être au format complet :
```
-----BEGIN OPENSSH PRIVATE KEY-----
[contenu de la clé privée]
-----END OPENSSH PRIVATE KEY-----
```

### **4. Configuration Serveur**

Sur le serveur (91.134.77.98), vérifiez :
- L'utilisateur existe
- La clé publique SSH est dans `~/.ssh/authorized_keys`
- SSH est configuré correctement

## 🔍 **DIAGNOSTIC**

### **Test de Connexion Manuel**
```bash
# Test de connexion SSH
ssh -i /path/to/private/key username@91.134.77.98

# Vérification des permissions
ls -la ~/.ssh/
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh/
```

### **Vérification des Logs SSH**
```bash
# Sur le serveur
sudo tail -f /var/log/auth.log

# Vérification du service SSH
sudo systemctl status ssh
```

## 🎯 **ÉTAPES DE RÉSOLUTION**

### **1. Vérifier les Secrets GitHub**
1. Aller dans **Settings** → **Secrets and variables** → **Actions**
2. Onglet **Secrets**
3. Vérifier `SERVER_SSH_KEY` et `SERVER_USERNAME`

### **2. Vérifier les Variables GitHub**
1. Onglet **Variables**
2. Vérifier `SERVER_HOST` et `SERVER_PORT`

### **3. Tester la Connexion SSH**
```bash
# Test local
ssh -i ~/.ssh/id_rsa username@91.134.77.98

# Test avec verbose
ssh -vvv -i ~/.ssh/id_rsa username@91.134.77.98
```

### **4. Configurer le Serveur**
```bash
# Sur le serveur
sudo mkdir -p /srv/activibe
sudo chown username:username /srv/activibe

# Vérifier Docker
docker --version
docker-compose --version
```

## 🚀 **STATUT ACTUEL**

| Étape | Statut | Détails |
|-------|--------|---------|
| Build Docker | ✅ | Réussi |
| Push DockerHub | ✅ | Réussi |
| Connexion SSH | ❌ | Échec d'authentification |
| Déploiement | ⏳ | En attente |

## 📝 **CONFIGURATION ATTENDUE**

### **Secrets GitHub Actions**
```yaml
SERVER_SSH_KEY: |
  -----BEGIN OPENSSH PRIVATE KEY-----
  [clé privée SSH complète]
  -----END OPENSSH PRIVATE KEY-----

SERVER_USERNAME: [nom d'utilisateur du serveur]
```

### **Variables GitHub Actions**
```yaml
SERVER_HOST: 91.134.77.98
SERVER_PORT: 22
```

## 🎉 **RÉSULTAT ATTENDU**

Une fois la connexion SSH corrigée :
1. ✅ **Connexion SSH** : Réussie
2. ✅ **Pull de l'image** : `docker pull olegrand1976/activibe-app:latest`
3. ✅ **Arrêt des conteneurs** : `docker-compose down`
4. ✅ **Démarrage des conteneurs** : `docker-compose up -d`
5. ✅ **Nettoyage** : `docker image prune -f`
6. ✅ **Statut** : `docker-compose ps`

## 🔧 **PROCHAINES ÉTAPES**

1. **Vérifier les secrets GitHub Actions**
2. **Tester la connexion SSH manuellement**
3. **Configurer le serveur si nécessaire**
4. **Relancer le déploiement**

**🎯 Une fois la connexion SSH corrigée, le déploiement devrait réussir !**
