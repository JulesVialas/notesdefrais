<?php

namespace services;

/**
 * Class Ticket
 * Gère les opérations liées aux tickets de notes de frais
 *
 * Cette classe permet de gérer l'ensemble des opérations CRUD sur les tickets,
 * incluant la création, la suppression, et la gestion des fichiers justificatifs.
 */
class Ticket
{
    /** @var array Liste des tickets stockés en mémoire */
    private array $tickets;

    /** @var TypeDepense Instance de la classe TypeDepense pour gérer les types de dépenses */
    private TypeDepense $typeDepense;

    /**
     * Constructeur de la classe
     * Initialise la liste des tickets depuis la base de données et l'instance TypeDepense
     */
    public function __construct()
    {
        $this->typeDepense = new TypeDepense();
        $pdo = Database::getPDO();
        $sql = "SELECT * FROM Ticket";
        $req = $pdo->prepare($sql);
        $req->execute();
        $this->tickets = $req->fetchAll();
    }

    /**
     * Crée un nouveau ticket temporaire
     *
     * Vérifie les données du ticket, gère l'upload du justificatif et met à jour
     * les totaux de la note de frais temporaire en session.
     *
     * @param array $ticketData Données du ticket à créer
     * @return bool True si la création réussit, False sinon
     */
    public function creerTicket(array $ticketData): bool
    {
        if (empty($ticketData['date_justificatif']) || empty($ticketData['type_depense']) || empty($ticketData['total_ttc'])) {
            $_SESSION['flash_error'] = "Tous les champs sont obligatoires";
            return false;
        }

        // Gestion du justificatif
        if (isset($_FILES['justificatif'])) {
            $uploadResult = $this->handleFileUpload($_FILES['justificatif']);
            if (!$uploadResult['success']) {
                $_SESSION['flash_error'] = $uploadResult['message'];
                return false;
            }
            $cheminJustificatif = $uploadResult['path'];
        } else {
            $_SESSION['flash_error'] = "L'upload du justificatif a échoué";
            return false;
        }

        $totalTTC = floatval($ticketData['total_ttc']);
        $typeDepense = $ticketData['type_depense'];

        // Vérifier si ce type de dépense a de la TVA
        $hasTva = $this->typeDepense->hasTva($typeDepense);

        if (!$hasTva) {
            // Pas de TVA pour ce type de dépense
            $totalTVA = 0;
        } else {
            // Logique TVA existante pour les types avec TVA
            if ($typeDepense === 'Carburant') {
                $tauxTVA = isset($ticketData['taux_tva']) ? floatval($ticketData['taux_tva']) : 20.0;
                $totalTVA = $totalTTC * $tauxTVA / (100 + $tauxTVA);
            } else {
                $totalTVA = isset($ticketData['total_tva']) ? floatval($ticketData['total_tva']) : 0;
            }
        }

        $utilistateurService = new Utilisateur();
        $codeTiers = $utilistateurService->getCodeTiers($_SESSION['id']);

        $ticket = [
            'DateJustificatif' => $ticketData['date_justificatif'],
            'CompteComptable' => $this->typeDepense->getCompteComptable($ticketData['type_depense']),
            'TypeDepense' => $typeDepense,
            'NumeroAffaire' => strtoupper(trim($ticketData['numero_affaire'])),
            'TotalTTC' => $totalTTC,
            'TotalTVA' => $totalTVA,
            'Commentaires' => $ticketData['commentaires'],
            'CheminJustificatif' => $cheminJustificatif,
            'CodeTiers' => $codeTiers
        ];

        if (!isset($_SESSION['temp_tickets'])) {
            $_SESSION['temp_tickets'] = [];
        }

        if (!isset($_SESSION['temp_note_frais'])) {
            $_SESSION['temp_note_frais'] = [
                'DateDemande' => date('Y-m-d'),
                'Statut' => 'En cours de saisie',
                'TotalTTC' => 0,
                'TotalTVA' => 0
            ];
        }

        $_SESSION['temp_note_frais']['TotalTTC'] += $totalTTC;
        $_SESSION['temp_note_frais']['TotalTVA'] += $totalTVA;
        $_SESSION['temp_tickets'][] = $ticket;

        return true;
    }

    /**
     * Gère l'upload et le traitement des fichiers justificatifs
     *
     * Vérifie le type de fichier, sa taille, crée le dossier de destination si nécessaire
     * et convertit les images en JPEG avec compression.
     *
     * @param array $file Données du fichier uploadé ($_FILES)
     * @return array Résultat de l'opération avec statut, message et chemin du fichier
     */
    public function handleFileUpload(array $file): array
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $message = "Le fichier dépasse la taille maximale autorisée par PHP";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $message = "Le fichier dépasse la taille maximale autorisée par le formulaire";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = "Le fichier n'a été que partiellement uploadé";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message = "Aucun fichier n'a été uploadé";
                    break;
                default:
                    $message = "Erreur lors de l'upload du fichier";
                    break;
            }
            return ['success' => false, 'message' => $message];
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            error_log("Invalid file type: " . $mimeType);
            return ['success' => false, 'message' => "Type de fichier non autorisé. Formats acceptés : JPEG, JPG, PNG"];
        }

        $maxFileSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxFileSize) {
            return ['success' => false, 'message' => "Le fichier est trop volumineux (maximum 5 Mo)"];
        }

        $uploadDir = 'uploads/justificatifs/';
        if (!file_exists($uploadDir)) {
            error_log("Creating directory: " . $uploadDir);
            $result = mkdir($uploadDir, 0777, true);
            if (!$result) {
                return ['success' => false, 'message' => "Impossible de créer le dossier pour les justificatifs"];
            }
        }

        $fileName = uniqid('justificatif_') . '.jpg';
        $targetPath = $uploadDir . $fileName;
        $webPath = 'uploads/justificatifs/' . $fileName;

        try {
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = imagecreatefromjpeg($file['tmp_name']);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($file['tmp_name']);
                    break;
            }

            if (!$image) {
                return ['success' => false, 'message' => "Impossible de traiter l'image"];
            }

            if ($mimeType === 'image/png') {
                $width = imagesx($image);
                $height = imagesy($image);
                $newImage = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($newImage, 255, 255, 255);
                imagefill($newImage, 0, 0, $white);
                imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
                $image = $newImage;
            }

            imagejpeg($image, $targetPath, 60);
            imagedestroy($image);

            return [
                'success' => true,
                'message' => "Fichier uploadé et converti avec succès",
                'path' => $webPath
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la conversion de l'image: " . $e->getMessage()
            ];
        }
    }

    /**
     * Supprime un ticket par son index ou identifiant
     *
     * Gère la suppression d'un ticket temporaire en session ou permanent en base de données,
     * incluant la suppression du fichier justificatif associé et la mise à jour des totaux.
     *
     * @param int $ticket_index Index du ticket en session ou identifiant en base de données
     */
    public function supprimerTicket(int $ticket_index): void
    {
        if (isset($_SESSION['temp_tickets'][$ticket_index])) {
            $cheminJustificatif = $_SESSION['temp_tickets'][$ticket_index]['CheminJustificatif'];
            if (!empty($cheminJustificatif) && file_exists($cheminJustificatif)) {
                unlink($cheminJustificatif);
            }

            $_SESSION['temp_note_frais']['TotalTTC'] -= $_SESSION['temp_tickets'][$ticket_index]['TotalTTC'];
            $_SESSION['temp_note_frais']['TotalTVA'] -= $_SESSION['temp_tickets'][$ticket_index]['TotalTVA'];

            unset($_SESSION['temp_tickets'][$ticket_index]);
            if (!empty($_SESSION['temp_tickets'])) {
                $_SESSION['temp_tickets'] = array_values($_SESSION['temp_tickets']);
            }
        } else {
            $pdo = Database::getPDO();

            $sql = "SELECT * FROM Ticket WHERE Identifiant = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $ticket_index]);
            $ticket = $stmt->fetch();

            if ($ticket) {
                $cheminJustificatif = $ticket['CheminJustificatif'];
                if (!empty($cheminJustificatif) && file_exists($cheminJustificatif)) {
                    unlink($cheminJustificatif);
                }

                $_SESSION['temp_note_frais']['TotalTTC'] -= $ticket['TotalTTC'];
                $_SESSION['temp_note_frais']['TotalTVA'] -= $ticket['TotalTVA'];

                $sql = "DELETE FROM Ticket WHERE Identifiant = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $ticket_index]);

                $_SESSION['temp_tickets'] = array_filter($_SESSION['temp_tickets'], function ($t) use ($ticket_index) {
                    return !isset($t['Identifiant']) || $t['Identifiant'] != $ticket_index;
                });

                if (!empty($_SESSION['temp_tickets'])) {
                    $_SESSION['temp_tickets'] = array_values($_SESSION['temp_tickets']);
                }
            }
        }
        $this->recalculerTotauxSessionNoteFrais();
    }

    private function recalculerTotauxSessionNoteFrais()
    {
        $totalTTC = 0;
        $totalTVA = 0;
        if (isset($_SESSION['temp_tickets']) && is_array($_SESSION['temp_tickets'])) {
            foreach ($_SESSION['temp_tickets'] as $ticket) {
                $totalTTC += isset($ticket['TotalTTC']) ? floatval($ticket['TotalTTC']) : 0;
                $totalTVA += isset($ticket['TotalTVA']) ? floatval($ticket['TotalTVA']) : 0;
            }
        }
        if (isset($_SESSION['temp_note_frais'])) {
            $_SESSION['temp_note_frais']['TotalTTC'] = $totalTTC;
            $_SESSION['temp_note_frais']['TotalTVA'] = $totalTVA;
        }
    }

    /**
     * Envoie les tickets temporaires vers la base de données
     *
     * @param int $identifiantNoteDeFrais Identifiant de la note de frais associée
     * @return bool True si l'opération réussit, False sinon
     */
    public function envoyerTickets(int $identifiantNoteDeFrais): bool
    {
        if (!isset($_SESSION['temp_tickets']) || empty($_SESSION['temp_tickets'])) {
            return true;
        }

        $pdo = Database::getPDO();

        // Récupérer les tickets existants pour cette note de frais
        $stmt = $pdo->prepare("SELECT Identifiant FROM Ticket WHERE IdentifiantNoteDeFrais = :id");
        $stmt->execute(['id' => $identifiantNoteDeFrais]);
        $existingTickets = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

        // Garder trace des IDs traités pour identifier ceux à supprimer
        $processedIds = [];

        // Traitement de chaque ticket en session
        foreach ($_SESSION['temp_tickets'] as $ticket) {
            if (isset($ticket['Identifiant']) && in_array($ticket['Identifiant'], $existingTickets)) {
                // Mise à jour des tickets existants
                $sql = "UPDATE Ticket SET
            DateJustificatif = :dateJustificatif,
            NumeroAffaire = :numeroAffaire,
            CompteComptable = :compteComptable,
            TypeDepense = :typeDepense,
            CheminJustificatif = :cheminJustificatif,
            TotalTTC = :totalTTC,
            TotalTVA = :totalTVA,
            Commentaires = :commentaires,
            CodeTiers = :codeTiers
            WHERE Identifiant = :id";

                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    'id' => $ticket['Identifiant'],
                    'dateJustificatif' => $ticket['DateJustificatif'],
                    'numeroAffaire' => $ticket['NumeroAffaire'],
                    'compteComptable' => $ticket['CompteComptable'],
                    'typeDepense' => $ticket['TypeDepense'],
                    'cheminJustificatif' => $ticket['CheminJustificatif'],
                    'totalTTC' => $ticket['TotalTTC'],
                    'totalTVA' => $ticket['TotalTVA'],
                    'commentaires' => $ticket['Commentaires'],
                    'codeTiers' => $ticket['CodeTiers']
                ]);

                $processedIds[] = $ticket['Identifiant'];
            } else {
                // Insertion des nouveaux tickets
                $sql = "INSERT INTO Ticket (IdentifiantNoteDeFrais, DateJustificatif, CompteComptable, NumeroAffaire,
            TypeDepense, CheminJustificatif, TotalTTC, TotalTVA, Commentaires, CodeTiers)
            VALUES (:identifiantNoteDeFrais, :dateJustificatif, :compteComptable, :numeroAffaire,
            :typeDepense, :cheminJustificatif, :totalTTC, :totalTVA, :commentaires, :codeTiers)";

                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    'identifiantNoteDeFrais' => $identifiantNoteDeFrais,
                    'dateJustificatif' => $ticket['DateJustificatif'],
                    'compteComptable' => $ticket['CompteComptable'],
                    'numeroAffaire' => $ticket['NumeroAffaire'],
                    'typeDepense' => $ticket['TypeDepense'],
                    'cheminJustificatif' => $ticket['CheminJustificatif'],
                    'totalTTC' => $ticket['TotalTTC'],
                    'totalTVA' => $ticket['TotalTVA'],
                    'commentaires' => $ticket['Commentaires'],
                    'codeTiers' => $ticket['CodeTiers']
                ]);
            }

            if (!$result) {
                return false;
            }
        }

        // Supprimer les tickets qui ne sont plus présents
        $ticketsToDelete = array_diff($existingTickets, $processedIds);
        if (!empty($ticketsToDelete)) {
            $inQuery = implode(',', array_fill(0, count($ticketsToDelete), '?'));
            $sql = "DELETE FROM Ticket WHERE Identifiant IN ($inQuery)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($ticketsToDelete));
        }

        return true;
    }

    /**
     * Récupère tous les tickets associés à une note de frais
     *
     * @param int $identifiantNoteDeFrais Identifiant de la note de frais
     * @return array Liste des tickets associés à la note de frais
     */
    public function getTicketsByNoteDeFrais(int $identifiantNoteDeFrais): array
    {
        return array_filter($this->tickets, function ($ticket) use ($identifiantNoteDeFrais) {
            return $ticket['IdentifiantNoteDeFrais'] === $identifiantNoteDeFrais;
        });
    }

    /**
     * Change le statut d'un ticket et ajoute éventuellement un commentaire de refus
     *
     * @param int $id Identifiant du ticket
     * @param string $statut Nouveau statut du ticket
     * @param string|null $motif Commentaire de refus optionnel
     * @return bool True si la mise à jour a réussi
     */
    public function changerStatutTicket(int $id, string $statut, ?string $motif = null): bool
    {
        $pdo = Database::getPDO();

        // Si c'est un refus avec motif, on détermine le bon champ de commentaire
        if ($statut === 'Refusé') {
            // Rechercher le ticket dans le tableau local
            $ticket = array_reduce($this->tickets, function ($carry, $item) use ($id) {
                return $item['Identifiant'] === $id ? $item : $carry;
            }, null);

            if (!$ticket) {
                return false;
            }

            // Récupérer la note de frais pour connaître son statut
            $stmt = $pdo->prepare("SELECT Statut FROM NoteDeFrais WHERE Identifiant = :id");
            $stmt->execute(['id' => $ticket['IdentifiantNoteDeFrais']]);
            $noteDeFrais = $stmt->fetch();

            if (!$noteDeFrais) {
                return false;
            }

            // Déterminer le champ de commentaire et de date en fonction du statut de la note de frais
            $champCommentaire = null;
            $champDate = null;

            switch ($noteDeFrais['Statut']) {
                case 'En cours de validation':
                    $champCommentaire = 'CommentaireRefusValidation';
                    $champDate = 'DateRefusValidation';
                    break;
                case 'En cours de traitement comptable':
                    $champCommentaire = 'CommentaireRefusAdministration';
                    $champDate = 'DateRefusAdministration';
                    break;
                default:
                    // Statut non géré pour le refus avec motif
                    return false;
            }

            // Mettre à jour le ticket avec le statut et le commentaire
            $sql = "UPDATE Ticket SET Statut = :statut, {$champCommentaire} = :motif, {$champDate} = :date
            WHERE Identifiant = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                'statut' => $statut,
                'motif' => $motif,
                'date' => date('Y-m-d'),
                'id' => $id
            ]);
        } else {
            // Simple changement de statut sans commentaire
            $sql = "UPDATE Ticket SET Statut = :statut WHERE Identifiant = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                'statut' => $statut,
                'id' => $id
            ]);
        }
    }

    /**
     * Met à jour le compte comptable d'un ticket
     *
     * @param int $ticketId L'identifiant du ticket
     * @param string $compteComptable Le numéro du compte comptable
     * @return bool
     */
    public function updateCompteComptable($ticketId, $compteComptable)
    {
        try {
            $pdo = Database::getPDO();

            $stmt = $pdo->prepare("
            UPDATE Ticket
            SET CompteComptable = :compteComptable
            WHERE Identifiant = :ticketId
        ");

            $stmt->bindParam(':compteComptable', $compteComptable);
            $stmt->bindParam(':ticketId', $ticketId, \PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Erreur lors de la mise à jour du compte comptable: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un ticket existant
     *
     * @param int $ticketId Identifiant du ticket à modifier
     * @param array $ticketData Nouvelles données du ticket
     * @return bool True si la mise à jour a réussi, False sinon
     */
    public function modifierTicket(int $ticketId, array $ticketData): bool
    {
        // Validation des données obligatoires
        if (empty($ticketData['date_justificatif']) || 
            empty($ticketData['type_depense']) || 
            empty($ticketData['total_ttc']) ||
            empty($ticketData['numero_affaire'])) {
            return false;
        }

        try {
            $pdo = Database::getPDO();
            
            // Récupération du compte comptable basé sur le type de dépense
            $compteComptable = $this->typeDepense->getCompteComptable($ticketData['type_depense']);
            
            $sql = "UPDATE Ticket SET
                        DateJustificatif = :dateJustificatif,
                        NumeroAffaire = :numeroAffaire,
                        CompteComptable = :compteComptable,
                        TypeDepense = :typeDepense,
                        TotalTTC = :totalTTC,
                        TotalTVA = :totalTVA,
                        Commentaires = :commentaires
                    WHERE Identifiant = :id";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                'id' => $ticketId,
                'dateJustificatif' => $ticketData['date_justificatif'],
                'numeroAffaire' => $ticketData['numero_affaire'],
                'compteComptable' => $compteComptable,
                'typeDepense' => $ticketData['type_depense'],
                'totalTTC' => floatval($ticketData['total_ttc']),
                'totalTVA' => floatval($ticketData['total_tva'] ?? 0),
                'commentaires' => $ticketData['commentaires'] ?? ''
            ]);

            if ($result) {
                // Recharger les tickets en mémoire après modification
                $this->rechargerTickets();
            }

            return $result;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la modification du ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Réinitialise le statut de tous les tickets d'une note de frais
     *
     * @param int $noteDeFraisId Identifiant de la note de frais
     * @return bool True si la réinitialisation a réussi
     */
    public function reinitialiserStatutsTickets(int $noteDeFraisId): bool
    {
        try {
            $pdo = Database::getPDO();
            $sql = "UPDATE Ticket SET Statut = '' WHERE IdentifiantNoteDeFrais = :id";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(['id' => $noteDeFraisId]);

            if ($result) {
                $this->rechargerTickets();
            }

            return $result;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la réinitialisation des statuts des tickets: " . $e->getMessage());
            return false;
        }
    }

    public function rechargerTickets(): void
    {
        $this->chargerTickets();
    }

    public function chargerTickets()
    {
        $pdo = Database::getPDO();
        $sql = "SELECT * FROM Ticket";
        $req = $pdo->prepare($sql);
        $req->execute();
        $this->tickets = $req->fetchAll();
    }
}