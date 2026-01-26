# Guide de Déploiement SliMail

Ce guide décrit le processus de déploiement de SliMail en production.

## Prérequis serveur

### Configuration minimale

- **OS** : Ubuntu 22.04 LTS
- **RAM** : 4 Go minimum (8 Go recommandé)
- **CPU** : 2 vCPU minimum
- **Stockage** : 40 Go SSD
- **PHP** : 8.2+
- **MySQL** : 8.0+
- **Nginx** : 1.18+
- **Node.js** : 18+ (pour le build)

### Extensions PHP requises

```bash
sudo apt install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl \
    php8.2-redis
```

## Déploiement initial

### 1. Préparer le serveur

```bash
# Créer l'utilisateur déploiement
sudo adduser slimail
sudo usermod -aG www-data slimail

# Créer le répertoire
sudo mkdir -p /var/www/slimail
sudo chown slimail:www-data /var/www/slimail
```

### 2. Cloner le projet

```bash
cd /var/www/slimail
git clone https://github.com/votre-org/slimail.git .
```

### 3. Configurer l'environnement

```bash
cp .env.example .env

# Éditer .env avec les valeurs de production
nano .env
```

**Variables importantes en production :**

```env
APP_NAME=SliMail
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.slimail.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=slimail_prod
DB_USERNAME=slimail_user
DB_PASSWORD=mot_de_passe_securise

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Amazon SES
AWS_ACCESS_KEY_ID=AKIAXXXXXXXX
AWS_SECRET_ACCESS_KEY=xxxxxxxx
AWS_DEFAULT_REGION=eu-west-3
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@slimail.com
MAIL_FROM_NAME="${APP_NAME}"

# CinetPay Production
CINETPAY_API_KEY=votre_cle_production
CINETPAY_SITE_ID=votre_site_id
CINETPAY_SECRET_KEY=votre_secret
CINETPAY_MODE=PRODUCTION
```

### 4. Installer les dépendances

```bash
# Dépendances PHP (sans dev)
composer install --no-dev --optimize-autoloader

# Dépendances Node.js
npm ci

# Build des assets
npm run build
```

### 5. Configurer la base de données

```bash
# Générer la clé
php artisan key:generate

# Migrations
php artisan migrate --force

# Données initiales
php artisan db:seed --class=PlanSeeder
php artisan db:seed --class=SystemTemplateSeeder
php artisan db:seed --class=TemplateLibrarySeeder
```

### 6. Optimisation

```bash
# Cache de configuration
php artisan config:cache

# Cache des routes
php artisan route:cache

# Cache des vues
php artisan view:cache

# Optimisation de l'autoloader
composer dump-autoload --optimize
```

### 7. Permissions

```bash
sudo chown -R slimail:www-data /var/www/slimail
sudo chmod -R 755 /var/www/slimail
sudo chmod -R 775 /var/www/slimail/storage
sudo chmod -R 775 /var/www/slimail/bootstrap/cache
```

## Configuration Nginx

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name app.slimail.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name app.slimail.com;

    root /var/www/slimail/public;
    index index.php;

    # SSL
    ssl_certificate /etc/letsencrypt/live/app.slimail.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.slimail.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Logs
    access_log /var/log/nginx/slimail_access.log;
    error_log /var/log/nginx/slimail_error.log;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;

    # Cache des assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Configuration Supervisor (Queue)

Créer `/etc/supervisor/conf.d/slimail-worker.conf` :

```ini
[program:slimail-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/slimail/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=slimail
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/slimail/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start slimail-worker:*
```

## Tâches CRON

Ajouter à `/etc/crontab` :

```cron
* * * * * slimail cd /var/www/slimail && php artisan schedule:run >> /dev/null 2>&1
```

## Mise à jour en production

### Script de déploiement

Créer `deploy.sh` :

```bash
#!/bin/bash
set -e

echo "Déploiement SliMail..."

cd /var/www/slimail

# Mode maintenance
php artisan down

# Pull des derniers changements
git pull origin main

# Dépendances
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
sudo chown -R slimail:www-data /var/www/slimail/storage
sudo chmod -R 775 /var/www/slimail/storage

# Redémarrer les workers
sudo supervisorctl restart slimail-worker:*

# Fin maintenance
php artisan up

echo "Déploiement terminé!"
```

### Rollback

```bash
# En cas de problème
php artisan down
git checkout HEAD~1
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback
php artisan config:cache
php artisan route:cache
php artisan up
```

## Monitoring

### Logs à surveiller

```bash
# Logs Laravel
tail -f /var/www/slimail/storage/logs/laravel.log

# Logs Nginx
tail -f /var/log/nginx/slimail_error.log

# Logs Worker
tail -f /var/www/slimail/storage/logs/worker.log
```

### Health check

Créer un endpoint `/health` :

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

## Sauvegardes

### Script de backup

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=/backups/slimail

# Base de données
mysqldump -u slimail_user -p slimail_prod | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Fichiers uploadés
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/slimail/storage/app

# Garder les 7 derniers jours
find $BACKUP_DIR -type f -mtime +7 -delete
```

## Sécurité

- Activer le pare-feu (UFW)
- Configurer fail2ban
- Mettre à jour régulièrement
- Sauvegardes chiffrées
- Rotation des clés API

## Support

En cas de problème, vérifier :

1. Logs Laravel : `storage/logs/laravel.log`
2. Logs Nginx : `/var/log/nginx/slimail_error.log`
3. Statut des workers : `supervisorctl status`
4. Statut de la queue : `php artisan queue:failed`
