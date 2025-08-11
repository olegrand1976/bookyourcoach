# Dockerfile pour l'application Nuxt.js
FROM node:18-alpine

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de configuration
COPY package*.json ./

# Installer les dépendances
RUN npm ci --only=production && npm cache clean --force

# Copier le code source
COPY . .

# Exposer le port
EXPOSE 3000

# Variables d'environnement
ENV NUXT_HOST=0.0.0.0
ENV NUXT_PORT=3000

# Démarrer l'application en mode développement
CMD ["npm", "run", "dev"]
