# Changelog

Toutes les modifications notables de SliMail sont documentées ici.

## [1.0.0] - 2026-01-25

### Ajouté

#### Gestion des contacts
- Import de contacts via CSV avec mapping des colonnes
- Création et gestion de listes de diffusion
- Système de tags pour organiser les contacts
- Segments dynamiques avec critères multiples
- Détection et fusion des doublons
- Export des contacts en CSV

#### Campagnes email
- Éditeur visuel d'emails avec GrapesJS
- Support des templates MJML responsive
- Bibliothèque de templates préconçus
- Tests A/B (objet, contenu, expéditeur)
- Planification des envois
- Envoi d'emails de test
- Statistiques de campagne en temps réel

#### Automatisations
- Workflows d'automatisation visuels
- Déclencheurs : bienvenue, anniversaire, inactivité
- Actions : envoyer email, ajouter tag, attendre
- Historique des exécutions

#### API transactionnelle
- Endpoint d'envoi d'emails (`POST /api/v1/send`)
- Gestion des clés API par tenant
- Documentation Swagger intégrée
- Webhooks pour les événements email

#### Statistiques et rapports
- Dashboard avec graphiques Chart.js
- Métriques : ouvertures, clics, bounces, désabonnements
- Comparaison entre périodes
- Export CSV et PDF des rapports

#### Facturation
- Intégration CinetPay (Mobile Money + cartes)
- Gestion des abonnements multi-plans
- Génération de factures PDF (format OHADA)
- Rappels de paiement automatiques
- Historique des transactions

#### Multi-tenant
- Isolation complète des données par organisation
- Rôles : Owner, Admin, Editor, Analyst, Developer
- Gestion des utilisateurs par tenant

#### Interface utilisateur
- Design responsive avec Tailwind CSS
- Mode clair (dark mode prévu)
- Notifications toast
- Recherche et filtres avancés

### Technique
- Laravel 11 + Inertia.js + Vue 3
- Tests unitaires et d'intégration (PHPUnit)
- Configuration Playwright pour tests E2E
- Documentation API avec L5 Swagger

---

## [0.9.0] - 2026-01-24 (Beta)

### Ajouté
- Structure de base du projet
- Système d'authentification
- Modèles et migrations principales
- Interface de base avec Tailwind

### En cours
- Tests de charge
- Optimisation des performances

---

## Roadmap

### Version 1.1 (prévu)
- [ ] Mode sombre
- [ ] Application mobile (PWA)
- [ ] Intégration Zapier
- [ ] Webhooks sortants personnalisés

### Version 1.2 (prévu)
- [ ] Templates SMS
- [ ] Envoi de SMS via API
- [ ] Segments prédictifs (IA)
- [ ] Recommandations d'heure d'envoi

---

## Convention de versionnage

Ce projet suit [Semantic Versioning](https://semver.org/) :

- **MAJOR** : Changements incompatibles avec l'API
- **MINOR** : Nouvelles fonctionnalités rétrocompatibles
- **PATCH** : Corrections de bugs rétrocompatibles
