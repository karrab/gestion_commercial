# 🔧 Correction de l'erreur après login

## Symptôme
```
Fatal error: Uncaught PDOException: SQLSTATE[HY093]: Invalid parameter number
in C:\wamp64pmb\www\stock_materielinfo\classes\Database.php on line 82
```

L'erreur apparaît **immédiatement après le login** sur le tableau de bord.

## Causes
1. Tables manquantes dans la base de données (`retours`, `ligne_retours`)
2. Colonne `qte_retour` manquante dans la table `articles`
3. Conflit dans la classe `Database.php` entre les paramètres bindés et les paramètres passés à `execute()`

## ✅ Solution complète

### Étape 1 : Corriger la base de données

**Via phpMyAdmin** (recommandé pour WAMP) :

1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Sélectionnez votre base de données `stock_materielinfo`
3. Cliquez sur l'onglet **SQL**
4. Copiez **TOUT** le contenu du fichier `database/fix_tables.sql`
5. Collez dans la zone SQL
6. Cliquez sur **Exécuter**

Vous devriez voir un message de succès : `Tables créées avec succès`

### Étape 2 : Vérifier que tout est OK

Dans phpMyAdmin, exécutez cette requête pour vérifier :

```sql
-- Vérifier que les tables existent
SHOW TABLES LIKE '%retours%';

-- Vérifier que la colonne qte_retour existe
DESCRIBE articles;
```

**Résultat attendu :**
- Vous devez voir les tables `retours` et `ligne_retours`
- Dans la description de `articles`, vous devez voir la colonne `qte_retour`

### Étape 3 : Tester

1. **Rafraîchissez** la page de votre navigateur
2. Essayez de vous reconnecter
3. Le tableau de bord devrait s'afficher sans erreur

## 🚨 Si l'erreur persiste

### Option 1 : Réimporter le schéma complet

Si les corrections ne fonctionnent pas, réimportez le schéma complet :

1. Dans phpMyAdmin, sélectionnez votre base `stock_materielinfo`
2. **ATTENTION** : Cela va supprimer toutes les données !
3. Cliquez sur "Opérations" > "Supprimer la base de données"
4. Recréez la base de données vide
5. Importez le fichier `database/schema.sql` :
   - Onglet "Importer"
   - "Choisir un fichier" > sélectionnez `schema.sql`
   - Cliquez sur "Exécuter"

### Option 2 : Vérifier config.php

Vérifiez que les paramètres de connexion sont corrects dans `config/config.php` :

```php
define('DB_HOST', '127.0.0.1');  // ou 'localhost'
define('DB_NAME', 'stock_materielinfo');
define('DB_USER', 'root');
define('DB_PASS', '');  // Votre mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');
```

### Option 3 : Vérifier les logs

Dans WAMP, vérifiez le fichier de log PHP :
- Chemin : `C:\wamp64\logs\php_error.log`
- Recherchez la ligne exacte qui cause l'erreur

## 📝 Détails techniques

### Ce qui a été corrigé

1. **Database.php** : Modification de la méthode `execute()` pour ne pas passer de paramètres vides quand on utilise `bind()`

2. **Tables manquantes** : Ajout des tables `retours` et `ligne_retours` nécessaires pour le module de retours

3. **Colonne manquante** : Ajout de la colonne `qte_retour` dans la table `articles`

### Pourquoi l'erreur apparaît après login ?

Le tableau de bord (`index.php`) fait plusieurs requêtes SQL pour afficher les statistiques. Si une table ou colonne manque, PDO génère cette erreur.

## ✔️ Vérification finale

Pour confirmer que tout fonctionne :

1. ✅ Login réussi
2. ✅ Tableau de bord s'affiche
3. ✅ Statistiques visibles (Articles actifs, Entrées du mois, etc.)
4. ✅ Pas d'erreur PHP

## 🆘 Besoin d'aide ?

Si l'erreur persiste après toutes ces étapes, notez :
- Le message d'erreur complet
- La page exacte où l'erreur apparaît
- Le contenu du fichier `php_error.log`
