<?php use services\Config; ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier une note de frais</title>
    <?php include 'elements/styles.php'; ?>
</head>
<body>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <?php unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>
<div class="container-fluid">
    <?php include 'elements/header.php'; ?>
    <div class="content">
        <div class="row mb-4">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <h4 class="mb-0">
                    <a href="<?= Config::get("APP_URL") ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    Modifier la note de frais
                </h4>
            </div>
        </div>
        <?php
        // Ensure $noteId is set
        if (!isset($noteId)) {
            $noteId = isset($_SESSION['temp_note_frais']['Identifiant']) ? $_SESSION['temp_note_frais']['Identifiant'] : '';
        }
        $formAction = Config::get("APP_URL") . "modifier-note-frais/" . $noteId;
        $ticketAction = "creer_ticket";
        $deleteFormAction = Config::get("APP_URL") . "modifier-note-frais/" . $noteId;
        $deleteAction = "supprimer_ticket";
        $ticketIdentifier = "ticket_index";

        include 'elements/informations-note-de-frais.php';
        include 'elements/formulaire-ticket.php';
        include 'elements/table-tickets.php';
        include 'elements/scripts.php';
        ?>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <form method="post" action="<?= Config::get("APP_URL") ?>modifier-note-frais/<?= $noteId ?>" class="me-2">
            <input type="hidden" name="action" value="annuler">
            <button type="submit" class="btn btn-secondary me-2">
                <i class="fas fa-times me-2"></i>Annuler
            </button>
        </form>
        <?php if (!empty($_SESSION['temp_tickets'])): ?>
            <form method="post" action="<?= Config::get("APP_URL") ?>modifier-note-frais/<?= $noteId ?>" class="me-2">
                <input type="hidden" name="action" value="sauvegarder_note">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Sauvegarder la note de frais
                </button>
            </form>
        <?php endif; ?>
        <?php if (!empty($_SESSION['temp_tickets'])): ?>
            <form method="post" action="<?= Config::get("APP_URL") ?>modifier-note-frais/<?= $noteId ?>">
                <input type="hidden" name="action" value="envoyer_note">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle me-2"></i>Soumettre la note de frais
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
