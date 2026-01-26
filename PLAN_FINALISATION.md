# Plan de Finalisation SliMail

## Vue d'ensemble

**État actuel** : ~85% complet
**Estimation restante** : 3-4 semaines de travail

---

## Phase 1 : Fonctionnalités Critiques (1 semaine)

### 1.1 Segments Dynamiques (2 jours)

**Objectif** : Permettre de créer des segments de contacts avec critères dynamiques

**Backend :**
- [ ] Créer migration `segments` table
- [ ] Créer modèle `Segment` avec relation tenant
- [ ] Créer `SegmentController` avec CRUD
- [ ] Créer `SegmentService` pour évaluation des critères
- [ ] Ajouter routes pour segments

**Frontend :**
- [ ] Créer `Pages/Segments/Index.vue` - Liste des segments
- [ ] Créer `Pages/Segments/Create.vue` - Création avec builder
- [ ] Créer `Components/Segments/SegmentBuilder.vue` - Builder visuel de critères
- [ ] Créer `Components/Segments/CriteriaRow.vue` - Ligne de critère

**Critères à supporter :**
- Champs standard : email, nom, ville, pays, statut
- Champs personnalisés (custom_fields)
- Opérateurs : égal, contient, commence par, supérieur, inférieur
- Logique ET/OU entre critères
- Tags et listes comme critères

---

### 1.2 Détection des Doublons (1 jour)

**Objectif** : Détecter et gérer les contacts en doublon

**Backend :**
- [ ] Ajouter méthode `findDuplicates()` dans `ContactService`
- [ ] Créer `DuplicateDetectionService` pour analyse
- [ ] Ajouter route `POST /contacts/check-duplicates`
- [ ] Ajouter route `POST /contacts/merge`

**Frontend :**
- [ ] Créer `Pages/Contacts/Duplicates.vue` - Liste des doublons
- [ ] Créer `Components/Contacts/MergeModal.vue` - Fusion de contacts
- [ ] Ajouter détection lors de l'import

**Règles de détection :**
- Email identique (exact match)
- Nom + Prénom similaires (fuzzy match)
- Téléphone identique

---

### 1.3 Dashboard Statistiques Complet (2 jours)

**Objectif** : Dashboard avec graphiques et métriques visuelles

**Backend :**
- [ ] Enrichir `StatisticsService` avec données pour graphiques
- [ ] Ajouter endpoint `/statistics/charts` pour données JSON
- [ ] Ajouter comparaison période précédente

**Frontend :**
- [ ] Installer Chart.js : `npm install chart.js vue-chartjs`
- [ ] Créer `Components/Charts/LineChart.vue` - Graphique linéaire
- [ ] Créer `Components/Charts/BarChart.vue` - Graphique barres
- [ ] Créer `Components/Charts/DoughnutChart.vue` - Graphique donut
- [ ] Enrichir `Pages/Statistics/Index.vue` avec graphiques
- [ ] Ajouter sélecteur de période (7j, 30j, 90j, personnalisé)
- [ ] Ajouter comparaison avec période précédente

**Métriques à afficher :**
- Volume d'envoi par jour/semaine
- Taux d'ouverture évolution
- Taux de clic évolution
- Répartition des statuts (delivered, bounced, etc.)
- Top 5 campagnes
- Croissance des contacts

---

## Phase 2 : Améliorations UX (1 semaine)

### 2.1 Blocs GrapesJS/MJML (2 jours)

**Objectif** : Enrichir l'éditeur d'emails avec blocs prédéfinis

**Blocs à créer :**
- [ ] Bloc Header (logo + navigation)
- [ ] Bloc Hero (image + titre + CTA)
- [ ] Bloc Texte (paragraphe formaté)
- [ ] Bloc Image (avec lien optionnel)
- [ ] Bloc Bouton (CTA stylisé)
- [ ] Bloc Colonnes (2, 3, 4 colonnes)
- [ ] Bloc Produit (image + titre + prix + bouton)
- [ ] Bloc Réseaux sociaux (icônes)
- [ ] Bloc Séparateur/Espaceur
- [ ] Bloc Footer (désinscription + copyright)

**Fichiers à modifier :**
- [ ] `Components/EmailEditor/EmailEditor.vue` - Configuration GrapesJS
- [ ] Créer `Components/EmailEditor/blocks/` - Dossier des blocs
- [ ] Créer `Components/EmailEditor/mjml-templates/` - Templates MJML

---

### 2.2 Templates Préconçus (1 jour)

**Objectif** : Bibliothèque de templates prêts à l'emploi

**Templates à créer :**
- [ ] Newsletter simple
- [ ] Annonce produit
- [ ] Email de bienvenue
- [ ] Email promotionnel
- [ ] Email transactionnel (confirmation)
- [ ] Email de réengagement

**Backend :**
- [ ] Créer seeder `TemplateLibrarySeeder`
- [ ] Ajouter champ `is_system` à `email_templates`

**Frontend :**
- [ ] Enrichir `Pages/Templates/Library.vue` avec previews

---

### 2.3 Interface A/B Testing (1 jour)

**Objectif** : Améliorer l'interface de création et suivi des tests A/B

**Frontend :**
- [ ] Créer `Components/Campaigns/ABTestCreator.vue` - Création variantes
- [ ] Créer `Components/Campaigns/ABTestResults.vue` - Résultats visuels
- [ ] Ajouter graphiques comparatifs (barres côte à côte)
- [ ] Ajouter indicateur de significativité statistique

**Améliorations :**
- [ ] Preview côte à côte des variantes
- [ ] Sélection du critère de victoire (ouvertures, clics)
- [ ] Durée du test configurable
- [ ] Envoi automatique du gagnant

---

### 2.4 Gestion des Utilisateurs par Tenant (1 jour)

**Objectif** : Permettre aux propriétaires de gérer leur équipe

**Backend :**
- [ ] Créer `UserController` pour gestion équipe
- [ ] Ajouter routes `/users` (CRUD)
- [ ] Créer `InviteUserJob` pour invitation par email

**Frontend :**
- [ ] Créer `Pages/Settings/Users/Index.vue` - Liste utilisateurs
- [ ] Créer `Pages/Settings/Users/Invite.vue` - Invitation
- [ ] Créer `Components/Users/RoleSelector.vue` - Attribution rôles

**Rôles à implémenter :**
- Propriétaire : accès complet
- Admin : tout sauf facturation
- Éditeur : campagnes et templates
- Analyste : statistiques en lecture
- Développeur : API uniquement

---

## Phase 3 : Tests et Qualité (1 semaine)

### 3.1 Tests Unitaires (2 jours)

**Services à tester :**
- [ ] `tests/Unit/Services/EmailServiceTest.php`
- [ ] `tests/Unit/Services/CampaignServiceTest.php`
- [ ] `tests/Unit/Services/AutomationServiceTest.php`
- [ ] `tests/Unit/Services/StatisticsServiceTest.php`
- [ ] `tests/Unit/Services/CinetPayServiceTest.php`

**Modèles à tester :**
- [ ] `tests/Unit/Models/ContactTest.php`
- [ ] `tests/Unit/Models/CampaignTest.php`
- [ ] `tests/Unit/Models/AutomationTest.php`

---

### 3.2 Tests d'Intégration (2 jours)

**Flux à tester :**
- [ ] `tests/Feature/ContactImportTest.php` - Import complet
- [ ] `tests/Feature/CampaignSendTest.php` - Création → Envoi → Stats
- [ ] `tests/Feature/AutomationWorkflowTest.php` - Trigger → Steps → Completion
- [ ] `tests/Feature/BillingFlowTest.php` - Abonnement → Paiement → Facture
- [ ] `tests/Feature/ApiTransactionalTest.php` - API /send complète

---

### 3.3 Tests E2E avec Playwright (1 jour)

**Installation :**
```bash
npm install -D @playwright/test
npx playwright install
```

**Scénarios à tester :**
- [ ] Inscription → Dashboard
- [ ] Création contact → Ajout liste
- [ ] Création campagne → Envoi test
- [ ] Création automation → Activation

---

## Phase 4 : Production Ready (3-4 jours)

### 4.1 Génération PDF Factures (1 jour)

**Backend :**
- [ ] Installer DomPDF : `composer require barryvdh/laravel-dompdf`
- [ ] Créer `InvoicePdfService`
- [ ] Créer template Blade `invoices/pdf.blade.php`
- [ ] Numérotation OHADA : `FACT-{ANNEE}-{NUMERO}`

---

### 4.2 Emails Système (1 jour)

**Emails à créer :**
- [ ] `emails/invoice-created.blade.php` - Nouvelle facture
- [ ] `emails/payment-reminder.blade.php` - Rappel échéance
- [ ] `emails/subscription-expiring.blade.php` - Expiration proche
- [ ] `emails/team-invite.blade.php` - Invitation équipe
- [ ] `emails/welcome.blade.php` - Bienvenue nouveau tenant

**Jobs associés :**
- [ ] `SendInvoiceEmailJob`
- [ ] `SendPaymentReminderJob`

---

### 4.3 Seeding des Données (1 jour)

**Seeders à créer :**
- [ ] `RolesAndPermissionsSeeder` - Rôles et permissions
- [ ] `PlansSeeder` - Forfaits par défaut
- [ ] `TemplateLibrarySeeder` - Templates système
- [ ] `DemoDataSeeder` - Données de démo (optionnel)

---

### 4.4 Documentation Finale (1 jour)

**À documenter :**
- [ ] README.md - Installation et configuration
- [ ] DEPLOYMENT.md - Guide de déploiement
- [ ] API.md - Guide d'intégration API
- [ ] CHANGELOG.md - Historique des versions

---

## Résumé du Planning

| Phase | Durée | Priorité |
|-------|-------|----------|
| Phase 1 : Fonctionnalités Critiques | 5 jours | Haute |
| Phase 2 : Améliorations UX | 5 jours | Moyenne |
| Phase 3 : Tests et Qualité | 5 jours | Haute |
| Phase 4 : Production Ready | 4 jours | Haute |
| **Total** | **~19 jours** | |

---

## Ordre de Réalisation Recommandé

1. **Semaine 1** : Phase 1 (Segments, Doublons, Dashboard Stats)
2. **Semaine 2** : Phase 3.1 + 3.2 (Tests unitaires et intégration)
3. **Semaine 3** : Phase 2 (Blocs GrapesJS, Templates, A/B, Users)
4. **Semaine 4** : Phase 4 (PDF, Emails, Seeding, Docs)

Cette approche priorise la stabilité (tests) avant les améliorations cosmétiques.

---

## Commandes Utiles

```bash
# Lancer les tests
php artisan test

# Lancer un test spécifique
php artisan test --filter=CampaignTest

# Générer couverture de code
php artisan test --coverage

# Seeding
php artisan db:seed

# Migration fresh avec seed
php artisan migrate:fresh --seed

# Build production
npm run build

# Déployer
php artisan down
git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

---

## Notes Importantes

1. **Tests d'abord** : Écrire les tests avant d'ajouter de nouvelles fonctionnalités
2. **Petit à petit** : Faire des commits fréquents et petits
3. **Code review** : Relire le code avant de merger
4. **Backup** : Toujours avoir une sauvegarde avant les migrations
5. **Staging** : Tester sur environnement de staging avant production
