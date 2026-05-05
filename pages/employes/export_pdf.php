<?php
// pages/employes/export_pdf.php


require_once __DIR__ . '/../../includes/header.php';

// Récupérer les paramètres
$title = isset($_GET['title']) ? $_GET['title'] : 'Liste des employés';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$service_id = isset($_GET['service_id']) ? $_GET['service_id'] : '';
$include_filters = isset($_GET['include_filters']) ? true : false;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die('Erreur de connexion à la base de données: ' . $e->getMessage());
}

// Construire la requête
$sql = "SELECT e.*, s.nom as service_nom
        FROM employes e
        INNER JOIN services s ON e.service_id = s.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.matricule LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search OR e.mail LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($service_id)) {
    $sql .= " AND e.service_id = :service_id";
    $params[':service_id'] = $service_id;
}

$sql .= " ORDER BY e.nom ASC, e.prenom ASC";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $employes = $stmt->fetchAll();
} catch (PDOException $e) {
    $employes = [];
    error_log("Erreur SQL (export PDF): " . $e->getMessage());
}

// Récupérer les informations de l'établissement
try {
    $stmt = $pdo->prepare("SELECT * FROM parametres LIMIT 1");
    $stmt->execute();
    $parametres = $stmt->fetch();
} catch (Exception $e) {
    $parametres = null;
    error_log("Erreur lors de la récupération des paramètres: " . $e->getMessage());
}

// Récupérer le nom du service pour l'affichage
$service_nom = '';
if (!empty($service_id)) {
    try {
        $stmt = $pdo->prepare("SELECT nom FROM services WHERE id = :id");
        $stmt->bindValue(':id', $service_id, PDO::PARAM_INT);
        $stmt->execute();
        $service = $stmt->fetch();
        if ($service) {
            $service_nom = $service['nom'];
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération du service: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .container { width: 100%; max-width: none; }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            background-color: #fff;
        }
        
        .header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        
        .company-info h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #555;
            margin-bottom: 10px;
        }
        
        .filters-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .filters-box h5 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .table {
            font-size: 12px;
            margin-bottom: 20px;
        }
        
        .table th {
            background-color: #343a40;
            color: white;
            border: 1px solid #454d55;
            padding: 8px;
            text-align: left;
        }
        
        .table td {
            border: 1px solid #dee2e6;
            padding: 6px;
            vertical-align: middle;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: normal;
            color: white !important;
        }
        
        .bg-success {
            background-color: #28a745 !important;
        }
        
        .bg-secondary {
            background-color: #6c757d !important;
        }
        
        .bg-info {
            background-color: #17a2b8 !important;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
        }
        
        .print-controls {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        /* Pour éviter les coupures de page dans les lignes du tableau */
        .table tbody tr {
            page-break-inside: avoid;
        }
        
        /* Pour l'impression */
        @media print {
            body {
                margin: 0;
                padding: 20px;
                background-color: white;
            }
            
            .container-fluid {
                padding: 0;
            }
            
            .table th, .table td {
                font-size: 11px;
                padding: 4px;
            }
            
            .badge {
                font-size: 10px;
                padding: 2px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Contrôles d'impression (masqués à l'impression) -->
        <div class="print-controls no-print">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button onclick="window.print()" class="btn btn-success btn-sm">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                    <button onclick="window.close()" class="btn btn-secondary btn-sm ms-2">
                        <i class="bi bi-x-circle"></i> Fermer
                    </button>
                </div>
                <div class="text-muted">
                    <i class="bi bi-info-circle"></i> Utilisez l'impression de votre navigateur pour générer un PDF
                </div>
            </div>
        </div>
        
        <!-- En-tête du rapport -->
        <div class="header">
            <?php if ($parametres): ?>
            <div class="company-info text-center mb-3">
                <h1><?php echo htmlspecialchars($parametres['nom_etablissement']); ?></h1>
                <div class="text-muted">
                    <?php echo htmlspecialchars($parametres['adresse'] ?? ''); ?>
                    <?php if (isset($parametres['tel_fixe']) && !empty($parametres['tel_fixe'])): ?>
                    • Tél: <?php echo htmlspecialchars($parametres['tel_fixe']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="report-title text-center">
                <?php echo htmlspecialchars($title); ?>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="text-muted">
                        <strong>Date de génération:</strong> <?php echo date('d/m/Y à H:i:s'); ?>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <div class="text-muted">
                        <strong>Nombre d'employés:</strong> <?php echo count($employes); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtres appliqués -->
        <?php if ($include_filters && (!empty($search) || !empty($service_id))): ?>
        <div class="filters-box">
            <h5><i class="bi bi-filter"></i> Filtres appliqués</h5>
            <div class="row">
                <div class="col-md-4">
                    <strong>Recherche:</strong> <?php echo !empty($search) ? htmlspecialchars($search) : 'Aucune'; ?>
                </div>
                <div class="col-md-4">
                    <strong>Service:</strong> 
                    <?php if (!empty($service_id)): ?>
                        <?php echo !empty($service_nom) ? htmlspecialchars($service_nom) : 'ID: ' . htmlspecialchars($service_id); ?>
                    <?php else: ?>
                        Tous
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <strong>Statut:</strong> Tous
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Tableau des employés -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="100">Matricule</th>
                        <th width="150">Nom</th>
                        <th width="150">Prénom</th>
                        <th width="150">Service</th>
                        <th width="200">Email</th>
                        <th width="120">Téléphone</th>
                        <th width="100">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($employes) > 0): ?>
                        <?php foreach ($employes as $employe): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($employe['matricule']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($employe['nom']); ?></strong></td>
                            <td><?php echo htmlspecialchars($employe['prenom']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($employe['service_nom']); ?>
                                </span>
                            </td>
                            <td><?php echo !empty($employe['mail']) ? htmlspecialchars($employe['mail']) : '-'; ?></td>
                            <td><?php echo !empty($employe['tel1']) ? htmlspecialchars($employe['tel1']) : '-'; ?></td>
                            <td class="text-center">
                                <?php if ($employe['actif']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <i class="bi bi-inbox"></i> Aucun employé trouvé
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pied de page -->
        <div class="footer">
            <div>Document généré par le système de gestion de stock</div>
            <div>Page 1 sur 1</div>
        </div>
    </div>

    <script>
    // Script pour l'impression automatique optionnelle
    document.addEventListener('DOMContentLoaded', function() {
        // Imprimer automatiquement après un délai (optionnel)
        setTimeout(function() {
            // Vous pouvez décommenter la ligne suivante pour l'impression automatique
            // window.print();
        }, 1000);
        
        // Redirection vers la page précédente si fenêtre fermée
        window.onbeforeunload = function() {
            // Rien à faire ici
        };
    });
    </script>
</body>
</html>