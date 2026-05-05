# Application de Gestion de Stock de Matériel Informatique

## Description
Application web complète de gestion de stock de matériel informatique développée en PHP, MySQL et Bootstrap 5.

## Fonctionnalités Principales

### Gestion des Référentiels
- **Services** : Gestion des services de l'entreprise
- **Employés** : Gestion des employés par service
- **Fournisseurs** : Gestion des fournisseurs
- **Bureaux** : Gestion des bureaux par service
- **Armoires** : Gestion des armoires de stockage
- **Équipes d'inventaire** : Gestion des équipes pour les inventaires

### Gestion du Stock
- **Articles** : CRUD avec calcul automatique des quantités (qte_disponible = stock_initial + qte_entree - qte_sortie)
- **Entrées** : Gestion des entrées de matériel avec lignes détaillées et upload de fichiers
- **Sorties** : Gestion des sorties avec vérification de disponibilité du stock
- **Retours** : Gestion des retours de matériel
- **Inventaires** : Système complet d'inventaire avec validation et réinitialisation du stock

### Fonctionnalités Avancées
- **Système de permissions** : Gestion fine des droits par rôle
- **Traçabilité** : Historique de toutes les opérations
- **Rapports PDF** : Export de tous les documents (bons d'entrée, sortie, retour, inventaire)
- **Tableau de bord** : Statistiques et alertes de stock
- **Recherche globale** : Recherche dans toute l'application
- **Pagination Keyset** : Pour de meilleures performances
- **Select2** : Sélection avec recherche pour tous les dropdowns

## Technologies Utilisées

### Backend
- PHP 7.4+
- MySQL 5.7+ avec InnoDB
- PDO pour les requêtes sécurisées
- OPcache + APCu pour les performances

### Frontend
- Bootstrap 5.3
- jQuery 3.7
- Select2 4.1 (sélection avec recherche)
- DataTables 1.13 (tableaux avec tri et recherche)
- Bootstrap Icons 1.10

### Librairies PDF
- Dompdf (pour génération de PDF)

## Structure du Projet

```
stock_materielinfo/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
│       └── logo.png
├── classes/
│   ├── Database.php
│   ├── Auth.php
│   └── PDF.php
├── config/
│   └── config.php
├── database/
│   └── schema.sql
├── includes/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
├── pages/
│   ├── articles/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   ├── view.php
│   │   └── delete.php
│   ├── entrees/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   ├── view.php
│   │   ├── delete.php
│   │   └── pdf.php
│   ├── sorties/
│   ├── retours/
│   ├── inventaires/
│   ├── services/
│   ├── employes/
│   ├── fournisseurs/
│   ├── bureaux/
│   ├── armoires/
│   ├── equipes/
│   ├── users/
│   ├── parametres/
│   ├── rapports/
│   ├── profil/
│   ├── recalcul/
│   └── search.php
├── api/
│   ├── articles.php
│   ├── employes.php
│   ├── fournisseurs.php
│   └── services.php
├── uploads/
│   ├── uploadse/ (fichiers entrées)
│   ├── uploadss/ (fichiers sorties)
│   ├── uploadsr/ (fichiers retours)
│   └── uploadsinv/ (fichiers inventaires)
├── index.php (tableau de bord)
├── login.php
├── logout.php
└── README.md
```

## Installation

### 1. Prérequis
- Serveur Web (Apache/Nginx)
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Extension PHP : PDO, PDO_MySQL, mbstring, gd

### 2. Configuration PHP (php.ini)

```ini
; OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

; APCu
apc.enabled=1
apc.shm_size=32M
apc.ttl=7200
```

### 3. Installation de la base de données

```bash
# Se connecter à MySQL
mysql -u root -p

# Créer et importer la base de données
mysql -u root -p < database/schema.sql
```

### 4. Configuration de l'application

Éditer le fichier `config/config.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stock_materiel');
define('DB_USER', 'root');
define('DB_PASS', 'votre_mot_de_passe');
define('BASE_URL', 'http://localhost/stock_materielinfo');
```

### 5. Permissions des dossiers

```bash
chmod 755 -R stock_materielinfo/
chmod 777 -R stock_materielinfo/uploads/
```

### 6. Installation de Dompdf (pour les PDF)

```bash
cd stock_materielinfo
composer require dompdf/dompdf
```

## Utilisation

### Comptes par défaut

Après l'installation, vous pouvez vous connecter avec :

- **Administrateur**
  - Login: `admin`
  - Mot de passe: `admin123`

- **Gestionnaire**
  - Login: `gestionnaire`
  - Mot de passe: `admin123`

- **Utilisateur**
  - Login: `user`
  - Mot de passe: `admin123`

⚠️ **Important** : Changez ces mots de passe après la première connexion !

## Fonctionnalités Détaillées

### 1. Gestion des Articles

- Création/Modification/Suppression d'articles
- Calcul automatique de la quantité disponible
- Alertes sur stock minimum/maximum
- Historique des mouvements par article
- Stock initial modifiable uniquement par l'admin

### 2. Entrées de Matériel

- Entête d'entrée avec fournisseur, date, fichier joint
- Lignes d'entrée avec articles et quantités
- Mise à jour automatique du stock
- Impression du bon d'entrée en PDF
- Téléchargement des fichiers joints

### 3. Sorties de Matériel

- Vérification automatique de la disponibilité du stock
- Affectation à un service, employé, bureau ou armoire
- Upload de documents
- Impression du bon de sortie en PDF
- Filtrage des employés par service

### 4. Retours de Matériel

- Enregistrement des retours par service/employé
- Mise à jour automatique du stock
- Documents joints et PDF

### 5. Inventaires

- Création d'inventaire avec équipe
- Comptage physique vs théorique
- Calcul automatique des écarts
- Validation (réservée à l'admin)
- Réinitialisation du stock (réservée à l'admin)
- États : En cours / Validé / Clôturé

### 6. Recalcul du Stock

- Recalcul par article
- Recalcul par période
- Affichage des divergences
- Correction automatique

### 7. Rapports

- Entrées par période et fournisseur
- Sorties par période et employé
- État du stock avec alertes
- Traçabilité des opérations
- Export PDF de tous les rapports

### 8. Système de Permissions

Trois rôles par défaut :
- **Admin** : Tous les droits
- **Gestionnaire** : Droits de gestion sans suppression
- **Utilisateur** : Consultation uniquement

Permissions personnalisables par module et action :
- view (voir)
- create (créer)
- update (modifier)
- delete (supprimer)
- validate (valider - inventaires)
- reset (réinitialiser - inventaires)

## Optimisations Performances

### Base de données
- Index sur toutes les clés étrangères
- Index composites pour les recherches fréquentes
- InnoDB pour support des transactions
- Pagination Keyset au lieu de OFFSET

### PHP
- OPcache activé pour cache du bytecode
- APCu pour cache des données
- Connexions PDO persistantes
- Requêtes préparées pour éviter les injections SQL

### Frontend
- CDN pour Bootstrap, jQuery, Select2
- Minification CSS/JS
- Lazy loading des images
- DataTables pour pagination côté client

## Sécurité

- ✅ Mots de passe hashés avec `password_hash()`
- ✅ Requêtes préparées (protection injection SQL)
- ✅ Validation des entrées utilisateur
- ✅ Protection CSRF
- ✅ Gestion des permissions par rôle
- ✅ Sessions sécurisées avec timeout
- ✅ Upload de fichiers sécurisé avec vérification du type
- ✅ Traçabilité de toutes les actions

## Support et Maintenance

### Logs
Les logs d'activité sont enregistrés dans la table `traces` :
- Utilisateur
- Module
- Action
- Date/Heure
- Adresse IP

### Backup
Il est recommandé de sauvegarder régulièrement :
- La base de données MySQL
- Le dossier `uploads/`

### Mise à jour
Les mises à jour peuvent être effectuées en :
1. Sauvegardant la base de données
2. Remplaçant les fichiers
3. Exécutant les migrations SQL si nécessaire

## Licence
Application propriétaire - Tous droits réservés

## Auteur
Développé pour la gestion de stock de matériel informatique

## Version
Version 1.0.0 - 2025
