<?php
// notes-de-frais-archivees.php
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notes de frais archivées</title>
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
                    <h4 class="mb-0">Notes de frais archivées</h4>
                    <p class="text-muted">Liste des notes de frais terminées</p>
                </div>
                <button id="exportSelectedBtn" class="btn btn-success me-2" style="display: none;">
                    <i class="fas fa-download me-1"></i>Exporter la sélection
                </button>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?? 'info' ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="get" class="mb-3 d-flex gap-2 align-items-end">
            <div>
                <label for="filter-nom" class="form-label mb-1">Nom</label>
                <input type="text" id="filter-nom" name="nom" class="form-control" placeholder="Nom"
                       value="<?= htmlspecialchars($_GET['nom'] ?? '') ?>">
            </div>
            <div>
                <label for="filter-date" class="form-label mb-1">Date</label>
                <input type="date" id="filter-date" name="date" class="form-control"
                       value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </div>
        </form>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Notes de frais terminées</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notesDeFrais)): ?>
                            <div class="alert alert-info">
                                Aucune note de frais archivée n'a été trouvée.
                            </div>
                        <?php else: ?>
                            <table class="table table-striped table-hover">
                                <thead>
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                        </div>
                                    </th>
                                    <th>Date de demande</th>
                                    <th>Utilisateur</th>
                                    <th>Total TTC</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($notesDeFrais as $note): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input note-checkbox" type="checkbox"
                                                       value="<?= $note['Identifiant'] ?>">
                                            </div>
                                        </td>
                                        <td><?= (new DateTime($note['DateDemande']))->format('d/m/Y') ?></td>
                                        <td><?= htmlspecialchars($note['LibelleUtilisateur']) ?></td>
                                        <td><?= number_format($note['TotalTTC'], 2, ',', ' ') ?> €</td>
                                        <td>
                                            <a href="<?= Config::get("APP_URL") ?>voir-note-frais/<?= $note['Identifiant'] ?>"
                                               class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-eye me-1"></i>Détails
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
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
        const exportSelectedBtn = document.getElementById('exportSelectedBtn');

        // Toggle "Select All" checkbox
        selectAllCheckbox.addEventListener('change', function () {
            noteCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateButtonsVisibility();
        });

        // Toggle individual checkboxes
        noteCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                updateButtonsVisibility();

                // Update "select all" checkbox state
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(noteCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });

        // Show/hide buttons based on selections
        function updateButtonsVisibility() {
            const hasSelection = Array.from(noteCheckboxes).some(cb => cb.checked);
            exportSelectedBtn.style.display = hasSelection ? 'inline-block' : 'none';
        }

        // Handle bulk export button click
        exportSelectedBtn.addEventListener('click', function () {
            const selectedIds = [];
            noteCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            });

            if (selectedIds.length > 0) {
                submitForm('export', selectedIds);
            }
        });

        // Helper function to submit forms
        function submitForm(action, selectedIds) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;

            const idsInput = document.createElement('input');
            idsInput.type = 'hidden';
            idsInput.name = 'ids';
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