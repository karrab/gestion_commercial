# Liste des fichiers à créer pour compléter l'application

## Fichiers déjà créés ✅

### Infrastructure
- ✅ `config/config.php` - Configuration principale
- ✅ `database/schema.sql` - Schéma complet de la base de données
- ✅ `.htaccess` - Sécurité Apache
- ✅ `composer.json` - Dépendances

### Classes
- ✅ `classes/Database.php` - Gestion base de données avec pagination Keyset
- ✅ `classes/Auth.php` - Authentification et permissions
- ✅ `classes/PDF.php` - Génération de PDF avec Dompdf

### Layouts
- ✅ `includes/header.php` - En-tête HTML
- ✅ `includes/navbar.php` - Menu de navigation Bootstrap
- ✅ `includes/footer.php` - Pied de page

### Assets
- ✅ `assets/css/style.css` - CSS personnalisé complet
- ✅ `assets/js/main.js` - JavaScript principal avec fonctions utilitaires

### Authentification
- ✅ `login.php` - Page de connexion
- ✅ `logout.php` - Déconnexion
- ✅ `index.php` - Tableau de bord avec statistiques

### API pour Select2
- ✅ `api/articles.php` - Recherche articles
- ✅ `api/employes.php` - Recherche employés (avec filtre par service)
- ✅ `api/fournisseurs.php` - Recherche fournisseurs
- ✅ `api/services.php` - Recherche services

### Exemples de CRUDs
- ✅ `pages/services/index.php` - Liste services avec DataTables
- ✅ `pages/entrees/create.php` - Création entrée avec lignes multiples et upload
- ✅ `pages/entrees/pdf.php` - Export PDF bon d'entrée

### Documentation
- ✅ `README.md` - Documentation générale
- ✅ `INSTALL.md` - Guide d'installation complet
- ✅ `IMPLEMENTATION_GUIDE.md` - Guide d'implémentation des CRUDs

## Fichiers à créer 📝

### 1. CRUD Services (pages/services/)
Suivre le template dans `IMPLEMENTATION_GUIDE.md`

- [ ] `create.php` - Création
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails
- [ ] `delete.php` - Suppression

### 2. CRUD Employés (pages/employes/)
Champs: matricule, nom, prenom, service_id (select), mail, tel1, tel2, notes

- [ ] `index.php` - Liste avec filtre par service
- [ ] `create.php` - Création (Select2 pour service)
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails + liste des sorties/retours de cet employé
- [ ] `delete.php` - Suppression (vérifier si utilisé dans sorties/retours/bureaux)
- [ ] `print.php` - Fiche employé PDF

**Particularités** :
- Validation matricule unique
- Select2 pour le service
- Afficher dans la vue les mouvements liés à cet employé

### 3. CRUD Fournisseurs (pages/fournisseurs/)
Champs: nom_complet, adresse, ville, pays, code_postal, tel1, tel2, notes

- [ ] `index.php` - Liste
- [ ] `create.php` - Création
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails + liste des entrées de ce fournisseur
- [ ] `delete.php` - Suppression (vérifier si utilisé dans entrées)
- [ ] `print.php` - Fiche fournisseur PDF

**Particularités** :
- Afficher dans la vue la liste des entrées du fournisseur
- Statistiques : total des entrées, dernière commande, etc.

### 4. CRUD Bureaux (pages/bureaux/)
Champs: code_local, service_id, employe_id

- [ ] `index.php` - Liste
- [ ] `create.php` - Création (Select2 pour service et employé)
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails
- [ ] `delete.php` - Suppression

**Particularités** :
- Validation code_local unique
- Filter employés par service sélectionné (AJAX)

### 5. CRUD Armoires (pages/armoires/)
Champs: numero, nom

- [ ] `index.php` - Liste
- [ ] `create.php` - Création
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails
- [ ] `delete.php` - Suppression

**Simple CRUD sans particularité**

### 6. CRUD Équipes Inventaire (pages/equipes/)
Champs: nom, description, notes

- [ ] `index.php` - Liste
- [ ] `create.php` - Création
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails + liste des inventaires de cette équipe
- [ ] `delete.php` - Suppression

### 7. CRUD Articles (pages/articles/) ⚠️ IMPORTANT
Champs: code_article, designation, actif, qte_entree, qte_sortie, qte_disponible, stock_initial, stock_min, stock_max, notes

- [ ] `index.php` - Liste avec alertes stock (badges colorés selon stock)
- [ ] `create.php` - Création (stock_initial modifiable uniquement à la création)
- [ ] `edit.php` - Modification (stock_initial en lecture seule sauf admin)
- [ ] `view.php` - Détails + historique mouvements (entrées/sorties/retours)
- [ ] `delete.php` - Suppression (vérifier si utilisé)
- [ ] `mouvements.php` - Liste détaillée entrées/sorties/retours de cet article

**Particularités CRITIQUES** :
```php
// NE JAMAIS permettre la modification directe de :
// - qte_entree (calculée par les entrées)
// - qte_sortie (calculée par les sorties)
// - qte_disponible (calculée automatiquement)

// Formule : qte_disponible = stock_initial + qte_entree - qte_sortie

// Stock initial :
// - Modifiable uniquement à la création pour tous
// - Modifiable après seulement par admin
// - Affiché en lecture seule pour les autres

// Dans view.php, afficher :
// - Graphique de l'évolution du stock
// - Tableau des derniers mouvements
// - Alertes si stock < stock_min ou stock > stock_max
```

### 8. CRUD Entrées (pages/entrees/) ⚠️ COMPLEXE
Entête: fournisseur_id, date, fichier, notes
Lignes: article_id, code_article, designation, qte_entree

- [✅] `create.php` - Création avec lignes multiples + upload
- [ ] `index.php` - Liste avec recherche par fournisseur/date
- [ ] `edit.php` - Modification (attention : recalcul du stock!)
- [ ] `view.php` - Détails + liste des articles
- [ ] `delete.php` - Suppression (IMPORTANT : remettre à jour le stock!)
- [✅] `pdf.php` - Bon d'entrée PDF

**Logique de création** (déjà fait dans create.php) :
```php
// Transaction :
// 1. Insert entête entrée
// 2. Pour chaque ligne :
//    - Insert ligne_entrees
//    - UPDATE articles SET qte_entree = qte_entree + QTE, qte_disponible = qte_disponible + QTE
```

**Logique de suppression** :
```php
// Transaction :
// 1. Pour chaque ligne :
//    - UPDATE articles SET qte_entree = qte_entree - QTE, qte_disponible = qte_disponible - QTE
// 2. DELETE ligne_entrees
// 3. DELETE entrees
// 4. Supprimer le fichier uploadé
```

### 9. CRUD Sorties (pages/sorties/) ⚠️ TRÈS COMPLEXE
Entête: service_id, employe_id, date, service_affectation_id, employe_affectation_id, armoire_id, bureau_id, fichier, notes
Lignes: article_id, code_article, designation, qte_sortie

- [ ] `index.php` - Liste avec filtres multiples
- [ ] `create.php` - Création avec vérification stock + upload
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails
- [ ] `delete.php` - Suppression (remettre à jour stock)
- [ ] `pdf.php` - Bon de sortie PDF

**Particularités CRITIQUES** :
```php
// Dans create.php :

// 1. Select2 pour service → filter employés par service (AJAX)
// 2. Select2 pour service_affectation → filter employe_affectation par service_affectation
// 3. Pour chaque article sélectionné :
//    - Vérifier stock disponible AVANT insert
//    - Si qte_disponible < qte_demandée : ERREUR "Quantité indisponible en stock"
//    - Afficher le stock disponible à côté de chaque article

// Transaction :
// 1. Insert entête sortie
// 2. Pour chaque ligne :
//    - SELECT qte_disponible FROM articles WHERE id = X
//    - IF qte_disponible < qte_sortie : ROLLBACK + erreur
//    - Insert ligne_sorties
//    - UPDATE articles SET qte_sortie = qte_sortie + QTE, qte_disponible = qte_disponible - QTE
```

**JavaScript** :
```javascript
// Lors de la sélection d'un article :
$('#article_select').on('select2:select', function(e) {
    var stock = e.params.data.qte_disponible;
    // Afficher le stock disponible
    // Valider que la quantité saisie ne dépasse pas le stock
});

// Lors de la sélection du service :
$('#service_id').on('change', function() {
    var service_id = $(this).val();
    // Recharger le select des employés filtré par service_id
    initEmployeSelect('#employe_id', service_id);
});
```

### 10. CRUD Retours (pages/retours/)
Entête: service_id, employe_id, date, fichier, notes
Lignes: article_id, code_article, designation, qte_retour

- [ ] `index.php` - Liste
- [ ] `create.php` - Création (même logique que entrées mais pour retours)
- [ ] `edit.php` - Modification
- [ ] `view.php` - Détails
- [ ] `delete.php` - Suppression
- [ ] `pdf.php` - Bon de retour PDF

**Particularités** :
```php
// Transaction :
// 1. Insert entête retour
// 2. Pour chaque ligne :
//    - Insert ligne_retours
//    - UPDATE articles SET qte_disponible = qte_disponible + QTE
```

**Important** : Les retours n'affectent PAS qte_entree ni qte_sortie, seulement qte_disponible

### 11. CRUD Inventaires (pages/inventaires/) ⚠️ LE PLUS COMPLEXE
Entête: date, equipe_id, fichier, etat (en_cours/valide/cloture), user_id, user_validation_id, date_validation
Lignes: article_id, code_article, designation, qte_theorique, qte_physique, ecart, observation

- [ ] `index.php` - Liste avec badge d'état
- [ ] `create.php` - Création
- [ ] `edit.php` - Modification (seulement si état = en_cours)
- [ ] `view.php` - Détails + actions selon état
- [ ] `delete.php` - Suppression (seulement si en_cours et admin)
- [ ] `pdf.php` - Rapport d'inventaire PDF
- [ ] `generer_ecarts.php` - Calculer les écarts
- [ ] `valider.php` - Valider l'inventaire (admin seulement)
- [ ] `reinitialiser.php` - Réinitialiser le stock (admin seulement)

**États de l'inventaire** :
1. **en_cours** : En cours de comptage
2. **valide** : Validé par admin, écarts calculés
3. **cloture** : Stock réinitialisé, inventaire terminé

**Boutons selon l'état** :

```php
// État: en_cours
// Boutons : Modifier | Supprimer | Enregistrer comptage | Générer écarts

// État: valide
// Boutons : Voir | Print | Réinitialiser stock (admin)

// État: cloture
// Boutons : Voir | Print
```

**Actions CRITIQUES** :

```php
// 1. CRÉATION (create.php)
// - Sélectionner des articles
// - qte_theorique = articles.qte_disponible (auto-rempli)
// - qte_physique = 0 (à remplir manuellement)
// - ecart = 0 (sera calculé)

// 2. GÉNÉRER ÉCARTS (generer_ecarts.php)
// - Pour chaque ligne : ecart = qte_physique - qte_theorique
// - UPDATE ligne_inventaires SET ecart = qte_physique - qte_theorique

// 3. VALIDER (valider.php) - ADMIN SEULEMENT
// - UPDATE inventaires SET etat = 'valide', user_validation_id = X, date_validation = NOW()
// - Indique que le créateur ET le rôle du user_validation

// 4. RÉINITIALISER STOCK (reinitialiser.php) - ADMIN SEULEMENT
// Transaction :
// - Pour chaque ligne d'inventaire :
//   UPDATE articles SET
//     qte_disponible = qte_physique,
//     stock_initial = qte_physique,
//     qte_entree = 0,
//     qte_sortie = 0
//   WHERE id = article_id
// - UPDATE inventaires SET etat = 'cloture'
```

**Interface view.php** :
```php
// Afficher :
// - Info entête (date, équipe, état, créé par, validé par si applicable)
// - Tableau des lignes avec : code, désignation, qte_théorique, qte_physique, écart
// - Colorier les écarts : vert si 0, orange si petit, rouge si important
// - Total des écarts
// - Boutons selon l'état et le rôle
```

### 12. CRUD Utilisateurs (pages/users/)
Champs: nom, prenom, mail, login, password, role_id

- [ ] `index.php` - Liste
- [ ] `create.php` - Création (avec sélection de rôle)
- [ ] `edit.php` - Modification (ne pas afficher le mot de passe)
- [ ] `view.php` - Détails
- [ ] `delete.php` - Suppression
- [ ] `permissions.php` - Gérer les permissions d'un rôle (admin seulement)

**Particularités** :
```php
// create.php
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// edit.php
// Ne modifier le mot de passe que s'il est fourni
if (!empty($_POST['new_password'])) {
    $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
}

// permissions.php
// Liste des modules avec checkboxes pour chaque action
// Table: role_permissions (role_id, permission_id)
```

### 13. CRUD Paramètres (pages/parametres/)
- [ ] `edit.php` - Modification des paramètres système (admin seulement)

**Champs** :
- nom_etablissement
- logo (upload d'image)
- adresse
- tel_fixe, tel_mobile, fax
- email

**Particularités** :
```php
// Upload du logo
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    // ... upload et redimensionnement avec GD
    move_uploaded_file($_FILES['logo']['tmp_name'], IMAGES_PATH . '/' . $newname);
}
```

### 14. Module Recalcul Stock (pages/recalcul/)
- [ ] `index.php` - Interface de recalcul

**Fonctionnalités** :
```php
// 1. Recalcul par article
// - Sélectionner un article
// - Afficher le stock actuel
// - Recalculer : qte_disponible = stock_initial + SUM(qte_entree) - SUM(qte_sortie)
// - Afficher la divergence
// - Bouton "Corriger" pour mettre à jour

// 2. Recalcul par période
// - Date début, date fin
// - Recalculer pour tous les articles
// - Afficher les divergences dans un tableau
// - Bouton "Tout corriger"

// SQL :
SELECT
    a.id,
    a.code_article,
    a.designation,
    a.stock_initial,
    a.qte_disponible as qte_actuelle,
    COALESCE(SUM(le.qte_entree), 0) as total_entrees,
    COALESCE(SUM(ls.qte_sortie), 0) as total_sorties,
    (a.stock_initial + COALESCE(SUM(le.qte_entree), 0) - COALESCE(SUM(ls.qte_sortie), 0)) as qte_calculee,
    (a.qte_disponible - (a.stock_initial + COALESCE(SUM(le.qte_entree), 0) - COALESCE(SUM(ls.qte_sortie), 0))) as divergence
FROM articles a
LEFT JOIN ligne_entrees le ON a.id = le.article_id
LEFT JOIN ligne_sorties ls ON a.id = ls.article_id
WHERE le.created_at BETWEEN :date_debut AND :date_fin
   OR ls.created_at BETWEEN :date_debut AND :date_fin
GROUP BY a.id
HAVING divergence != 0
```

### 15. Module Profil (pages/profil/)
- [ ] `edit.php` - Modifier son profil
- [ ] `change_password.php` - Changer son mot de passe

**change_password.php** :
```php
// Vérifier l'ancien mot de passe
if (!password_verify($_POST['old_password'], $user['password'])) {
    $errors[] = 'Ancien mot de passe incorrect.';
}

// Valider le nouveau
if ($_POST['new_password'] !== $_POST['confirm_password']) {
    $errors[] = 'Les mots de passe ne correspondent pas.';
}

// Mettre à jour
$auth->changePassword($user_id, $_POST['old_password'], $_POST['new_password']);
```

### 16. Rapports (pages/rapports/)

#### `entrees.php` - Rapport des entrées
```php
// Filtres : date_debut, date_fin, fournisseur_id (optionnel)
// Afficher :
// - Liste des entrées avec fournisseur, date, nombre d'articles
// - Pour chaque entrée sélectionnée : détail des articles
// - Bouton "Export PDF"
// - Bouton "Download fichiers" pour télécharger les fichiers uploadés
```

- [ ] `entrees.php` - Entrées par période
- [ ] `sorties.php` - Sorties par période
- [ ] `stock.php` - État du stock
- [ ] `traces.php` - Traçabilité
- [ ] `export_pdf.php` - Export PDF générique

#### `sorties.php` - Rapport des sorties
```php
// Filtres : date_debut, date_fin, employe_id (optionnel), service_id (optionnel)
// Afficher :
// - Liste des sorties avec employé, service, date
// - Détail des articles
// - Export PDF
```

#### `stock.php` - État du stock
```php
// Afficher tous les articles avec :
// - Code, désignation
// - Stock initial, qte_entree, qte_sortie, qte_disponible
// - Stock min, stock max
// - État (OK / Stock faible / Rupture)
// - Export PDF et Excel (CSV)

// Filtres :
// - Afficher seulement les alertes
// - Afficher seulement les ruptures
// - Par catégorie (si vous ajoutez les catégories)
```

#### `traces.php` - Traçabilité
```php
// Table traces
// Filtres : date_debut, date_fin, user_id, module, action
// Colonnes : date, utilisateur, module, action, description, IP
// Pagination
// Export PDF
```

### 17. Recherche Globale (pages/search.php)
- [ ] `search.php` - Recherche dans tous les modules

```php
// Rechercher dans :
// - Articles (code, désignation)
// - Employés (matricule, nom, prénom)
// - Fournisseurs (nom)
// - Services (nom)
// - Entrées/Sorties/Retours (numéro, notes)

// Afficher les résultats par catégorie
// Liens vers les pages de détails
```

### 18. API supplémentaires (api/)
- [ ] `get_employes_by_service.php` - Employés filtrés par service (pour AJAX dans sorties)
- [ ] `check_stock.php` - Vérifier la disponibilité d'un article
- [ ] `get_article_stock.php` - Obtenir le stock d'un article

```php
// get_employes_by_service.php
$service_id = $_GET['service_id'] ?? 0;
$sql = "SELECT id, matricule, nom, prenom FROM employes WHERE service_id = :service_id AND actif = 1";
// ... return JSON

// check_stock.php
$article_id = $_GET['article_id'] ?? 0;
$qte = $_GET['qte'] ?? 0;
// Retourner {available: true/false, qte_disponible: X}

// get_article_stock.php
$article_id = $_GET['id'] ?? 0;
$sql = "SELECT code_article, designation, qte_disponible, stock_min, stock_max FROM articles WHERE id = :id";
// ... return JSON
```

## Ordre de développement recommandé

### Phase 1 : Référentiel de base
1. Services (simple)
2. Équipes inventaire (simple)
3. Armoires (simple)
4. Fournisseurs
5. Employés (avec relation service)
6. Bureaux (avec relations)

### Phase 2 : Articles et mouvements simples
7. Articles (sans les mouvements pour l'instant)
8. Entrées (avec mise à jour stock)
9. Retours (avec mise à jour stock)

### Phase 3 : Mouvements complexes
10. Sorties (avec vérification stock)
11. Recalcul stock

### Phase 4 : Inventaires
12. Inventaires (le plus complexe)

### Phase 5 : Administration
13. Utilisateurs et permissions
14. Paramètres
15. Profil

### Phase 6 : Rapports
16. Tous les rapports
17. Recherche globale
18. Traçabilité

## Tests à effectuer

### Pour chaque CRUD :
- [ ] Création d'un enregistrement
- [ ] Modification d'un enregistrement
- [ ] Suppression d'un enregistrement (avec vérification des contraintes)
- [ ] Affichage de la liste (avec pagination et recherche)
- [ ] Affichage des détails
- [ ] Export PDF (si applicable)

### Tests spécifiques :

#### Articles :
- [ ] Stock initial modifiable uniquement à la création
- [ ] Stock initial en lecture seule après création (sauf admin)
- [ ] Alertes stock correctes (min/max)

#### Entrées :
- [ ] Ajout de lignes dynamiques
- [ ] Upload de fichier
- [ ] Mise à jour correcte du stock
- [ ] Suppression remet le stock à jour

#### Sorties :
- [ ] Vérification de la disponibilité du stock
- [ ] Message d'erreur si stock insuffisant
- [ ] Filtrage des employés par service
- [ ] Mise à jour correcte du stock

#### Inventaires :
- [ ] Création avec qte_theorique auto-remplie
- [ ] Génération des écarts
- [ ] Validation par admin
- [ ] Réinitialisation du stock par admin
- [ ] États correctement gérés

## Optimisations futures

### Performance :
- [ ] Mise en cache des requêtes fréquentes avec APCu
- [ ] Index supplémentaires sur les colonnes de recherche
- [ ] Compression des ressources CSS/JS
- [ ] CDN pour les librairies

### Fonctionnalités :
- [ ] Catégories d'articles
- [ ] Sous-catégories
- [ ] Fournisseurs multiples par article
- [ ] Prix et valorisation du stock
- [ ] Seuil de réapprovisionnement automatique
- [ ] Notifications email sur alertes stock
- [ ] Graphiques et tableaux de bord avancés
- [ ] API REST pour intégrations
- [ ] Application mobile (PWA)
- [ ] Import/Export Excel
- [ ] Codes-barres et QR codes

Bon courage pour le développement ! 🚀
