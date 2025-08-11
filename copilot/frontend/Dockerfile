# Dockerfile pour l'application Nuxt.js
FROM node:18-alpine

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de configuration
COPY package*.json ./

# Installer les dépendances
RUN npm install && npm cache clean --force

# Copier le code source
COPY . .

# Construire l'application
RUN npm run build

# Exposer le port
EXPOSE 3000

# Variables d'environnement
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000

# Démarrer l'application en mode production
CMD ["npm", "run", "preview"]
