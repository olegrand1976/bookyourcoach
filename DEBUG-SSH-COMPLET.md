# 🔧 Debug SSH Complet - Diagnostic Approfondi

## 📋 **AMÉLIORATION DU DEBUG**

### **Nouveaux Étapes de Debug Ajoutées**

1. **Debug SSH Configuration** : Affiche les paramètres de connexion
2. **Test SSH Connection** : Teste la connexion avec output verbose
3. **Debug Mode** : Active le mode debug dans appleboy/ssh-action

## ✅ **INFORMATIONS DE DEBUG**

### **1. Debug SSH Configuration**
```bash
=== DEBUG SSH CONFIGURATION ===
SERVER_HOST: 91.134.77.98
SERVER_PORT: 22
SERVER_USERNAME: [nom d'utilisateur]
SSH_KEY length: [longueur de la clé]
KNOWN_HOSTS length: [longueur du known_hosts]
SSH_KEY starts with: -----BEGIN OPENSSH PRIVATE KEY-----...
KNOWN_HOSTS starts with: 91.134.77.98 ssh-rsa AAAAB3NzaC1yc2E...
=================================
```

### **2. Test SSH Connection**
```bash
=== TESTING SSH CONNECTION ===
Testing connection to 91.134.77.98:22

# Création des fichiers temporaires
echo "${{ secrets.SERVER_SSH_KEY }}" > /tmp/ssh_key
chmod 600 /tmp/ssh_key
echo "${{ secrets.SERVER_KNOWN_HOSTS }}" > /tmp/known_hosts
chmod 644 /tmp/known_hosts

# Test SSH avec output verbose
ssh -vvv -i /tmp/ssh_key -o UserKnownHostsFile=/tmp/known_hosts -o StrictHostKeyChecking=yes -p 22 username@91.134.77.98 "echo 'SSH connection successful!'"

# Nettoyage
rm -f /tmp/ssh_key /tmp/known_hosts
=================================
```

### **3. Debug Mode dans appleboy/ssh-action**
```yaml
- name: Deploy to server
  uses: appleboy/ssh-action@v1.0.0
  with:
    debug: true  # ← NOUVEAU
```

## 🔍 **DIAGNOSTIC ATTENDU**

### **Informations à Vérifier**

1. **Longueur des Secrets** :
   - `SSH_KEY length` : Doit être > 1000 caractères
   - `KNOWN_HOSTS length` : Doit être > 100 caractères

2. **Format des Secrets** :
   - `SSH_KEY starts with` : `-----BEGIN OPENSSH PRIVATE KEY-----`
   - `KNOWN_HOSTS starts with` : `91.134.77.98 ssh-rsa` ou `ssh-ed25519`

3. **Test SSH** :
   - Output verbose (`-vvv`) pour voir chaque étape
   - Messages d'erreur détaillés
   - Échec précis de l'authentification

## 🚀 **PROBLÈMES COURANTS IDENTIFIÉS**

### **1. Format de Clé SSH**
```bash
# CORRECT
-----BEGIN OPENSSH PRIVATE KEY-----
[contenu de la clé]
-----END OPENSSH PRIVATE KEY-----

# INCORRECT
-----BEGIN RSA PRIVATE KEY-----
[ancien format]
-----END RSA PRIVATE KEY-----
```

### **2. Format de known_hosts**
```bash
# CORRECT
91.134.77.98 ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC...
91.134.77.98 ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI...

# INCORRECT
91.134.77.98,192.168.1.1 ssh-rsa AAAAB3NzaC1yc2E...
```

### **3. Permissions SSH**
```bash
# Sur le serveur
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh/
chmod 600 ~/.ssh/id_rsa
```

## 📊 **OUTPUT VERBOSE SSH**

### **Messages d'Erreur Courants**
```bash
# Clé incorrecte
debug1: Offering public key: /tmp/ssh_key RSA SHA256:...
debug1: Authentications that can continue: publickey
debug1: No more authentication methods to try.

# Host non reconnu
debug1: Host '91.134.77.98' is not in the list of allowed hosts
Host key verification failed.

# Utilisateur incorrect
debug1: Authenticating to 91.134.77.98:22 as 'wronguser'
debug1: Authentications that can continue: publickey
```

## 🎯 **ACTIONS APRÈS DEBUG**

### **Si SSH_KEY est incorrecte** :
1. Vérifier le format (OpenSSH vs RSA)
2. Régénérer la clé si nécessaire
3. Ajouter la clé publique au serveur

### **Si KNOWN_HOSTS est incorrect** :
1. Obtenir le bon known_hosts :
   ```bash
   ssh-keyscan -H 91.134.77.98
   ```
2. Mettre à jour le secret GitHub

### **Si l'utilisateur est incorrect** :
1. Vérifier que l'utilisateur existe sur le serveur
2. Vérifier les permissions SSH

## 🎉 **RÉSULTAT ATTENDU**

Avec le debug complet, vous devriez voir :
1. ✅ **Configuration SSH** : Paramètres affichés
2. ✅ **Test de connexion** : Output verbose détaillé
3. ✅ **Erreur précise** : Localisation exacte du problème
4. ✅ **Solution claire** : Action corrective à effectuer

**🔍 Relancez le déploiement pour voir le debug complet !**
