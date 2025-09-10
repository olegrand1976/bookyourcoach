# 🔧 Correction known_hosts - Clé ED25519 Manquante

## 📋 **PROBLÈME IDENTIFIÉ**

### **Erreur SSH**
```
No ED25519 host key is known for 91.134.77.98 and you have requested strict checking.
Host key verification failed.
```

### **Cause**
- Le serveur utilise maintenant la clé ED25519
- Votre `known_hosts` ne contient que la clé RSA
- SSH refuse la connexion par sécurité

## ✅ **SOLUTION**

### **known_hosts Complet**
Ajoutez ce contenu dans le secret `SERVER_KNOWN_HOSTS` :

```
91.134.77.98 ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQCzrV3IqSUGbJJr+f9O1+1zOLK+efZRJO9aScyv9SIyjQIDISEjDHhiz8hVtzxNX1Jo2gRRffDCOf6JMYl1UtZQ7eacgOZNQq1ZSYgmkoFegZxmc6S3IGcTLMAkZmb4XfGezzdNdqPt7PerAnQibP/bFfxfURLeuW64KLQACGVwIptXHxbPgVp8PJjaylkBqaRy4s//Jdfi7taUkCCF/zVJ2cz9AsRcMrn8+fuSriDRNfiOyxNj16bHlWJMATA6lgL0C7PO+fRV5N7/2pHLFSfupYDfqD7QTMkIId1h6XPdffvNx+JWEaAqhJcX1mPguWSwUJRWXKL+4s3+CT78aQHjFzoALpEN5PB8/CsoZr0GJ5em4UyOKkoHve1R6wbotdBp7DinaweRo23e3KVDoxcmo8UJPfDku15w/STnELm95+6eCkgY7BDFzuI59/TPRCfCSgWc9dzTnSoVHlfqvw3CGZO/DyOb0unox32Ukc0PN60oRaX9MG9YHQoYmZgaLM0=
91.134.77.98 ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIBEQbwA+uTNnomBROORLmM3B359IxRXzWKVPh73kXZXo
91.134.77.98 ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBJyELEbpRtvSrqMsXPrthDRyehBOxwkiQ8tZd+/G0YJ4k86Tbs0jdkm6YZzVFipTPdek/adf0IEj4UGo/Fp241Y=
```

## 🎯 **ACTION REQUISE**

1. **Aller dans GitHub Actions** → **Settings** → **Secrets and variables** → **Actions**
2. **Onglet Secrets**
3. **Éditer le secret `SERVER_KNOWN_HOSTS`**
4. **Remplacer le contenu** par le known_hosts complet ci-dessus
5. **Sauvegarder**

## 🚀 **RÉSULTAT ATTENDU**

Avec le known_hosts complet :
```bash
debug1: Server host key: ssh-ed25519 SHA256:NZu2h0w5p3P5LnyDdWj+mv9dgVzN0jd4XFjS2v2pW0Y
debug1: load_hostkeys: fopen /tmp/known_hosts: No such file or directory
debug1: Host '91.134.77.98' is known and matches the ED25519 host key.
debug1: Found key in /tmp/known_hosts:1
debug1: Offering public key: /tmp/ssh_key RSA SHA256:... user@host
debug1: Server accepts key: pkalg rsa-sha2-512 blen 279
debug1: Authentication succeeded (publickey).
Authenticated to 91.134.77.98 ([91.134.77.98]:22).
SSH connection successful!
```

**🎯 Mettez à jour le secret SERVER_KNOWN_HOSTS et relancez le déploiement !**
