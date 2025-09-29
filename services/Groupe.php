<?php

namespace services;

use PDO;

/**
 * Classe Groupe
 *
 * Gère les opérations liées aux groupes d'utilisateurs.
 *
 * @package services
 */
class Groupe
{
    /**
     * Instance PDO pour l'accès à la base de données.
     *
     * @var PDO
     */
    private $db;

    /**
     * Constructeur.
     *
     * Initialise la connexion à la base de données.
     */
    public function __construct()
    {
        $this->db = Database::getPDO();
    }

    /**
     * Récupère tous les groupes.
     *
     * @return array Liste des groupes (tableau associatif)
     */
    public function getAllGroupes()
    {
        $query = $this->db->prepare("SELECT * FROM sub_usergroups");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les groupes associés à un utilisateur par son identifiant.
     *
     * @param int $id Identifiant de l'utilisateur
     * @return array Liste des groupes de l'utilisateur (tableau associatif)
     */
    public function getGroupesById($id)
    {
        $query = $this->db->prepare("SELECT g.id, g.title 
                                FROM sub_user_usergroup_map m 
                                JOIN sub_usergroups g ON m.group_id = g.id 
                                WHERE m.user_id = :id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un utilisateur à un groupe.
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $groupId Identifiant du groupe
     * @return bool Succès de l'opération
     */
    public function addUserToGroup($userId, $groupId)
    {
        $query = $this->db->prepare("INSERT INTO sub_user_usergroup_map (user_id, group_id) 
                                VALUES (:user_id, :group_id)");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        return $query->execute();
    }

    /**
     * Retire un utilisateur d'un groupe.
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $groupId Identifiant du groupe
     * @return bool Succès de l'opération
     */
    public function removeUserFromGroup($userId, $groupId)
    {
        $query = $this->db->prepare("DELETE FROM sub_user_usergroup_map 
                                WHERE user_id = :user_id AND group_id = :group_id");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        return $query->execute();
    }

}