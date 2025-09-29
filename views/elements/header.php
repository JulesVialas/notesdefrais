<?php

use services\Config;
use services\NoteDeFrais;

$page_actuelle = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$noteDeFrais = new NoteDeFrais();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($page_actuelle == '<?= Config::get("APP_URL") ?>') ? 'active' : ''; ?>"
                       href="<?= Config::get("APP_URL") ?>">
                        <i class="fas fa-chart-line me-1"></i>
                        Mes notes de frais
                    </a>
                </li>
                <?php if (in_array(10, $_SESSION['Role'])) : ?>
                    <?php $countToValidate = count($noteDeFrais->getNotesDeFraisByStatut($_SESSION['LibelleUtilisateur'], 'En cours de validation', true)); ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page_actuelle == Config::get("APP_URL") . 'valider-notes-frais') ? 'active' : ''; ?>"
                           href="<?= Config::get("APP_URL") ?>valider-notes-frais">
                            <i class="fas fa-check-double me-1"></i>
                            <span class="<?= $countToValidate > 0 ? 'text-primary fw-bold' : '' ?>">Je dois valider <?= $countToValidate ?> note(s) de frais.</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array(11, $_SESSION['Role'])) : ?>
                    <?php $countToProcess = count($noteDeFrais->getNotesDeFraisByStatut($_SESSION['LibelleUtilisateur'], 'En cours de traitement comptable', true)); ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page_actuelle == Config::get("APP_URL") . 'traitement-comptable') ? 'active' : ''; ?>"
                           href="<?= Config::get("APP_URL") ?>traitement-comptable">
                            <i class="fas fa-check-double me-1"></i>
                            <span class="<?= $countToProcess > 0 ? 'text-primary fw-bold' : '' ?>">Je dois traiter <?= $countToProcess ?> note(s) de frais.</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array(11, $_SESSION['Role'])) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page_actuelle == Config::get("APP_URL") . 'gerer-types-depense') ? 'active' : ''; ?>"
                           href="<?= Config::get("APP_URL") ?>gerer-types-depense">
                            <i class="fas fa-list-alt me-1"></i>
                            Je peux gérer les types de dépenses
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array(11, $_SESSION['Role'])) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page_actuelle == Config::get("APP_URL") . 'notes-frais-archives') ? 'active' : ''; ?>"
                           href="<?= Config::get("APP_URL") ?>notes-frais-archives">
                            <i class="fas fa-clipboard-check me-1"></i>
                            Je peux voir les notes de frais archivées
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array(11, $_SESSION['Role'])) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page_actuelle == Config::get("APP_URL") . 'notes-frais-archives') ? 'active' : ''; ?>"
                           href="<?= Config::get("APP_URL") ?>synthese-notes-frais">
                            <i class="fas fa-clipboard-check me-1"></i>
                            Je peux exporter les synthèses des notes de frais
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array(8, $_SESSION['Role'])) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page_actuelle == Config::get("APP_URL") . 'gerer-utilisateurs') ? 'active' : ''; ?>"
                           href="<?= Config::get("APP_URL") ?>gerer-utilisateurs">
                            <i class="fas fa-stamp me-1"></i>
                            Paramètres
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>