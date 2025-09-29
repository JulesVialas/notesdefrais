<?php

namespace services;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'Database.php';
require_once 'Utilisateur.php';
require_once 'Config.php';

class Mail
{
    private $nombreMin; // Nombre minimum de notes de frais pour envoyer une notification

    private $utilisateurService;
//    private $entetes;
    private $db;

    public function __construct()
    {
        $this->nombreMin = 5; // Définir le nombre minimum de notes de frais pour envoyer une notification
        $this->utilisateurService = new Utilisateur();
        $this->db = Database::getPDO();


//        $this->entetes = 'From: nepasrepondre@subterra.fr' . "\r\n" .
//            'Reply-To: nepasrepondre@subterra.fr' . "\r\n" .
//            'X-Mailer: PHP/' . phpversion();
    }

    public function notifierUtilisateurs()
    {
        $roles = [
            15 => ['statut' => 'en cours de vérification', 'libelle' => 'vérifier'],
            5 => ['statut' => 'en cours de validation', 'libelle' => 'valider'],
            16 => ['statut' => 'en cours de traitement comptable', 'libelle' => 'traiter'],
        ];

        foreach ($roles as $role => $data) {
            $count = $this->countNotesByStatus($data['statut']);
            if ($count >= $this->nombreMin) {
                $utilisateurs = $this->utilisateurService->getUtilisateursByRole($role);
                foreach ($utilisateurs as $utilisateur) {
                    if (!empty($utilisateur['email'])) {
                        $message = "<html lang='fr'>
                                    <head>
                                        <title>Notification Notes de frais</title>                                       
                                    </head>
                                    <body>
                                    <p>Bonjour {$utilisateur['name']},</p>
                                    <p>Vous avez au moins {$count} notes de frais &agrave; {$data['libelle']}&nbsp;!</p>
                                    <p>Merci de vous connecter sur votre <a href='https://extranet.subterra.fr/index.php?option=com_users&amp;view=login&amp;Itemid=101'>espace collaborateur &laquo;Notes de frais&nbsp;&raquo;</a></p>
                                    <p>&nbsp;</p>
                                    <p>Ceci est un message automatique, merci de ne pas y r&eacute;pondre.</p>
                                    <p><img src='https://extranet.subterra.fr/notesdefrais/Logo-Subterra.png' alt='' width='270' height='80' /></p>
                                    </body>
                                    </html>";

                        $this->debugMail(
//                            $utilisateur['email']
                            "informatique@subterra.fr",
                            'Notification Note de Frais',
                            $message);
                    }
                }
            }
        }
        //echo "Script terminé\n";
    }

    private function countNotesByStatus($status)
    {
        $query = $this->db->prepare("SELECT COUNT(*) as nb FROM NoteDeFrais WHERE statut = :status");
        $query->bindParam(':status', $status, \PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(\PDO::FETCH_ASSOC);
        return $result ? (int)$result['nb'] : 0;
    }

    private function debugMail($destinataire, $sujet, $message)
    {
//        if (mail($destinataire, $sujet, $message, $this->entetes)) {
//            echo "Email envoyé à {$destinataire} avec le sujet '{$sujet}'\n";
//        } else {
//            echo "Échec de l'envoi à {$destinataire} avec le sujet '{$sujet}'\n";
//        }
        // To send HTML mail, the Content-type header must be set
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        // Additional headers
        $headers[] = 'From: Note de Frais <nepasrepondre@subterra.fr>';
        mail($destinataire, $sujet, $message, implode("\r\n", $headers));
    }
}

// Utilisation
$mailService = new Mail();
$mailService->notifierUtilisateurs();