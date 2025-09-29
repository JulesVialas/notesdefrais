<?php
// ... (header and includes)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <?php

    use services\Config;

    include 'elements/styles.php';
    include 'elements/scripts.php';
    ?>
</head>
<body>
<?php include 'elements/header.php'; ?>
<div class="container">
    <h1>Synthèse des notes de frais par personne</h1>
    <h2>Liste des utilisateurs</h2>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Code Analytique</th>
            <th>Code Tiers</th>
            <th>Nom</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($utilisateurs as $utilisateur): ?>
            <tr>
                <td><?= htmlspecialchars($utilisateur['codeAnalytique'] ?? '') ?></td>
                <td><?= htmlspecialchars($utilisateur['codeTiers'] ?? '') ?></td>
                <td><?= htmlspecialchars($utilisateur['name'] ?? '') ?></td>
                <td>
                    <button type="button"
                            class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#exportModal<?= $utilisateur['id'] ?>">
                        Exporter PDF
                    </button>
                    <!-- Modal -->
                    <div class="modal fade" id="exportModal<?= $utilisateur['id'] ?>" tabindex="-1"
                         aria-labelledby="exportModalLabel<?= $utilisateur['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="<?= Config::get("APP_URL") ?>synthese-notes-frais"
                                      target="_blank">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exportModalLabel<?= $utilisateur['id'] ?>">Exporter
                                            les notes de frais</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="user_id" value="<?= $utilisateur['id'] ?>">
                                        <input type="hidden" name="code_tiers"
                                               value="<?= htmlspecialchars($utilisateur['codeTiers'] ?? '') ?>">
                                        <label for="periode<?= $utilisateur['id'] ?>">Sélectionnez la période :</label>
                                        <input type="month" id="periode<?= $utilisateur['id'] ?>" name="periode"
                                               class="form-control" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Annuler
                                        </button>
                                        <button type="submit" class="btn btn-primary">Exporter le PDF</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>