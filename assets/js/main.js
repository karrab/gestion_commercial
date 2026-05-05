/**
 * Gestion Stock Matériel - JavaScript Principal
 */

// Configuration globale (BASE_URL est défini dans footer.php)
// Si pas défini, fallback sur détection automatique
if (typeof BASE_URL === 'undefined') {
    const BASE_URL = window.location.origin + '/stock_materielinfo';
}

// Formatage des nombres
function formatNumber(num, decimals = 2) {
    return Number(num).toLocaleString('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

// Formatage des dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

// Confirmation de suppression
function confirmDelete(message = 'Voulez-vous vraiment supprimer cet élément ?') {
    return confirm(message);
}

// Affichage d'une alerte
function showAlert(message, type = 'info') {
    const alertDiv = $('<div>')
        .addClass(`alert alert-${type} alert-dismissible fade show`)
        .attr('role', 'alert')
        .html(`
            <i class="bi bi-${getAlertIcon(type)}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `);

    $('.main-container').prepend(alertDiv);

    setTimeout(() => {
        alertDiv.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
}

// Icône d'alerte selon le type
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Loading overlay
function showLoading() {
    if ($('.spinner-overlay').length === 0) {
        $('body').append(`
            <div class="spinner-overlay">
                <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `);
    }
}

function hideLoading() {
    $('.spinner-overlay').remove();
}

// Initialisation DataTables avec configuration par défaut
function initDataTable(selector, options = {}) {
    const defaultOptions = {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
        responsive: true,

        // State saving - sauvegarde tri, pagination, recherche, ordre colonnes
        stateSave: true,
        stateDuration: 60 * 60 * 24 * 7, // 7 jours

        // ColReorder - réorganiser colonnes par glisser-déposer
        colReorder: true,

        // FixedHeader - en-tête fixe lors du scroll
        fixedHeader: true,

        // Buttons - export Excel, PDF, Print, Copy
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"Bf>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',

        buttons: [
            {
                extend: 'copy',
                text: '<i class="bi bi-clipboard"></i> Copier',
                className: 'btn btn-sm btn-secondary',
                exportOptions: { columns: ':not(.no-export)' }
            },
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                className: 'btn btn-sm btn-success',
                exportOptions: { columns: ':not(.no-export)' }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger',
                exportOptions: { columns: ':not(.no-export)' },
                orientation: 'landscape'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Imprimer',
                className: 'btn btn-sm btn-info',
                exportOptions: { columns: ':not(.no-export)' }
            }
        ],

        order: [[0, 'desc']], // Tri décroissant par défaut sur la première colonne

        columnDefs: [
            {
                targets: 'no-sort',
                orderable: false
            },
            {
                targets: 'no-export',
                visible: true
            }
        ],

        // initComplete - ajouter recherche par colonne dans footer
        initComplete: function() {
            const api = this.api();
            const table = $(selector);

            // Ajouter une ligne footer si elle n'existe pas
            if (table.find('tfoot').length === 0) {
                const footer = $('<tfoot></tfoot>');
                const footerRow = $('<tr></tr>');

                api.columns().every(function() {
                    const column = this;
                    const header = $(column.header());

                    // Vérifier si la colonne est triable (pas de classe no-sort)
                    if (!header.hasClass('no-sort') && !header.hasClass('no-search')) {
                        const th = $('<th></th>');
                        const input = $('<input type="text" class="form-control form-control-sm" placeholder="🔍 ' + header.text() + '" />')
                            .on('keyup change clear', function() {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                        th.append(input);
                        footerRow.append(th);
                    } else {
                        // Colonne non triable - cellule vide
                        footerRow.append($('<th></th>'));
                    }
                });

                footer.append(footerRow);
                table.append(footer);
            }
        }
    };

    return $(selector).DataTable($.extend({}, defaultOptions, options));
}

// Initialisation Select2 pour sélection article avec recherche
function initArticleSelect(selector, placeholder = 'Sélectionner un article...') {
    $(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/articles.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function(data) {
                // data est déjà un array [{id, text, ...}, ...]
                if (!Array.isArray(data)) {
                    console.error('API articles.php returned invalid data:', data);
                    return { results: [] };
                }
                return { results: data };
            },
            error: function(xhr, status, error) {
                console.error('Error loading articles:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        minimumInputLength: 0,
        templateResult: formatArticleResult,
        templateSelection: formatArticleSelection
    });
}

// Format résultat recherche article
function formatArticleResult(item) {
    if (item.loading || !item.text) {
        return item.text || item.id;
    }

    const stock = (item.qte_disponible !== undefined && item.qte_disponible !== null)
        ? parseFloat(item.qte_disponible)
        : null;

    const $el = $('<span>');
    $el.append(document.createTextNode(item.text));
    if (stock !== null) {
        $el.append(' ');
        $el.append($('<span style="color:green;font-weight:600;">').text('(' + stock.toLocaleString('fr-FR', {maximumFractionDigits: 2}) + ')'));
    }
    return $el;
}

// Format sélection article
function formatArticleSelection(item) {
    return item.text;
}

// Initialisation Select2 pour sélection employé avec recherche
function initEmployeSelect(selector, serviceId = null, placeholder = 'Sélectionner un employé...') {
    $(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/employes.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || '',
                    service_id: serviceId
                };
            },
            processResults: function(data) {
                // data est déjà un array [{id, text, ...}, ...]
                if (!Array.isArray(data)) {
                    console.error('API employes.php returned invalid data:', data);
                    return { results: [] };
                }
                return { results: data };
            },
            error: function(xhr, status, error) {
                console.error('Error loading employes:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        minimumInputLength: 0
    });
}

// Initialisation Select2 pour sélection fournisseur avec recherche
function initFournisseurSelect(selector, placeholder = 'Sélectionner un fournisseur...') {
    $(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/fournisseurs.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function(data) {
                // data est déjà un array [{id, text, ...}, ...]
                if (!Array.isArray(data)) {
                    console.error('API fournisseurs.php returned invalid data:', data);
                    return { results: [] };
                }
                return { results: data };
            },
            error: function(xhr, status, error) {
                console.error('Error loading fournisseurs:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        minimumInputLength: 0
    });
}

// Chargement de la dernière entrée d'un fournisseur
// Chargement de la dernière entrée d'un fournisseur - VERSION CORRIGÉE
function loadLastEntreeFournisseur(fournisseurId, callback) {
    if (!fournisseurId) {
        if (typeof callback === 'function') {
            callback('ID fournisseur manquant', null);
        }
        return;
    }

    $.ajax({
        url: BASE_URL + '/api/last_entree_articles.php',
        method: 'GET',
        data: { 
            fournisseur_id: fournisseurId,
            _t: new Date().getTime() // Éviter le cache
        },
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (typeof callback === 'function') {
                callback(null, response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur lors du chargement de la dernière entrée:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            if (typeof callback === 'function') {
                callback(error, null);
            }
        }
    });
}

// Initialisation Select2 pour sélection service
function initServiceSelect(selector, placeholder = 'Sélectionner un service...') {
    $(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/services.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function(data) {
                // data est déjà un array [{id, text}, ...]
                if (!Array.isArray(data)) {
                    console.error('API services.php returned invalid data:', data);
                    return { results: [] };
                }
                return { results: data };
            },
            error: function(xhr, status, error) {
                console.error('Error loading services:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        minimumInputLength: 0
    });
}

function initBureauSelect(selector, placeholder = 'Sélectionner un bureau...') {
    $(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/bureaux.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function(data) {
                if (!Array.isArray(data)) {
                    console.error('API bureaux.php returned invalid data:', data);
                    return { results: [] };
                }
                return { results: data };
            },
            error: function(xhr, status, error) {
                console.error('Error loading bureaux:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        minimumInputLength: 0
    });
}

function initArmoireSelect(selector, placeholder = 'Sélectionner une armoire...') {
    $(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        ajax: {
            url: BASE_URL + '/api/armoires.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function(data) {
                if (!Array.isArray(data)) {
                    console.error('API armoires.php returned invalid data:', data);
                    return { results: [] };
                }
                return { results: data };
            },
            error: function(xhr, status, error) {
                console.error('Error loading armoires:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        minimumInputLength: 0
    });
}

// Gestion du drag & drop pour upload de fichiers
function initFileUploadZone(zoneSelector, inputSelector) {
    const zone = $(zoneSelector);
    const input = $(inputSelector);

    zone.on('click', function() {
        input.click();
    });

    zone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    zone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    zone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');

        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            input[0].files = files;
            updateFileInfo(files[0]);
        }
    });

    input.on('change', function() {
        if (this.files.length > 0) {
            updateFileInfo(this.files[0]);
        }
    });

    function updateFileInfo(file) {
        zone.find('.file-name').text(file.name);
        zone.find('.file-size').text(formatFileSize(file.size));
    }
}

// Formatage de la taille de fichier
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Validation de formulaire
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }

    return true;
}

// Export PDF
function exportToPDF(url) {
    showLoading();
    window.location.href = url;
    setTimeout(hideLoading, 2000);
}

// Print
function printPage() {
    window.print();
}

// Copier dans le presse-papier
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copié dans le presse-papier !', 'success');
    }).catch(() => {
        showAlert('Erreur lors de la copie', 'danger');
    });
}

// Initialisation au chargement du document
$(document).ready(function() {
    // Activation des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Activation des popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-focus sur le premier champ de formulaire
    $('form:not(.no-autofocus) input:not([type=hidden]):first').focus();
});