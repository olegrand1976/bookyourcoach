# Dockerfile pour l'application Nuxt.js
FROM node:20-alpine AS builder

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de configuration
COPY package*.json ./

# Installer les dépendances de manière robuste
RUN npm cache clean --force && \
    if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Copier le code source
COPY . .

# Construire l'application
RUN npm run build

# Installer les dépendances de prod Nitro côté builder pour éviter un npm install en prod
WORKDIR /app/.output/server
# Utiliser ci (plus fiable en build CI) et ignorer les devDeps
RUN npm ci --omit=dev || npm install --omit=dev


# Stage de production
FROM node:20-alpine AS production

# Définir le répertoire de travail
WORKDIR /app

# Copier seulement les fichiers nécessaires
COPY --from=builder /app/.output ./.output
# Copier également les node_modules déjà installés pour le serveur Nitro
COPY --from=builder /app/.output/server/node_modules ./.output/server/node_modules

# Changer vers le répertoire server pour installer les dépendances
# Revenir au répertoire principal pour l'exécution
WORKDIR /app

# Variables d'environnement
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000

# Exposer le port
EXPOSE 3000

# Démarrer l'application en mode production
CMD ["node", ".output/server/index.mjs"]
