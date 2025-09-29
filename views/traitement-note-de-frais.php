<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitement Note de Frais</title>
    <?php use services\Config;

    include 'elements/styles.php'; ?>
</head>
<body>
<div class="container-fluid">
    <?php include 'elements/header.php'; ?>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détails de la note de frais</h5>

                    <!-- Bulk validation button -->
                    <button id="validateSelectedBtn" class="btn btn-success" style="display: none;">
                        <i class="fas fa-check-circle"></i> Valider les tickets sélectionnés
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Utilisateur:</strong> <?= $noteDeFraisDetails['LibelleUtilisateur'] ?></p>
                            <p><strong>Date de demande:</strong> <?= $noteDeFraisDetails['DateDemande'] ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Statut:</strong> <?= $noteDeFraisDetails['Statut'] ?></p>
                            <p><strong>Montant total:</strong> <?= number_format($noteDeFraisDetails['TotalTTC'], 2) ?>
                                €</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['flash_success'] ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['flash_error'] ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <!-- Add checkbox for "Select All" -->
                                <th>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>N° Affaire</th>
                                <th>Montant TTC</th>
                                <th>Montant TVA</th>
                                <th>Justificatif</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <!-- Add checkbox for each ticket -->
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input ticket-checkbox" type="checkbox"
                                                   value="<?= $ticket['Identifiant'] ?>"
                                                <?= ($ticket['Statut'] === 'Validé' || $ticket['Statut'] === 'Refusé') ? 'disabled' : '' ?>>
                                        </div>
                                    </td>
                                    <td><?= $ticket['DateJustificatif'] ?></td>
                                    <td><?= $ticket['TypeDepense'] ?></td>
                                    <td><?= $ticket['NumeroAffaire'] ?></td>
                                    <td><?= number_format($ticket['TotalTTC'], 2) ?> €</td>
                                    <td><?= number_format($ticket['TotalTVA'], 2) ?> €</td>
                                    <td>
                                        <?php if (!empty($ticket['CheminJustificatif'])): ?>
                                            <?php


                                            // Fix URL path for justificatifs by making it absolute
                                            $justificatifPath = $ticket['CheminJustificatif'];

                                            // If path doesn't start with http or https, convert to absolute URL
                                            if (strpos($justificatifPath, 'http') !== 0) {
                                                // Remove "/notesdefrais/" from path if it exists at beginning
                                                if (strpos($justificatifPath, '/notesdefrais/') === 0) {
                                                    $justificatifPath = substr($justificatifPath, strlen('/notesdefrais/'));
                                                }
                                                // Remove any "valider-ticket/" or "verifier-ticket/" from path
                                                $justificatifPath = preg_replace('/(valider-ticket\/|verifier-ticket\/)/i', '', $justificatifPath);

                                                // Build absolute URL
                                                $baseUrl = rtrim(Config::get("APP_URL"), '/');
                                                $justificatifPath = $baseUrl . '/' . ltrim($justificatifPath, '/');
                                            }
                                            ?>
                                            <a href="<?= $justificatifPath ?>" target="_blank">
                                                <img src="<?= $justificatifPath ?>" class="img-thumbnail"
                                                     style="max-height: 50px;">
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Aucun</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ticket['Statut'] === 'Validé'): ?>
                                            <span class="badge bg-success">Validé</span>
                                        <?php elseif ($ticket['Statut'] === 'Refusé'): ?>
                                            <span class="badge bg-danger">Refusé</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ticket['Statut'] !== 'Validé' && $ticket['Statut'] !== 'Refusé'): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="valider_ticket">
                                                <input type="hidden" name="ticket_id"
                                                       value="<?= $ticket['Identifiant'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Valider
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#refuserModal<?= $ticket['Identifiant'] ?>">
                                                <i class="fas fa-times"></i> Refuser
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refuser Modals -->
    <?php foreach ($tickets as $ticket): ?>
        <?php if ($ticket['Statut'] !== 'Validé' && $ticket['Statut'] !== 'Refusé'): ?>
            <div class="modal fade" id="refuserModal<?= $ticket['Identifiant'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Refuser le ticket</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="refuser_ticket">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['Identifiant'] ?>">
                                <div class="mb-3">
                                    <label for="motif<?= $ticket['Identifiant'] ?>" class="form-label">Motif du refus</label>
                                    <textarea name="motif" class="form-control" rows="3"
                                              placeholder="Saisissez un motif " required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-danger">Refuser</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php include 'elements/scripts.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllCheckbox = document.getElementById('selectAll');
        const ticketCheckboxes = document.querySelectorAll('.ticket-checkbox:not([disabled])');
        const validateSelectedBtn = document.getElementById('validateSelectedBtn');

        // Toggle "Select All" checkbox
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            ticketCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateValidateButtonVisibility();
        });

        // Toggle individual checkboxes
        ticketCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                updateValidateButtonVisibility();

                // Update "select all" checkbox state
                const allChecked = Array.from(ticketCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = !allChecked && Array.from(ticketCheckboxes).some(cb => cb.checked);
            });
        });

        // Show/hide Validate button based on selections
        function updateValidateButtonVisibility() {
            const anyChecked = Array.from(ticketCheckboxes).some(checkbox => checkbox.checked);
            validateSelectedBtn.style.display = anyChecked ? 'block' : 'none';
        }

        // Handle bulk validation button click
        validateSelectedBtn.addEventListener('click', function () {
            if (!confirm('Êtes-vous sûr de vouloir valider tous les tickets sélectionnés?')) {
                return;
            }

            const selectedIds = [];
            ticketCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            });

            if (selectedIds.length > 0) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = ''; // Current URL

                // Add the action input
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'valider_tickets_multiples';

                // Add the selected IDs input
                const idsInput = document.createElement('input');
                idsInput.type = 'hidden';
                idsInput.name = 'ticket_ids';
                idsInput.value = selectedIds.join(',');

                form.appendChild(actionInput);
                form.appendChild(idsInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
</script>

</body>
</html>