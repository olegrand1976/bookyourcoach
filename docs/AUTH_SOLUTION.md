# Solution d'authentification pour Acti'Vibe

## 🎯 Problème résolu

La boucle infinie d'authentification en local a été résolue en créant une solution qui gère différemment les environnements local et de production.

## 🔧 Solution implémentée

### 1. **Contrôleur d'authentification adaptatif** (`app/Http/Controllers/Api/AuthControllerSimple.php`)

- **Mode local** : Authentification simple avec token
- **Mode production** : Authentification Sanctum SPA avec cookies de session

```php
// Vérifier l'environnement
$isLocal = app()->environment('local');

if ($isLocal) {
    // Mode local : authentification par session simple
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    $user = Auth::user();
    $token = $user->createToken('local-api-token')->plainTextToken;
    // ...
} else {
    // Mode production : authentification Sanctum SPA
    if (Auth::attempt($request->only('email', 'password'))) {
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;
        // ...
    }
}
```

### 2. **Store d'authentification adaptatif** (`frontend/stores/auth.ts`)

- **Mode local** : Connexion simple sans cookies de session
- **Mode production** : Connexion avec Sanctum et cookies sécurisés

```typescript
const config = useRuntimeConfig()
const isLocal = config.public.apiBase.includes('localhost') || config.public.apiBase.includes('127.0.0.1')

if (isLocal) {
    // Mode local : connexion simple avec token
    const response = await $fetch('/auth/login', {
        method: 'POST',
        baseURL: config.public.apiBase,
        body: credentials,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
} else {
    // Mode production : connexion avec Sanctum
    const response = await $fetch('/auth/login', {
        method: 'POST',
        baseURL: config.public.apiBase,
        body: credentials,
        credentials: 'include', // Important pour Sanctum
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
}
```

### 3. **Plugin API adaptatif** (`frontend/plugins/api.client.ts`)

- **Mode local** : Pas de cookies de session
- **Mode production** : Cookies de session Sanctum

```typescript
const isLocal = config.public.apiBase.includes('localhost') || config.public.apiBase.includes('127.0.0.1')

const api = axios.create({
    baseURL: config.public.apiBase,
    withCredentials: isLocal ? false : true, // Sanctum seulement en production
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(isLocal ? {} : { 'X-Requested-With': 'XMLHttpRequest' }) // Sanctum seulement en production
    }
})
```

## 🚀 Configuration requise

### **Environnement local** (déjà configuré)

```bash
# docker-compose.local.yml
environment:
  - NUXT_PUBLIC_API_BASE=http://localhost:8080/api
  - NUXT_API_BASE=http://localhost:8080/api
  - FRONTEND_URL=http://localhost:3000
```

### **Environnement de production**

```bash
# .env de production
SANCTUM_STATEFUL_DOMAINS=activibe.be,www.activibe.be,localhost:3000,127.0.0.1:3000
FRONTEND_URL=https://activibe.be
CORS_ALLOWED_ORIGINS=https://activibe.be,https://www.activibe.be
SESSION_DRIVER=redis
SESSION_DOMAIN=.activibe.be
SESSION_PATH=/
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

## 🧪 Tests

### **Test local**
```bash
./test_auth_local.sh
```

### **Test production**
```bash
./test_auth_production.sh
```

## ✅ Résultats

### **Environnement local**
- ✅ Connexion sans boucle infinie
- ✅ Token d'authentification fonctionnel
- ✅ Dashboard enseignant accessible
- ✅ Données utilisateur récupérées correctement

### **Environnement de production**
- ✅ Authentification Sanctum SPA
- ✅ Cookies de session sécurisés
- ✅ CORS configuré pour le domaine
- ✅ Plus de boucle infinie

## 🔄 Déploiement

### **Local**
```bash
docker-compose -f docker-compose.local.yml up -d
```

### **Production**
```bash
./scripts/deploy-production.sh
```

## 🎉 Avantages de cette solution

1. **Simplicité en local** : Pas de complexité Sanctum pour le développement
2. **Sécurité en production** : Authentification Sanctum SPA complète
3. **Détection automatique** : L'environnement est détecté automatiquement
4. **Maintenance facile** : Une seule base de code pour les deux environnements
5. **Tests automatisés** : Scripts de test pour vérifier le bon fonctionnement

## 🚨 Points d'attention

- **Variables d'environnement** : S'assurer que `APP_ENV` est correctement défini
- **CORS** : Vérifier que les domaines sont correctement configurés
- **Cookies** : En production, s'assurer que les cookies sont sécurisés
- **Tokens** : Vérifier que les tokens sont correctement générés et validés

Cette solution résout définitivement le problème de boucle infinie tout en maintenant une authentification robuste et sécurisée pour les deux environnements.
