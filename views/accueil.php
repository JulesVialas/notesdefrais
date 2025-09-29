<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord</title>
    <?php use services\Config;

    include 'elements/styles.php'; ?>
</head>
<body>
<div class="container-fluid">
    <?php include 'elements/header.php'; ?>
    <div class="content">
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= $_SESSION['flash_success'] ?>
                <?php unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $_SESSION['flash_error'] ?>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <a href="<?= Config::get("APP_URL") ?>creer-note-frais"
                   class="btn btn-primary btn-m<?= ($nbNotesCeMois >= 4) ? ' disabled' : '' ?>"
                    <?= ($nbNotesCeMois >= 4) ? 'aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:0.65;"' : '' ?>>
                    <i class="fas fa-plus-circle me-2"></i>Demander le remboursement d'une note de frais
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Filtres</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary active" onclick="showTab('enCours')">
                                <i class="fas fa-spinner me-2"></i>Mes notes de frais en cours
                                (<?php echo count($notesDeFraisEnCours); ?>)
                            </button>
                            <button class="btn btn-success" onclick="showTab('terminees')">
                                <i class="fas fa-check-circle me-2"></i>Mes notes de frais terminées
                                (<?php echo count($notesDeFraisTerminees); ?>)
                            </button>
                            <button class="btn btn-danger" onclick="showTab('refusees')">
                                <i class="fas fa-times-circle me-2"></i>Mes notes de frais refusées
                                (<?php echo count($notesDeFraisRefusees); ?>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="tableHeading">Liste des notes de frais</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead class="table-light text-center">
                                <tr>
                                    <th>Id</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Ttc</th>
                                    <th>Tva</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody id="notesDeFraisTable" class="text-center">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer cette note de frais ? Cette action est irréversible et supprimera
                également tous les tickets associés.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="post" action="<?= Config::get("APP_URL") ?>" style="display: inline;">
                    <input type="hidden" name="action" value="supprimer_note">
                    <input type="hidden" name="note_id" id="deleteNoteId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.appBaseUrl = '<?= Config::get("APP_URL") ?>';

    notesDeFrais = {
        'enCours': <?= json_encode(array_values($notesDeFraisEnCours ?? [])) ?>,
        'terminees': <?= json_encode(array_values($notesDeFraisTerminees ?? [])) ?>,
        'refusees': <?= json_encode(array_values($notesDeFraisRefusees ?? [])) ?>
    };

    function confirmDelete(noteId) {
        document.getElementById('deleteNoteId').value = noteId;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>
<?php include 'elements/scripts.php'; ?>
<script>
    // Override the showTab function to update the heading and add delete buttons
    const originalShowTab = window.showTab;
    window.showTab = function (tabName) {
        const headingMap = {
            'enCours': 'Liste des notes de frais en cours',
            'terminees': 'Liste des notes de frais terminées',
            'refusees': 'Liste des notes de frais refusées'
        };
        document.getElementById('tableHeading').textContent = headingMap[tabName];

        // Call the original function
        originalShowTab(tabName);

        // Add delete buttons for notes "En cours de saisie"
        if (tabName === 'enCours') {
            const rows = document.querySelectorAll('#notesDeFraisTable tr');
            rows.forEach(row => {
                const statutCell = row.cells[2];
                if (statutCell && statutCell.textContent.trim() === 'En cours de saisie') {
                    const actionsCell = row.cells[5];
                    const noteId = row.cells[0].textContent.trim();

                    // Add delete button
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'btn btn-sm btn-danger ms-1';
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.onclick = () => confirmDelete(noteId);
                    deleteBtn.title = 'Supprimer la note de frais';

                    actionsCell.appendChild(deleteBtn);
                }
            });
        }
    };
</script>
</body>
</html>