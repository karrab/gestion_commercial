# Guide d'installation - Application de Gestion de Stock

Ce guide vous accompagne dans l'installation complète de l'application de gestion de stock de matériel informatique.

## Table des matières

1. [Prérequis](#prérequis)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Optimisations](#optimisations)
5. [Sécurité](#sécurité)
6. [Dépannage](#dépannage)

## Prérequis

### Serveur Web
- **Apache 2.4+** ou **Nginx 1.18+**
- **PHP 7.4** ou supérieur (recommandé: PHP 8.0+)
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Composer** (gestionnaire de dépendances PHP)

### Extensions PHP requises
```bash
php -m | grep -E "pdo|pdo_mysql|mbstring|gd|fileinfo|opcache"
```

Les extensions suivantes doivent être activées :
- `pdo`
- `pdo_mysql`
- `mbstring`
- `gd` (pour le traitement d'images)
- `fileinfo` (pour la vérification des types de fichiers)
- `opcache` (pour les performances)
- `apcu` (optionnel, pour le cache)
- `zip` (pour Composer)

## Installation

### Étape 1 : Téléchargement

Placez les fichiers dans le répertoire web de votre serveur :

```bash
cd /var/www/html
# Ou pour XAMPP/WAMP : C:\xampp\htdocs\

# Cloner ou copier le projet
# git clone [URL_DU_PROJET] stock_materielinfo
# OU
# Copier manuellement les fichiers
```

### Étape 2 : Installation des dépendances

Installez Composer si ce n'est pas déjà fait :

```bash
# Linux/Mac
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows : Télécharger depuis https://getcomposer.org/
```

Puis installez les dépendances du projet :

```bash
cd stock_materielinfo
composer install
```

### Étape 3 : Configuration de la base de données

#### 3.1. Créer la base de données

```bash
# Se connecter à MySQL
mysql -u root -p

# Ou utiliser PhpMyAdmin
```

```sql
-- Dans MySQL
CREATE DATABASE stock_materiel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Créer un utilisateur dédié (recommandé)
CREATE USER 'stock_user'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
GRANT ALL PRIVILEGES ON stock_materiel.* TO 'stock_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 3.2. Importer le schéma

```bash
mysql -u root -p stock_materiel < database/schema.sql
```

Ou via PhpMyAdmin :
1. Sélectionner la base `stock_materiel`
2. Aller dans l'onglet "Importer"
3. Choisir le fichier `database/schema.sql`
4. Cliquer sur "Exécuter"

### Étape 4 : Configuration de l'application

Éditer le fichier `config/config.php` :

```php
<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'stock_materiel');
define('DB_USER', 'stock_user');           // Votre utilisateur MySQL
define('DB_PASS', 'MOT_DE_PASSE_FORT');    // Votre mot de passe MySQL

// URL de base (IMPORTANT : adapter selon votre installation)
define('BASE_URL', 'http://localhost/stock_materielinfo');
// OU pour un sous-domaine
// define('BASE_URL', 'http://stock.monentreprise.com');
// OU en production avec SSL
// define('BASE_URL', 'https://stock.monentreprise.com');
```

### Étape 5 : Permissions des dossiers

```bash
# Linux/Mac
cd /var/www/html/stock_materielinfo
chmod 755 -R .
chmod 777 -R uploads/
chmod 777 -R assets/images/

# Pour plus de sécurité, changer le propriétaire
chown -R www-data:www-data .
# Ou pour Nginx
chown -R nginx:nginx .

# Windows (via CMD ou PowerShell en admin)
# Clic droit sur le dossier uploads > Propriétés > Sécurité
# Donner les permissions en écriture à IIS_IUSRS ou IUSR
```

### Étape 6 : Vérification

1. Ouvrir votre navigateur
2. Accéder à : `http://localhost/stock_materielinfo`
3. Vous devriez voir la page de connexion

**Comptes par défaut** :
- **Admin** : `admin` / `admin123`
- **Gestionnaire** : `gestionnaire` / `admin123`
- **Utilisateur** : `user` / `admin123`

⚠️ **IMPORTANT** : Changez ces mots de passe immédiatement après la première connexion !

## Configuration

### Configuration Apache

#### VirtualHost (Recommandé)

Créer un fichier `/etc/apache2/sites-available/stock.conf` :

```apache
<VirtualHost *:80>
    ServerName stock.monentreprise.local
    ServerAlias www.stock.monentreprise.local

    DocumentRoot /var/www/html/stock_materielinfo

    <Directory /var/www/html/stock_materielinfo>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/stock_error.log
    CustomLog ${APACHE_LOG_DIR}/stock_access.log combined
</VirtualHost>
```

Activer le site :

```bash
sudo a2ensite stock.conf
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2
```

Ajouter au fichier `/etc/hosts` :

```
127.0.0.1   stock.monentreprise.local
```

### Configuration Nginx

Créer un fichier `/etc/nginx/sites-available/stock` :

```nginx
server {
    listen 80;
    server_name stock.monentreprise.local;
    root /var/www/html/stock_materielinfo;
    index index.php index.html;

    access_log /var/log/nginx/stock_access.log;
    error_log /var/log/nginx/stock_error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location ~ /uploads/ {
        # Permettre le téléchargement des fichiers uploadés
        allow all;
    }

    # Cache des ressources statiques
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Activer le site :

```bash
sudo ln -s /etc/nginx/sites-available/stock /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Configuration SSL/HTTPS (Production)

#### Avec Let's Encrypt (Gratuit)

```bash
# Installer Certbot
sudo apt install certbot python3-certbot-apache
# OU pour Nginx
sudo apt install certbot python3-certbot-nginx

# Obtenir un certificat
sudo certbot --apache -d stock.monentreprise.com
# OU pour Nginx
sudo certbot --nginx -d stock.monentreprise.com

# Renouvellement automatique
sudo certbot renew --dry-run
```

Modifier `config/config.php` :

```php
define('BASE_URL', 'https://stock.monentreprise.com');
```

## Optimisations

### 1. Configuration PHP (php.ini)

Éditer `/etc/php/7.4/apache2/php.ini` (adapter la version) :

```ini
; Général
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 10M
upload_max_filesize = 5M

; Erreurs (désactiver en production)
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php_errors.log

; Session
session.gc_maxlifetime = 3600
session.cookie_httponly = On
session.cookie_secure = On  ; Si SSL activé
session.use_strict_mode = On

; OPcache (IMPORTANT pour les performances)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
opcache.enable_cli = 0

; APCu (Cache utilisateur)
apc.enabled = 1
apc.shm_size = 32M
apc.ttl = 7200
apc.enable_cli = 0
```

Redémarrer Apache/PHP-FPM :

```bash
sudo systemctl restart apache2
# OU
sudo systemctl restart php7.4-fpm
```

### 2. Configuration MySQL

Éditer `/etc/mysql/my.cnf` ou `/etc/mysql/mysql.conf.d/mysqld.cnf` :

```ini
[mysqld]
# Performance InnoDB
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1

# Cache des requêtes (désactivé par défaut dans MySQL 8.0+)
query_cache_type = 0
query_cache_size = 0

# Connexions
max_connections = 200
thread_cache_size = 8

# Buffers
sort_buffer_size = 2M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
join_buffer_size = 2M

# Logs (désactiver en production pour performance)
general_log = 0
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

Redémarrer MySQL :

```bash
sudo systemctl restart mysql
```

### 3. Index MySQL supplémentaires

Si vous avez beaucoup de données, créez des index supplémentaires :

```sql
USE stock_materiel;

-- Index pour améliorer les recherches
CREATE INDEX idx_articles_search ON articles(code_article, designation, actif);
CREATE INDEX idx_entrees_date_fournisseur ON entrees(date, fournisseur_id);
CREATE INDEX idx_sorties_date_service ON sorties(date, service_id);

-- Index pour les statistiques
CREATE INDEX idx_ligne_entrees_article_date ON ligne_entrees(article_id, created_at);
CREATE INDEX idx_ligne_sorties_article_date ON ligne_sorties(article_id, created_at);

-- Analyse des tables
ANALYZE TABLE articles, entrees, sorties, ligne_entrees, ligne_sorties;
```

## Sécurité

### 1. Changer les mots de passe par défaut

Après la première connexion :
1. Se connecter en tant qu'admin
2. Aller dans "Administration > Utilisateurs"
3. Modifier chaque compte et changer les mots de passe

### 2. Désactiver les messages d'erreur en production

Dans `config/config.php` :

```php
// Mettre à Off en production
ini_set('display_errors', 0);
error_reporting(0);
```

### 3. Protéger les dossiers sensibles

Le fichier `.htaccess` fourni protège déjà :
- Le dossier `/config/`
- Le dossier `/classes/`
- Le dossier `/includes/`
- Les fichiers `.env`, `composer.json`, etc.

### 4. Backup régulier

#### Backup base de données (cron quotidien)

Créer un script `/home/user/backup_stock.sh` :

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/backups/stock"
mkdir -p $BACKUP_DIR

# Backup BDD
mysqldump -u stock_user -pMOT_DE_PASSE stock_materiel > $BACKUP_DIR/stock_$DATE.sql
gzip $BACKUP_DIR/stock_$DATE.sql

# Backup fichiers
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/html/stock_materielinfo/uploads/

# Supprimer les backups de plus de 30 jours
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

Rendre exécutable et ajouter au cron :

```bash
chmod +x /home/user/backup_stock.sh

# Ajouter au cron (tous les jours à 2h du matin)
crontab -e
0 2 * * * /home/user/backup_stock.sh
```

### 5. Pare-feu

```bash
# UFW (Ubuntu)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp  # SSH
sudo ufw enable

# Firewalld (CentOS/RHEL)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## Dépannage

### Problème : Page blanche

**Cause** : Erreur PHP non affichée

**Solution** :
```bash
# Activer temporairement les erreurs
# Dans config/config.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

# Vérifier les logs
tail -f /var/log/apache2/error.log
# OU
tail -f /var/log/php_errors.log
```

### Problème : Erreur de connexion à la base

**Cause** : Mauvaise configuration ou permissions MySQL

**Solution** :
```bash
# Vérifier la connexion
mysql -u stock_user -p stock_materiel

# Recréer les permissions
mysql -u root -p
GRANT ALL PRIVILEGES ON stock_materiel.* TO 'stock_user'@'localhost';
FLUSH PRIVILEGES;
```

### Problème : Upload de fichiers échoue

**Cause** : Permissions ou taille maximale

**Solution** :
```bash
# Vérifier les permissions
ls -la uploads/
chmod 777 -R uploads/

# Vérifier php.ini
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Augmenter si nécessaire
sudo nano /etc/php/7.4/apache2/php.ini
upload_max_filesize = 10M
post_max_size = 20M
```

### Problème : PDF ne se génère pas

**Cause** : Dompdf non installé ou permissions

**Solution** :
```bash
# Réinstaller Dompdf
cd stock_materielinfo
composer require dompdf/dompdf

# Vérifier les permissions
chmod 755 -R vendor/
```

### Problème : Session expire trop vite

**Cause** : Configuration session

**Solution** :
```php
// Dans config/config.php
define('SESSION_LIFETIME', 7200); // 2 heures

// OU dans php.ini
session.gc_maxlifetime = 7200
```

## Support

Pour toute question ou problème :
1. Consulter la documentation complète dans `README.md`
2. Consulter le guide d'implémentation dans `IMPLEMENTATION_GUIDE.md`
3. Vérifier les logs d'erreurs
4. Contacter l'administrateur système

## Checklist post-installation

- [ ] Base de données créée et importée
- [ ] Dépendances Composer installées
- [ ] Configuration `config/config.php` modifiée
- [ ] Permissions des dossiers définies
- [ ] VirtualHost configuré (optionnel)
- [ ] SSL/HTTPS configuré (production)
- [ ] OPcache activé
- [ ] Mots de passe par défaut changés
- [ ] Backup configuré
- [ ] Tests de fonctionnement effectués
- [ ] Utilisateurs créés
- [ ] Données de base importées

Félicitations ! Votre application de gestion de stock est maintenant opérationnelle ! 🎉
