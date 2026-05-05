# Correction de l'erreur "Invalid parameter number"

## Cause de l'erreur

L'erreur se produit car votre base de données n'a pas toutes les tables et colonnes nécessaires, notamment :
- La colonne `qte_retour` dans la table `articles`
- Les tables `retours` et `ligne_retours`

## Solution

### Étape 1 : Exécuter la migration SQL

Connectez-vous à votre base de données MySQL et exécutez le fichier de migration :

**Option A : Via phpMyAdmin (recommandé pour WAMP)**
1. Ouvrez phpMyAdmin
2. Sélectionnez votre base de données `stock_materielinfo`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu du fichier `database/migration_retours.sql`
5. Cliquez sur "Exécuter"

**Option B : Via ligne de commande MySQL**
```bash
mysql -u root -p stock_materielinfo < database/migration_retours.sql
```

### Étape 2 : Créer le dossier uploads manquant

Dans le répertoire racine de l'application :

**Windows (WAMP)**
```cmd
mkdir uploads\retours
mkdir uploads\logo
```

**Linux/Mac**
```bash
mkdir -p uploads/retours uploads/logo
chmod -R 755 uploads/
```

### Étape 3 : Vérifier que tout fonctionne

1. Rafraîchissez votre page
2. L'erreur devrait disparaître

## Vérification rapide

Pour vérifier que la migration a fonctionné, exécutez cette requête SQL :

```sql
-- Vérifier que la colonne qte_retour existe
DESCRIBE articles;

-- Vérifier que les tables retours existent
SHOW TABLES LIKE '%retours%';
```

Vous devriez voir :
- La colonne `qte_retour` dans la table `articles`
- Les tables `retours` et `ligne_retours`

## Autre cause possible

Si l'erreur persiste après la migration, cela peut être dû à :

1. **Schéma non importé** : Assurez-vous d'avoir importé le schéma complet depuis `database/schema.sql`

2. **Mauvaise configuration** : Vérifiez les paramètres dans `config/config.php` :
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'stock_materielinfo');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Base de données vide** : Si c'est une nouvelle installation, importez d'abord le schéma complet :
   ```bash
   mysql -u root -p stock_materielinfo < database/schema.sql
   ```

## Support

Si l'erreur persiste, vérifiez :
- Les logs d'erreur PHP (dans WAMP : `C:\wamp64\logs\php_error.log`)
- Le message d'erreur complet pour identifier quelle page cause le problème
