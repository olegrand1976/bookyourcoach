# 🚀 Guide de Déploiement Acti'Vibe sur Serveur Cloud

## 📋 **INFORMATIONS NÉCESSAIRES**

### **1. Comptes et Services Requis**
- ✅ **GitHub** : Repository avec le code source
- ✅ **DockerHub** : Compte pour stocker les images Docker
- ✅ **Serveur Cloud** : VPS/Cloud avec Ubuntu 20.04+ (minimum 2GB RAM, 2 CPU)
- ✅ **Nom de domaine** : activibe.com (ou votre domaine)

### **2. Variables et Secrets GitHub**
Configurez dans GitHub Settings > Secrets and variables > Actions :

#### **Variables (non sensibles)**
```
DOCKERHUB_USERNAME=votre_username_dockerhub
SERVER_HOST=ip_ou_domaine_serveur
SERVER_PORT=22
```

#### **Secrets (sensibles)**
```
DOCKERHUB_PASSWORD=votre_mot_de_passe_dockerhub
SERVER_USERNAME=utilisateur_ssh
SERVER_SSH_KEY=clé_privée_ssh
SLACK_WEBHOOK=url_webhook_slack (optionnel)
```

---

## 🔧 **CONFIGURATION SERVEUR CLOUD**

### **1. Préparation du Serveur**

#### **Connexion SSH**
```bash
ssh utilisateur@ip_serveur
```

#### **Mise à jour du système**
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip
```

#### **Installation de Docker**
```bash
# Supprimer les anciennes versions
sudo apt remove docker docker-engine docker.io containerd runc

# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER

# Installer Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Redémarrer la session
exit
ssh utilisateur@ip_serveur
```

#### **Vérification de l'installation**
```bash
docker --version
docker-compose --version
```

### **2. Configuration de l'Application**

#### **Créer le répertoire de l'application**
```bash
sudo mkdir -p /opt/activibe
sudo chown $USER:$USER /opt/activibe
cd /opt/activibe
```

#### **Copier les fichiers de configuration**
```bash
# Copier le docker-compose.prod.yml
# Copier le fichier .env (basé sur env.production.example)
# Copier le script deploy.sh
```

#### **Configuration des variables d'environnement**
```bash
cp env.production.example .env
nano .env
```

**Variables importantes à configurer :**
```bash
APP_KEY=base64:générez_une_clé_32_caractères
DB_PASSWORD=mot_de_passe_mysql_sécurisé
MYSQL_ROOT_PASSWORD=mot_de_passe_root_mysql_sécurisé
MAIL_HOST=votre_smtp_host
MAIL_USERNAME=votre_email
MAIL_PASSWORD=votre_mot_de_passe_email
```

#### **Générer la clé d'application Laravel**
```bash
# Sur votre machine locale
php artisan key:generate --show
# Copier la clé générée dans APP_KEY du fichier .env
```

### **3. Configuration SSL avec Let's Encrypt**

#### **Installation de Certbot**
```bash
sudo apt install certbot python3-certbot-nginx
```

#### **Configuration du domaine**
```bash
# Éditer le fichier hosts si nécessaire
sudo nano /etc/hosts
# Ajouter : ip_serveur activibe.com www.activibe.com
```

#### **Génération du certificat SSL**
```bash
sudo certbot --nginx -d activibe.com -d www.activibe.com
```

#### **Configuration automatique du renouvellement**
```bash
sudo crontab -e
# Ajouter : 0 12 * * * /usr/bin/certbot renew --quiet
```

### **4. Configuration du Firewall**

#### **UFW (Ubuntu Firewall)**
```bash
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

---

## 🐳 **CONFIGURATION DOCKERHUB**

### **1. Créer un compte DockerHub**
- Aller sur https://hub.docker.com
- Créer un compte
- Créer un repository public nommé `activibe/app`

### **2. Configurer GitHub Variables et Secrets**
Dans GitHub > Settings > Secrets and variables > Actions :

#### **Variables (onglet Variables)**
- `DOCKERHUB_USERNAME` : votre nom d'utilisateur DockerHub
- `SERVER_HOST` : IP ou domaine de votre serveur
- `SERVER_PORT` : 22 (port SSH par défaut)

#### **Secrets (onglet Secrets)**
- `DOCKERHUB_PASSWORD` : votre mot de passe DockerHub
- `SERVER_USERNAME` : nom d'utilisateur SSH
- `SERVER_SSH_KEY` : clé privée SSH complète

---

## 🔑 **CONFIGURATION SSH**

### **1. Générer une paire de clés SSH**
```bash
# Sur votre machine locale
ssh-keygen -t rsa -b 4096 -C "github-actions"
# Sauvegarder dans ~/.ssh/github_actions
```

### **2. Copier la clé publique sur le serveur**
```bash
ssh-copy-id -i ~/.ssh/github_actions.pub utilisateur@ip_serveur
```

### **3. Configurer GitHub Variables et Secrets**
- **Variables** : `SERVER_HOST`, `SERVER_PORT`
- **Secrets** : `SERVER_USERNAME`, `SERVER_SSH_KEY`

---

## 🚀 **DÉPLOIEMENT**

### **1. Premier déploiement manuel**
```bash
# Sur le serveur
cd /opt/activibe
chmod +x deploy.sh
./deploy.sh
```

### **2. Déploiement automatique via GitHub Actions**
- Pousser le code sur la branche `main` ou `production`
- Le workflow GitHub Actions se déclenche automatiquement
- Vérifier les logs dans GitHub Actions

### **3. Vérification du déploiement**
```bash
# Vérifier les conteneurs
docker ps

# Vérifier les logs
docker-compose -f docker-compose.prod.yml logs

# Tester l'application
curl http://localhost/health
```

---

## 📊 **MONITORING ET MAINTENANCE**

### **1. Surveillance des logs**
```bash
# Logs de l'application
docker-compose -f docker-compose.prod.yml logs -f app

# Logs Nginx
docker-compose -f docker-compose.prod.yml logs -f nginx

# Logs MySQL
docker-compose -f docker-compose.prod.yml logs -f mysql
```

### **2. Sauvegardes automatiques**
```bash
# Créer un cron job pour les sauvegardes
crontab -e
# Ajouter : 0 2 * * * /opt/activibe/backup.sh
```

### **3. Mise à jour de l'application**
- Les mises à jour se font automatiquement via GitHub Actions
- Pour une mise à jour manuelle : `./deploy.sh`

---

## 🔒 **SÉCURITÉ**

### **1. Configuration de sécurité**
- ✅ **Firewall** : UFW configuré
- ✅ **SSL** : Certificat Let's Encrypt
- ✅ **Headers de sécurité** : Configurés dans Nginx
- ✅ **Rate limiting** : Configuré dans Nginx
- ✅ **Mots de passe forts** : Pour MySQL et services

### **2. Surveillance**
- ✅ **Logs** : Surveillance des erreurs
- ✅ **Métriques** : Monitoring des performances
- ✅ **Sauvegardes** : Automatiques quotidiennes

---

## 🆘 **DÉPANNAGE**

### **1. Problèmes courants**

#### **Application non accessible**
```bash
# Vérifier les conteneurs
docker ps

# Vérifier les logs
docker-compose logs app

# Redémarrer les services
docker-compose restart
```

#### **Problème de base de données**
```bash
# Vérifier MySQL
docker-compose logs mysql

# Accéder à MySQL
docker-compose exec mysql mysql -u root -p
```

#### **Problème SSL**
```bash
# Renouveler le certificat
sudo certbot renew

# Vérifier la configuration Nginx
sudo nginx -t
```

### **2. Commandes utiles**
```bash
# Redémarrer tous les services
docker-compose restart

# Voir l'utilisation des ressources
docker stats

# Nettoyer les images inutilisées
docker system prune -a

# Sauvegarder la base de données
docker-compose exec mysql mysqldump -u root -p activibe_prod > backup.sql
```

---

## 📞 **SUPPORT**

### **1. Logs GitHub Actions**
- Aller dans GitHub > Actions
- Consulter les logs du workflow "Deploy to Cloud Server"

### **2. Logs serveur**
```bash
# Logs système
sudo journalctl -u docker

# Logs application
tail -f /opt/activibe/logs/app.log
```

### **3. Contact**
- **Email** : support@activibe.com
- **Documentation** : [Lien vers la documentation]
- **Issues** : [Lien vers GitHub Issues]

---

## ✅ **CHECKLIST DE DÉPLOIEMENT**

### **Pré-déploiement**
- [ ] Serveur cloud configuré
- [ ] Docker et Docker Compose installés
- [ ] Compte DockerHub créé
- [ ] Clés SSH configurées
- [ ] Variables GitHub Secrets configurées
- [ ] Nom de domaine configuré

### **Déploiement**
- [ ] Fichiers de configuration copiés
- [ ] Variables d'environnement configurées
- [ ] Premier déploiement manuel réussi
- [ ] SSL configuré
- [ ] Firewall configuré

### **Post-déploiement**
- [ ] Application accessible via HTTPS
- [ ] Base de données fonctionnelle
- [ ] Emails fonctionnels
- [ ] Sauvegardes automatiques
- [ ] Monitoring configuré

**🎉 Votre application Acti'Vibe est maintenant déployée en production !**
