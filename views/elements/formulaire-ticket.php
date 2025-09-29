<?php use services\Config; ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    Ajouter un ticket
                </h5>
            </div>
            <div class="card-body">
                <form method="post"
                      action="<?= isset($formAction) ? $formAction : Config::get("APP_URL") . "creer-note-frais" ?>"
                      enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= isset($ticketAction) ? $ticketAction : 'creer_ticket' ?>">
                    <div class="row justify-content-center mb-3">
                        <div class="col-md-10">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="date_justificatif" class="form-label">Date du justificatif</label>
                                    <input type="date" class="form-control" id="date_justificatif"
                                           name="date_justificatif"
                                           max="<?= date('Y-m-d') ?>"
                                           value="<?= isset($formData['date_justificatif']) ? htmlspecialchars($formData['date_justificatif']) : date('Y-m-d') ?>"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label for="numero_affaire" class="form-label">Numéro d'affaire (Code analytique)</label>
                                    <input type="text" class="form-control" id="numero_affaire" name="numero_affaire"
                                           value="<?= isset($formData['numero_affaire']) ? htmlspecialchars($formData['numero_affaire']) : (isset($matricule) ? $matricule : '') ?>"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label for="type_depense" class="form-label">Type de dépense</label>
                                    <select class="form-select" id="type_depense" name="type_depense" required>
                                        <option value="">Choisir un type de dépense</option>
                                        <?php if (isset($typesDepenses) && is_array($typesDepenses)): ?>
                                            <?php foreach ($typesDepenses as $typeDepense): ?>
                                                <option value="<?= htmlspecialchars($typeDepense['Libelle']) ?>"
                                                        data-tva="<?= $typeDepense['tva'] ? '1' : '0' ?>"
                                                        <?= (isset($formData['type_depense']) && $formData['type_depense'] === $typeDepense['Libelle']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($typeDepense['Libelle']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="total_ttc" class="form-label">Total TTC</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="total_ttc"
                                               name="total_ttc"
                                               value="<?= isset($formData['total_ttc']) ? htmlspecialchars($formData['total_ttc']) : '' ?>"
                                               required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                <div class="col-md-4 tva-field" id="taux-tva-container" style="display: none;">
                                    <label for="taux_tva" class="form-label">Taux de TVA</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" max="100" class="form-control" id="taux_tva"
                                               name="taux_tva" placeholder="20.0" value="20.0">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-4 tva-field" id="montant-tva-container" style="display: none;">
                                    <label for="total_tva" class="form-label">Montant TVA</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="total_tva"
                                               name="total_tva">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                <div class="col-md-4" id="commentaires" style="display: none;">
                                    <label for="commentaires" class="form-label">Commentaires (nom clients,etc...)</label>
                                    <input type="text" class="form-control" id="commentaires" name="commentaires">
                                </div>
                                <div class="col-md-4">
                                    <label for="justificatif" class="form-label">Justificatif</label>
                                    <div class="d-flex flex-column">
                                        <input type="file" class="form-control d-none" id="justificatif"
                                               name="justificatif"
                                               accept="image/*" capture="environment" required>
                                        <button type="button" class="btn btn-outline-primary mb-2" id="takePhotoBtn">
                                            <i class="fas fa-camera me-2"></i>Prendre une photo
                                        </button>
                                        <div id="previewContainer" class="mt-2 d-none">
                                            <img id="imagePreview" class="img-fluid mb-2 border rounded"
                                                 style="max-height: 200px;" alt="Aperçu">
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    id="resetPhotoBtn">
                                                <i class="fas fa-times me-1"></i>Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">
                                        Ajouter le ticket
                                    </label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus-circle me-2"></i>
                                            Appuyez ici pour ajouter le ticket
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeDepenseSelect = document.getElementById('type_depense');
        const tauxTVAContainer = document.getElementById('taux-tva-container');
        const montantTVAContainer = document.getElementById('montant-tva-container');
        const totalTTC = document.getElementById('total_ttc');
        const tauxTVA = document.getElementById('taux_tva');
        const totalTVA = document.getElementById('total_tva');
        const commentaires = document.getElementById('commentaires');
        const commentairesInput = document.querySelector('#commentaires input');

        function updateCommentairesRequired() {
            if (commentaires.style.display === 'block') {
                commentairesInput.required = true;
            } else {
                commentairesInput.required = false;
            }
        }

        function updateTVAFields() {
            const selectedOption = typeDepenseSelect.options[typeDepenseSelect.selectedIndex];
            const hasTva = selectedOption ? selectedOption.dataset.tva === '1' : false;
            const selectedType = typeDepenseSelect.value;

            tauxTVA.readOnly = false;

            tauxTVAContainer.style.display = 'none';
            montantTVAContainer.style.display = 'none';
            commentaires.style.display = 'none';

            if (!selectedType) {
                updateCommentairesRequired();
                return;
            }

            if (hasTva) {
                if (selectedType === 'Carburant') {
                    tauxTVAContainer.style.display = 'block';
                    montantTVAContainer.style.display = 'block';
                    tauxTVA.value = 18;
                    tauxTVA.readOnly = true;
                    calculateTVA();
                } else if (selectedType === 'Invitation Client') {
                    commentaires.style.display = 'block';
                    montantTVAContainer.style.display = 'block';
                } else {
                    montantTVAContainer.style.display = 'block';
                }
            } else {
                totalTVA.value = '0';
            }

            updateCommentairesRequired();
        }
        function calculateTVA() {
            if (totalTTC.value && tauxTVA.value && typeDepenseSelect.value === 'Carburant') {
                const ttc = parseFloat(totalTTC.value);
                const taux = parseFloat(tauxTVA.value);
                const tva = ttc * taux / (100 + taux);
                totalTVA.value = tva.toFixed(2);
            }
        }

        typeDepenseSelect.addEventListener('change', updateTVAFields);
        totalTTC.addEventListener('input', calculateTVA);
        tauxTVA.addEventListener('input', calculateTVA);

        updateTVAFields();

        // Photo capture
        const takePhotoBtn = document.getElementById('takePhotoBtn');
        const resetPhotoBtn = document.getElementById('resetPhotoBtn');
        const fileInput = document.getElementById('justificatif');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');

        takePhotoBtn.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                    takePhotoBtn.innerHTML = '<i class="fas fa-camera me-2"></i>Changer la photo';
                };

                reader.readAsDataURL(fileInput.files[0]);
            }
        });

        resetPhotoBtn.addEventListener('click', function () {
            fileInput.value = '';
            previewContainer.classList.add('d-none');
            takePhotoBtn.innerHTML = '<i class="fas fa-camera me-2"></i>Prendre une photo';
            fileInput.required = true;
        });
    });
</script>
