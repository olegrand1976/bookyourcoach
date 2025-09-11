# 🚀 Configuration GitHub Actions - Déploiement Automatique

## 📋 **VARIABLES À CONFIGURER**

### **1. Variables GitHub (Repository > Settings > Secrets and variables > Actions > Variables)**

Ajoutez ces **Variables** (non sensibles, visibles) :

```
DOCKERHUB_USERNAME=votre_username_dockerhub
SERVER_HOST=91.134.77.98
SERVER_USERNAME=rocky
SERVER_PORT=22
```

### **2. Secrets GitHub (Repository > Settings > Secrets and variables > Actions > Secrets)**

Ajoutez ces **Secrets** (sensibles, chiffrés) :

```
DOCKERHUB_PASSWORD=votre_mot_de_passe_dockerhub
SERVER_SSH_KEY=votre_cle_privee_ssh_complete
```

---

## 🔑 **DÉTAIL DES VARIABLES**

### **Variables (Publiques)**

| Variable | Valeur | Description |
|----------|--------|-------------|
| `DOCKERHUB_USERNAME` | `olegrand1976` | Votre nom d'utilisateur DockerHub |
| `SERVER_HOST` | `91.134.77.98` | IP de votre serveur de production |
| `SERVER_USERNAME` | `rocky` | Nom d'utilisateur SSH sur le serveur |
| `SERVER_PORT` | `22` | Port SSH (généralement 22) |

### **Secrets (Privés)**

| Secret | Description | Format |
|--------|-------------|--------|
| `DOCKERHUB_PASSWORD` | Mot de passe DockerHub | Texte simple |
| `SERVER_SSH_KEY` | Clé privée SSH complète | Inclure `-----BEGIN` et `-----END` |

---

## 🔧 **EXEMPLE DE CLÉ SSH**

Votre `SERVER_SSH_KEY` doit ressembler à ceci :

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn
NhAAAAAwEAAQAAAYEA1234567890abcdef...
[... contenu de la clé ...]
...xyz789AAAAB3NzaC1yc2EAAAADAQABAAABgQC1234567890
-----END OPENSSH PRIVATE KEY-----
```

**⚠️ Important** : Copiez la clé privée **complète** avec les en-têtes `-----BEGIN` et `-----END`.

---

## 🎯 **WORKFLOW AUTOMATIQUE**

### **Déclenchement**

Le déploiement se lance automatiquement :
- ✅ **À chaque push sur `main`**
- ✅ **Manuellement** via l'onglet "Actions" de GitHub

### **Étapes du déploiement**

1. **🏗️ Build** : Construction de l'image Docker
2. **📤 Push** : Envoi vers DockerHub (`olegrand1976/activibe-app`)
3. **📝 Configuration** : Génération des fichiers de config
4. **🚀 Déploiement** : Installation sur le serveur
5. **🧪 Tests** : Vérification des services
6. **📧 Notification** : Résultat du déploiement

### **Services déployés automatiquement**

| Service | Port | Description |
|---------|------|-------------|
| **infiswap-front** | 80 | Page d'accueil (préservée) |
| **nginx-proxy** | 8081 | Application BookYourCoach |
| **Neo4j** | 7474 | Interface graphique Neo4j |
| **MySQL** | - | Base de données (interne) |
| **Redis** | - | Cache et sessions (interne) |

---

## 🔍 **VÉRIFICATION DE LA CONFIGURATION**

### **1. Tester les variables**

Allez dans **GitHub > Actions > Run workflow** et lancez manuellement le workflow pour vérifier.

### **2. Vérifier DockerHub**

- Repository : `https://hub.docker.com/r/olegrand1976/activibe-app`
- L'image doit être publique et accessible

### **3. Tester SSH**

```bash
# Sur votre machine locale
ssh rocky@91.134.77.98
```

Si la connexion fonctionne, GitHub Actions pourra aussi se connecter.

---

## 🚀 **PREMIER DÉPLOIEMENT**

### **Étape 1 : Configuration GitHub**

1. Aller dans **GitHub > Settings > Secrets and variables > Actions**
2. Ajouter toutes les **Variables** et **Secrets** listées ci-dessus
3. Vérifier que les valeurs sont correctes

### **Étape 2 : Lancement automatique**

```bash
# Sur votre machine locale
git add .
git commit -m "🚀 Configuration déploiement automatique"
git push origin main
```

### **Étape 3 : Surveillance**

1. Aller dans **GitHub > Actions**
2. Suivre l'exécution du workflow "🚀 Déploiement Production Automatique"
3. Consulter les logs pour voir le progression

### **Étape 4 : Vérification**

Une fois le déploiement terminé :

- ✅ **Application** : http://91.134.77.98:8081
- ✅ **Infiswap** : http://91.134.77.98:80
- ✅ **Neo4j** : http://91.134.77.98:7474

---

## 🆘 **DÉPANNAGE**

### **Erreur : Variable not found**

```
Error: The key 'SERVER_HOST' was not found
```

**Solution** : Vérifiez que la variable est dans l'onglet **"Variables"** (pas "Secrets").

### **Erreur : Permission denied (publickey)**

```
Permission denied (publickey)
```

**Solution** : 
1. Vérifiez que `SERVER_SSH_KEY` contient la clé privée complète
2. Testez la connexion SSH manuellement
3. Assurez-vous que la clé publique est dans `~/.ssh/authorized_keys` sur le serveur

### **Erreur : Docker login failed**

```
Error: Cannot perform an interactive login
```

**Solution** : Vérifiez `DOCKERHUB_USERNAME` et `DOCKERHUB_PASSWORD` dans les secrets.

### **Workflow ne se déclenche pas**

**Solutions** :
1. Vérifiez que vous poussez sur la branche `main`
2. Le workflow doit être dans `.github/workflows/`
3. Lancez manuellement via "Actions > Run workflow"

---

## 📊 **MONITORING**

### **Logs GitHub Actions**

- Consultez les logs détaillés dans GitHub > Actions
- Chaque étape affiche son progression
- Les erreurs sont clairement identifiées

### **Logs du serveur**

```bash
# Connexion au serveur
ssh rocky@91.134.77.98

# État des services
cd /srv/activibe
docker-compose -f docker-compose.nginx-proxy.yml ps

# Logs des services
docker-compose -f docker-compose.nginx-proxy.yml logs -f
```

---

## 🎉 **RÉSULTAT ATTENDU**

Après configuration et premier déploiement :

- ✅ **Déploiement automatique** à chaque push sur `main`
- ✅ **Application accessible** sur http://91.134.77.98:8081
- ✅ **Infiswap préservé** sur http://91.134.77.98:80
- ✅ **Services fonctionnels** (MySQL, Redis, Neo4j)
- ✅ **Monitoring intégré** via GitHub Actions

**🚀 Votre application BookYourCoach sera déployée automatiquement à chaque modification !**
