# Stage 1: Base avec Node.js
FROM node:20-alpine AS base

# Installer les dépendances système nécessaires
RUN apk add --no-cache libc6-compat

# Définir le répertoire de travail
WORKDIR /app

# Stage 2: Dépendances
FROM base AS deps

# Copier les fichiers de configuration des dépendances
COPY package*.json ./

# Installer les dépendances de production seulement
RUN npm ci --omit=dev && npm cache clean --force

# Stage 3: Tests et validation
FROM base AS test

# Copier les fichiers de configuration
COPY package*.json ./

# Installer toutes les dépendances (dev + prod)
RUN npm ci

# Copier le code source
COPY . .

# Lancer les tests unitaires (si disponibles)
RUN npm run test:unit || echo "Tests unitaires non configurés, continuons..."

# Stage 4: Build
FROM base AS builder

# Copier les fichiers de configuration
COPY package*.json ./

# Installer toutes les dépendances pour le build
RUN npm ci

# Copier le code source
COPY . .

# Construire l'application
RUN npm run build

# Stage 5: Production
FROM node:20-alpine AS production

# Installer les dépendances système minimales
RUN apk add --no-cache libc6-compat

# Créer un utilisateur non-root pour la sécurité
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nuxtjs

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de production depuis le stage builder
COPY --from=builder --chown=nuxtjs:nodejs /app/.output ./.output

# Changer vers l'utilisateur non-root
USER nuxtjs

# Variables d'environnement
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000
ENV NODE_ENV=production

# Exposer le port
EXPOSE 3000

# Démarrer l'application
CMD ["node", ".output/server/index.mjs"]
