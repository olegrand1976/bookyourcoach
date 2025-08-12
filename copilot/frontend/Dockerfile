# Dockerfile pour l'application Nuxt.js
FROM node:20-alpine AS builder

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de configuration
COPY package*.json ./

# Installer les dépendances
RUN npm install

# Copier le code source
COPY . .

# Construire l'application
RUN npm run build

# Stage de production
FROM node:20-alpine AS production

# Définir le répertoire de travail
WORKDIR /app

# Copier seulement les fichiers nécessaires
COPY --from=builder /app/.output ./.output

# Changer vers le répertoire server pour installer les dépendances
WORKDIR /app/.output/server
RUN npm install --omit=dev

# Revenir au répertoire principal pour l'exécution
WORKDIR /app

# Variables d'environnement
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000

# Exposer le port
EXPOSE 3000

# Démarrer l'application en mode production
CMD ["node", ".output/server/index.mjs"]
