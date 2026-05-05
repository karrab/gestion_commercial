    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo $parametres['nom_etablissement'] ?? APP_NAME; ?> - v<?php echo APP_VERSION; ?>
                </div>
                <div class="col-md-6 text-end text-muted">
                    Développé avec <i class="bi bi-heart-fill text-danger"></i> pour la gestion de stock
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="<?php echo BASE_URL; ?>/assets/js/jquery.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/select2.min.js"></script>

    <!-- DataTables JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/jquery.dataTables.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dataTables.colReorder.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dataTables.buttons.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/buttons.bootstrap5.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/buttons.html5.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/buttons.print.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/dataTables.fixedHeader.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/jszip.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/pdfmake.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/vfs_fonts.js"></script>






    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js_file): ?>
            <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Configuration globale Select2
        $(document).ready(function() {
            // Initialiser Select2 pour tous les select avec classe .select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                language: 'fr',
                placeholder: 'Sélectionner...',
                allowClear: true
            });

            // Auto-hide alerts après 5 secondes
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Confirmation de suppression
            $('.delete-confirm').on('click', function(e) {
                if (!confirm('Voulez-vous vraiment supprimer cet élément ?')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
