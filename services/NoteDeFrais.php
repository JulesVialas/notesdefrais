<?php

namespace services;

use DateTime;
use Pdf;

/**
 * Classe de gestion des notes de frais
 */
class NoteDeFrais
{
    /** @var array Tableau contenant les notes de frais chargées */
    private array $notesDeFrais;

    /** @var Ticket Instance pour gérer les tickets associés */
    private Ticket $ticket;


    /**
     * Constructeur de la classe NoteDeFrais
     */
    public function __construct()
    {
        $this->chargerNotesDeFrais();
        $this->ticket = new Ticket();
    }

    /**
     * Charge les notes de frais depuis la base de données
     */
    private function chargerNotesDeFrais(): void
    {
        $pdo = Database::getPDO();
        $sql = "SELECT * FROM NoteDeFrais";
        $req = $pdo->prepare($sql);
        $req->execute();
        $this->notesDeFrais = $req->fetchAll();
    }

    /**
     * Récupère les notes de frais filtrées par statut et utilisateur
     *
     * @param string $libelleUtilisateur Libellé de l'utilisateur
     * @param string $statut Statut des notes de frais à récupérer
     * @param bool $toutesLesNotes Si true, récupère les notes de tous les utilisateurs (pour vérificateurs/validateurs)
     * @return array Notes de frais correspondant aux critères
     */
    public function getNotesDeFraisByStatut(
        string $libelleUtilisateur,
        string $statut,
        bool   $toutesLesNotes = false,
        string $filterNom = '',
        string $filterDate = ''
    ): array
    {
        $pdo = Database::getPDO();
        $sql = "SELECT * FROM NoteDeFrais WHERE Statut = :statut";
        $params = ['statut' => $statut];

        if (!$toutesLesNotes) {
            // Normaliser le libellé pour gérer les noms composés
            $mots = array_filter(explode(' ', trim($libelleUtilisateur)));

            if (count($mots) >= 2) {
                // Créer différentes variantes possibles
                $variantes = [];

                // Format original
                $variantes[] = $libelleUtilisateur;

                // Format simple inversé (dernier mot + premier mot)
                $variantes[] = end($mots) . ' ' . $mots[0];

                // Pour les noms composés, essayer différentes combinaisons
                if (count($mots) >= 3) {
                    // Prendre les 2 derniers mots comme nom de famille
                    $prenoms = array_slice($mots, 0, -2);
                    $noms = array_slice($mots, -2);
                    $variantes[] = implode(' ', $noms) . ' ' . implode(' ', $prenoms);

                    // Prendre seulement le dernier mot comme nom
                    $prenoms = array_slice($mots, 0, -1);
                    $nom = end($mots);
                    $variantes[] = $nom . ' ' . implode(' ', $prenoms);
                }

                // Construire la condition SQL avec toutes les variantes
                $conditions = [];
                foreach ($variantes as $index => $variante) {
                    $conditions[] = "LOWER(LibelleUtilisateur) = LOWER(:variante$index)";
                    $params["variante$index"] = $variante;
                }

                $sql .= " AND (" . implode(' OR ', $conditions) . ")";
            } else {
                $sql .= " AND LOWER(LibelleUtilisateur) = LOWER(:libelleUtilisateur)";
                $params['libelleUtilisateur'] = $libelleUtilisateur;
            }
        }

        if ($filterNom !== '') {
            $sql .= " AND LOWER(LibelleUtilisateur) LIKE LOWER(:filterNom)";
            $params['filterNom'] = '%' . $filterNom . '%';
        }

        if ($filterDate !== '') {
            $sql .= " AND DATE(DateDemande) = :filterDate";
            $params['filterDate'] = $filterDate;
        }

        $sql .= " ORDER BY DateDemande DESC";

        $req = $pdo->prepare($sql);
        $req->execute($params);
        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Envoie une nouvelle note de frais pour vérification
     */
    public function envoyerNoteDeFrais(): bool
    {
        $libelleUtilisateur = $_SESSION['LibelleUtilisateur'] ?? null;
        $pdo = Database::getPDO();
        $result = $this->sauvegarderNoteDeFrais();

        if (!$result) {
            return false;
        }

        $noteDeFraisId = $pdo->lastInsertId();
        $result = $this->ticket->envoyerTickets($noteDeFraisId);

        if ($result) {
            unset($_SESSION['temp_tickets'], $_SESSION['temp_note_frais']);
            $this->rechargerNotesDeFrais();
        }

        return $result;
    }

    /**
     * Enregistre les données d'une note de frais en base
     */
    private function sauvegarderNoteDeFrais(?int $id = null): bool
    {
        $pdo = Database::getPDO();

        if ($id === null) {
            // Création d'une nouvelle note de frais
            $sql = "INSERT INTO NoteDeFrais (LibelleUtilisateur, DateDemande, Statut, TotalTTC, TotalTVA) 
                VALUES (:libelleUtilisateur, :dateDemande, :statut, :totalTTC, :totalTVA)";
            $params = [
                'libelleUtilisateur' => $_SESSION['LibelleUtilisateur'],
                'dateDemande' => date('Y-m-d H:i:s'),
                'statut' => 'En cours de validation',
                'totalTTC' => $_SESSION['temp_note_frais']['TotalTTC'],
                'totalTVA' => $_SESSION['temp_note_frais']['TotalTVA'],
            ];
        } else {
            // Mise à jour d'une note existante
            $sql = "UPDATE NoteDeFrais SET 
                DateDemande = :dateDemande,
                Statut = :statut,
                TotalTTC = :totalTTC,
                TotalTVA = :totalTVA
                WHERE Identifiant = :id";
            $params = [
                'id' => $id,
                'dateDemande' => date('Y-m-d H:i:s'),
                'statut' => 'En cours de validation',
                'totalTTC' => $_SESSION['temp_note_frais']['TotalTTC'],
                'totalTVA' => $_SESSION['temp_note_frais']['TotalTVA'],
            ];
        }

        $req = $pdo->prepare($sql);
        return $req->execute($params);
    }

    /**
     * Recharge les données des notes de frais
     */
    public function rechargerNotesDeFrais(): void
    {
        $this->chargerNotesDeFrais();
    }

    /**
     * Met à jour une note de frais existante
     */
    public function mettreAJourNoteDeFrais(int $id): void
    {
        $result = $this->sauvegarderNoteDeFrais($id);
        $result = $this->ticket->envoyerTickets($id);

        if ($result) {
            unset($_SESSION['temp_tickets'], $_SESSION['temp_note_frais']);
            $this->rechargerNotesDeFrais();
        }
    }

    /**
     * Met à jour le statut selon l'état des tickets
     */
    public function mettreAJourStatutSelonTickets(int $noteDeFraisId): bool
    {
        $this->ticket->rechargerTickets();
        $tickets = $this->ticket->getTicketsByNoteDeFrais($noteDeFraisId);
        $tousTraites = true;
        $auMoinsUnRefus = false;

        $noteDeFrais = $this->getNoteDeFraisById($noteDeFraisId);
        $statutActuel = $noteDeFrais['Statut'];

        foreach ($tickets as $ticket) {
            if ($ticket['Statut'] === 'Refusé') {
                $auMoinsUnRefus = true;
            } elseif ($ticket['Statut'] !== 'Validé') {
                $tousTraites = false;
                break;
            }
        }

        if ($tousTraites) {
            if ($auMoinsUnRefus) {
                return $this->changerStatutNoteDeFrais($noteDeFraisId, 'Refusée');
            } else {
                $nouveauStatut = $this->getNextStatus($statutActuel);
                return $this->changerStatutNoteDeFrais($noteDeFraisId, $nouveauStatut);
            }
        }

        return true;
    }

    /**
     * Récupère une note de frais par son identifiant
     */
    public function getNoteDeFraisById(int $id): ?array
    {
        return array_reduce($this->notesDeFrais, function ($carry, $note) use ($id) {
            return $note['Identifiant'] === $id ? $note : $carry;
        }, null);
    }

    /**
     * Change le statut d'une note de frais
     */
    public function changerStatutNoteDeFrais(int $id, string $statut): bool
    {
        $pdo = Database::getPDO();
        $dateColumn = $this->getDateColumnForStatus($statut);

        // Récupérer le statut actuel pour vérifier si c'est un changement de workflow
        $statutActuel = $this->getStatus($id);
        $changementWorkflow = $this->getNextStatus($statutActuel) === $statut;

        $sql = "UPDATE NoteDeFrais SET Statut = :statut";
        if ($dateColumn) {
            $sql .= ", $dateColumn = :date";
        }
        $sql .= " WHERE Identifiant = :id";

        $req = $pdo->prepare($sql);

        $params = ['id' => $id, 'statut' => $statut];
        if ($dateColumn) {
            $params['date'] = date('Y-m-d H:i:s');
        }

        $result = $req->execute($params);

        if ($result) {
            $this->rechargerNotesDeFrais();

            // Si le statut change au sein du workflow (vérification -> validation -> comptable)
            // alors réinitialiser les statuts des tickets
            if ($changementWorkflow) {
                $this->ticket->reinitialiserStatutsTickets($id);
            }
        }

        return $result;
    }

    private function getDateColumnForStatus(string $statut): ?string
    {
        switch ($statut) {
            case 'En cours de validation':
                return 'DateValidation';
            case 'En cours de traitement comptable':
                return 'DateAdministration';
            default:
                return null;
        }
    }

    /**
     * Change le statut d'une note de frais
     */

    public function getStatus(int $id): string
    {
        $noteDeFrais = $this->getNoteDeFraisById($id);
        return $noteDeFrais['Statut'] ?? '';
    }

    public function getNextStatus(string $statutActuel): string
    {
        switch ($statutActuel) {
            case 'En cours de validation':
                return 'En cours de traitement comptable';
            case 'En cours de traitement comptable':
                return 'Terminée';
            default:
                return $statutActuel;
        }
    }

    /**
     * Export expense reports as downloadable file
     *
     * @param array $notesDeFrais Array of expense reports to export
     * @return void
     */

    public function exportation(array $notesDeFrais): void
    {
        $utilisateurService = new Utilisateur();
        $ticketsByMonth = [];

        // Grouper les tickets par mois
        foreach ($notesDeFrais as $noteDeFrais) {
            $noteDeFraisId = $noteDeFrais['Identifiant'];
            $libelleUtilisateur = $noteDeFrais['LibelleUtilisateur'];
            $tickets = $this->ticket->getTicketsByNoteDeFrais($noteDeFraisId);

            foreach ($tickets as &$ticket) {
                $ticket['LibelleUtilisateur'] = $libelleUtilisateur;
                $ticket['DateDemande'] = $noteDeFrais['DateDemande'];
                $date = DateTime::createFromFormat('Y-m-d', $ticket['DateJustificatif']);
                $mois = $date ? $date->format('Ym') : 'inconnu';
                $ticketsByMonth[$mois][] = $ticket;
            }
        }

        // Créer un zip temporaire
        $zipFilename = "Export_NDF_" . date('Ymd_His') . ".zip";
        $zipPath = sys_get_temp_dir() . "/" . $zipFilename;
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Impossible de créer l'archive ZIP");
        }
        $pieceCounter = 0;
        foreach ($ticketsByMonth as $mois => $tickets) {
            $content = "";

            // Grouper les tickets par utilisateur et par note de frais pour créer une pièce comptable par utilisateur/note
            $ticketsByUserAndNote = [];
            foreach ($tickets as $ticket) {
                $user = $ticket['LibelleUtilisateur'];
                $dateDemande = $ticket['DateDemande'];
                // Créer une clé unique combinant utilisateur et date de demande (note de frais)
                $userNoteKey = $user . '_' . $dateDemande;
                $ticketsByUserAndNote[$userNoteKey]['tickets'][] = $ticket;
                $ticketsByUserAndNote[$userNoteKey]['libelle'] = $user;
                $ticketsByUserAndNote[$userNoteKey]['dateDemande'] = $dateDemande;
            }

            // Trier les clés pour assurer un ordre cohérent
            ksort($ticketsByUserAndNote);

            foreach ($ticketsByUserAndNote as $userNoteKey => $userData) {
                // Incrémenter le compteur de pièce pour chaque combinaison utilisateur/note de frais dans chaque mois
                $pieceCounter++;
                $userTickets = $userData['tickets'];
                $user = $userData['libelle'];
                $dateDemande = $userData['dateDemande'];

                // Générer un numéro de pièce unique pour cet utilisateur/note ce mois-ci
                $datefixeExport = $mois . str_pad($pieceCounter, 3, '0', STR_PAD_LEFT);
                

                // Calculer le total pour cet utilisateur
                $totalUtilisateur = 0;

                // Trier les tickets par compte comptable pour la lisibilité
                usort($userTickets, function ($a, $b) {
                    return strcmp($a['CompteComptable'], $b['CompteComptable']);
                });

                // Générer les lignes de débit (charges) pour chaque ticket
                foreach ($userTickets as $ticket) {
                    $date = DateTime::createFromFormat('Y-m-d', $ticket['DateJustificatif']);
                    $jour = $date ? $date->format('d') : '01';
                    $moisFormat = $date ? $date->format('m') : '01';
                    $annee = $date ? $date->format('y') : date('y');
                    $datefixeExport = $jour . $moisFormat . $annee;

                    $LibelleEcriture = "NDF_" . $ticket['LibelleUtilisateur'] . "_" . $moisFormat;

                    $totalHT = $ticket['TotalTTC'] - $ticket['TotalTVA'];
                    $totalHTFormatted = number_format($totalHT, 2, ',', '');
                    $totalTVAFormatted = number_format($ticket['TotalTVA'], 2, ',', '');

                    $totalUtilisateur += $ticket['TotalTTC'];

                    // Ligne de charge HT
                    $content .= 'HA;' . $pieceCounter . ';' . $ticket['CompteComptable'] . ';;G;0;;' .
                        $ticket['TypeDepense'] . ';' . $totalHTFormatted . ';D;' . $LibelleEcriture . "_" . $datefixeExport . "\r\n";

                    // Ligne analytique
                    $userId = $utilisateurService->getUserIdByLibelle($user);
                    $codeAnalytique = $userId ? $utilisateurService->getCodeAnalytique($userId) : '';

                    $content .= 'HA;' . $pieceCounter . ';' . $ticket['CompteComptable'] . ';' .
                        $codeAnalytique . ';A;1;' . $ticket['NumeroAffaire'] . ';' .
                        $ticket['TypeDepense'] . ';' . $totalHTFormatted . ';D;' . $LibelleEcriture . "_" . $datefixeExport .  "\r\n";

                    // Ligne TVA si applicable
                    if ($ticket['TotalTVA'] > 0) {
                        $content .= 'HA;' . $pieceCounter . ';445660000;;G;0;;TVA;' .
                            $totalTVAFormatted . ';D;' . $LibelleEcriture . "_" . $datefixeExport .  "\r\n";
                    }
                }

                // Générer la ligne de crédit (compte fournisseur) pour équilibrer la pièce
                $userId = $utilisateurService->getUserIdByLibelle($user);
                $codeTiers = $userId ? $utilisateurService->getCodeTiers($userId) : '';
                $totalUtilisateurFormatted = number_format($totalUtilisateur, 2, ',', '');

                $LibelleEcritureUser = 'NDF_' . $user . '_' . substr($mois, 4, 2);

                $content .= 'HA;' . $pieceCounter . ';425000000;' . $codeTiers .
                    ';G;0;;;' . $totalUtilisateurFormatted . ';C;' . $LibelleEcritureUser . "_" . $datefixeExport .  "\r\n";
            }

            $txtFilename = "NDF_" . $mois . ".txt";
            $zip->addFromString($txtFilename, $content);
        }

        $zip->close();

        // Envoie le zip en téléchargement
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipFilename) . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);

        // Supprimer le fichier temporaire après envoi
        unlink($zipPath);
        exit;
    }

    public function sauvegarderNoteDeFraisEnCoursDeSaisie(): bool
    {
        $pdo = Database::getPDO();

        // Vérifier si la note de frais est vide (aucun ticket)
        if (empty($_SESSION['temp_tickets']) || count($_SESSION['temp_tickets']) === 0) {
            // Si on modifie une note existante et qu'elle est vide, la supprimer
            if (isset($_SESSION['temp_note_frais']['Identifiant'])) {
                $sql = "DELETE FROM NoteDeFrais WHERE Identifiant = :id";
                $req = $pdo->prepare($sql);
                $result = $req->execute(['id' => $_SESSION['temp_note_frais']['Identifiant']]);

                if ($result) {
                    unset($_SESSION['temp_tickets'], $_SESSION['temp_note_frais']);
                    $this->rechargerNotesDeFrais();
                }
                return $result;
            }
            // Si c'est une nouvelle note vide, ne rien faire
            return true;
        }

        // Vérifier si on modifie une note existante
        if (isset($_SESSION['temp_note_frais']['Identifiant'])) {
            // Mise à jour d'une note existante
            $sql = "UPDATE NoteDeFrais SET
            TotalTTC = :totalTTC,
            TotalTVA = :totalTVA,
            DateDemande = :dateDemande
            WHERE Identifiant = :id";
            $params = [
                'id' => $_SESSION['temp_note_frais']['Identifiant'],
                'totalTTC' => $_SESSION['temp_note_frais']['TotalTTC'],
                'totalTVA' => $_SESSION['temp_note_frais']['TotalTVA'],
                'dateDemande' => date('Y-m-d H:i:s')
            ];
            $req = $pdo->prepare($sql);
            $result = $req->execute($params);

            if ($result) {
                $noteDeFraisId = $_SESSION['temp_note_frais']['Identifiant'];
                $this->ticket->envoyerTickets($noteDeFraisId);
                unset($_SESSION['temp_tickets'], $_SESSION['temp_note_frais']);
                $this->rechargerNotesDeFrais();
            }
        } else {
            // Création d'une nouvelle note
            $sql = "INSERT INTO NoteDeFrais (LibelleUtilisateur, DateDemande, Statut, TotalTTC, TotalTVA)
            VALUES (:libelleUtilisateur, :dateDemande, :statut, :totalTTC, :totalTVA)";
            $params = [
                'libelleUtilisateur' => $_SESSION['LibelleUtilisateur'],
                'dateDemande' => date('Y-m-d H:i:s'),
                'statut' => 'En cours de saisie',
                'totalTTC' => $_SESSION['temp_note_frais']['TotalTTC'],
                'totalTVA' => $_SESSION['temp_note_frais']['TotalTVA'],
            ];
            $req = $pdo->prepare($sql);
            $result = $req->execute($params);

            if ($result) {
                $noteDeFraisId = $pdo->lastInsertId();
                $this->ticket->envoyerTickets($noteDeFraisId);
                unset($_SESSION['temp_tickets'], $_SESSION['temp_note_frais']);
                $this->rechargerNotesDeFrais();
            }
        }

        return $result;
    }

    public function countNotesDeFraisForCurrentMonth(string $libelleUtilisateur): int
    {
        $currentMonth = date('Y-m');
        $statutsBloquants = [
            'En cours de validation',
            'En cours de traitement comptable',
            'En cours de saisie'
        ];

        return count(array_filter($this->notesDeFrais, function ($note) use ($libelleUtilisateur, $currentMonth, $statutsBloquants) {
            return $note['LibelleUtilisateur'] === $libelleUtilisateur
                && strpos($note['DateDemande'], $currentMonth) === 0
                && in_array($note['Statut'], $statutsBloquants, true);
        }));
    }

    public function getNotesDeFraisByUtilisateur(string $libelleUtilisateur): array
    {
        return array_filter($this->notesDeFrais, function ($note) use ($libelleUtilisateur) {
            $a = explode(' ', strtolower(trim($note['LibelleUtilisateur'])));
            $b = explode(' ', strtolower(trim($libelleUtilisateur)));
            sort($a);
            sort($b);
            return $a === $b;
        });
    }

    public function getNotesDeFraisByUtilisateurEtPeriode($libelleUtilisateur, $periode)
    {
        $pdo = Database::getPDO();
        $query = $pdo->prepare("
        SELECT * FROM NoteDeFrais
        WHERE LibelleUtilisateur = :libelle
        AND DATE_FORMAT(DateDemande, '%Y-%m') = :periode
    ");
        $query->bindParam(':libelle', $libelleUtilisateur, \PDO::PARAM_STR);
        $query->bindParam(':periode', $periode, \PDO::PARAM_STR);
        $query->execute();
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function supprimerNoteDeFrais($noteId)
    {
        $pdo = Database::getPDO();

        try {
            $pdo->beginTransaction();

            // Supprimer tous les tickets associés à cette note
            $stmt = $pdo->prepare("DELETE FROM Ticket WHERE IdentifiantNoteDeFrais = ?");
            $stmt->execute([$noteId]);

            // Supprimer la note de frais
            $stmt = $pdo->prepare("DELETE FROM NoteDeFrais WHERE Identifiant = ?");
            $stmt->execute([$noteId]);

            $pdo->commit();
            $this->rechargerNotesDeFrais();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function countNotesThisMonth($userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM NotesDeFrais 
        WHERE UtilisateurId = ? 
        AND MONTH(DateCreation) = MONTH(CURRENT_DATE()) 
        AND YEAR(DateCreation) = YEAR(CURRENT_DATE())
    ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    public function getNotesDeFraisByUser($userId, $statuts)
    {
        $db = Database::getInstance();
        $placeholders = str_repeat('?,', count($statuts) - 1) . '?';
        $sql = "SELECT * FROM NotesDeFrais WHERE UtilisateurId = ? AND Statut IN ($placeholders) ORDER BY DateCreation DESC";

        $params = array_merge([$userId], $statuts);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}