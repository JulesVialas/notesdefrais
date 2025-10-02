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
                                            <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal"
                                                    data-bs-target="#modifierModal<?= $ticket['Identifiant'] ?>">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
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

    <!-- Modifier Modals -->
    <?php foreach ($tickets as $ticket): ?>
        <?php if ($ticket['Statut'] !== 'Validé' && $ticket['Statut'] !== 'Refusé'): ?>
            <div class="modal fade" id="modifierModal<?= $ticket['Identifiant'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier le ticket</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="modifier_ticket">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['Identifiant'] ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="date_justificatif_<?= $ticket['Identifiant'] ?>" class="form-label">Date du justificatif</label>
                                        <input type="date" class="form-control" 
                                               id="date_justificatif_<?= $ticket['Identifiant'] ?>"
                                               name="date_justificatif"
                                               max="<?= date('Y-m-d') ?>"
                                               value="<?= $ticket['DateJustificatif'] ?>"
                                               required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="numero_affaire_<?= $ticket['Identifiant'] ?>" class="form-label">Numéro d'affaire</label>
                                        <input type="text" class="form-control" 
                                               id="numero_affaire_<?= $ticket['Identifiant'] ?>"
                                               name="numero_affaire"
                                               value="<?= htmlspecialchars($ticket['NumeroAffaire']) ?>"
                                               required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="type_depense_<?= $ticket['Identifiant'] ?>" class="form-label">Type de dépense</label>
                                        <select class="form-select type-depense-select" 
                                                id="type_depense_<?= $ticket['Identifiant'] ?>"
                                                name="type_depense" 
                                                data-ticket-id="<?= $ticket['Identifiant'] ?>"
                                                required>
                                            <option value="">Choisir un type de dépense</option>
                                            <?php if (isset($typesDepenses) && is_array($typesDepenses)): ?>
                                                <?php foreach ($typesDepenses as $typeDepense): ?>
                                                    <option value="<?= htmlspecialchars($typeDepense['Libelle']) ?>"
                                                            data-tva="<?= $typeDepense['tva'] ? '1' : '0' ?>"
                                                            <?= ($ticket['TypeDepense'] === $typeDepense['Libelle']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($typeDepense['Libelle']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="total_ttc_<?= $ticket['Identifiant'] ?>" class="form-label">Total TTC</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control total-ttc-input" 
                                                   id="total_ttc_<?= $ticket['Identifiant'] ?>"
                                                   name="total_ttc"
                                                   data-ticket-id="<?= $ticket['Identifiant'] ?>"
                                                   value="<?= $ticket['TotalTTC'] ?>"
                                                   required>
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 tva-field-<?= $ticket['Identifiant'] ?>" 
                                         style="display: <?= ($ticket['TotalTVA'] > 0) ? 'block' : 'none' ?>;">
                                        <label for="taux_tva_<?= $ticket['Identifiant'] ?>" class="form-label">Taux de TVA</label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" max="100" class="form-control taux-tva-input" 
                                                   id="taux_tva_<?= $ticket['Identifiant'] ?>"
                                                   name="taux_tva" 
                                                   data-ticket-id="<?= $ticket['Identifiant'] ?>"
                                                   value="<?= ($ticket['TotalTTC'] > 0 && $ticket['TotalTVA'] > 0) ? round(($ticket['TotalTVA'] / ($ticket['TotalTTC'] - $ticket['TotalTVA'])) * 100, 1) : '20.0' ?>">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 tva-field-<?= $ticket['Identifiant'] ?>" 
                                         style="display: <?= ($ticket['TotalTVA'] > 0) ? 'block' : 'none' ?>;">
                                        <label for="total_tva_<?= $ticket['Identifiant'] ?>" class="form-label">Montant TVA</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control total-tva-input" 
                                                   id="total_tva_<?= $ticket['Identifiant'] ?>"
                                                   name="total_tva"
                                                   data-ticket-id="<?= $ticket['Identifiant'] ?>"
                                                   value="<?= $ticket['TotalTVA'] ?>">
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="commentaires_<?= $ticket['Identifiant'] ?>" class="form-label">Commentaires</label>
                                        <input type="text" class="form-control" 
                                               id="commentaires_<?= $ticket['Identifiant'] ?>"
                                               name="commentaires"
                                               value="<?= htmlspecialchars($ticket['Commentaires'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Justificatif actuel</label>
                                        <?php if (!empty($ticket['CheminJustificatif'])): ?>
                                            <?php
                                            $justificatifPath = $ticket['CheminJustificatif'];
                                            if (strpos($justificatifPath, 'http') !== 0) {
                                                if (strpos($justificatifPath, '/notesdefrais/') === 0) {
                                                    $justificatifPath = substr($justificatifPath, strlen('/notesdefrais/'));
                                                }
                                                $justificatifPath = preg_replace('/(valider-ticket\/|verifier-ticket\/)/i', '', $justificatifPath);
                                                $baseUrl = rtrim(Config::get("APP_URL"), '/');
                                                $justificatifPath = $baseUrl . '/' . ltrim($justificatifPath, '/');
                                            }
                                            ?>
                                            <div class="mb-2">
                                                <img src="<?= $justificatifPath ?>" class="img-thumbnail" style="max-height: 100px;">
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">Aucun justificatif</p>
                                        <?php endif; ?>
                                        
                                        <label for="nouveau_justificatif_<?= $ticket['Identifiant'] ?>" class="form-label">Nouveau justificatif (optionnel)</label>
                                        <input type="file" class="form-control" 
                                               id="nouveau_justificatif_<?= $ticket['Identifiant'] ?>"
                                               name="nouveau_justificatif"
                                               accept="image/*">
                                        <div class="form-text">Laissez vide pour conserver le justificatif actuel</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Modifier</button>
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

        // Gestion des modales de modification
        // Gestion du changement de type de dépense pour afficher/masquer les champs TVA
        document.querySelectorAll('.type-depense-select').forEach(select => {
            select.addEventListener('change', function() {
                const ticketId = this.dataset.ticketId;
                const selectedOption = this.options[this.selectedIndex];
                const hasTva = selectedOption.dataset.tva === '1';
                const tvaFields = document.querySelectorAll(`.tva-field-${ticketId}`);
                
                tvaFields.forEach(field => {
                    field.style.display = hasTva ? 'block' : 'none';
                });

                // Reset TVA values if no TVA
                if (!hasTva) {
                    document.getElementById(`taux_tva_${ticketId}`).value = '';
                    document.getElementById(`total_tva_${ticketId}`).value = '';
                } else {
                    // Set default TVA rate
                    document.getElementById(`taux_tva_${ticketId}`).value = '20.0';
                    calculateTva(ticketId);
                }
            });
        });

        // Calcul automatique de la TVA lors de la modification du montant TTC
        document.querySelectorAll('.total-ttc-input').forEach(input => {
            input.addEventListener('input', function() {
                const ticketId = this.dataset.ticketId;
                calculateTva(ticketId);
            });
        });

        // Calcul automatique de la TVA lors de la modification du taux
        document.querySelectorAll('.taux-tva-input').forEach(input => {
            input.addEventListener('input', function() {
                const ticketId = this.dataset.ticketId;
                calculateTva(ticketId);
            });
        });

        // Fonction pour calculer la TVA
        function calculateTva(ticketId) {
            const totalTtcInput = document.getElementById(`total_ttc_${ticketId}`);
            const tauxTvaInput = document.getElementById(`taux_tva_${ticketId}`);
            const totalTvaInput = document.getElementById(`total_tva_${ticketId}`);

            const totalTtc = parseFloat(totalTtcInput.value) || 0;
            const tauxTva = parseFloat(tauxTvaInput.value) || 0;

            if (totalTtc > 0 && tauxTva > 0) {
                // Calcul: TVA = TTC * (taux / (100 + taux))
                const totalTva = totalTtc * (tauxTva / (100 + tauxTva));
                totalTvaInput.value = totalTva.toFixed(2);
            } else {
                totalTvaInput.value = '';
            }
        }
    });
</script>

</body>
</html>