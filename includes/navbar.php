<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <!-- Logo et nom établissement -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/index.php">
            <?php if (!empty($parametres['logo'])): ?>
                <img src="<?php echo BASE_URL; ?>/uploads/logo/<?php echo htmlspecialchars($parametres['logo']); ?>" alt="Logo" height="40" class="me-2" style="object-fit:contain;">
            <?php endif; ?>
            <span class="fw-bold"><?php echo htmlspecialchars($parametres['nom_etablissement'] ?? APP_NAME); ?></span>
        </a>

        <!-- Bouton toggle pour mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu principal -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Tableau de bord -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-speedometer2"></i> Tableau de bord
                    </a>
                </li>

                <!-- Articles -->
                <?php if ($auth->hasPermission('articles', 'view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'articles') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/articles/index.php">
                        <i class="bi bi-box-seam"></i> Articles
                    </a>
                </li>
                <?php endif; ?>

                <!-- Mouvements -->
                <?php if ($auth->hasPermission('entrees', 'view') || $auth->hasPermission('sorties', 'view') || $auth->hasPermission('retours', 'view')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarMouvements" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-arrow-left-right"></i> Mouvements
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarMouvements">
                        <?php if ($auth->hasPermission('entrees', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/entrees/index.php"><i class="bi bi-box-arrow-in-down"></i> Entrées</a></li>
                        <?php endif; ?>
                        <?php if ($auth->hasPermission('sorties', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/sorties/index.php"><i class="bi bi-box-arrow-up"></i> Sorties</a></li>
                        <?php endif; ?>
                        <?php if ($auth->hasPermission('retours', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/retours/index.php"><i class="bi bi-box-arrow-in-up"></i>Retours Employé</a></li>
                        <?php endif; ?>
                        <?php if ($auth->hasPermission('retour_fournisseur', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/retour_fournisseur/index.php"><i class="bi bi-box-arrow-left"></i> Retours Fournisseur</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/mouvements/index.php"><i class="bi bi-clock-history"></i> Historique des mouvements</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Inventaires -->
                <?php if ($auth->hasPermission('inventaires', 'view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'inventaires') !== false ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/inventaires/index.php">
                        <i class="bi bi-clipboard-check"></i> Inventaires
                    </a>
                </li>
                <?php endif; ?>

                <!-- Rapports -->
                <?php if ($auth->hasPermission('rapports', 'view')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarRapports" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-file-earmark-text"></i> Rapports
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarRapports">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/rapports/entrees.php"><i class="bi bi-file-pdf"></i> Entrées par période</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/rapports/sorties.php"><i class="bi bi-file-pdf"></i> Sorties par période</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/rapports/traces.php"><i class="bi bi-clock-history"></i> Traçabilité</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/rapports/stock.php"><i class="bi bi-graph-up"></i> État du stock</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Référentiel -->
                <?php if ($auth->hasPermission('services', 'view') || $auth->hasPermission('employes', 'view') || $auth->hasPermission('fournisseurs', 'view') || $auth->hasPermission('equipes', 'view')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarReferentiel" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-database"></i> Référentiel
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarReferentiel">
                        <?php if ($auth->hasPermission('services', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/services/index.php"><i class="bi bi-building"></i> Services</a></li>
                        <?php endif; ?>
                        <?php if ($auth->hasPermission('employes', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/employes/index.php"><i class="bi bi-people"></i> Employés</a></li>
                        <?php endif; ?>
                        <?php if ($auth->hasPermission('fournisseurs', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/fournisseurs/index.php"><i class="bi bi-truck"></i> Fournisseurs</a></li>
                        <?php endif; ?>
                        <?php if ($auth->hasPermission('equipes', 'view')): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/equipes/index.php"><i class="bi bi-person-workspace"></i> Équipes</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Administration -->
                <?php if ($auth->isAdmin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear"></i> Administration
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarAdmin">
                        <li><h6 class="dropdown-header"><i class="bi bi-shield-lock"></i> Sécurité</h6></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/users/index.php"><i class="bi bi-person-badge"></i> Utilisateurs</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/roles/index.php"><i class="bi bi-shield-check"></i> Rôles</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/permissions/index.php"><i class="bi bi-key"></i> Permissions</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/historique/index.php"><i class="bi bi-clock-history"></i> Historique</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header"><i class="bi bi-tools"></i> Système</h6></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/parametres/edit.php"><i class="bi bi-sliders"></i> Paramètres</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/recalcul/index.php"><i class="bi bi-calculator"></i> Recalcul Stock</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/versions/index.php"><i class="bi bi-tag"></i> Versions</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- À propos -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/apropos/index.php">
                        <i class="bi bi-info-circle"></i> À propos
                    </a>
                </li>
            </ul>

            <!-- Recherche globale -->
            <form class="d-flex me-3" action="<?php echo BASE_URL; ?>/pages/search.php" method="GET">
                <div class="input-group">
                    <input class="form-control form-control-sm" type="search" placeholder="Rechercher..." aria-label="Rechercher" name="q" required>
                    <button class="btn btn-outline-light btn-sm" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <!-- Menu utilisateur -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo $current_user['prenom'] . ' ' . $current_user['nom']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUser">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/profil/edit.php"><i class="bi bi-person"></i> Mon profil</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/profil/change_password.php"><i class="bi bi-key"></i> Changer mot de passe</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Affichage des messages flash -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show m-3" role="alert">
    <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
    <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['warning'])): ?>
<div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
    <i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['info'])): ?>
<div class="alert alert-info alert-dismissible fade show m-3" role="alert">
    <i class="bi bi-info-circle"></i> <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
