<?php use services\Config; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les types de dépense</title>
    <?php include 'elements/styles.php'; ?>
</head>
<body>
<?php include 'elements/header.php'; ?>
<div class="container mt-4">
    <h2>Gestion des types de dépense</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <table class="table table-bordered align-middle">
        <thead>
        <tr>
            <th>Libellé</th>
            <th>Compte Comptable</th>
            <th>TVA</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($typesDepenses as $type): ?>
            <tr>
                <form method="post" action="<?= Config::get("APP_URL") ?>gerer-types-depense" style="display:inline;">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $type['Identifiant'] ?>">
                    <td>
                        <input type="text" name="libelle" value="<?= htmlspecialchars($type['Libelle']) ?>"
                               class="form-control" required>
                    </td>
                    <td>
                        <input type="text" name="compte_comptable"
                               value="<?= htmlspecialchars($type['CompteComptable']) ?>" class="form-control" required>
                    </td>
                    <td>
                        <div class="form-check">
                            <input type="checkbox" name="tva" value="1" class="form-check-input"
                                <?= $type['tva'] ? 'checked' : '' ?>>
                            <label class="form-check-label">Avec TVA</label>
                        </div>
                    </td>
                    <td>
                        <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
                </form>
                <form method="post" action="<?= Config::get("APP_URL") ?>gerer-types-depense" style="display:inline;"
                      onsubmit="return confirm('Supprimer ce type de dépense ?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $type['Identifiant'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <form method="post" action="<?= Config::get("APP_URL") ?>gerer-types-depense">
                <input type="hidden" name="action" value="add">
                <td>
                    <input type="text" name="libelle" class="form-control" required>
                </td>
                <td>
                    <input type="text" name="compte_comptable" class="form-control" required>
                </td>
                <td>
                    <div class="form-check">
                        <input type="checkbox" name="tva" value="1" class="form-check-input">
                        <label class="form-check-label">Avec TVA</label>
                    </div>
                </td>
                <td>
                    <button type="submit" class="btn btn-success btn-sm">Ajouter</button>
                </td>
            </form>
        </tr>
        </tbody>
    </table>
</div>
<?php include 'elements/scripts.php'; ?>
</body>
</html>