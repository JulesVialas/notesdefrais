<?php use services\Config; ?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tickets ajoutés</h5>
            </div>
            <div class="card-body">
                <?php if (empty($_SESSION['temp_tickets'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun ticket ajouté pour le moment
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Affaire</th>
                                <th>Dépense</th>
                                <th>Ttc</th>
                                <th>Tva</th>
                                <th>Justif.</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($_SESSION['temp_tickets'] as $index => $ticket): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($ticket['DateJustificatif'])) ?></td>
                                    <td><?= htmlspecialchars($ticket['NumeroAffaire']) ?></td>
                                    <td><?= htmlspecialchars($ticket['TypeDepense']) ?></td>
                                    <td class="text-end"><?= number_format($ticket['TotalTTC'], 2, ',', ' ') ?> €</td>
                                    <td class="text-end"><?= number_format($ticket['TotalTVA'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <?php if (!empty($ticket['CheminJustificatif'])): ?>
                                            <a href="<?= Config::get("APP_URL") ?><?= htmlspecialchars($ticket['CheminJustificatif']) ?>"
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-alt me-1"></i>Voir le justificatif
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Aucun</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="post"
                                              action="<?= $deleteFormAction ?? Config::get("APP_URL") . "creer-note-frais" ?>">
                                            <input type="hidden" name="action"
                                                   value="<?= $deleteAction ?? 'supprimer_ticket' ?>">
                                            <input type="hidden" name="<?= $ticketIdentifier ?? 'ticket_index' ?>"
                                                   value="<?= isset($ticket['Identifiant']) ? $ticket['Identifiant'] : $index ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
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