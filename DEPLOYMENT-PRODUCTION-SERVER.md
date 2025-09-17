# Déploiement sur Serveur de Production

## Architecture

```
Internet -> Reverse Proxy (SSL) -> 10.0.0.244:3000 (Frontend)
                                -> 10.0.0.244:8080 (API)
```

## Prérequis sur le Serveur de Production

1. **Docker** et **Docker Compose** installés
2. **Ports 3000 et 8080** ouverts
3. **Accès réseau** à l'IP privée 10.0.0.244

## Fichiers Requis sur le Serveur

Copiez ces fichiers sur le serveur de production :

- `docker-compose.yml`
- `deploy_production_server.sh`
- `test_production_containers.sh`
- `.env` (avec les bonnes configurations)

## Déploiement

### 1. Copier les fichiers sur le serveur

```bash
# Sur votre machine locale
scp docker-compose.yml user@10.0.0.244:/path/to/project/
scp deploy_production_server.sh user@10.0.0.244:/path/to/project/
scp test_production_containers.sh user@10.0.0.244:/path/to/project/
scp .env user@10.0.0.244:/path/to/project/
```

### 2. Se connecter au serveur

```bash
ssh user@10.0.0.244
cd /path/to/project/
```

### 3. Rendre les scripts exécutables

```bash
chmod +x deploy_production_server.sh
chmod +x test_production_containers.sh
```

### 4. Lancer le déploiement

```bash
./deploy_production_server.sh
```

### 5. Tester le déploiement

```bash
./test_production_containers.sh
```

## Configuration du Reverse Proxy Externe

Le reverse proxy externe doit être configuré pour rediriger :

```nginx
# Frontend
activibe.be/ -> 10.0.0.244:3000

# API
activibe.be/api/* -> 10.0.0.244:8080/api/*
```

## Vérification

### 1. Conteneurs actifs

```bash
docker ps
```

### 2. Ports ouverts

```bash
netstat -tlnp | grep -E ":3000|:8080"
```

### 3. Tests de connectivité

```bash
# Frontend
curl http://10.0.0.244:3000

# API
curl http://10.0.0.244:8080/api/auth/login
```

## Dépannage

### Problème de port déjà utilisé

```bash
# Arrêter tous les conteneurs
docker-compose down

# Nettoyer les conteneurs orphelins
docker-compose down --remove-orphans

# Redémarrer
docker-compose up -d
```

### Problème de réseau

```bash
# Vérifier les conteneurs
docker ps

# Vérifier les logs
docker logs activibe-frontend
docker logs activibe-backend

# Vérifier les ports
netstat -tlnp | grep -E ":3000|:8080"
```

### Problème de configuration

```bash
# Vérifier le fichier .env
cat .env

# Vérifier la configuration Docker Compose
docker-compose config
```

## Maintenance

### Mise à jour des conteneurs

```bash
# Récupérer les dernières images
docker-compose pull

# Redémarrer avec les nouvelles images
docker-compose up -d
```

### Sauvegarde des données

```bash
# Sauvegarder les volumes Docker
docker run --rm -v activibe_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_backup.tar.gz -C /data .
```

### Restauration des données

```bash
# Restaurer les volumes Docker
docker run --rm -v activibe_mysql_data:/data -v $(pwd):/backup alpine tar xzf /backup/mysql_backup.tar.gz -C /data
```

## URLs d'Accès

- **Frontend** : https://activibe.be (via reverse proxy)
- **API** : https://activibe.be/api (via reverse proxy)
- **Frontend direct** : http://10.0.0.244:3000
- **API directe** : http://10.0.0.244:8080

## Support

En cas de problème, vérifiez :

1. Les logs des conteneurs
2. La configuration du reverse proxy externe
3. Les ports ouverts sur le serveur
4. La connectivité réseau entre le reverse proxy et le serveur
