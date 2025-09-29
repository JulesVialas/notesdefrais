<?php

namespace controllers;

use services\Config;
use services\NoteDeFrais;
use services\Ticket;
use services\TypeDepense;
use services\Utilisateur;

/**
 * Contrôleur de modification des notes de frais
 *
 * Cette classe gère le processus de modification d'une note de frais existante,
 * y compris l'ajout et la suppression de tickets, ainsi que la soumission finale
 * après modifications.
 */
class ModifierNoteDeFraisController
{
    /** @var NoteDeFrais Service de gestion des notes de frais */
    private NoteDeFrais $noteDeFrais;

    /** @var Ticket Service de gestion des tickets */
    private Ticket $ticket;

    /** @var TypeDepense Service de gestion des types de dépenses */
    private TypeDepense $typeDepenses;

    private Utilisateur $utilisateur;

    public function __construct()
    {
        $this->noteDeFrais = new NoteDeFrais();
        $this->ticket = new Ticket();
        $this->typeDepenses = new TypeDepense();
        // Add this line:
        $this->utilisateur = new Utilisateur();
    }

    /**
     * Traite les requêtes POST pour la modification de note de frais
     *
     * Gère les différentes actions possibles : ajout de ticket, suppression de ticket
     * et soumission finale de la note de frais modifiée.
     *
     * @param int $id Identifiant de la note de frais à modifier
     * @return void
     */
    public function post($id)
    {
        $noteDeFrais = $this->noteDeFrais->getNoteDeFraisById((int)$id);
        if (!$noteDeFrais || ($noteDeFrais['Statut'] !== 'En cours de saisie' && $noteDeFrais['Statut'] !== 'Refusée')) {
            header('Location: ' . Config::get("APP_URL"));
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'creer_ticket') {
            $this->ticket->creerTicket($_POST);
            header("Location: " . Config::get("APP_URL") . "modifier-note-frais/$id");
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'supprimer_ticket') {
            $this->ticket->supprimerTicket($_POST['ticket_index']);
            header("Location: " . Config::get("APP_URL") . "modifier-note-frais/$id");
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'sauvegarder_note') {
            $this->noteDeFrais->sauvegarderNoteDeFraisEnCoursDeSaisie();
            header('Location: ' . Config::get("APP_URL") . '?tab=enCours');
            exit;
        }

        if (isset($_POST['action']) && ($_POST['action'] === 'envoyer_note')) {
            $this->noteDeFrais->mettreAJourNoteDeFrais($_SESSION['temp_note_frais']['Identifiant']);
            header('Location: ' . Config::get("APP_URL") . '?tab=enCours');
            exit;
        }

        // Nouvelle condition pour gérer l'annulation
        if (isset($_POST['action']) && $_POST['action'] === 'annuler') {
            // Nettoyer les données temporaires en session
            if (isset($_SESSION['temp_tickets'])) {
                unset($_SESSION['temp_tickets']);
            }
            if (isset($_SESSION['temp_note_frais'])) {
                unset($_SESSION['temp_note_frais']);
            }

            header('Location: ' . Config::get("APP_URL") . 'voir-note-frais/' . $id);
            exit;
        }

        header("Location: " . Config::get("APP_URL") . "modifier-note-frais/$id");
        exit;
    }

    public function get($id)
    {
        $noteDeFrais = $this->noteDeFrais->getNoteDeFraisById((int)$id);
        if (!$noteDeFrais || ($noteDeFrais['Statut'] !== 'En cours de saisie' && $noteDeFrais['Statut'] !== 'Refusée')) {
            header('Location: ' . Config::get("APP_URL"));
            exit;
        }
        if (!isset($_SESSION['temp_note_frais']) || $_SESSION['temp_note_frais']['Identifiant'] != $id) {
            $_SESSION['temp_note_frais'] = $noteDeFrais;
            $_SESSION['temp_tickets'] = $this->ticket->getTicketsByNoteDeFrais((int)$id);
        }

        $userId = $_SESSION['id'];
        $matricule = $this->utilisateur->getCodeAnalytique($userId);
        $typesDepenses = $this->typeDepenses->getTypesDepenses();
        $formData = $_SESSION['form_data'] ?? null;
        require __DIR__ . '/../views/modifier-note-de-frais.php';
    }
}