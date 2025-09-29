<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détails de la note de frais</title>
    <?php use services\Config;

    include 'elements/styles.php'; ?>
</head>
<body>
<div class="container-fluid">
    <?php include 'elements/header.php'; ?>
    <div class="content">
        <div class="row mb-4">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <h4 class="mb-0">
                    <a href="<?= Config::get("APP_URL") ?>?tab=<?= $_GET['tab'] ?? 'enCours' ?>"
                       class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    Note de frais #<?= $noteDeFraisDetails['Identifiant'] ?>
                    de <?php echo $_SESSION['LibelleUtilisateur'] ?>
                </h4>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Informations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date de
                                        demande:</strong> <?= $noteDeFraisDetails['DateDemande'] ? date('d/m/Y', strtotime($noteDeFraisDetails['DateDemande'])) : 'N/A' ?>
                                </p>
                                <p>
                                    <strong>Statut:</strong>
                                    <?php
                                    $statusClass = 'bg-secondary';
                                    if (strpos(strtolower($noteDeFraisDetails['Statut']), 'cours') !== false) {
                                        $statusClass = 'bg-primary';
                                    } elseif ($noteDeFraisDetails['Statut'] === 'Terminée') {
                                        $statusClass = 'bg-success';
                                    } elseif ($noteDeFraisDetails['Statut'] === 'Refusée') {
                                        $statusClass = 'bg-danger';
                                    }
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $noteDeFraisDetails['Statut'] ?></span>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><strong>Total
                                        TTC:</strong> <?= number_format($noteDeFraisDetails['TotalTTC'], 2, ',', ' ') ?>
                                    €</p>
                                <p><strong>Total
                                        TVA:</strong> <?= number_format($noteDeFraisDetails['TotalTVA'], 2, ',', ' ') ?>
                                    €</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Tickets associés</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun ticket associé à cette note de frais
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-bordered">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Numéro d'affaire</th>
                                        <th>Type de dépense</th>
                                        <th>Justificatif</th>
                                        <th>Total TTC</th>
                                        <th>Total TVA</th>
                                        <?php if ($noteDeFraisDetails['Statut'] == 'Refusée') { ?>
                                            <th>Motif(s) de refus</th>
                                        <?php } ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($ticket['DateJustificatif'])) ?></td>
                                            <td><?= $ticket['NumeroAffaire'] ?></td>
                                            <td><?= $ticket['TypeDepense'] ?></td>
                                            <td>
                                                <?php if (!empty($ticket['CheminJustificatif'])): ?>
                                                    <a href="<?= Config::get("APP_URL") ?><?= $ticket['CheminJustificatif'] ?>"
                                                       target="_blank"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-file-alt me-1"></i>Voir le justificatif
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Aucun justificatif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end"><?= number_format($ticket['TotalTTC'], 2, ',', ' ') ?>
                                                €
                                            </td>
                                            <td class="text-end"><?= number_format($ticket['TotalTVA'], 2, ',', ' ') ?>
                                                €
                                            </td>
                                            <?php if ($noteDeFraisDetails['Statut'] == 'Refusée') { ?>
                                                <td>
                                                    <?php if (!empty($ticket['CommentaireRefusVerification'])): ?>
                                                        <p class="small mb-1">
                                                            <strong>Vérification:</strong> <?= htmlspecialchars($ticket['CommentaireRefusVerification']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($ticket['CommentaireRefusValidation'])): ?>
                                                        <p class="small mb-1">
                                                            <strong>Validation:</strong> <?= htmlspecialchars($ticket['CommentaireRefusValidation']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($ticket['CommentaireRefusAdministration'])): ?>
                                                        <p class="small mb-1">
                                                            <strong>Administration:</strong> <?= htmlspecialchars($ticket['CommentaireRefusAdministration']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if (empty($ticket['CommentaireRefusVerification']) &&
                                                        empty($ticket['CommentaireRefusValidation']) &&
                                                        empty($ticket['CommentaireRefusAdministration'])): ?>
                                                        <span class="text-muted">Aucun</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php } ?>
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
    <div class="d-flex justify-content-end mt-3">
        <?php if ($noteDeFraisDetails['Statut'] === 'En cours de saisie' || $noteDeFraisDetails['Statut'] === 'Refusée'): ?>
            <a href="<?= Config::get("APP_URL") ?>modifier-note-frais/<?= $noteDeFraisDetails['Identifiant'] ?>"
               class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Modifier la note de frais
            </a>
        <?php endif; ?>
    </div>
</div>
<?php include 'elements/scripts.php'; ?>
</body>
</html>