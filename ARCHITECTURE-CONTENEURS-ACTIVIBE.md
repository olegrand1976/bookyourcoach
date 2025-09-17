# Architecture Conteneurs pour activibe.be

## 🏗️ Architecture Actuelle

### Conteneurs Docker
```
┌─────────────────────────────────────────────────────────────┐
│                    Serveur (activibe.be)                   │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   Nginx     │  │  Frontend   │  │   Backend   │        │
│  │  (Proxy)    │  │  (Nuxt.js)  │  │  (Laravel)  │        │
│  │             │  │             │  │             │        │
│  │ Port 443    │  │ Port 3000   │  │ Port 80     │        │
│  │ (HTTPS)     │  │ (Interne)   │  │ (Interne)   │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
│         │                │                │                │
│         └────────────────┼────────────────┘                │
│                          │                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │  phpMyAdmin │  │    Neo4j    │  │   MySQL     │        │
│  │             │  │             │  │             │        │
│  │ Port 8082   │  │ Port 7474   │  │ Port 3306   │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
```

### Flux de Communication

1. **Utilisateur** → `https://activibe.be` (HTTPS)
2. **Nginx** → Proxy vers `activibe-frontend:3000` (HTTP interne)
3. **Frontend** → `http://activibe-backend:80/api` (HTTP interne)
4. **Backend** → Base de données MySQL (interne)

## 🔧 Configuration

### Frontend (Nuxt.js)
```typescript
// nuxt.config.ts
runtimeConfig: {
    public: {
        apiBase: 'http://activibe-backend:80/api'  // Communication interne
    }
}
```

### Backend (Laravel)
```yaml
# docker-compose.yml
services:
  backend:
    ports:
      - "8080:80"  # Exposition pour accès direct si nécessaire
    networks:
      - app-network
```

### Nginx (Proxy)
```nginx
# activibe-frontend-proxy.conf
location /api {
    proxy_pass http://activibe-backend:80;  # Proxy vers le conteneur
}

location / {
    proxy_pass http://activibe-frontend:3000;  # Proxy vers le frontend
}
```

## 🚀 Avantages de cette Architecture

1. **Sécurité** : L'API n'est pas exposée directement sur Internet
2. **Performance** : Communication interne entre conteneurs
3. **Scalabilité** : Facile d'ajouter des conteneurs
4. **Isolation** : Chaque service dans son propre conteneur
5. **HTTPS** : SSL/TLS géré par Nginx

## 🔍 Tests de Vérification

```bash
# Test du site principal
curl -I https://activibe.be

# Test de l'API via proxy
curl -I https://activibe.be/api/auth/login

# Test direct du conteneur backend (si nécessaire)
curl -I http://localhost:8080/api/auth/login
```

## 📝 Notes Importantes

- L'API n'est accessible que via le proxy Nginx
- La communication entre conteneurs se fait en HTTP (interne)
- Seul Nginx gère HTTPS/SSL
- Les ports 8080 et 3000 sont exposés pour debug si nécessaire
