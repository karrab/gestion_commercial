<?php
require_once __DIR__ . '/../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid main-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-code-square"></i> Test direct des APIs</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    Test en temps réel
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button class="btn btn-success me-2" onclick="testAPI('services')">Test Services API</button>
                        <button class="btn btn-success me-2" onclick="testAPI('employes')">Test Employes API</button>
                        <button class="btn btn-success me-2" onclick="testAPI('fournisseurs')">Test Fournisseurs API</button>
                        <button class="btn btn-success me-2" onclick="testAPI('bureaux')">Test Bureaux API</button>
                        <button class="btn btn-success me-2" onclick="testAPI('armoires')">Test Armoires API</button>
                    </div>

                    <div id="results" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function testAPI(apiName) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = '<div class="alert alert-info">Chargement...</div>';

    const apiUrl = BASE_URL + '/api/' + apiName + '.php?search=';

    fetch(apiUrl)
        .then(response => {
            console.log('Status:', response.status);
            console.log('Headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);

            try {
                const data = JSON.parse(text);
                displayResults(apiName, data, apiUrl);
            } catch(e) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>❌ Erreur de parsing JSON pour ${apiName}</h5>
                        <p><strong>URL:</strong> <code>${apiUrl}</code></p>
                        <p><strong>Erreur:</strong> ${e.message}</p>
                        <p><strong>Réponse brute:</strong></p>
                        <pre class="bg-light p-3">${text}</pre>
                    </div>
                `;
            }
        })
        .catch(error => {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h5>❌ Erreur réseau</h5>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

function displayResults(apiName, data, apiUrl) {
    const resultsDiv = document.getElementById('results');

    if (Array.isArray(data) && data.length > 0) {
        let html = `
            <div class="alert alert-success">
                <h5>✅ ${apiName} - ${data.length} résultat(s)</h5>
                <p><strong>URL:</strong> <code>${apiUrl}</code></p>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Text</th>
                        <th>Données complètes</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.forEach(item => {
            html += `
                <tr>
                    <td>${item.id}</td>
                    <td>${item.text}</td>
                    <td><pre class="mb-0">${JSON.stringify(item, null, 2)}</pre></td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        resultsDiv.innerHTML = html;
    } else if (Array.isArray(data)) {
        resultsDiv.innerHTML = `
            <div class="alert alert-warning">
                <h5>⚠️ ${apiName} - Aucun résultat</h5>
                <p><strong>URL:</strong> <code>${apiUrl}</code></p>
                <p>L'API retourne un tableau vide. La table est probablement vide.</p>
            </div>
        `;
    } else {
        resultsDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5>❌ ${apiName} - Format invalide</h5>
                <p><strong>URL:</strong> <code>${apiUrl}</code></p>
                <p>L'API ne retourne pas un tableau.</p>
                <pre class="bg-light p-3">${JSON.stringify(data, null, 2)}</pre>
            </div>
        `;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
