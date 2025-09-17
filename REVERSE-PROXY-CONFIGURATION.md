# Configuration du Reverse Proxy Externe

## Architecture Actuelle

```
Internet -> Reverse Proxy (SSL) -> 10.0.0.244:3000 (Frontend)
                                -> 10.0.0.244:8080 (API via proxy interne)
```

## Configuration Requise pour le Reverse Proxy Externe

Le reverse proxy externe doit être configuré pour rediriger :

### 1. Frontend (Port 3000)
```
activibe.be/ -> 10.0.0.244:3000
```

### 2. API (Port 8080)
```
activibe.be/api/* -> 10.0.0.244:8080/api/*
```

## Exemple de Configuration Nginx pour le Reverse Proxy Externe

```nginx
server {
    listen 443 ssl http2;
    server_name activibe.be www.activibe.be;
    
    # Certificat SSL (géré par le reverse proxy)
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Frontend - Redirection vers le port 3000
    location / {
        proxy_pass http://10.0.0.244:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # WebSocket support pour HMR
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
    
    # API - Redirection vers le port 8080
    location /api {
        proxy_pass http://10.0.0.244:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # CORS Headers
        add_header Access-Control-Allow-Origin "https://activibe.be" always;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS, PATCH" always;
        add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept" always;
        add_header Access-Control-Allow-Credentials "true" always;
        
        # Handle preflight requests
        if ($request_method = 'OPTIONS') {
            add_header Access-Control-Allow-Origin "https://activibe.be";
            add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS, PATCH";
            add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept";
            add_header Access-Control-Allow-Credentials "true";
            add_header Access-Control-Max-Age 1728000;
            add_header Content-Type "text/plain charset=UTF-8";
            add_header Content-Length 0;
            return 204;
        }
    }
}
```

## Configuration sur le Serveur Interne (10.0.0.244)

### 1. Docker Compose
```yaml
services:
  backend:
    ports:
      - "8080:80"  # Exposition pour le reverse proxy
    networks:
      - app-network

  frontend:
    ports:
      - "3000:3000"  # Exposition pour le reverse proxy
    networks:
      - app-network
```

### 2. Nginx Interne (Optionnel)
Si vous voulez un proxy interne sur le serveur :

```nginx
server {
    listen 8080;
    server_name localhost;
    
    location /api {
        proxy_pass http://activibe-backend:80;
        # ... configuration proxy
    }
}
```

## Flux de Communication

1. **Navigateur** -> `https://activibe.be/api/auth/login`
2. **Reverse Proxy Externe** -> `http://10.0.0.244:8080/api/auth/login`
3. **Serveur Interne** -> `http://activibe-backend:80/api/auth/login`
4. **Conteneur Backend** -> Traite la requête et répond
5. **Réponse** -> Remonte la chaîne en sens inverse

## Avantages de cette Architecture

- ✅ Certificat SSL géré centralement
- ✅ Communication interne sécurisée
- ✅ Pas d'exposition directe des conteneurs
- ✅ Scalabilité facile
- ✅ Gestion centralisée des domaines
