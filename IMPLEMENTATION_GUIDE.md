# Guide d'implémentation des CRUDs

Ce guide explique comment implémenter tous les CRUDs de l'application en utilisant le pattern établi.

## Structure standard d'un CRUD

Chaque module CRUD doit avoir les fichiers suivants dans `pages/[module]/` :

1. **index.php** - Liste paginée avec recherche et DataTables
2. **create.php** - Formulaire de création
3. **edit.php** - Formulaire d'édition
4. **view.php** - Affichage détaillé
5. **delete.php** - Suppression avec confirmation
6. **print.php** (optionnel) - Impression PDF

## Template CREATE.PHP

```php
<?php
$page_title = 'Nouveau [Module]';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    $errors = [];
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire.';
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO [table] (nom, notes) VALUES (:nom, :notes)";
            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':notes', $notes);

            if ($db->execute()) {
                $id = $db->lastInsertId();

                // Log de la trace
                $auth->logTrace($auth->getUserId(), '[module]', 'create', '[table]', $id, "Création: $nom");

                $_SESSION['success'] = '[Module] créé avec succès.';
                header('Location: ' . BASE_URL . '/pages/[module]/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-[icon]"></i> Nouveau [Module]</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/[module]/index.php">[Modules]</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle"></i> Informations du [module]
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="nom" class="form-label required">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required
                                   value="<?php echo htmlspecialchars($nom ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/pages/[module]/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

## Template EDIT.PHP

```php
<?php
$page_title = 'Modifier [Module]';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Récupération de l'enregistrement
$db->prepare("SELECT * FROM [table] WHERE id = :id");
$db->bind(':id', $id);
$record = $db->fetch();

if (!$record) {
    $_SESSION['error'] = '[Module] introuvable.';
    header('Location: ' . BASE_URL . '/pages/[module]/index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire.';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE [table] SET nom = :nom, notes = :notes WHERE id = :id";
            $db->prepare($sql);
            $db->bind(':nom', $nom);
            $db->bind(':notes', $notes);
            $db->bind(':id', $id);

            if ($db->execute()) {
                $auth->logTrace($auth->getUserId(), '[module]', 'update', '[table]', $id, "Modification: $nom");

                $_SESSION['success'] = '[Module] modifié avec succès.';
                header('Location: ' . BASE_URL . '/pages/[module]/index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
} else {
    $nom = $record['nom'];
    $notes = $record['notes'];
}
?>

<!-- Même structure de formulaire que create.php -->
```

## Template DELETE.PHP

```php
<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

// Vérification de l'existence
$db->prepare("SELECT * FROM [table] WHERE id = :id");
$db->bind(':id', $id);
$record = $db->fetch();

if (!$record) {
    $_SESSION['error'] = '[Module] introuvable.';
    header('Location: ' . BASE_URL . '/pages/[module]/index.php');
    exit;
}

// Vérifier si l'enregistrement est utilisé ailleurs (clés étrangères)
$db->prepare("SELECT COUNT(*) as count FROM [table_liee] WHERE [module]_id = :id");
$db->bind(':id', $id);
$usage = $db->fetch();

if ($usage['count'] > 0) {
    $_SESSION['error'] = 'Impossible de supprimer ce [module] car il est utilisé.';
    header('Location: ' . BASE_URL; ?>/pages/[module]/index.php');
    exit;
}

try {
    $db->prepare("DELETE FROM [table] WHERE id = :id");
    $db->bind(':id', $id);

    if ($db->execute()) {
        $auth->logTrace($auth->getUserId(), '[module]', 'delete', '[table]', $id, "Suppression: " . $record['nom']);

        $_SESSION['success'] = '[Module] supprimé avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
}

header('Location: ' . BASE_URL . '/pages/[module]/index.php');
exit;
```

## Template VIEW.PHP

```php
<?php
$page_title = 'Détails [Module]';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$id = $_GET['id'] ?? 0;

$db->prepare("SELECT * FROM [table] WHERE id = :id");
$db->bind(':id', $id);
$record = $db->fetch();

if (!$record) {
    $_SESSION['error'] = '[Module] introuvable.';
    header('Location: ' . BASE_URL . '/pages/[module]/index.php');
    exit;
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-[icon]"></i> Détails du [module]</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/[module]/index.php">[Modules]</a></li>
                            <li class="breadcrumb-item active">Détails</li>
                        </ol>
                    </nav>
                </div>
                <div class="no-print">
                    <a href="<?php echo BASE_URL; ?>/pages/[module]/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/[module]/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Informations
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">ID</th>
                            <td><?php echo $record['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td><?php echo htmlspecialchars($record['nom']); ?></td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo nl2br(htmlspecialchars($record['notes'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <th>Date de création</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière modification</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

## Implémentations spécifiques

### CRUD ARTICLES (avec calculs automatiques)

Dans create.php et edit.php, ajouter :

```php
// NE PAS permettre la modification directe de qte_entree, qte_sortie, qte_disponible
// Ces champs sont calculés automatiquement via les mouvements

// Seul stock_initial peut être modifié (et seulement par admin lors de la création)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... autres champs

    // Stock initial (uniquement à la création ou par admin)
    if (!isset($record) || $auth->isAdmin()) {
        $stock_initial = floatval($_POST['stock_initial'] ?? 0);
    } else {
        $stock_initial = $record['stock_initial']; // Garder la valeur existante
    }

    // Calcul de qte_disponible
    // À la création: qte_disponible = stock_initial
    // Après: maintenu via triggers ou updates dans entrées/sorties
}
```

### CRUD ENTRÉES (avec lignes et upload)

create.php :

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gestion de l'upload
    $fichier = '';
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['fichier']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['fichier']['size'] <= MAX_FILE_SIZE) {
            $newname = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], UPLOAD_ENTREES_PATH . '/' . $newname)) {
                $fichier = $newname;
            }
        }
    }

    // Transaction pour entête + lignes
    try {
        $db->beginTransaction();

        // Insert entête
        $sql = "INSERT INTO entrees (fournisseur_id, date, fichier, notes, user_id)
                VALUES (:fournisseur_id, :date, :fichier, :notes, :user_id)";
        $db->prepare($sql);
        // ... binds et execute
        $entree_id = $db->lastInsertId();

        // Insert lignes
        $articles = $_POST['articles'] ?? []; // Array d'IDs d'articles
        $quantites = $_POST['quantites'] ?? []; // Array de quantités

        foreach ($articles as $index => $article_id) {
            if (empty($article_id) || empty($quantites[$index])) continue;

            // Récupérer info article
            $db->prepare("SELECT code_article, designation FROM articles WHERE id = :id");
            $db->bind(':id', $article_id);
            $article = $db->fetch();

            // Insert ligne
            $sql = "INSERT INTO ligne_entrees (entree_id, article_id, code_article, designation, qte_entree)
                    VALUES (:entree_id, :article_id, :code, :designation, :qte)";
            // ... insert

            // Mise à jour stock article
            $sql = "UPDATE articles
                    SET qte_entree = qte_entree + :qte,
                        qte_disponible = qte_disponible + :qte
                    WHERE id = :article_id";
            // ... update
        }

        $db->commit();
        $_SESSION['success'] = 'Entrée créée avec succès.';
        header('Location: ...');
        exit;

    } catch (Exception $e) {
        $db->rollback();
        $errors[] = 'Erreur: ' . $e->getMessage();
    }
}
```

### CRUD SORTIES (avec vérification stock)

```php
// Avant l'insert de la ligne
$db->prepare("SELECT qte_disponible FROM articles WHERE id = :id");
$db->bind(':id', $article_id);
$article = $db->fetch();

if ($article['qte_disponible'] < $quantite) {
    throw new Exception('Quantité indisponible en stock pour ' . $article['designation']);
}

// Si OK, procéder avec l'insert et la mise à jour du stock
$sql = "UPDATE articles
        SET qte_sortie = qte_sortie + :qte,
            qte_disponible = qte_disponible - :qte
        WHERE id = :article_id";
```

### CRUD RETOURS

```php
// Mise à jour du stock lors d'un retour
$sql = "UPDATE articles
        SET qte_disponible = qte_disponible + :qte
        WHERE id = :article_id";
```

### CRUD INVENTAIRES (le plus complexe)

```php
// État: en_cours, valide, cloture
// Seul admin peut valider et réinitialiser

// Bouton "Générer" - Calcule les écarts
if (isset($_POST['generer'])) {
    $sql = "UPDATE ligne_inventaires
            SET ecart = qte_physique - qte_theorique
            WHERE inventaire_id = :id";
}

// Bouton "Valider" - Change l'état (admin seulement)
if (isset($_POST['valider']) && $auth->isAdmin()) {
    $sql = "UPDATE inventaires
            SET etat = 'valide',
                user_validation_id = :user_id,
                date_validation = NOW()
            WHERE id = :id";
}

// Bouton "Réinitialiser" - Remet les stocks (admin seulement)
if (isset($_POST['reinitialiser']) && $auth->isAdmin()) {
    // Pour chaque ligne d'inventaire
    $sql = "UPDATE articles a
            INNER JOIN ligne_inventaires li ON a.id = li.article_id
            SET a.qte_disponible = li.qte_physique,
                a.stock_initial = li.qte_physique,
                a.qte_entree = 0,
                a.qte_sortie = 0
            WHERE li.inventaire_id = :id";

    // Marquer l'inventaire comme cloturé
    $sql = "UPDATE inventaires SET etat = 'cloture' WHERE id = :id";
}
```

## Exports PDF

Créer une classe PDF helper :

```php
// classes/PDF.php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class PDF {
    private $dompdf;
    private $options;

    public function __construct() {
        $this->options = new Options();
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('isRemoteEnabled', true);
        $this->dompdf = new Dompdf($this->options);
    }

    public function generate($html, $filename, $orientation = 'portrait') {
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', $orientation);
        $this->dompdf->render();
        $this->dompdf->stream($filename, ['Attachment' => false]);
    }

    public function getHeader($parametres) {
        return '
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="' . IMAGES_PATH . '/' . $parametres['logo'] . '" height="60">
                <h3>' . $parametres['nom_etablissement'] . '</h3>
                <p>' . $parametres['adresse'] . '<br>
                Tel: ' . $parametres['tel_fixe'] . ' - Email: ' . $parametres['email'] . '</p>
            </div>
            <hr>
        ';
    }
}
```

Utilisation :

```php
// pages/entrees/pdf.php
require_once __DIR__ . '/../../config/config.php';
$pdf = new PDF();
$db = Database::getInstance();

// Récupérer les données...

$html = $pdf->getHeader($parametres);
$html .= '<h2>Bon d\'entrée N° ' . $entree['id'] . '</h2>';
$html .= '<table>...</table>';

$pdf->generate($html, 'bon_entree_' . $entree['id'] . '.pdf');
```

## Liste complète des modules à créer

1. ✅ Services
2. Employés (avec relation service)
3. Fournisseurs
4. Bureaux (avec relations service + employé)
5. Armoires
6. Équipes inventaire
7. Articles (avec calculs)
8. Entrées (avec lignes + upload)
9. Sorties (avec lignes + vérif stock + upload)
10. Retours (avec lignes + upload)
11. Inventaires (complexe)
12. Utilisateurs (avec rôles/permissions)
13. Paramètres
14. Recalcul stock
15. Rapports
16. Traces
17. Profil/Change password

Suivez les templates ci-dessus pour chaque module en adaptant les champs spécifiques !
