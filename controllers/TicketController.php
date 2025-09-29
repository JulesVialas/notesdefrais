<?php

namespace controllers;

use services\NoteDeFrais;
use services\Ticket;

/**
 * Contrôleur de visualisation des tickets de notes de frais
 *
 * Cette classe gère l'affichage détaillé d'une note de frais et de ses tickets associés,
 * permettant à l'utilisateur de consulter l'ensemble des justificatifs d'une note de frais.
 */
class TicketController
{
    /** @var NoteDeFrais Service de gestion des notes de frais */
    private NoteDeFrais $noteDeFrais;

    /** @var Ticket Service de gestion des tickets */
    private Ticket $ticket;

    /**
     * Constructeur du contrôleur de tickets
     *
     * Initialise les services nécessaires pour accéder aux notes de frais
     * et aux tickets associés.
     */
    public function __construct()
    {
        $this->noteDeFrais = new NoteDeFrais();
        $this->ticket = new Ticket();
    }

    /**
     * Affiche le détail d'une note de frais et ses tickets associés
     *
     * Vérifie l'existence de la note de frais demandée et charge
     * tous les tickets qui y sont rattachés.
     *
     * @param int $id Identifiant de la note de frais à consulter
     * @return void
     */
    public function get($id)
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        $noteDeFraisDetails = $this->noteDeFrais->getNoteDeFraisById((int)$id);
        if (!$noteDeFraisDetails) {
            header('Location: /');
            exit;
        }
        $tickets = $this->ticket->getTicketsByNoteDeFrais((int)$id);
        require __DIR__ . '/../views/ticket.php';
    }
}