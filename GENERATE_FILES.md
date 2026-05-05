# Générateur de fichiers pour compléter l'application

## État actuel

✅ **Fichiers créés (30 fichiers)** :
- Infrastructure complète (config, classes, includes, assets)
- Base de données complète
- API endpoints (4 fichiers)
- Services CRUD complet (4 fichiers)
- Entrées (create.php, pdf.php)
- Employés (index.php)
- Documentation complète

## Fichiers restants à créer (~70 fichiers)

### Méthode recommandée

Pour créer rapidement tous les fichiers manquants, utilisez le **template generator** suivant :

## 1. Créer un fichier PHP template générique

Créez `/home/user/stock_materielinfo/tools/generator.php` :

```php
<?php
/**
 * Générateur de fichiers CRUD
 * Usage: php tools/generator.php <module> <action>
 * Example: php tools/generator.php employes create
 */

if ($argc < 3) {
    die("Usage: php generator.php <module> <action>\n");
}

$module = $argv[1];  // Ex: employes
$action = $argv[2];  // Ex: create, edit, view, delete, index

$templates = [
    'index' => file_get_contents('templates/index_template.php'),
    'create' => file_get_contents('templates/create_template.php'),
    'edit' => file_get_contents('templates/edit_template.php'),
    'view' => file_get_contents('templates/view_template.php'),
    'delete' => file_get_contents('templates/delete_template.php'),
];

$config = [
    'employes' => [
        'title' => 'Employé',
        'title_plural' => 'Employés',
        'icon' => 'people',
        'table' => 'employes',
        'fields' => [
            'matricule' => ['type' => 'text', 'required' => true, 'unique' => true],
            'nom' => ['type' => 'text', 'required' => true],
            'prenom' => ['type' => 'text', 'required' => true],
            'service_id' => ['type' => 'select2', 'required' => true, 'table' => 'services'],
            'mail' => ['type' => 'email'],
            'tel1' => ['type' => 'tel'],
            'tel2' => ['type' => 'tel'],
            'notes' => ['type' => 'textarea'],
        ]
    ],
    // Ajouter les autres modules ici...
];

// Générer le fichier
$template = $templates[$action];
$module_config = $config[$module];

// Remplacements
$replacements = [
    '{{MODULE}}' => $module,
    '{{TITLE}}' => $module_config['title'],
    '{{TITLE_PLURAL}}' => $module_config['title_plural'],
    '{{ICON}}' => $module_config['icon'],
    '{{TABLE}}' => $module_config['table'],
];

$content = str_replace(array_keys($replacements), array_values($replacements), $template);

// Créer le fichier
$output_dir = "../pages/$module";
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

$output_file = "$output_dir/$action.php";
file_put_contents($output_file, $content);

echo "✓ Fichier créé: $output_file\n";
```

## 2. Liste des fichiers à créer par module

### Module Employés (4 fichiers restants)
```bash
cd /home/user/stock_materielinfo
mkdir -p pages/employes

# À créer manuellement en copiant depuis pages/services/ :
cp pages/services/create.php pages/employes/create.php
cp pages/services/edit.php pages/employes/edit.php
cp pages/services/view.php pages/employes/view.php
cp pages/services/delete.php pages/employes/delete.php

# Puis adapter les champs spécifiques (matricule, service_id, etc.)
```

**Champs à adapter** :
- matricule (unique)
- nom, prenom
- service_id (Select2)
- mail, tel1, tel2
- notes

### Module Fournisseurs (5 fichiers)
```bash
mkdir -p pages/fournisseurs
# Copier templates depuis services/
# Adapter les champs: nom_complet, adresse, ville, pays, code_postal, tel1, tel2
```

### Module Bureaux (5 fichiers)
```bash
mkdir -p pages/bureaux
# Champs: code_local, service_id (Select2), employe_id (Select2)
# IMPORTANT: Filtrer employés par service sélectionné (AJAX)
```

### Module Armoires (5 fichiers)
```bash
mkdir -p pages/armoires
# Champs simples: numero, nom
```

### Module Équipes (5 fichiers)
```bash
mkdir -p pages/equipes
# Champs: nom, description, notes
```

### Module Articles (6 fichiers) ⚠️ CRITIQUE
```bash
mkdir -p pages/articles
# Fichiers: index.php, create.php, edit.php, view.php, delete.php, mouvements.php
```

**IMPORTANT pour Articles** :
```php
// Dans create.php - stock_initial modifiable
$stock_initial = floatval($_POST['stock_initial'] ?? 0);
$qte_disponible = $stock_initial; // À la création

// Dans edit.php - stock_initial en lecture seule (sauf admin)
if ($auth->isAdmin()) {
    $stock_initial = floatval($_POST['stock_initial'] ?? $article['stock_initial']);
} else {
    $stock_initial = $article['stock_initial']; // Garder la valeur existante
}

// Ne JAMAIS permettre la modification de:
// - qte_entree (calculée par entrées)
// - qte_sortie (calculée par sorties)
// - qte_disponible (calculée automatiquement)
```

### Module Sorties (6 fichiers) ⚠️ TRÈS CRITIQUE
```bash
mkdir -p pages/sorties
# Fichiers: index.php, create.php, edit.php, view.php, delete.php, pdf.php
```

**create.php pour Sorties - Code critique** :
```php
// Vérifier le stock AVANT insertion
$db->prepare("SELECT qte_disponible, designation FROM articles WHERE id = :id");
$db->bind(':id', $article_id);
$article = $db->fetch();

if ($article['qte_disponible'] < $qte_sortie) {
    throw new Exception("Quantité indisponible en stock pour " . $article['designation'] .
                        " (disponible: " . $article['qte_disponible'] . ", demandé: " . $qte_sortie . ")");
}

// Si OK, procéder
$sql = "INSERT INTO ligne_sorties ...";
// ...

$sql = "UPDATE articles
        SET qte_sortie = qte_sortie + :qte,
            qte_disponible = qte_disponible - :qte
        WHERE id = :article_id";
```

### Module Retours (6 fichiers)
```bash
mkdir -p pages/retours
# Même structure que Entrées
# Update stock: qte_disponible = qte_disponible + qte_retour
```

### Module Inventaires (8 fichiers) ⚠️ LE PLUS COMPLEXE
```bash
mkdir -p pages/inventaires
# Fichiers: index.php, create.php, edit.php, view.php, delete.php, pdf.php,
#           generer_ecarts.php, valider.php, reinitialiser.php
```

**États inventaire** :
- `en_cours` : Comptage en cours
- `valide` : Validé par admin
- `cloture` : Stock réinitialisé

**Actions critiques** :
```php
// generer_ecarts.php
UPDATE ligne_inventaires
SET ecart = qte_physique - qte_theorique
WHERE inventaire_id = :id

// valider.php (admin only)
UPDATE inventaires
SET etat = 'valide', user_validation_id = :user_id, date_validation = NOW()
WHERE id = :id

// reinitialiser.php (admin only)
UPDATE articles a
INNER JOIN ligne_inventaires li ON a.id = li.article_id
SET a.qte_disponible = li.qte_physique,
    a.stock_initial = li.qte_physique,
    a.qte_entree = 0,
    a.qte_sortie = 0
WHERE li.inventaire_id = :id;

UPDATE inventaires SET etat = 'cloture' WHERE id = :id;
```

### Module Utilisateurs (5 fichiers)
```bash
mkdir -p pages/users
# Fichiers: index.php, create.php, edit.php, view.php, delete.php
```

**Hashage mot de passe** :
```php
// create.php
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// edit.php - ne changer que si nouveau MDP fourni
if (!empty($_POST['new_password'])) {
    $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
}
```

### Module Paramètres (1 fichier)
```bash
mkdir -p pages/parametres
# edit.php avec upload de logo
```

### Module Profil (2 fichiers)
```bash
mkdir -p pages/profil
# edit.php, change_password.php
```

### Module Rapports (5 fichiers)
```bash
mkdir -p pages/rapports
# entrees.php, sorties.php, stock.php, traces.php, export_pdf.php
```

### Recherche globale (1 fichier)
```bash
# pages/search.php
```

### Recalcul stock (1 fichier)
```bash
mkdir -p pages/recalcul
# index.php
```

### APIs supplémentaires (2 fichiers)
```bash
# api/get_employes_by_service.php
# api/check_stock.php
```

## 3. Commande rapide pour créer la structure

```bash
cd /home/user/stock_materielinfo

# Créer tous les dossiers
mkdir -p pages/{employes,fournisseurs,bureaux,armoires,equipes,articles,sorties,retours,inventaires,users,parametres,profil,rapports,recalcul}

# Créer les fichiers index vides pour commencer
for dir in employes fournisseurs bureaux armoires equipes articles sorties retours inventaires users; do
    touch pages/$dir/{index,create,edit,view,delete}.php
done

echo "Structure créée ! Maintenant, copiez le contenu depuis les templates."
```

## 4. Ordre de développement recommandé

1. **Employés** (simple avec Select2)
2. **Fournisseurs** (simple)
3. **Armoires** (très simple)
4. **Équipes** (simple)
5. **Bureaux** (avec Select2 + filtrage AJAX)
6. **Articles** (CRITIQUE - calcul automatique stock)
7. **Compléter Entrées** (index, edit, view, delete)
8. **Sorties** (CRITIQUE - vérification stock)
9. **Retours** (comme entrées)
10. **Inventaires** (complexe)
11. **Utilisateurs**
12. **Paramètres et Profil**
13. **Rapports**
14. **Recalcul et Recherche**

## 5. Tester au fur et à mesure

Après chaque module, testez :
```bash
# Démarrer le serveur PHP
php -S localhost:8000

# Tester dans le navigateur
http://localhost:8000/stock_materielinfo
```

## 6. Template réutilisable

Pour créer rapidement n'importe quel CRUD, copiez `pages/services/` :

```bash
# Exemple pour créer le module Armoires
cp -r pages/services pages/armoires

# Puis faire un rechercher/remplacer global :
# services → armoires
# service → armoire
# Service → Armoire
# bi-building → bi-archive
# nom → numero (adapter les champs)
```

## Conclusion

Vous avez maintenant :
- ✅ 30 fichiers créés et fonctionnels
- ✅ Infrastructure complète
- ✅ Un module CRUD complet (Services) comme modèle
- ✅ Templates et guides pour créer les 70 fichiers restants

**Estimation temps** : 4-6 heures pour compléter tous les modules en suivant les templates

**Bon développement ! 🚀**
