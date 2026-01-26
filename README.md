# SliMail

Plateforme d'email marketing multi-tenant pour l'Afrique de l'Ouest.

## Fonctionnalités

- **Gestion des contacts** : Import CSV, listes de diffusion, tags, segments dynamiques
- **Campagnes email** : Editeur visuel GrapesJS, templates MJML, A/B testing
- **Automatisations** : Workflows automatisés (bienvenue, anniversaire, etc.)
- **Statistiques** : Taux d'ouverture, clics, bounces, graphiques
- **API transactionnelle** : Envoi d'emails programmatique
- **Multi-tenant** : Isolation complète des données par organisation
- **Facturation** : Intégration CinetPay (Mobile Money, cartes)

## Stack technique

- **Backend** : Laravel 11, PHP 8.2+
- **Frontend** : Vue 3, Inertia.js, Tailwind CSS
- **Base de données** : MySQL 8.0+
- **Email** : Amazon SES
- **Paiement** : CinetPay

## Installation

### Prérequis

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+

### Installation locale

```bash
# Cloner le repository
git clone https://github.com/votre-org/slimail.git
cd slimail

# Installer les dépendances PHP
composer install

# Installer les dépendances Node.js
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données dans .env
# DB_DATABASE=slimail
# DB_USERNAME=root
# DB_PASSWORD=

# Exécuter les migrations
php artisan migrate

# Charger les données initiales
php artisan db:seed

# Compiler les assets
npm run build

# Démarrer le serveur
php artisan serve
```

### Configuration requise

Créez un fichier `.env` avec les variables suivantes :

```env
APP_NAME=SliMail
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=slimail
DB_USERNAME=root
DB_PASSWORD=

# Amazon SES
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-west-3
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@slimail.com
MAIL_FROM_NAME="${APP_NAME}"

# CinetPay
CINETPAY_API_KEY=
CINETPAY_SITE_ID=
CINETPAY_SECRET_KEY=
CINETPAY_NOTIFY_URL=https://votre-domaine.com/webhooks/cinetpay
CINETPAY_RETURN_URL=https://votre-domaine.com/billing/payment/{payment}/return

# Queue
QUEUE_CONNECTION=database
```

## Commandes utiles

```bash
# Lancer les tests
php artisan test

# Lancer un test spécifique
php artisan test --filter=CampaignTest

# Traiter la queue
php artisan queue:work

# Envoyer les rappels de paiement
php artisan billing:send-reminders

# Envoyer les rappels d'expiration
php artisan subscriptions:send-expiring-reminders

# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Regénérer les assets
npm run build
```

## Structure du projet

```
slimail/
├── app/
│   ├── Console/Commands/     # Commandes Artisan
│   ├── Http/Controllers/     # Contrôleurs
│   ├── Jobs/                 # Jobs de queue
│   ├── Models/               # Modèles Eloquent
│   ├── Services/             # Services métier
│   └── Traits/               # Traits réutilisables
├── database/
│   ├── factories/            # Factories pour les tests
│   ├── migrations/           # Migrations de base
│   └── seeders/              # Données initiales
├── resources/
│   ├── js/                   # Composants Vue.js
│   └── views/                # Templates Blade
├── routes/
│   ├── api.php               # Routes API
│   └── web.php               # Routes web
└── tests/
    ├── Feature/              # Tests d'intégration
    └── Unit/                 # Tests unitaires
```

## API

Documentation disponible à `/api/docs` après connexion.

### Authentification

```bash
# Obtenir une clé API depuis l'interface
curl -H "Authorization: Bearer VOTRE_CLE_API" \
     https://votre-domaine.com/api/v1/send
```

### Envoyer un email transactionnel

```bash
curl -X POST https://votre-domaine.com/api/v1/send \
  -H "Authorization: Bearer VOTRE_CLE_API" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "destinataire@example.com",
    "subject": "Sujet de l'email",
    "html": "<p>Contenu HTML</p>",
    "tags": ["transactionnel", "notification"]
  }'
```

## Rôles utilisateur

| Rôle | Permissions |
|------|-------------|
| Owner | Accès complet |
| Admin | Tout sauf facturation |
| Editor | Contacts, campagnes, templates |
| Analyst | Lecture seule (stats) |
| Developer | API uniquement |

## Licence

Propriétaire - SLIMAT SARL
