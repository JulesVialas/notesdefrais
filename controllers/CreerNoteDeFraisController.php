<?php

namespace controllers;

use services\Config;
use services\NoteDeFrais;
use services\Ticket;
use services\TypeDepense;
use services\Utilisateur;

/**
 * Contrôleur de création des notes de frais
 *
 * Cette classe gère le processus de création d'une nouvelle note de frais,
 * y compris l'ajout de tickets, l'upload de justificatifs et la soumission finale.
 */
class CreerNoteDeFraisController
{
    /** @var NoteDeFrais Service de gestion des notes de frais */
    private NoteDeFrais $noteDeFrais;

    /** @var Ticket Service de gestion des tickets */
    private Ticket $ticket;

    /** @var TypeDepense Service de gestion des types de dépenses */
    private TypeDepense $typeDepenses;

    /** @var Utilisateur Service de gestion des utilisateurs */
    private Utilisateur $Utilisateur;

    /**
     * Constructeur du contrôleur de création de note de frais
     *
     * Initialise les services nécessaires pour la manipulation des notes de frais,
     * des tickets et des types de dépenses.
     */
    public function __construct()
    {
        $this->noteDeFrais = new NoteDeFrais();
        $this->ticket = new Ticket();
        $this->typeDepenses = new TypeDepense();
        $this->Utilisateur = new Utilisateur();
    }

    /**
     * Traite les requêtes POST pour la création de note de frais
     *
     * Gère les différentes actions possibles : ajout de ticket, suppression de ticket
     * et soumission finale de la note de frais.
     *
     * @return void
     */
    public function post()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'creer_ticket') {
            $this->ticket->creerTicket($_POST);
            header('Location: ' . Config::get("APP_URL") . 'creer-note-frais');
            exit;
        }
        if (isset($_POST['action']) && $_POST['action'] === 'supprimer_ticket') {
            $this->ticket->supprimerTicket($_POST['ticket_index']);
            header('Location: ' . Config::get("APP_URL") . 'creer-note-frais');
            exit;
        }
        if (isset($_POST['action']) && $_POST['action'] === 'sauvegarder_note') {
            $this->noteDeFrais->sauvegarderNoteDeFraisEnCoursDeSaisie();
            header('Location: ' . Config::get("APP_URL") . '?tab=enCours');
            exit;
        }
        if (isset($_POST['action']) && $_POST['action'] === 'envoyer_note') {
            $this->noteDeFrais->envoyerNoteDeFrais();
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

            header('Location: ' . Config::get("APP_URL"));
            exit;
        }

        header('Location: ' . Config::get("APP_URL") . 'creer-note-frais');
        exit;
    }

    /**
     * Affiche le formulaire de création d'une note de frais
     *
     * Initialise une note de frais temporaire en session si nécessaire et
     * prépare les données pour l'affichage du formulaire.
     *
     * @return void
     */
    public function get()
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        $typesDepenses = $this->typeDepenses->getTypesDepenses();

        // Get current user ID (assuming you have it in session)
        $userId = $_SESSION['id']; // Adjust based on how you store the current user ID
        $matricule = $this->Utilisateur->getCodeAnalytique($userId);

        require __DIR__ . '/../views/creer-note-de-frais.php';
    }
}