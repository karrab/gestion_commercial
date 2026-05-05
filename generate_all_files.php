<?php
/**
 * Générateur automatique de tous les fichiers CRUD manquants
 * Usage: php generate_all_files.php
 */

$base_path = __DIR__ . '/pages';
$files_created = 0;
$errors = [];

// Configuration des modules
$modules_config = [
    // Modules simples
    'fournisseurs' => [
        'title' => 'Fournisseur', 'title_plural' => 'Fournisseurs', 'icon' => 'truck',
        'table' => 'fournisseurs', 'genre' => 'm',
        'fields' => [
            'nom_complet' => ['type' => 'text', 'required' => true, 'unique' => false],
            'adresse' => ['type' => 'textarea'],
            'ville' => ['type' => 'text'],
            'pays' => ['type' => 'text'],
            'code_postal' => ['type' => 'text'],
            'tel1' => ['type' => 'tel'],
            'tel2' => ['type' => 'tel'],
            'notes' => ['type' => 'textarea'],
        ],
        'has_actif' => true,
    ],
    'armoires' => [
        'title' => 'Armoire', 'title_plural' => 'Armoires', 'icon' => 'archive',
        'table' => 'armoires', 'genre' => 'f',
        'fields' => [
            'numero' => ['type' => 'text', 'required' => true, 'unique' => true],
            'nom' => ['type' => 'text', 'required' => true],
        ],
    ],
    'equipes' => [
        'title' => 'Équipe', 'title_plural' => 'Équipes d\'inventaire', 'icon' => 'person-workspace',
        'table' => 'equipes_inventaire', 'genre' => 'f',
        'fields' => [
            'nom' => ['type' => 'text', 'required' => true],
            'description' => ['type' => 'textarea'],
            'notes' => ['type' => 'textarea'],
        ],
    ],
    'bureaux' => [
        'title' => 'Bureau', 'title_plural' => 'Bureaux', 'icon' => 'door-open',
        'table' => 'bureaux', 'genre' => 'm',
        'fields' => [
            'code_local' => ['type' => 'text', 'required' => true, 'unique' => true],
            'service_id' => ['type' => 'select2', 'required' => true, 'relation' => 'services'],
            'employe_id' => ['type' => 'select2', 'relation' => 'employes'],
        ],
    ],
];

echo "=== GÉNÉRATION DES FICHIERS CRUD ===\n\n";

// Fonction pour générer le code CREATE
function generate_create($module, $config) {
    $title = $config['title'];
    $icon = $config['icon'];
    $table = $config['table'];
    $fields = $config['fields'];
    $genre = $config['genre'] ?? 'm';
    $le_la = $genre === 'f' ? 'la' : 'le';
    $un_une = $genre === 'f' ? 'une' : 'un';

    // Génération des champs du formulaire
    $form_fields = '';
    $validation_code = '';
    $insert_fields = [];
    $insert_values = [];
    $bind_code = '';

    foreach ($fields as $field_name => $field_config) {
        $field_type = $field_config['type'] ?? 'text';
        $required = $field_config['required'] ?? false;
        $unique = $field_config['unique'] ?? false;

        // Validation
        if ($required) {
            $label = ucfirst(str_replace('_', ' ', $field_name));
            $validation_code .= "    if (empty(\${$field_name})) \$errors[] = '{$label} est obligatoire.';\n";
        }

        if ($unique) {
            $validation_code .= "    // Vérifier unicité\n";
            $validation_code .= "    \$db->prepare(\"SELECT COUNT(*) as count FROM {$table} WHERE {$field_name} = :{$field_name}\");\n";
            $validation_code .= "    \$db->bind(':{$field_name}', \${$field_name});\n";
            $validation_code .= "    if (\$db->fetch()['count'] > 0) \$errors[] = 'Ce {$field_name} existe déjà.';\n\n";
        }

        $insert_fields[] = $field_name;
        $insert_values[] = ":{$field_name}";

        if ($field_type === 'select2') {
            $bind_code .= "            \$db->bind(':{$field_name}', \${$field_name});\n";
        } else {
            $bind_code .= "            \$db->bind(':{$field_name}', \${$field_name});\n";
        }

        // Champ de formulaire
        $label = ucfirst(str_replace('_', ' ', $field_name));
        $req_class = $required ? 'required' : '';
        $req_attr = $required ? 'required' : '';

        if ($field_type === 'textarea') {
            $form_fields .= "                        <div class=\"mb-3\">\n";
            $form_fields .= "                            <label for=\"{$field_name}\" class=\"form-label {$req_class}\">{$label}</label>\n";
            $form_fields .= "                            <textarea class=\"form-control\" id=\"{$field_name}\" name=\"{$field_name}\" rows=\"3\" {$req_attr}><?php echo htmlspecialchars(\${$field_name} ?? ''); ?></textarea>\n";
            $form_fields .= "                        </div>\n";
        } elseif ($field_type === 'select2') {
            $relation = $field_config['relation'] ?? '';
            $form_fields .= "                        <div class=\"mb-3\">\n";
            $form_fields .= "                            <label for=\"{$field_name}\" class=\"form-label {$req_class}\">{$label}</label>\n";
            $form_fields .= "                            <select class=\"form-select select2\" id=\"{$field_name}\" name=\"{$field_name}\" {$req_attr}>\n";
            $form_fields .= "                                <option value=\"\">Sélectionner...</option>\n";
            $form_fields .= "                            </select>\n";
            $form_fields .= "                        </div>\n";
        } else {
            $input_type = $field_type === 'tel' ? 'tel' : ($field_type === 'email' ? 'email' : 'text');
            $form_fields .= "                        <div class=\"mb-3\">\n";
            $form_fields .= "                            <label for=\"{$field_name}\" class=\"form-label {$req_class}\">{$label}</label>\n";
            $form_fields .= "                            <input type=\"{$input_type}\" class=\"form-control\" id=\"{$field_name}\" name=\"{$field_name}\" {$req_attr}\n";
            $form_fields .= "                                   value=\"<?php echo htmlspecialchars(\${$field_name} ?? ''); ?>\">\n";
            $form_fields .= "                        </div>\n";
        }
    }

    $has_actif = $config['has_actif'] ?? false;
    if ($has_actif) {
        $insert_fields[] = 'actif';
        $insert_values[] = '1';
    }

    $insert_fields_str = implode(', ', $insert_fields);
    $insert_values_str = implode(', ', $insert_values);

    // POST variables
    $post_vars = '';
    foreach ($fields as $field_name => $field_config) {
        $field_type = $field_config['type'] ?? 'text';
        if ($field_type === 'select2') {
            $post_vars .= "    \${$field_name} = intval(\$_POST['{$field_name}'] ?? 0);\n";
        } elseif ($field_type === 'textarea') {
            $post_vars .= "    \${$field_name} = trim(\$_POST['{$field_name}'] ?? '');\n";
        } else {
            $post_vars .= "    \${$field_name} = trim(\$_POST['{$field_name}'] ?? '');\n";
        }
    }

    $code = <<<PHP
<?php
\$page_title = 'Nouveau{$genre === 'f' ? 'le' : ''} {$title}';
require_once __DIR__ . '/../../includes/header.php';

\$db = Database::getInstance();

if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
$post_vars
    \$errors = [];

$validation_code
    if (empty(\$errors)) {
        try {
            \$sql = "INSERT INTO {$table} ({$insert_fields_str}) VALUES ({$insert_values_str})";
            \$db->prepare(\$sql);
$bind_code
            if (\$db->execute()) {
                \$id = \$db->lastInsertId();
                \$auth->logTrace(\$auth->getUserId(), '{$module}', 'create', '{$table}', \$id, "Création");
                \$_SESSION['success'] = '{$title} créé{$genre === 'f' ? 'e' : ''} avec succès.';
                header('Location: ' . BASE_URL . '/pages/{$module}/index.php');
                exit;
            }
        } catch (Exception \$e) {
            \$errors[] = 'Erreur: ' . \$e->getMessage();
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-{$icon}"></i> Nouveau{$genre === 'f' ? 'le' : ''} {$title}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/pages/{$module}/index.php">{$config['title_plural']}</a></li>
                    <li class="breadcrumb-item active">Nouveau</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty(\$errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach (\$errors as \$error): ?><li><?php echo \$error; ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-info-circle"></i> Informations</div>
                    <div class="card-body">
$form_fields
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-gear"></i> Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                            <a href="<?php echo BASE_URL; ?>/pages/{$module}/index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
PHP;

    return $code;
}

// Générer tous les fichiers CREATE pour les modules simples
foreach ($modules_config as $module => $config) {
    $module_path = "$base_path/$module";

    if (!is_dir($module_path)) {
        mkdir($module_path, 0755, true);
        echo "✓ Créé dossier: $module\n";
    }

    // Générer create.php
    $create_file = "$module_path/create.php";
    if (!file_exists($create_file)) {
        $code = generate_create($module, $config);
        file_put_contents($create_file, $code);
        $files_created++;
        echo "✓ Créé: pages/$module/create.php\n";
    }

    // Les autres fichiers (edit, view, delete, index si nécessaire)
    // seront créés de manière similaire
}

echo "\n=== RÉSUMÉ ===\n";
echo "Fichiers créés: $files_created\n";
echo "Erreurs: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nErreurs:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nPour compléter la génération, exécutez ce script et créez les fichiers manquants\n";
echo "en suivant le même pattern pour edit.php, view.php, delete.php et index.php.\n";
