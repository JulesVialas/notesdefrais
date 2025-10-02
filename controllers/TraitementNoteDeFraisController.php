<?php

namespace controllers;

use services\MotifRejet;
use services\NoteDeFrais;
use services\Ticket;
use services\Utilisateur;
use services\Mail;
use services\TypeDepense;
use services\Config;

/**
 * Contrôleur pour la gestion d'une note de frais spécifique
 */
class TraitementNoteDeFraisController
{
    private NoteDeFrais $noteDeFrais;
    private Ticket $ticket;
    private MotifRejet $motifRejet;
    private Utilisateur $utilisateur;
    private Mail $mailService;
    private TypeDepense $typeDepense;
    private string $viewPath;

    public function __construct($mode = 'verification')
    {
        $this->noteDeFrais = new NoteDeFrais();
        $this->ticket = new Ticket();
        $this->motifRejet = new MotifRejet();
        $this->utilisateur = new Utilisateur();
        $this->mailService = new Mail();
        $this->typeDepense = new TypeDepense();
        $this->viewPath = __DIR__ . '/../views/traitement-note-de-frais.php';
    }

    /**
     * Traite les actions sur les tickets d'une note de frais
     *
     * @param int $id Identifiant de la note de frais
     */
    public function post($id)
    {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'valider_tickets_multiples':
                if (isset($_POST['ticket_ids']) && is_string($_POST['ticket_ids'])) {
                    $ticketIds = explode(',', $_POST['ticket_ids']);
                    $success = true;

                    foreach ($ticketIds as $ticketId) {
                        $ticketId = (int)$ticketId;
                        if (!$this->ticket->changerStatutTicket($ticketId, 'Validé')) {
                            $success = false;
                        }
                    }

                    // Update the note de frais status after processing all tickets
                    $this->noteDeFrais->mettreAJourStatutSelonTickets($id);

                    if ($success) {
                        $_SESSION['flash_success'] = "Les tickets sélectionnés ont été validés.";
                    } else {
                        $_SESSION['flash_error'] = "Une erreur s'est produite lors de la validation.";
                    }
                }

                // Redirect back to the same page
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();

            case 'valider_ticket':
                $ticketId = $_POST['ticket_id'] ?? 0;

                // Update the ticket status
                $this->ticket->changerStatutTicket($ticketId, 'Validé');

                // Update the note de frais status
                $tousTraites = $this->noteDeFrais->mettreAJourStatutSelonTickets($id);

                if ($tousTraites) {
                    header('Location: /notesdefrais');
                    exit(); // Ensure script stops here
                }

                header("Location: /verifier-ticket/$id");
                exit(); // Ensure script stops here

            case 'refuser_ticket':
                $ticketId = $_POST['ticket_id'] ?? 0;
                $motif = $_POST['motif'] ?? '';

                // Récupérer le statut actuel de la note de frais avant le changement
                $statutActuel = $this->noteDeFrais->getStatus($id);

                // Update the ticket status with a refusal
                $this->ticket->changerStatutTicket($ticketId, 'Refusé', $motif);

                // Update the note de frais status
                $this->noteDeFrais->mettreAJourStatutSelonTickets($id);

                // Vérifier si le statut a changé vers "Refusée" et envoyer une notification
                $nouveauStatut = $this->noteDeFrais->getStatus($id);
                if ($statutActuel !== 'Refusée' && $nouveauStatut === 'Refusée') {
                    $this->envoyerNotificationRefus($id);
                }

                header('Location: /notesdefrais');
                exit(); // Ensure script stops here
        }
    }

    /**
     * Envoie une notification par email en cas de refus de note de frais
     */
    private function envoyerNotificationRefus(int $noteDeFraisId): void
    {
        $utilisateur = $this->utilisateur->getUserByNoteDeFrais($noteDeFraisId);

        if ($utilisateur && !empty($utilisateur['email'])) {
            $this->mailService->envoyerNotificationRefus(
                $utilisateur['email'],
                $utilisateur['name']
            );
        }
    }

    /**
     * Affiche la page de traitement d'une note de frais
     *
     * @param int $id Identifiant de la note de frais
     * @param string|null $message Message à afficher
     * @param string|null $messageType Type de message (success, danger, warning, info)
     */
    public function get($id, $message = null, $messageType = null)
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        $noteDeFraisDetails = $this->noteDeFrais->getNoteDeFraisById($id);
        $tickets = $this->ticket->getTicketsByNoteDeFrais($id);
        $motifsRejet = $this->motifRejet->getMotifsRejet();
        $typesDepenses = $this->typeDepense->getTypesDepenses();

        require $this->viewPath;
    }
}