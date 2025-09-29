<?php

namespace controllers;

use services\MotifRejet;
use services\NoteDeFrais;
use services\Ticket;
use services\TypeDepense;
use services\Utilisateur;

/**
 * Contrôleur pour la gestion des vérifications et validations de notes de frais
 */
class TraitementNotesDeFraisController
{
    private NoteDeFrais $noteDeFrais;
    private MotifRejet $motifRejet;
    private string $mode;
    private string $statut;
    private string $viewPath;
    private Ticket $ticket;
    private TypeDepense $typeDepense;
    private Utilisateur $utilisateurService;

    public function __construct($mode = 'verification')
    {
        // Code existant...
        $this->utilisateurService = new Utilisateur();
        $this->noteDeFrais = new NoteDeFrais();
        $this->ticket = new Ticket();
        $this->motifRejet = new MotifRejet();
        $this->typeDepense = new TypeDepense();
        $this->mode = $mode;

        // Configuration du statut et du chemin de vue en fonction du mode
        if ($this->mode === 'validation') {
            $this->statut = 'En cours de validation';
            $this->viewPath = __DIR__ . '/../views/traitement-notes-de-frais.php';
        } else if ($this->mode === 'verification') {
            $this->statut = 'En cours de vérification';
            $this->viewPath = __DIR__ . '/../views/traitement-notes-de-frais.php';
        } else if ($this->mode === 'comptable') {
            $this->statut = 'En cours de traitement comptable';
            $this->viewPath = __DIR__ . '/../views/traitement-notes-de-frais.php';
        } else {
            throw new \InvalidArgumentException('Mode non valide');
        }
    }

    /**
     * Traite la validation ou le refus d'une note de frais
     */
    public function post()
    {
        // Handle bulk export for accountants
        if (isset($_POST['action']) && $_POST['action'] === 'export_bulk' && isset($_POST['selected_ids'])) {
            $selectedIds = explode(',', $_POST['selected_ids']);
            $notesAExporter = [];

            foreach ($selectedIds as $id) {
                $id = intval($id);
                if ($id > 0) {
                    $note = $this->noteDeFrais->getNoteDeFraisById($id);
                    if ($note && $note['Statut'] === "En cours de traitement comptable") {
                        $notesAExporter[] = $note;

                        // Mark as exported by changing status
                        $this->noteDeFrais->changerStatutNoteDeFrais($id, 'Terminée');
                    }
                }
            }

            // If we have notes to export, call the export function
            if (!empty($notesAExporter)) {
                $this->noteDeFrais->exportation($notesAExporter);
                // The exportation method handles the download and exit, so no need for redirect
            } else {
                $_SESSION['flash_warning'] = "Aucune note de frais éligible à l'exportation.";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        } // Handle bulk validation
        elseif (isset($_POST['action']) && $_POST['action'] === 'validate_bulk' && isset($_POST['selected_ids'])) {
            $selectedIds = explode(',', $_POST['selected_ids']);
            foreach ($selectedIds as $id) {
                $id = intval($id); // Ensure ID is an integer
                if ($id > 0) {
                    $statutActuel = $this->noteDeFrais->getStatus($id);
                    // Determine the next status in the workflow
                    $nouveauStatut = $this->noteDeFrais->getNextStatus($statutActuel);

                    // Apply the status change
                    $this->noteDeFrais->changerStatutNoteDeFrais($id, $nouveauStatut);
                }
            }

            $_SESSION['flash_success'] = count($selectedIds) . " note(s) de frais traitée(s) avec succès.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        $this->get();
    }

    /**
     * Affiche la liste des notes de frais à vérifier ou valider
     */
    public function get()
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        $notesDeFrais = $this->noteDeFrais->getNotesDeFraisByStatut($_SESSION['LibelleUtilisateur'], $this->statut, true);
        $motifsRejet = $this->motifRejet->getMotifsRejet();
        $mode = $this->mode; // Transmettre le mode à la vue
        require $this->viewPath;
    }
}