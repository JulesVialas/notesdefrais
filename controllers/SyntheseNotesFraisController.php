<?php

namespace controllers;

use services\Groupe;
use services\Utilisateur;
use services\NoteDeFrais;
use services\Ticket;
use services\TypeDepense;
use services\Pdf;

class SyntheseNotesFraisController
{
    private $utilisateurService;
    private $groupes;

    public function __construct()
    {
        $this->utilisateurService = new Utilisateur();
        $this->groupes = new Groupe();
    }

    public function get()
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        $utilisateurs = $this->utilisateurService->getAllUtilisateurs();

        // Récupérer les groupes pour chaque utilisateur
        foreach ($utilisateurs as &$utilisateur) {
            $utilisateur['groupes'] = $this->groupes->getGroupesById($utilisateur['id']);
            $utilisateur['codeAnalytique'] = $this->utilisateurService->getCodeAnalytique($utilisateur['id']);
            $utilisateur['codeTiers'] = $this->utilisateurService->getCodeTiers($utilisateur['id']);
        }
        unset($utilisateur);

        require __DIR__ . '/../views/synthese-notes-de-frais.php';
    }

    public function post()
    {
        if (!isset($_POST['user_id'], $_POST['periode'])) {
            http_response_code(400);
            exit('Missing parameters');
        }

        $userId = (int)$_POST['user_id'];
        $periode = $_POST['periode'];
        $matricule = $_POST['code_tiers'];

        if (preg_match('#^(\d{2})/(\d{4})$#', $periode, $matches)) {
            $periode = $matches[2] . '-' . $matches[1];
        }

        $utilisateurService = new Utilisateur();
        $noteDeFraisService = new NoteDeFrais();
        $ticketService = new Ticket();
        $typeDepenseService = new TypeDepense();

        $libelleUtilisateur = $utilisateurService->getLibelleById($userId);
        $typesDepense = $typeDepenseService->getTypesDepenses();

        // Récupérer toutes les notes de frais de l'utilisateur
        $notes = $noteDeFraisService->getNotesDeFraisByUtilisateur($libelleUtilisateur);

        if (empty($notes)) {
            echo "Aucune note de frais trouvée pour cet utilisateur.";
            exit;
        }

        // Récupérer tous les tickets de toutes les notes
        $allTickets = [];
        foreach ($notes as $note) {
            $noteId = $note['Identifiant'];
            $tickets = $ticketService->getTicketsByNoteDeFrais($noteId);
            $allTickets = array_merge($allTickets, array_values($tickets));
        }

        // Filtrer les tickets par DateJustificatif sur la période demandée
        $filteredTickets = array_filter($allTickets, function($ticket) use ($periode) {
            return isset($ticket['DateJustificatif']) && strpos($ticket['DateJustificatif'], $periode) === 0;
        });

        if (empty($filteredTickets)) {
            echo "Aucun ticket trouvé pour cette période.";
            exit;
        }

        // Préparer les données pour le PDF
        $noteDeFrais = [
            'LibelleUtilisateur' => $libelleUtilisateur
        ];

        // Générer le PDF
        $pdfService = new Pdf();
        $pdfService->creerPDF($noteDeFrais, $filteredTickets, $matricule, $periode, $typesDepense);
    }
}