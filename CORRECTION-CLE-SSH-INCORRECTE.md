# 🔧 Correction Authentification SSH - Clé Privée Incorrecte

## 📋 **PROBLÈME IDENTIFIÉ**

### **Erreur SSH**
```
debug1: Offering public key: /tmp/ssh_key ED25519 SHA256:H6JeDmbOKjvfs+ceSKPB2tqYeRVvLpQ2AMgWmDFPS4c explicit
debug1: Authentications that can continue: publickey,gssapi-keyex,gssapi-with-mic
debug1: No more authentication methods to try.
deploy@91.134.77.98: Permission denied (publickey,gssapi-keyex,gssapi-with-mic).
```

### **Cause**
- La clé privée SSH ne correspond pas à la clé publique autorisée sur le serveur
- Le serveur refuse l'authentification avec cette clé
- Hash de la clé : `SHA256:H6JeDmbOKjvfs+ceSKPB2tqYeRVvLpQ2AMgWmDFPS4c`

## ✅ **SOLUTIONS**

### **Solution 1 : Vérifier la clé publique sur le serveur**

Connectez-vous manuellement au serveur et vérifiez :
```bash
# Sur le serveur (91.134.77.98)
cat ~/.ssh/authorized_keys
# ou
cat /home/deploy/.ssh/authorized_keys
```

### **Solution 2 : Générer une nouvelle paire de clés**

#### **Étape 1 : Générer une nouvelle clé SSH**
```bash
# Sur votre machine locale
ssh-keygen -t ed25519 -C "deploy@activibe" -f ~/.ssh/activibe_deploy
```

#### **Étape 2 : Ajouter la clé publique au serveur**
```bash
# Copier la clé publique vers le serveur
ssh-copy-id -i ~/.ssh/activibe_deploy.pub deploy@91.134.77.98

# Ou manuellement
cat ~/.ssh/activibe_deploy.pub | ssh deploy@91.134.77.98 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

#### **Étape 3 : Mettre à jour le secret GitHub Actions**
```bash
# Copier la clé privée
cat ~/.ssh/activibe_deploy
```

Puis mettre à jour le secret `SERVER_SSH_KEY` dans GitHub Actions.

### **Solution 3 : Utiliser la clé existante**

Si vous avez la bonne clé privée :
1. **Trouver la clé privée** correspondant à la clé publique autorisée
2. **Mettre à jour le secret** `SERVER_SSH_KEY` dans GitHub Actions

## 🔍 **DIAGNOSTIC**

### **Vérifier la clé publique autorisée**
```bash
# Sur le serveur
ssh-keygen -lf /home/deploy/.ssh/authorized_keys
```

### **Vérifier votre clé privée**
```bash
# Sur votre machine locale
ssh-keygen -lf ~/.ssh/activibe_deploy.pub
```

Les hashs doivent correspondre !

## 🎯 **ACTION RECOMMANDÉE**

### **Option A : Générer une nouvelle clé (Recommandé)**
1. **Générer** une nouvelle paire de clés SSH
2. **Ajouter** la clé publique au serveur
3. **Mettre à jour** le secret `SERVER_SSH_KEY` dans GitHub Actions

### **Option B : Utiliser une clé existante**
1. **Trouver** la clé privée correspondant à la clé publique autorisée
2. **Mettre à jour** le secret `SERVER_SSH_KEY` dans GitHub Actions

## 🚀 **RÉSULTAT ATTENDU**

Avec la bonne clé privée :
```bash
debug1: Offering public key: /tmp/ssh_key ED25519 SHA256:[CORRECT_HASH] explicit
debug3: receive packet: type 51
debug1: Server accepts key: pkalg ssh-ed25519 blen 51
debug1: Authentication succeeded (publickey).
Authenticated to 91.134.77.98 ([91.134.77.98]:22).
SSH connection successful!
```

## 📝 **ÉTAPES DÉTAILLÉES**

### **Génération d'une nouvelle clé**
```bash
# 1. Générer la clé
ssh-keygen -t ed25519 -C "deploy@activibe" -f ~/.ssh/activibe_deploy

# 2. Afficher la clé publique (à ajouter au serveur)
cat ~/.ssh/activibe_deploy.pub

# 3. Afficher la clé privée (pour GitHub Actions)
cat ~/.ssh/activibe_deploy
```

### **Ajout au serveur**
```bash
# Méthode 1 : ssh-copy-id
ssh-copy-id -i ~/.ssh/activibe_deploy.pub deploy@91.134.77.98

# Méthode 2 : Manuel
cat ~/.ssh/activibe_deploy.pub | ssh deploy@91.134.77.98 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

**🎯 Choisissez une solution et mettez à jour le secret `SERVER_SSH_KEY` !**
