# 🔧 Correction erreur "Champ 'reference' inconnu"

## Symptôme
```
Fatal error: Column not found: 1054 Champ 'reference' inconnu dans where clause
```

L'erreur apparaît quand vous cliquez sur **"Créer l'inventaire"** dans le module Inventaires.

## Cause
La table `inventaires` dans votre base de données n'a pas la structure correcte. Il manque plusieurs colonnes :
- `reference` (référence unique de l'inventaire)
- `date_debut` (date de début d'inventaire)
- `date_fin` (date de fin d'inventaire)
- `notes` (commentaires)

## ✅ Solution rapide

### Étape 1 : Exécuter la migration

**Via phpMyAdmin** (recommandé) :

1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Sélectionnez votre base de données `stock_materielinfo`
3. Cliquez sur l'onglet **"SQL"**
4. Ouvrez le fichier **`database/migration_inventaires.sql`**
5. **Copiez TOUT le contenu** du fichier
6. Collez dans la zone SQL de phpMyAdmin
7. Cliquez sur **"Exécuter"**

✅ Vous devriez voir : `Migration inventaires terminée avec succès`

### Étape 2 : Tester

1. Retournez sur la page "Nouvel inventaire"
2. Remplissez le formulaire :
   - **Référence** : INV-2024-001
   - **Date début** : Aujourd'hui
   - **Équipe** : (optionnel)
   - Ajoutez au moins un article
3. Cliquez sur **"Créer l'inventaire"**
4. ✅ L'inventaire devrait être créé sans erreur !

## 🔍 Vérification

Pour vérifier que la migration a fonctionné, exécutez dans phpMyAdmin :

```sql
DESCRIBE inventaires;
```

Vous devriez voir ces colonnes :
- ✅ `id`
- ✅ `reference` (VARCHAR 100, UNIQUE)
- ✅ `date_debut` (DATE)
- ✅ `date_fin` (DATE, nullable)
- ✅ `equipe_id` (INT, nullable)
- ✅ `fichier`
- ✅ `notes` (TEXT, nullable)
- ✅ `etat` (enum: en_cours, valide, cloture)
- ✅ `user_id`
- ✅ `user_validation_id`
- ✅ `date_validation`
- ✅ `created_at`
- ✅ `updated_at`

## ⚠️ Notes importantes

### Si vous avez déjà des inventaires dans la base

**ATTENTION** : Cette migration va supprimer la colonne `date` originale.

Si vous avez déjà créé des inventaires, faites d'abord une sauvegarde :

```sql
-- Sauvegarder les données existantes
CREATE TABLE inventaires_backup AS SELECT * FROM inventaires;
```

Puis après la migration, vous devrez peut-être copier les données :

```sql
-- Copier l'ancienne date vers date_debut (si nécessaire)
-- Cette étape n'est nécessaire QUE si vous aviez des données avant
```

### Structure finale attendue

Après la migration, la table `inventaires` aura cette structure :

```sql
CREATE TABLE `inventaires` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `reference` varchar(100) NOT NULL UNIQUE,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `equipe_id` int(11) DEFAULT NULL,
  `fichier` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `etat` enum('en_cours','valide','cloture') DEFAULT 'en_cours',
  `user_id` int(11) NOT NULL,
  `user_validation_id` int(11) DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_reference` (`reference`),
  KEY `idx_date_debut` (`date_debut`),
  KEY `idx_date_fin` (`date_fin`),
  KEY `idx_equipe` (`equipe_id`),
  KEY `idx_etat` (`etat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🚨 Si l'erreur persiste

1. **Vérifiez que la migration s'est bien exécutée** :
   ```sql
   SHOW COLUMNS FROM inventaires LIKE 'reference';
   ```
   Vous devriez voir une ligne avec la colonne `reference`.

2. **Vérifiez les logs d'erreur** :
   - WAMP : `C:\wamp64\logs\php_error.log`
   - Cherchez les messages d'erreur détaillés

3. **Rafraîchissez le cache** :
   - Videz le cache de votre navigateur (Ctrl+Shift+Delete)
   - Redémarrez les services Apache/MySQL de WAMP

## 📝 Fichiers concernés

Les fichiers du module Inventaires qui utilisent ces colonnes :
- `pages/inventaires/index.php`
- `pages/inventaires/create.php`
- `pages/inventaires/edit.php`
- `pages/inventaires/view.php`

Tous ces fichiers ont été créés pour fonctionner avec la nouvelle structure après migration.

## ✔️ Test complet du module Inventaires

Après la migration, testez le workflow complet :

1. ✅ **Créer** un inventaire (état: en_cours)
2. ✅ **Saisir** les quantités physiques dans la vue
3. ✅ **Générer les écarts** (bouton dans la vue)
4. ✅ **Valider** l'inventaire (passe en état: valide)
5. ✅ **Clôturer** l'inventaire en tant qu'admin (passe en état: cloture)

Le système à 3 états devrait fonctionner correctement après la migration !
