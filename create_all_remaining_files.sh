#!/bin/bash
# Script pour créer automatiquement tous les fichiers CRUD manquants
# Usage: bash create_all_remaining_files.sh

BASE_DIR="/home/user/stock_materielinfo/pages"
TOTAL_FILES=0

echo "================================================"
echo "GÉNÉRATION AUTOMATIQUE DES FICHIERS CRUD"
echo "================================================"
echo ""

# Fonction pour créer les dossiers nécessaires
create_dirs() {
    mkdir -p "$BASE_DIR/bureaux" "$BASE_DIR/users" "$BASE_DIR/parametres" \
             "$BASE_DIR/profil" "$BASE_DIR/rapports" "$BASE_DIR/recalcul" \
             "$BASE_DIR/retours" "$BASE_DIR/inventaires"
    echo "✓ Dossiers créés"
}

# Fonction pour créer un CRUD complet basé sur Services
create_simple_crud() {
    local SOURCE_MODULE=$1
    local TARGET_MODULE=$2
    local TITLE_SINGULAR=$3
    local TITLE_PLURAL=$4
    local ICON=$5
    local TABLE=$6

    echo "Création du module $TARGET_MODULE..."

    # Copier tous les fichiers du module source
    for file in index.php create.php edit.php view.php delete.php; do
        if [ -f "$BASE_DIR/$SOURCE_MODULE/$file" ]; then
            # Copier et adapter le fichier
            sed -e "s/$SOURCE_MODULE/$TARGET_MODULE/g" \
                -e "s/${SOURCE_MODULE%s}/${TARGET_MODULE%s}/g" \
                -e "s/Service/$TITLE_SINGULAR/g" \
                -e "s/service/${TARGET_MODULE%s}/g" \
                -e "s/bi-building/bi-$ICON/g" \
                "$BASE_DIR/$SOURCE_MODULE/$file" > "$BASE_DIR/$TARGET_MODULE/$file"

            echo "  ✓ Créé: $TARGET_MODULE/$file"
            ((TOTAL_FILES++))
        fi
    done
}

# Créer les dossiers
create_dirs

# 1. Créer les CRUDs simples basés sur Services
create_simple_crud "services" "armoires" "Armoire" "Armoires" "archive" "armoires"
create_simple_crud "services" "equipes" "Équipe" "Équipes" "person-workspace" "equipes_inventaire"

# 2. Créer les fichiers manquants pour Fournisseurs
echo ""
echo "Création des fichiers manquants pour Fournisseurs..."
for file in create.php edit.php view.php delete.php; do
    if [ ! -f "$BASE_DIR/fournisseurs/$file" ]; then
        sed -e "s/services/fournisseurs/g" \
            -e "s/service/fournisseur/g" \
            -e "s/Service/Fournisseur/g" \
            -e "s/bi-building/bi-truck/g" \
            "$BASE_DIR/services/$file" > "$BASE_DIR/fournisseurs/$file"

        echo "  ✓ Créé: fournisseurs/$file"
        ((TOTAL_FILES++))
    fi
done

# 3. Créer les fichiers manquants pour Employés
echo ""
echo "Création des fichiers manquants pour Employés..."
for file in edit.php view.php delete.php; do
    if [ ! -f "$BASE_DIR/employes/$file" ]; then
        sed -e "s/services/employes/g" \
            -e "s/service/employe/g" \
            -e "s/Service/Employé/g" \
            -e "s/bi-building/bi-people/g" \
            "$BASE_DIR/services/$file" > "$BASE_DIR/employes/$file"

        echo "  ✓ Créé: employes/$file"
        ((TOTAL_FILES++))
    fi
done

# 4. Créer les fichiers manquants pour Articles
echo ""
echo "Création des fichiers manquants pour Articles..."
for file in edit.php view.php delete.php; do
    if [ ! -f "$BASE_DIR/articles/$file" ]; then
        sed -e "s/services/articles/g" \
            -e "s/service/article/g" \
            -e "s/Service/Article/g" \
            -e "s/bi-building/bi-box-seam/g" \
            "$BASE_DIR/services/$file" > "$BASE_DIR/articles/$file"

        echo "  ✓ Créé: articles/$file"
        ((TOTAL_FILES++))
    fi
done

# 5. Créer les fichiers manquants pour Entrées
echo ""
echo "Création des fichiers manquants pour Entrées..."
for file in edit.php view.php delete.php; do
    if [ ! -f "$BASE_DIR/entrees/$file" ]; then
        sed -e "s/services/entrees/g" \
            -e "s/service/entree/g" \
            -e "s/Service/Entrée/g" \
            -e "s/bi-building/bi-box-arrow-in-down/g" \
            "$BASE_DIR/services/$file" > "$BASE_DIR/entrees/$file"

        echo "  ✓ Créé: entrees/$file"
        ((TOTAL_FILES++))
    fi
done

# 6. Créer les fichiers manquants pour Sorties
echo ""
echo "Création des fichiers manquants pour Sorties..."
for file in index.php edit.php view.php delete.php pdf.php; do
    if [ ! -f "$BASE_DIR/sorties/$file" ]; then
        if [ "$file" = "pdf.php" ]; then
            # Copier depuis entrees/pdf.php
            sed -e "s/entrees/sorties/g" \
                -e "s/entree/sortie/g" \
                -e "s/Entrée/Sortie/g" \
                "$BASE_DIR/entrees/pdf.php" > "$BASE_DIR/sorties/pdf.php"
        else
            sed -e "s/services/sorties/g" \
                -e "s/service/sortie/g" \
                -e "s/Service/Sortie/g" \
                -e "s/bi-building/bi-box-arrow-up/g" \
                "$BASE_DIR/services/$file" > "$BASE_DIR/sorties/$file"
        fi

        echo "  ✓ Créé: sorties/$file"
        ((TOTAL_FILES++))
    fi
done

# 7. Créer le module Retours (copie de Entrées)
echo ""
echo "Création du module Retours..."
if [ ! -d "$BASE_DIR/retours" ]; then
    mkdir -p "$BASE_DIR/retours"
fi

for file in index.php create.php edit.php view.php delete.php pdf.php; do
    if [ ! -f "$BASE_DIR/retours/$file" ]; then
        sed -e "s/entrees/retours/g" \
            -e "s/entree/retour/g" \
            -e "s/Entrée/Retour/g" \
            -e "s/bi-box-arrow-in-down/bi-box-arrow-in-up/g" \
            "$BASE_DIR/entrees/$file" > "$BASE_DIR/retours/$file" 2>/dev/null || echo "  ⚠ Fichier source non trouvé: entrees/$file"

        if [ -f "$BASE_DIR/retours/$file" ]; then
            echo "  ✓ Créé: retours/$file"
            ((TOTAL_FILES++))
        fi
    fi
done

# 8. Créer le fichier de recherche globale
echo ""
echo "Création du fichier de recherche globale..."
cat > "$BASE_DIR/search.php" << 'EOF'
<?php
$page_title = 'Recherche globale';
require_once __DIR__ . '/../includes/header.php';

$auth->requireLogin();
$db = Database::getInstance();
$q = $_GET['q'] ?? '';

$results = ['articles' => [], 'employes' => [], 'services' => [], 'fournisseurs' => []];

if (!empty($q) && strlen($q) >= 2) {
    $search = '%' . $q . '%';

    // Recherche articles
    $db->prepare("SELECT id, code_article, designation FROM articles WHERE (code_article LIKE :search OR designation LIKE :search) AND actif = 1 LIMIT 10");
    $db->bind(':search', $search);
    $results['articles'] = $db->fetchAll();

    // Recherche employés
    $db->prepare("SELECT id, matricule, nom, prenom FROM employes WHERE (matricule LIKE :search OR nom LIKE :search OR prenom LIKE :search) AND actif = 1 LIMIT 10");
    $db->bind(':search', $search);
    $results['employes'] = $db->fetchAll();

    // Recherche services
    $db->prepare("SELECT id, nom FROM services WHERE nom LIKE :search LIMIT 10");
    $db->bind(':search', $search);
    $results['services'] = $db->fetchAll();

    // Recherche fournisseurs
    $db->prepare("SELECT id, nom_complet FROM fournisseurs WHERE nom_complet LIKE :search AND actif = 1 LIMIT 10");
    $db->bind(':search', $search);
    $results['fournisseurs'] = $db->fetchAll();
}
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-search"></i> Recherche globale</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Recherche</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control" name="q" placeholder="Rechercher..." value="<?php echo htmlspecialchars($q); ?>" autofocus>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($q)): ?>
        <?php
        $total_results = count($results['articles']) + count($results['employes']) + count($results['services']) + count($results['fournisseurs']);
        ?>

        <div class="row mt-4">
            <div class="col-12">
                <h4><?php echo $total_results; ?> résultat(s) pour "<?php echo htmlspecialchars($q); ?>"</h4>
            </div>
        </div>

        <?php if (count($results['articles']) > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-box-seam"></i> Articles (<?php echo count($results['articles']); ?>)</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($results['articles'] as $item): ?>
                                    <li class="list-group-item">
                                        <a href="<?php echo BASE_URL; ?>/pages/articles/view.php?id=<?php echo $item['id']; ?>">
                                            <strong><?php echo htmlspecialchars($item['code_article']); ?></strong> - <?php echo htmlspecialchars($item['designation']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($results['employes']) > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-people"></i> Employés (<?php echo count($results['employes']); ?>)</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($results['employes'] as $item): ?>
                                    <li class="list-group-item">
                                        <a href="<?php echo BASE_URL; ?>/pages/employes/view.php?id=<?php echo $item['id']; ?>">
                                            <strong><?php echo htmlspecialchars($item['matricule']); ?></strong> - <?php echo htmlspecialchars($item['nom'] . ' ' . $item['prenom']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($total_results === 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Aucun résultat trouvé pour "<?php echo htmlspecialchars($q); ?>"
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
EOF

echo "  ✓ Créé: search.php"
((TOTAL_FILES++))

# Résumé
echo ""
echo "================================================"
echo "RÉSUMÉ"
echo "================================================"
echo "Fichiers créés: $TOTAL_FILES"
echo ""
echo "⚠️ IMPORTANT: Ces fichiers sont des templates de base."
echo "Vous devez les personnaliser selon vos besoins:"
echo ""
echo "1. Articles/edit.php - Ajouter la logique stock_initial en lecture seule"
echo "2. Sorties/index.php - Adapter avec les champs spécifiques"
echo "3. Retours/* - Vérifier la logique de mise à jour du stock"
echo "4. Bureaux - À créer avec Select2 pour service et employé"
echo "5. Inventaires - Module complexe à créer manuellement"
echo "6. Utilisateurs - À créer avec gestion des permissions"
echo "7. Paramètres, Profil, Rapports - À créer selon spécifications"
echo ""
echo "Consultez IMPLEMENTATION_GUIDE.md pour les détails de chaque module."
echo ""
