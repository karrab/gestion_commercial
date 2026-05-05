<?php
/**
 * Classe PDF - Génération de documents PDF avec design Bootstrap 5
 */

// Charger Dompdf si installé via Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Dompdf\Dompdf;
use Dompdf\Options;

class PDF {
    private $dompdf;
    private $options;
    private $parametres;
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();

        // Récupérer les paramètres
        $this->db->prepare("SELECT * FROM parametres WHERE id = 1");
        $this->parametres = $this->db->fetch();

        // Configuration Dompdf
        $this->options = new Options();
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('isRemoteEnabled', true);
        $this->options->set('defaultFont', 'DejaVu Sans');
        $this->options->set('chroot', ROOT_PATH);

        $this->dompdf = new Dompdf($this->options);
    }

    /**
     * Générer un PDF
     */
    public function generate($html, $filename = 'document.pdf', $orientation = 'portrait', $paperSize = 'A4') {
        $fullHtml = $this->getTemplate($html);

        $this->dompdf->loadHtml($fullHtml);
        $this->dompdf->setPaper($paperSize, $orientation);
        $this->dompdf->render();

        // Stream le PDF (afficher dans le navigateur)
        $this->dompdf->stream($filename, ['Attachment' => false]);
    }

    /**
     * Sauvegarder un PDF dans un fichier
     */
    public function save($html, $filepath, $orientation = 'portrait', $paperSize = 'A4') {
        $fullHtml = $this->getTemplate($html);

        $this->dompdf->loadHtml($fullHtml);
        $this->dompdf->setPaper($paperSize, $orientation);
        $this->dompdf->render();

        file_put_contents($filepath, $this->dompdf->output());
    }

    /**
     * Template HTML complet avec styles Bootstrap 5
     */
    private function getTemplate($content) {
        $logoPath = IMAGES_PATH . '/' . ($this->parametres['logo'] ?? 'logo.png');
        $logoData = '';

        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoData = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    margin: 15mm 10mm;
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 10pt;
                    color: #212529;
                    line-height: 1.5;
                }

                /* En-tête avec design Bootstrap 5 */
                .header-container {
                    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
                    padding: 20px;
                    margin-bottom: 25px;
                    border-radius: 8px;
                    color: white;
                    position: relative;
                }

                .header-grid {
                    display: table;
                    width: 100%;
                }

                .header-logo {
                    display: table-cell;
                    width: 25%;
                    vertical-align: middle;
                    text-align: center;
                }

                .header-logo img {
                    max-height: 70px;
                    max-width: 100%;
                    background: white;
                    padding: 8px;
                    border-radius: 6px;
                }

                .header-info {
                    display: table-cell;
                    width: 75%;
                    vertical-align: middle;
                    padding-left: 20px;
                }

                .header-info h1 {
                    font-size: 20pt;
                    font-weight: bold;
                    margin: 0 0 8px 0;
                    color: white;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }

                .header-info .details {
                    font-size: 9pt;
                    line-height: 1.4;
                    opacity: 0.95;
                }

                .header-info .details div {
                    margin: 2px 0;
                }

                /* Badge coin supérieur */
                .header-badge {
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    background: rgba(255, 255, 255, 0.2);
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 8pt;
                    font-weight: bold;
                    backdrop-filter: blur(10px);
                }

                /* Titres */
                h2 {
                    color: #0d6efd;
                    font-size: 16pt;
                    font-weight: bold;
                    margin: 25px 0 15px 0;
                    padding: 10px 15px;
                    background: #e7f1ff;
                    border-left: 5px solid #0d6efd;
                    border-radius: 4px;
                }

                h3 {
                    color: #495057;
                    font-size: 12pt;
                    font-weight: bold;
                    margin: 20px 0 12px 0;
                    padding-bottom: 5px;
                    border-bottom: 2px solid #dee2e6;
                }

                /* Boîtes d\'information avec style card Bootstrap */
                .info-box {
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 6px;
                    padding: 15px;
                    margin: 15px 0;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                }

                .info-box .row {
                    display: table;
                    width: 100%;
                    margin: 8px 0;
                }

                .info-box .col {
                    display: table-cell;
                    width: 50%;
                    padding: 5px;
                }

                .info-box .label {
                    font-weight: 600;
                    color: #0d6efd;
                    display: inline-block;
                    min-width: 140px;
                }

                .info-box .value {
                    color: #212529;
                    font-weight: normal;
                }

                /* Tables avec style Bootstrap striped */
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                    border-radius: 6px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }

                table thead {
                    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
                }

                table thead th {
                    color: white;
                    padding: 12px 10px;
                    text-align: left;
                    font-weight: bold;
                    font-size: 10pt;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                table tbody td {
                    padding: 10px;
                    border: 1px solid #dee2e6;
                    font-size: 9.5pt;
                }

                table tbody tr:nth-child(odd) {
                    background-color: #ffffff;
                }

                table tbody tr:nth-child(even) {
                    background-color: #f8f9fa;
                }

                table tbody tr:hover {
                    background-color: #e7f1ff;
                }

                /* Ligne totale */
                .total-row {
                    background: linear-gradient(to right, #e7f1ff, #cfe2ff) !important;
                    font-weight: bold;
                    font-size: 10.5pt;
                    border-top: 3px solid #0d6efd !important;
                }

                .total-row td {
                    padding: 12px 10px !important;
                    color: #0d6efd;
                }

                /* Badges Bootstrap 5 */
                .badge {
                    display: inline-block;
                    padding: 4px 10px;
                    font-size: 9pt;
                    font-weight: 600;
                    border-radius: 4px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .badge-primary { background-color: #0d6efd; color: white; }
                .badge-success { background-color: #198754; color: white; }
                .badge-warning { background-color: #ffc107; color: #000; }
                .badge-danger { background-color: #dc3545; color: white; }
                .badge-info { background-color: #0dcaf0; color: #000; }
                .badge-secondary { background-color: #6c757d; color: white; }

                /* Signatures */
                .signatures {
                    margin-top: 60px;
                    page-break-inside: avoid;
                }

                .signatures table {
                    border: none;
                    box-shadow: none;
                }

                .signatures td {
                    border: none !important;
                    text-align: center;
                    vertical-align: bottom;
                    padding: 20px;
                }

                .signature-box {
                    display: inline-block;
                    min-width: 200px;
                    padding-top: 60px;
                    border-top: 2px solid #212529;
                }

                .signature-label {
                    font-weight: 600;
                    color: #495057;
                    font-size: 10pt;
                    margin-top: 8px;
                }

                /* Footer */
                .footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    height: 60px;
                    background: #f8f9fa;
                    border-top: 3px solid #0d6efd;
                    padding: 15px 20px;
                    font-size: 8pt;
                    color: #6c757d;
                }

                .footer-grid {
                    display: table;
                    width: 100%;
                }

                .footer-left {
                    display: table-cell;
                    width: 70%;
                    vertical-align: middle;
                }

                .footer-right {
                    display: table-cell;
                    width: 30%;
                    vertical-align: middle;
                    text-align: right;
                }

                .page-number:before {
                    content: "Page " counter(page);
                }

                /* Utilitaires */
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .text-left { text-align: left; }
                .fw-bold { font-weight: bold; }
                .text-muted { color: #6c757d; }
                .text-primary { color: #0d6efd; }
                .text-success { color: #198754; }
                .text-danger { color: #dc3545; }

                /* Alert boxes */
                .alert {
                    padding: 12px 15px;
                    margin: 15px 0;
                    border-radius: 6px;
                    border-left: 4px solid;
                }

                .alert-info {
                    background-color: #cff4fc;
                    border-left-color: #0dcaf0;
                    color: #055160;
                }

                .alert-warning {
                    background-color: #fff3cd;
                    border-left-color: #ffc107;
                    color: #664d03;
                }

                /* Section notes */
                .notes-section {
                    background: #fffbea;
                    border: 1px solid #ffc107;
                    border-radius: 6px;
                    padding: 15px;
                    margin: 20px 0;
                }

                .notes-section h4 {
                    color: #664d03;
                    font-size: 11pt;
                    margin: 0 0 10px 0;
                }
            </style>
        </head>
        <body>
            <!-- En-tête avec logo et informations -->
            <div class="header-container">
                <div class="header-badge">Généré le ' . date('d/m/Y') . '</div>
                <div class="header-grid">
                    ' . (!empty($logoData) ? '
                    <div class="header-logo">
                        <img src="' . $logoData . '" alt="Logo">
                    </div>' : '') . '
                    <div class="header-info">
                        <h1>' . htmlspecialchars($this->parametres['nom_etablissement'] ?? APP_NAME) . '</h1>
                        <div class="details">
                            ' . (!empty($this->parametres['adresse']) ? '<div>📍 ' . htmlspecialchars($this->parametres['adresse']) . '</div>' : '') . '
                            <div>
                                ' . (!empty($this->parametres['tel_fixe']) ? '📞 ' . htmlspecialchars($this->parametres['tel_fixe']) : '') . '
                                ' . (!empty($this->parametres['tel_port']) ? ' / 📱 ' . htmlspecialchars($this->parametres['tel_port']) : '') . '
                            </div>
                            ' . (!empty($this->parametres['email']) ? '<div>✉️ ' . htmlspecialchars($this->parametres['email']) . '</div>' : '') . '
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu du document -->
            <div class="content">
                ' . $content . '
            </div>

            <!-- Footer fixe -->
            <div class="footer">
                <div class="footer-grid">
                    <div class="footer-left">
                        <strong>' . htmlspecialchars($this->parametres['nom_etablissement'] ?? APP_NAME) . '</strong><br>
                        Document généré le ' . date('d/m/Y à H:i') . ' - © ' . date('Y') . '
                    </div>
                    <div class="footer-right">
                        <div class="page-number"></div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ';
    }

    /**
     * Générer un bon d'entrée avec design Bootstrap 5
     */
    public function generateBonEntree($entree_id) {
        // Récupérer les données
        $sql = "SELECT e.*, f.nom_complet as fournisseur, f.adresse as fournisseur_adresse,
                       f.ville, f.tel1, 
                       u.nom as user_nom, u.prenom as user_prenom
                FROM entrees e
                INNER JOIN fournisseurs f ON e.fournisseur_id = f.id
                INNER JOIN users u ON e.user_id = u.id
                WHERE e.id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $entree_id);
        $entree = $this->db->fetch();

        if (!$entree) {
            throw new Exception("Entrée introuvable");
        }

        // Récupérer les lignes
        $this->db->prepare("SELECT * FROM ligne_entrees WHERE entree_id = :id ORDER BY id");
        $this->db->bind(':id', $entree_id);
        $lignes = $this->db->fetchAll();

        // Construire le HTML
        $html = '
            <h2>📦 BON D\'ENTRÉE N° ' . str_pad($entree['id'], 5, '0', STR_PAD_LEFT) . '</h2>

            <div class="info-box">
                <div class="row">
                    <div class="col">
                        <div><span class="label">📅 Date d\'entrée:</span> <span class="value">' . date('d/m/Y', strtotime($entree['date'])) . '</span></div>
                        <div><span class="label">👤 Créé par:</span> <span class="value">' . htmlspecialchars($entree['user_prenom'] . ' ' . $entree['user_nom']) . '</span></div>
                    </div>
                    <div class="col">
                        <div><span class="label">🏢 Fournisseur:</span> <span class="value fw-bold">' . htmlspecialchars($entree['fournisseur']) . '</span></div>
                        <div><span class="label">📍 Adresse:</span> <span class="value">' . htmlspecialchars($entree['fournisseur_adresse'] . ', ' . $entree['ville']) . '</span></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div><span class="label">📞 Téléphone:</span> <span class="value">' . htmlspecialchars($entree['tel1'] ?? '-') . '</span></div>
                    </div>
                    <div class="col">
                        <div><span class="label">✉️ Email:</span> <span class="value">' . htmlspecialchars($entree['fournisseur_email'] ?? '-') . '</span></div>
                    </div>
                </div>
            </div>

            <h3>📋 Liste des articles</h3>
            <table>
                <thead>
                    <tr>
                        <th width="12%">Code</th>
                        <th width="48%">Désignation</th>
                        <th width="20%" class="text-right">Quantité</th>
                        <th width="20%" class="text-right">Unité</th>
                    </tr>
                </thead>
                <tbody>';

        $total_qte = 0;
        $nb_articles = 0;
        foreach ($lignes as $ligne) {
            $total_qte += $ligne['qte_entree'];
            $nb_articles++;

            $html .= '
                    <tr>
                        <td><span class="badge badge-secondary">' . htmlspecialchars($ligne['code_article']) . '</span></td>
                        <td>' . htmlspecialchars($ligne['designation']) . '</td>
                        <td class="text-right fw-bold">' . number_format($ligne['qte_entree'], 2, ',', ' ') . '</td>
                        <td class="text-right">Unité(s)</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>TOTAL (' . $nb_articles . ' article(s))</strong></td>
                        <td class="text-right"><strong>' . number_format($total_qte, 2, ',', ' ') . '</strong></td>
                        <td class="text-right"><strong>Unité(s)</strong></td>
                    </tr>
                </tbody>
            </table>';

        if (!empty($entree['notes'])) {
            $html .= '
                <div class="notes-section">
                    <h4>📝 Notes et observations</h4>
                    <div>' . nl2br(htmlspecialchars($entree['notes'])) . '</div>
                </div>';
        }

        $html .= '
            <div class="signatures">
                <table>
                    <tr>
                        <td width="50%">
                            <div class="signature-box"></div>
                            <div class="signature-label">Signature Fournisseur</div>
                            <div class="text-muted" style="font-size: 8pt;">Date et cachet</div>
                        </td>
                        <td width="50%">
                            <div class="signature-box"></div>
                            <div class="signature-label">Signature Réceptionnaire</div>
                            <div class="text-muted" style="font-size: 8pt;">Date et cachet</div>
                        </td>
                    </tr>
                </table>
            </div>';

        $this->generate($html, 'bon_entree_' . str_pad($entree_id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }

    /**
     * Générer un bon de sortie avec design Bootstrap 5
     */
    public function generateBonSortie($sortie_id) {
        // Récupérer les données
        $sql = "SELECT s.*, ser.nom as service, CONCAT(emp.nom, ' ', emp.prenom) as employe,
                       ser2.nom as service_affectation, CONCAT(emp2.nom, ' ', emp2.prenom) as employe_affectation,
                       b.code_local as bureau, a.numero as armoire,
                       u.nom as user_nom, u.prenom as user_prenom
                FROM sorties s
                INNER JOIN services ser ON s.service_id = ser.id
                INNER JOIN employes emp ON s.employe_id = emp.id
                LEFT JOIN services ser2 ON s.service_affectation_id = ser2.id
                LEFT JOIN employes emp2 ON s.employe_affectation_id = emp2.id
                LEFT JOIN bureaux b ON s.bureau_id = b.id
                LEFT JOIN armoires a ON s.armoire_id = a.id
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $sortie_id);
        $sortie = $this->db->fetch();

        if (!$sortie) {
            throw new Exception("Sortie introuvable");
        }

        // Récupérer les lignes
        $this->db->prepare("SELECT * FROM ligne_sorties WHERE sortie_id = :id ORDER BY id");
        $this->db->bind(':id', $sortie_id);
        $lignes = $this->db->fetchAll();

        // Construire le HTML
        $html = '
            <h2>📤 BON DE SORTIE N° ' . str_pad($sortie['id'], 5, '0', STR_PAD_LEFT) . '</h2>

            <div class="info-box">
                <h3 style="margin-top: 0; font-size: 11pt; color: #0d6efd; border: none; padding: 0;">👥 Demandeur</h3>
                <div class="row">
                    <div class="col">
                        <div><span class="label">📅 Date de sortie:</span> <span class="value">' . date('d/m/Y', strtotime($sortie['date'])) . '</span></div>
                        <div><span class="label">🏢 Service:</span> <span class="value fw-bold">' . htmlspecialchars($sortie['service']) . '</span></div>
                    </div>
                    <div class="col">
                        <div><span class="label">👤 Employé:</span> <span class="value fw-bold">' . htmlspecialchars($sortie['employe']) . '</span></div>
                        <div><span class="label">✍️ Créé par:</span> <span class="value">' . htmlspecialchars($sortie['user_prenom'] . ' ' . $sortie['user_nom']) . '</span></div>
                    </div>
                </div>
            </div>';

        if (!empty($sortie['service_affectation']) || !empty($sortie['employe_affectation']) || !empty($sortie['bureau']) || !empty($sortie['armoire'])) {
            $html .= '
            <div class="info-box">
                <h3 style="margin-top: 0; font-size: 11pt; color: #198754; border: none; padding: 0;">📍 Affectation</h3>
                <div class="row">
                    <div class="col">';

            if (!empty($sortie['service_affectation'])) {
                $html .= '<div><span class="label">🏢 Service:</span> <span class="value">' . htmlspecialchars($sortie['service_affectation']) . '</span></div>';
            }
            if (!empty($sortie['employe_affectation'])) {
                $html .= '<div><span class="label">👤 Employé:</span> <span class="value">' . htmlspecialchars($sortie['employe_affectation']) . '</span></div>';
            }

            $html .= '</div><div class="col">';

            if (!empty($sortie['bureau'])) {
                $html .= '<div><span class="label">🚪 Bureau:</span> <span class="value">' . htmlspecialchars($sortie['bureau']) . '</span></div>';
            }
            if (!empty($sortie['armoire'])) {
                $html .= '<div><span class="label">🗄️ Armoire:</span> <span class="value">' . htmlspecialchars($sortie['armoire']) . '</span></div>';
            }

            $html .= '</div>
                </div>
            </div>';
        }

        $html .= '
            <h3>📋 Liste des articles</h3>
            <table>
                <thead>
                    <tr>
                        <th width="12%">Code</th>
                        <th width="48%">Désignation</th>
                        <th width="20%" class="text-right">Quantité</th>
                        <th width="20%" class="text-right">Unité</th>
                    </tr>
                </thead>
                <tbody>';

        $total_qte = 0;
        $nb_articles = 0;
        foreach ($lignes as $ligne) {
            $total_qte += $ligne['qte_sortie'];
            $nb_articles++;

            $html .= '
                    <tr>
                        <td><span class="badge badge-secondary">' . htmlspecialchars($ligne['code_article']) . '</span></td>
                        <td>' . htmlspecialchars($ligne['designation']) . '</td>
                        <td class="text-right fw-bold">' . number_format($ligne['qte_sortie'], 2, ',', ' ') . '</td>
                        <td class="text-right">Unité(s)</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>TOTAL (' . $nb_articles . ' article(s))</strong></td>
                        <td class="text-right"><strong>' . number_format($total_qte, 2, ',', ' ') . '</strong></td>
                        <td class="text-right"><strong>Unité(s)</strong></td>
                    </tr>
                </tbody>
            </table>';

        if (!empty($sortie['notes'])) {
            $html .= '
                <div class="notes-section">
                    <h4>📝 Notes et observations</h4>
                    <div>' . nl2br(htmlspecialchars($sortie['notes'])) . '</div>
                </div>';
        }

        $html .= '
            <div class="signatures">
                <table>
                    <tr>
                        <td width="50%">
                            <div class="signature-box"></div>
                            <div class="signature-label">Signature Magasinier</div>
                            <div class="text-muted" style="font-size: 8pt;">Date et cachet</div>
                        </td>
                        <td width="50%">
                            <div class="signature-box"></div>
                            <div class="signature-label">Signature Bénéficiaire</div>
                            <div class="text-muted" style="font-size: 8pt;">Date et cachet</div>
                        </td>
                    </tr>
                </table>
            </div>';

        $this->generate($html, 'bon_sortie_' . str_pad($sortie_id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }

    /**
     * Générer bon de retour fournisseur
     */
    public function generateBonRetourFournisseur($retour_id) {
        // Récupérer les données du retour
        $sql = "SELECT rf.*, f.nom_complet as fournisseur, f.adresse as fournisseur_adresse,
                       f.ville, f.tel1,
                       u.nom as user_nom, u.prenom as user_prenom
                FROM retour_fournisseur rf
                INNER JOIN fournisseurs f ON rf.fournisseur_id = f.id
                INNER JOIN users u ON rf.user_id = u.id
                WHERE rf.id = :id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $retour_id);
        $this->db->execute();
        $retour = $this->db->fetch();

        if (!$retour) {
            throw new Exception("Retour fournisseur introuvable");
        }

        // Récupérer les lignes
        $sql = "SELECT lrf.*, a.qte_disponible as stock_actuel
                FROM ligne_retour_fournisseur lrf
                INNER JOIN articles a ON lrf.article_id = a.id
                WHERE lrf.retour_fournisseur_id = :id
                ORDER BY lrf.id";

        $this->db->prepare($sql);
        $this->db->bind(':id', $retour_id);
        $this->db->execute();
        $lignes = $this->db->fetchAll();

        // Calculer le total
        $total_qte = 0;
        foreach ($lignes as $ligne) {
            $total_qte += $ligne['qte'];
        }

        // Construire le HTML
        $html = '
        <h2>📤 BON DE RETOUR FOURNISSEUR N° ' . str_pad($retour['id'], 5, '0', STR_PAD_LEFT) . '</h2>

        <div class="info-box">
            <h3 style="margin-top: 0; font-size: 11pt; color: #dc3545; border: none; padding: 0;">🏢 Fournisseur</h3>
            <table width="100%">
                <tr>
                    <td width="50%">
                        <div><span class="label">📦 Fournisseur:</span> <span class="value">' . htmlspecialchars($retour['fournisseur']) . '</span></div>
                        ' . (!empty($retour['fournisseur_adresse']) ? '<div><span class="label">📍 Adresse:</span> <span class="value">' . htmlspecialchars($retour['fournisseur_adresse']) . '</span></div>' : '') . '
                        ' . (!empty($retour['ville']) ? '<div><span class="label">🏙️ Ville:</span> <span class="value">' . htmlspecialchars($retour['ville']) . '</span></div>' : '') . '
                    </td>
                    <td width="50%">
                        <div><span class="label">📅 Date retour:</span> <span class="value">' . date('d/m/Y', strtotime($retour['date'])) . '</span></div>
                        ' . (!empty($retour['tel1']) ? '<div><span class="label">📞 Téléphone:</span> <span class="value">' . htmlspecialchars($retour['tel1']) . '</span></div>' : '') . '
                        ' . (!empty($retour['fournisseur_email']) ? '<div><span class="label">✉️ Email:</span> <span class="value">' . htmlspecialchars($retour['fournisseur_email']) . '</span></div>' : '') . '
                        <div><span class="label">👤 Créé par:</span> <span class="value">' . htmlspecialchars($retour['user_nom'] . ' ' . $retour['user_prenom']) . '</span></div>
                    </td>
                </tr>
            </table>
        </div>';

        // Notes si présentes
        if (!empty($retour['notes'])) {
            $html .= '
        <div class="alert alert-warning">
            <strong>📝 Motif du retour:</strong><br>
            ' . nl2br(htmlspecialchars($retour['notes'])) . '
        </div>';
        }

        // Tableau des articles
        $html .= '
        <h3 style="color: #dc3545; margin-top: 15px; margin-bottom: 10px; font-size: 11pt; border-bottom: 2px solid #dc3545; padding-bottom: 5px;">📦 Articles retournés</h3>

        <table class="table-articles">
            <thead>
                <tr>
                    <th width="5%" class="text-center">#</th>
                    <th width="15%">Code</th>
                    <th width="45%">Désignation</th>
                    <th width="20%" class="text-right">Quantité</th>
                    <th width="15%" class="text-right">Stock actuel</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($lignes as $index => $ligne) {
            $html .= '
                <tr>
                    <td class="text-center">' . ($index + 1) . '</td>
                    <td><span class="badge">' . htmlspecialchars($ligne['code_article']) . '</span></td>
                    <td>' . htmlspecialchars($ligne['designation']) . '</td>
                    <td class="text-right"><strong>' . number_format($ligne['qte'], 2, ',', ' ') . '</strong></td>
                    <td class="text-right">' . number_format($ligne['stock_actuel'], 2, ',', ' ') . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>' . number_format($total_qte, 2, ',', ' ') . '</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="alert alert-danger" style="margin-top: 20px;">
            <strong>⚠️ IMPORTANT:</strong> Ce retour diminue le stock disponible. Les articles retournés sont défectueux ou non conformes.
        </div>

        <div class="signatures">
            <table>
                <tr>
                    <td width="50%">
                        <div class="signature-box"></div>
                        <div class="signature-label">Signature Magasinier</div>
                        <div class="text-muted" style="font-size: 8pt;">Date et cachet</div>
                    </td>
                    <td width="50%">
                        <div class="signature-box"></div>
                        <div class="signature-label">Signature Fournisseur</div>
                        <div class="text-muted" style="font-size: 8pt;">Date et cachet</div>
                    </td>
                </tr>
            </table>
        </div>';

        $this->generate($html, 'bon_retour_fournisseur_' . str_pad($retour_id, 5, '0', STR_PAD_LEFT) . '.pdf');
    }
}
