<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification des notes de frais</title>
    <?php

    use services\Config;

    include 'elements/styles.php';
    ?>
</head>
<body>
<div class="container-fluid">
    <?php include 'elements/header.php'; ?>
    <div class="content">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">Notes de frais à traiter</h4>
                    <p class="text-muted">Liste des notes de frais en attente de traitement</p>
                </div>
                <button id="validateSelectedBtn" class="btn btn-success" style="display: none;">
                    <?php if ($mode === 'comptable'): ?>
                        <i class="fas fa-download me-1"></i>J'exporte le(s) note(s) de frais sélectionnée(s)
                    <?php elseif ($mode === 'validation'): ?>
                        <i class="fas fa-check-double me-1"></i>je valide le(s) note(s) de frais sélectionnée(s)
                    <?php elseif ($mode === 'verification'): ?>
                        <i class="fas fa-check-double me-1"></i>j'envoie en validation le(s) note(s) de frais sélectionnée(s)
                    <?php endif; ?>
                </button>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?? 'info' ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Notes de frais en attente de traitement</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notesDeFrais)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucune note de frais en attente de traitement.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered">
                                    <thead class="table-light">
                                    <tr>
                                        <th width="40px">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>ID</th>
                                        <th>Collaborateur</th>
                                        <th>Date de demande</th>
                                        <th>Total TTC</th>
                                        <th>Total TVA</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($notesDeFrais as $note): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input note-checkbox"
                                                       value="<?= $note['Identifiant'] ?>">
                                            </td>
                                            <td><?= $note['Identifiant'] ?></td>
                                            <td><?= $note['LibelleUtilisateur'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($note['DateDemande'])) ?></td>
                                            <td class="text-end"><?= number_format($note['TotalTTC'], 2, ',', ' ') ?>€
                                            </td>
                                            <td class="text-end"><?= number_format($note['TotalTVA'], 2, ',', ' ') ?>€
                                            </td>
                                            <td>
                                                <?php if ($note['Statut'] == 'En cours de traitement comptable') { ?>
                                                    <a href="<?= Config::get("APP_URL") ?>voir-note-frais/<?= $note['Identifiant'] ?>"
                                                       class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-eye me-1"></i>Détails
                                                    </a>
                                                <?php }
                                                if ($note['Statut'] == 'En cours de vérification') { ?>
                                                    <a href="<?= Config::get("APP_URL") ?>verifier-ticket/<?= $note['Identifiant'] ?>"
                                                       class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-eye me-1"></i>Détails
                                                    </a>
                                                <?php }
                                                if ($note['Statut'] == 'En cours de validation') { ?>
                                                    <a href="<?= Config::get("APP_URL") ?>valider-ticket/<?= $note['Identifiant'] ?>"
                                                       class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-eye me-1"></i>Détails
                                                    </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'elements/scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllCheckbox = document.getElementById('selectAll');
        const noteCheckboxes = document.querySelectorAll('.note-checkbox');
        const validateSelectedBtn = document.getElementById('validateSelectedBtn');
        const mode = '<?= $mode ?>'; // Get the current mode from PHP

        // Toggle "Select All" checkbox
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            noteCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateButtonsVisibility();
        });

        // Toggle individual checkboxes
        noteCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                updateButtonsVisibility();

                // Update "Select All" checkbox state
                let allChecked = true;
                noteCheckboxes.forEach(cb => {
                    if (!cb.checked) allChecked = false;
                });
                selectAllCheckbox.checked = allChecked;
            });
        });

        function updateButtonsVisibility() {
            let hasSelection = false;
            noteCheckboxes.forEach(checkbox => {
                if (checkbox.checked) hasSelection = true;
            });
            validateSelectedBtn.style.display = hasSelection ? 'block' : 'none';
        }

        validateSelectedBtn.addEventListener('click', function () {
            // Set different message and action based on mode
            let confirmMessage = 'Êtes-vous sûr de vouloir valider toutes les notes de frais sélectionnées?';
            let action = 'validate_bulk';

            if (mode === 'comptable') {
                confirmMessage = 'Êtes-vous sûr de vouloir exporter toutes les notes de frais sélectionnées?';
                action = 'export_bulk';
            } else if (mode === 'validation') {
                confirmMessage = 'Êtes-vous sûr de vouloir valider toutes les notes de frais sélectionnées?';
                action = 'validate_bulk';
            } else if (mode === 'verification') {
                confirmMessage = 'Êtes-vous sûr de vouloir envoyer en validation toutes les notes de frais sélectionnées?';
                action = 'validate_bulk';
            }

            if (!confirm(confirmMessage)) {
                return;
            }

            const selectedIds = [];
            noteCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            });

            if (selectedIds.length > 0) {
                submitForm(action, selectedIds);
            }
        });

        // Helper function to submit forms
        function submitForm(action, selectedIds) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = ''; // Empty action means current URL

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;

            const idsInput = document.createElement('input');
            idsInput.type = 'hidden';
            idsInput.name = 'selected_ids';
            idsInput.value = selectedIds.join(',');

            form.appendChild(actionInput);
            form.appendChild(idsInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
</script>
</body>
</html>