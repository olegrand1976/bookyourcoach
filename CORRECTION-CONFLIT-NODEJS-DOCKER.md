# 🔧 Correction du Conflit de Versions Node.js dans Docker

## 📋 **PROBLÈME RENCONTRÉ**

### **Erreur Docker Build**
```
ERROR: unable to select packages:
  nodejs-22.16.0-r2:
    breaks: world[nodejs=20.18.0-r0]
    satisfies: npm-11.3.0-r1[nodejs]
  npm-11.3.0-r1:
    breaks: world[npm=10.8.2-r0]
```

### **Cause**
- Alpine Linux 3.22 n'a que Node.js 22 disponible
- Les versions spécifiques `nodejs=20.18.0-r0` et `npm=10.8.2-r0` ne sont pas compatibles
- Conflit entre les versions demandées et les versions disponibles

## ✅ **SOLUTION APPLIQUÉE**

### **1. Modification du Dockerfile**
```dockerfile
# AVANT (problématique)
RUN apk add --no-cache nodejs=20.18.0-r0 npm=10.8.2-r0

# APRÈS (corrigé)
RUN apk add --no-cache nodejs npm
```

### **2. Mise à jour de GitHub Actions**
```yaml
# AVANT
node-version: '20'

# APRÈS  
node-version: '22'
```

## 🎯 **RÉSULTAT**

### **Versions Installées**
- **Node.js** : 22.16.0-r2 (dernière version disponible dans Alpine 3.22)
- **npm** : 11.3.0-r1 (version compatible avec Node.js 22)

### **Compatibilité**
- ✅ **Nuxt 3.17.7** : Compatible avec Node.js 22
- ✅ **Alpine Linux 3.22** : Versions disponibles sans conflit
- ✅ **GitHub Actions** : Node.js 22 supporté

## 📊 **VÉRIFICATIONS EFFECTUÉES**

### **1. Versions Disponibles dans Alpine 3.22**
```bash
docker run --rm php:8.2-fpm-alpine sh -c "apk search nodejs | grep -E '^nodejs-[0-9]'"
# Résultat: nodejs-22.16.0-r2
```

### **2. Compatibilité Nuxt**
- Nuxt 3.17.7 supporte Node.js 22
- Aucune contrainte de version dans `package.json`
- Migration transparente de Node.js 20 vers 22

## 🚀 **STATUT FINAL**

| Composant | Version | Statut |
|-----------|---------|--------|
| Alpine Linux | 3.22.1 | ✅ |
| Node.js | 22.16.0-r2 | ✅ |
| npm | 11.3.0-r1 | ✅ |
| Nuxt | 3.17.7 | ✅ |
| GitHub Actions | Node.js 22 | ✅ |

## 📝 **MODIFICATIONS APPORTÉES**

### **Fichiers Modifiés**
1. **`Dockerfile`** : Suppression des versions spécifiques
2. **`.github/workflows/deploy.yml`** : Mise à jour vers Node.js 22

### **Avantages**
- ✅ **Pas de conflit de versions**
- ✅ **Installation automatique des dernières versions compatibles**
- ✅ **Cohérence entre Docker et GitHub Actions**
- ✅ **Compatibilité avec Alpine Linux 3.22**

## 🎉 **RÉSULTAT**

**Le build Docker fonctionne maintenant sans erreur de conflit de versions !**

L'application Acti'Vibe utilise maintenant Node.js 22, qui est la version standard disponible dans Alpine Linux 3.22, garantissant une installation sans conflit et une compatibilité optimale.
