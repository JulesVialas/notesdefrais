<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <?php use services\Config;

    include 'elements/styles.php';
    include 'elements/scripts.php'; ?>
</head>
<body>
<?php include 'elements/header.php'; ?>
<div class="container">
    <h1>Gestion des utilisateurs</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?= $_SESSION['success']; ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Tableau des utilisateurs -->
    <h2>Liste des utilisateurs</h2>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Code Analytique</th>
            <th>Code Tiers</th>
            <th>Nom</th>
            <th>Roles</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($utilisateurs as $utilisateur): ?>
            <tr data-id="<?= $utilisateur['id'] ?>">
                <td><?= is_array($utilisateur['codeAnalytique']) ? htmlspecialchars(implode(', ', $utilisateur['codeAnalytique'])) : htmlspecialchars($utilisateur['codeAnalytique'] ?? '') ?></td>
                <td><?= htmlspecialchars($utilisateur['codeTiers'] ?? '') ?></td>
                <td><?= htmlspecialchars($utilisateur['name']) ?></td>
                <td>
                    <?php foreach ($utilisateur['groupes'] as $groupe): ?>
                        <div class="d-flex align-items-center mb-1">
                            <span class="badge bg-secondary me-2"
                                  data-group-id="<?= $groupe['id'] ?>"><?= htmlspecialchars($groupe['title']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($utilisateur['groupes'])): ?>
                        <span class="badge bg-secondary">Aucun rôle</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                <td>
                    <div class="d-flex">
                        <form method="post" action="<?= Config::get("APP_URL") ?>gerer-utilisateurs" class="me-2">
                            <input type="hidden" name="user_id" value="<?= $utilisateur['id'] ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <button type="submit" class="btn btn-<?= $utilisateur['block'] ? 'success' : 'danger' ?>">
                                <?= $utilisateur['block'] ? 'Activer' : 'Désactiver' ?>
                            </button>
                        </form>
                        <button type="button" class="btn btn-warning edit-user-btn"
                                data-id="<?= $utilisateur['id'] ?>"
                                data-name="<?= htmlspecialchars($utilisateur['name']) ?>"
                                data-email="<?= htmlspecialchars($utilisateur['email']) ?>"
                                data-code-tiers="<?= htmlspecialchars($utilisateur['codeTiers'] ?? '') ?>"
                                data-codeAnalytique="<?php
                                if (is_array($utilisateur['codeAnalytique'])) {
                                    if (isset($utilisateur['codeAnalytique']['value'])) {
                                        echo htmlspecialchars($utilisateur['codeAnalytique']['value']);
                                    } else {
                                        echo htmlspecialchars(implode(', ', $utilisateur['codeAnalytique']));
                                    }
                                } else {
                                    echo htmlspecialchars($utilisateur['codeAnalytique'] ?? '');
                                }
                                ?>">
                            Modifier
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modal Ajout Type de Dépense -->
    <div class="modal fade" id="addTypeDepenseModal" tabindex="-1" aria-labelledby="addTypeDepenseModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="<?= Config::get("APP_URL") ?>gerer-utilisateurs">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTypeDepenseModalLabel">Ajouter un type de dépense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_type_depense">
                        <div class="mb-3">
                            <label for="libelle_type_depense" class="form-label">Libellé</label>
                            <input type="text" class="form-control" id="libelle_type_depense"
                                   name="libelle_type_depense" required>
                        </div>
                        <div class="mb-3">
                            <label for="compte_comptable" class="form-label">Compte comptable</label>
                            <input type="text" class="form-control" id="compte_comptable" name="compte_comptable"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Utilisateur -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Ajouter un utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= Config::get("APP_URL") ?>gerer-utilisateurs">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="codeAnalytique" class="form-label">code Analytique</label>
                                <input type="text" class="form-control" id="codeAnalytique" name="codeAnalytique">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="code_tiers" class="form-label">Code Tiers</label>
                                <input type="text" class="form-control" id="code_tiers" name="code_tiers" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Confirmez le mot de passe</label>
                                <input type="password" class="form-control" id="password_confirm"
                                       name="password_confirm" required>
                            </div>
                        </div>
                        <!-- Group selection for Add User Modal -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Groupes</label>
                                <div class="border p-3 rounded">
                                    <?php
                                    $allGroupes = (new \services\Groupe())->getAllGroupes();
                                    foreach ($allGroupes as $groupe): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="groupes[]"
                                                   value="<?= $groupe['id'] ?>" id="groupe_<?= $groupe['id'] ?>">
                                            <label class="form-check-label" for="groupe_<?= $groupe['id'] ?>">
                                                <?= htmlspecialchars($groupe['title']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter l'utilisateur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modification Utilisateur -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= Config::get("APP_URL") ?>gerer-utilisateurs">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="edit_name" name="edit_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_codeAnalytique" class="form-label">codeAnalytique</label>
                                <input type="text" class="form-control" id="edit_codeAnalytique"
                                       name="edit_codeAnalytique">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_code_tiers" class="form-label">Code Tiers</label>
                                <input type="text" class="form-control" id="edit_code_tiers" name="edit_code_tiers"
                                       required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_password" class="form-label">Nouveau mot de passe (laisser vide pour
                                    conserver l'ancien)</label>
                                <input type="password" class="form-control" id="edit_password" name="edit_password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_password_confirm" class="form-label">Confirmez le nouveau mot de
                                    passe</label>
                                <input type="password" class="form-control" id="edit_password_confirm"
                                       name="edit_password_confirm">
                            </div>
                        </div>
                        <!-- Group selection for Edit User Modal -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Groupes</label>
                                <div class="border p-3 rounded">
                                    <?php
                                    $allGroupes = (new \services\Groupe())->getAllGroupes();
                                    foreach ($allGroupes as $groupe): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="edit_groupes[]"
                                                   value="<?= $groupe['id'] ?>" id="edit_groupe_<?= $groupe['id'] ?>">
                                            <label class="form-check-label" for="edit_groupe_<?= $groupe['id'] ?>">
                                                <?= htmlspecialchars($groupe['title']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.edit-user-btn');

        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                // Récupérer les données de l'utilisateur depuis les attributs data-*
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const email = this.getAttribute('data-email');
                const codeAnalytique = this.getAttribute('data-codeAnalytique');
                const codeTiers = this.getAttribute('data-code-tiers');

                // Remplir le formulaire avec ces données
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_codeAnalytique').value = codeAnalytique || '';
                document.getElementById('edit_code_tiers').value = codeTiers || '';

                // Reset password fields
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_password_confirm').value = '';

                // Reset all group checkboxes first
                document.querySelectorAll('#editUserModal input[name="edit_groupes[]"]')
                    .forEach(checkbox => checkbox.checked = false);

                // Get the user's row by ID
                const userRow = document.querySelector(`tr[data-id="${id}"]`);

                if (userRow) {
                    // Find all badges in this row
                    const badges = userRow.querySelectorAll('.badge');

                    // For each badge, get the text and find the matching checkbox
                    badges.forEach(badge => {
                        if (badge.textContent.trim() !== 'Aucun rôle') {
                            const groupTitle = badge.textContent.trim();

                            // Find checkboxes by looking at their labels
                            document.querySelectorAll('#editUserModal .form-check-label').forEach(label => {
                                if (label.textContent.trim() === groupTitle) {
                                    // Get the associated checkbox and check it
                                    const checkboxId = label.getAttribute('for');
                                    const checkbox = document.getElementById(checkboxId);
                                    if (checkbox) {
                                        checkbox.checked = true;
                                    }
                                }
                            });
                        }
                    });
                }

                // Ouvrir la modal
                const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            });
        });

        // Form validation for password confirmation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function (event) {
                // For add user form
                const password = form.querySelector('input[name="password"]');
                const passwordConfirm = form.querySelector('input[name="password_confirm"]');

                if (password && passwordConfirm && password.value !== passwordConfirm.value) {
                    event.preventDefault();
                    alert("Les mots de passe ne correspondent pas");
                    return false;
                }

                // For edit user form
                const editPassword = form.querySelector('input[name="edit_password"]');
                const editPasswordConfirm = form.querySelector('input[name="edit_password_confirm"]');

                if (editPassword && editPasswordConfirm &&
                    editPassword.value !== '' &&
                    editPassword.value !== editPasswordConfirm.value) {
                    event.preventDefault();
                    alert("Les mots de passe ne correspondent pas");
                    return false;
                }
            });
        });

        // Toggle checkboxes in group sections
        const groupSections = document.querySelectorAll('.border.p-3.rounded');
        groupSections.forEach(section => {
            const selectAllBtn = document.createElement('button');
            selectAllBtn.type = 'button';
            selectAllBtn.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'mb-2');
            selectAllBtn.textContent = 'Tout sélectionner';

            const deselectAllBtn = document.createElement('button');
            deselectAllBtn.type = 'button';
            deselectAllBtn.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'mb-2', 'ms-2');
            deselectAllBtn.textContent = 'Tout désélectionner';

            section.prepend(deselectAllBtn);
            section.prepend(selectAllBtn);

            selectAllBtn.addEventListener('click', () => {
                section.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
            });

            deselectAllBtn.addEventListener('click', () => {
                section.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            });
        });
    });
</script>
</body>
</html>