# Configuration SSL pour Acti'Vibe
# Ce fichier contient les instructions pour configurer SSL avec Let's Encrypt

# 1. Installation de Certbot
sudo apt update
sudo apt install certbot python3-certbot-nginx

# 2. Génération du certificat SSL
sudo certbot --nginx -d activibe.com -d www.activibe.com

# 3. Configuration automatique du renouvellement
sudo crontab -e
# Ajouter cette ligne :
# 0 12 * * * /usr/bin/certbot renew --quiet

# 4. Configuration Nginx pour SSL
# Le fichier sera automatiquement généré par Certbot
# Exemple de configuration :

server {
    listen 80;
    server_name activibe.com www.activibe.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name activibe.com www.activibe.com;

    ssl_certificate /etc/letsencrypt/live/activibe.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/activibe.com/privkey.pem;
    
    # Configuration SSL moderne
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Configuration de l'application
    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
