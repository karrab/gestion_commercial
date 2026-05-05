<?php
$page_title = 'Test API';
require_once __DIR__ . '/../includes/header.php';

$auth->requireAdmin();
?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-bug"></i> Test des API</h2>
            <p class="text-muted">Cette page permet de tester si les API retournent bien des données</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-building"></i> Test API Services
                </div>
                <div class="card-body">
                    <button class="btn btn-primary" onclick="testAPI('services')">
                        <i class="bi bi-play-fill"></i> Tester
                    </button>
                    <pre id="result-services" class="mt-3 bg-light p-3" style="max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-people"></i> Test API Employés
                </div>
                <div class="card-body">
                    <button class="btn btn-success" onclick="testAPI('employes')">
                        <i class="bi bi-play-fill"></i> Tester
                    </button>
                    <pre id="result-employes" class="mt-3 bg-light p-3" style="max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-warning">
                    <i class="bi bi-truck"></i> Test API Fournisseurs
                </div>
                <div class="card-body">
                    <button class="btn btn-warning" onclick="testAPI('fournisseurs')">
                        <i class="bi bi-play-fill"></i> Tester
                    </button>
                    <pre id="result-fournisseurs" class="mt-3 bg-light p-3" style="max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-door-open"></i> Test API Bureaux
                </div>
                <div class="card-body">
                    <button class="btn btn-info" onclick="testAPI('bureaux')">
                        <i class="bi bi-play-fill"></i> Tester
                    </button>
                    <pre id="result-bureaux" class="mt-3 bg-light p-3" style="max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-archive"></i> Test API Armoires
                </div>
                <div class="card-body">
                    <button class="btn btn-secondary" onclick="testAPI('armoires')">
                        <i class="bi bi-play-fill"></i> Tester
                    </button>
                    <pre id="result-armoires" class="mt-3 bg-light p-3" style="max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-box-seam"></i> Test API Articles
                </div>
                <div class="card-body">
                    <button class="btn btn-danger" onclick="testAPI('articles')">
                        <i class="bi bi-play-fill"></i> Tester
                    </button>
                    <pre id="result-articles" class="mt-3 bg-light p-3" style="max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-info-circle"></i> Instructions
                </div>
                <div class="card-body">
                    <h5>Comment utiliser cette page :</h5>
                    <ol>
                        <li>Cliquez sur le bouton "Tester" pour chaque API</li>
                        <li>Vérifiez que les données s'affichent correctement</li>
                        <li>Si vous voyez <code>[]</code> (tableau vide), cela signifie que la table est vide dans la base de données</li>
                        <li>Si vous voyez une erreur, il y a un problème avec l'API</li>
                    </ol>

                    <h5 class="mt-4">Que faire si les données sont vides :</h5>
                    <ul>
                        <li><strong>Services :</strong> Allez dans Référentiel > Services et créez au moins un service</li>
                        <li><strong>Employés :</strong> Créez d'abord un service, puis allez dans Référentiel > Employés</li>
                        <li><strong>Fournisseurs :</strong> Allez dans Référentiel > Fournisseurs et créez au moins un fournisseur</li>
                        <li><strong>Bureaux :</strong> Allez dans Référentiel > Bureaux</li>
                        <li><strong>Armoires :</strong> Allez dans Référentiel > Armoires</li>
                        <li><strong>Articles :</strong> Allez dans Articles et créez vos articles</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testAPI(apiName) {
    const resultDiv = document.getElementById('result-' + apiName);
    resultDiv.textContent = 'Chargement...';

    fetch(BASE_URL + '/api/' + apiName + '.php?search=')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            resultDiv.textContent = JSON.stringify(data, null, 2);

            if (Array.isArray(data)) {
                if (data.length === 0) {
                    resultDiv.textContent = '⚠️ TABLEAU VIDE\n\nLa table "' + apiName + '" ne contient aucune donnée.\n\nVeuillez créer des données dans le module correspondant.';
                    resultDiv.style.color = 'orange';
                } else {
                    resultDiv.style.color = 'green';
                    resultDiv.textContent = '✅ ' + data.length + ' résultat(s) trouvé(s)\n\n' + JSON.stringify(data, null, 2);
                }
            }
        })
        .catch(error => {
            resultDiv.textContent = '❌ ERREUR\n\n' + error.message;
            resultDiv.style.color = 'red';
            console.error('Erreur API ' + apiName + ':', error);
        });
}

// Test automatique au chargement
$(document).ready(function() {
    console.log('Page de test des API chargée');
    console.log('BASE_URL:', BASE_URL);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
