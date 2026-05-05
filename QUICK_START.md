# 🚀 Démarrage rapide - Application Stock Matériel

## Installation rapide (5 minutes)

### 1. Importer la base de données

```bash
mysql -u root -p
```

```sql
CREATE DATABASE stock_materiel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

```bash
mysql -u root -p stock_materiel < database/schema.sql
```

### 2. Installer les dépendances

```bash
cd stock_materielinfo
composer install
```

### 3. Configurer l'application

Éditer `config/config.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stock_materiel');
define('DB_USER', 'root');
define('DB_PASS', 'votre_mot_de_passe');
define('BASE_URL', 'http://localhost/stock_materielinfo');
```

### 4. Permissions des dossiers

```bash
chmod 777 -R uploads/
chmod 755 -R .
```

### 5. Accéder à l'application

Ouvrir : `http://localhost/stock_materielinfo`

**Login** : `admin` / `admin123`

## Fichiers créés ✅

### Structure complète
```
stock_materielinfo/
├── 📁 assets/
│   ├── css/style.css (CSS personnalisé complet)
│   └── js/main.js (JavaScript avec Select2, DataTables, etc.)
├── 📁 classes/
│   ├── Database.php (Connexion PDO + pagination Keyset)
│   ├── Auth.php (Authentification + permissions)
│   └── PDF.php (Génération PDF avec Dompdf)
├── 📁 config/
│   └── config.php (Configuration complète)
├── 📁 database/
│   └── schema.sql (Base de données complète avec données de test)
├── 📁 includes/
│   ├── header.php (En-tête HTML)
│   ├── navbar.php (Navigation Bootstrap 5)
│   └── footer.php (Pied de page)
├── 📁 api/
│   ├── articles.php (API Select2 articles)
│   ├── employes.php (API Select2 employés)
│   ├── fournisseurs.php (API Select2 fournisseurs)
│   └── services.php (API Select2 services)
├── 📁 pages/
│   ├── services/
│   │   └── index.php (Exemple CRUD liste)
│   └── entrees/
│       ├── create.php (Exemple CRUD complexe avec lignes + upload)
│       └── pdf.php (Export PDF)
├── login.php (Page de connexion)
├── logout.php (Déconnexion)
├── index.php (Tableau de bord avec statistiques)
├── composer.json (Dépendances)
├── .htaccess (Sécurité Apache)
├── README.md (Documentation générale)
├── INSTALL.md (Guide d'installation complet)
├── IMPLEMENTATION_GUIDE.md (Templates CRUDs)
└── DEVELOPMENT_TODO.md (Liste complète des fichiers à créer)
```

## Prochaines étapes

### Completer les CRUDs (voir DEVELOPMENT_TODO.md)

**Ordre recommandé** :

1. **Services** (simple) - Exemple déjà fourni dans `pages/services/index.php`
   - Créer : create.php, edit.php, view.php, delete.php

2. **Fournisseurs** - Suivre le template dans IMPLEMENTATION_GUIDE.md

3. **Employés** - Ajouter Select2 pour service

4. **Articles** - IMPORTANT : Stock initial modifiable uniquement à la création

5. **Entrées** - Exemple fourni dans `pages/entrees/create.php`

6. **Sorties** - Vérification stock + filtrage employés par service

7. **Inventaires** - Le plus complexe (3 états : en_cours, valide, cloture)

### Utiliser les templates fournis

Tous les templates sont dans **IMPLEMENTATION_GUIDE.md** :
- Template CREATE.PHP
- Template EDIT.PHP
- Template DELETE.PHP
- Template VIEW.PHP
- Logique spécifique pour chaque module

## Fonctionnalités déjà implémentées ✅

### Backend
- ✅ Base de données complète avec index optimisés
- ✅ Système d'authentification avec hashage bcrypt
- ✅ Gestion des permissions par rôles
- ✅ Pagination Keyset (performante)
- ✅ Transactions pour intégrité des données
- ✅ Upload de fichiers sécurisé
- ✅ Traçabilité des actions
- ✅ Génération de PDF (bons d'entrée/sortie)

### Frontend
- ✅ Bootstrap 5 responsive
- ✅ Select2 pour recherche avancée
- ✅ DataTables pour tableaux interactifs
- ✅ Design personnalisé et moderne
- ✅ Alertes et notifications
- ✅ Menu de navigation complet
- ✅ Tableau de bord avec statistiques

### Sécurité
- ✅ Protection injection SQL (requêtes préparées)
- ✅ Protection XSS (htmlspecialchars)
- ✅ Protection CSRF (session tokens)
- ✅ .htaccess avec règles de sécurité
- ✅ Gestion des permissions fines

## Données de test

La base contient déjà :
- 3 utilisateurs (admin, gestionnaire, user)
- 5 services
- 5 employés
- 4 fournisseurs
- 5 bureaux
- 3 armoires
- 3 équipes d'inventaire
- 10 articles (PC, écrans, claviers, etc.)
- Permissions complètes par rôle

## Accès rapide

### Connexion
**URL** : `http://localhost/stock_materielinfo`

**Comptes** :
- Admin : `admin` / `admin123`
- Gestionnaire : `gestionnaire` / `admin123`
- Utilisateur : `user` / `admin123`

### Tableau de bord
Affiche :
- Nombre total d'articles
- Entrées/sorties du mois
- Alertes de stock
- Articles les plus utilisés
- Dernières entrées/sorties

### Menu principal
- Articles
- Mouvements (Entrées, Sorties, Retours)
- Inventaires
- Rapports
- Référentiel (Services, Employés, Fournisseurs, Bureaux, Armoires, Équipes)
- Administration (Utilisateurs, Paramètres)

## Documentation

### Guides disponibles

1. **README.md** - Vue d'ensemble et fonctionnalités
2. **INSTALL.md** - Installation détaillée (Apache, Nginx, SSL, optimisations)
3. **IMPLEMENTATION_GUIDE.md** - Templates pour créer tous les CRUDs
4. **DEVELOPMENT_TODO.md** - Liste exhaustive des fichiers à créer

### Templates de code

Tous les templates sont prêts à l'emploi dans **IMPLEMENTATION_GUIDE.md** :
- CRUD simple (Services, Armoires, etc.)
- CRUD avec relations (Employés, Bureaux)
- CRUD complexe avec lignes (Entrées, Sorties, Retours)
- CRUD inventaire avec états multiples
- Export PDF
- Rapports

## Support technique

### Problèmes courants

**Page blanche ?**
```php
// Dans config/config.php, activer temporairement :
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**Erreur connexion BDD ?**
```bash
# Vérifier les credentials dans config/config.php
# Vérifier que MySQL est démarré :
sudo systemctl status mysql
```

**Upload ne fonctionne pas ?**
```bash
chmod 777 -R uploads/
# Vérifier upload_max_filesize dans php.ini
```

**PDF ne se génère pas ?**
```bash
composer require dompdf/dompdf
```

## Optimisations recommandées

### Pour la production

1. **Activer OPcache** dans `php.ini` :
```ini
opcache.enable=1
opcache.memory_consumption=128
```

2. **Désactiver les erreurs** dans `config/config.php` :
```php
ini_set('display_errors', 0);
error_reporting(0);
```

3. **Configurer SSL** :
```bash
sudo certbot --apache -d stock.monentreprise.com
```

4. **Backup automatique** :
```bash
# Créer un script de backup (voir INSTALL.md)
crontab -e
0 2 * * * /home/user/backup_stock.sh
```

## Ressources

### Librairies utilisées
- **Bootstrap 5.3** : https://getbootstrap.com/
- **Select2 4.1** : https://select2.org/
- **DataTables 1.13** : https://datatables.net/
- **Dompdf 2.0** : https://github.com/dompdf/dompdf

### Documentation PHP
- PDO : https://www.php.net/manual/fr/book.pdo.php
- Password hashing : https://www.php.net/manual/fr/function.password-hash.php
- Sessions : https://www.php.net/manual/fr/book.session.php

## Contact

Pour toute question :
1. Consulter la documentation complète
2. Vérifier DEVELOPMENT_TODO.md pour l'implémentation
3. Consulter les logs d'erreurs

Bonne chance avec votre application ! 🎉
