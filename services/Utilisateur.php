<?php

namespace services;

use PDO;

class Utilisateur
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getPDO();
    }

    public function getAllUtilisateurs()
    {
        $query = $this->db->prepare("SELECT * FROM sub_users");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleUtilisateurStatus($id)
    {
        $query = $this->db->prepare("UPDATE sub_users SET block = NOT block WHERE id = :id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        return $query->execute();
    }

    public function getUserById($id)
    {
        $query = $this->db->prepare("SELECT * FROM sub_users WHERE id = :id");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser($name, $password, $email, $codeTiers = null)
    {
        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $query = $this->db->prepare("INSERT INTO sub_users (name, username, password, email)
                         VALUES (:name, :username, :password, :email)");
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->bindParam(':username', $email, PDO::PARAM_STR);
        $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);

        if ($query->execute()) {
            $userId = $this->db->lastInsertId();

            // Save code_tiers in the separate table if provided
            if ($codeTiers) {
                $this->saveCodeTiers($userId, $codeTiers);
            }

            return $userId;
        }
        return false;
    }

    public function saveCodeTiers($userId, $codeTiers)
    {
        // Check if a code_tiers record already exists for this user
        $checkQuery = $this->db->prepare("SELECT field_id FROM sub_fields_values WHERE item_id = :user_id AND field_id = 2");
        $checkQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $checkQuery->execute();

        if ($checkQuery->rowCount() > 0) {
            // Update existing code_tiers
            $updateQuery = $this->db->prepare("UPDATE sub_fields_values SET value = :code_tiers WHERE item_id = :user_id AND field_id = 2");
            $updateQuery->bindParam(':code_tiers', $codeTiers, PDO::PARAM_STR);
            $updateQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $updateQuery->execute();
        } else {
            // Insert new code_tiers
            $insertQuery = $this->db->prepare("INSERT INTO sub_fields_values (field_id, item_id, value) VALUES (2, :user_id, :code_tiers)");
            $insertQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $insertQuery->bindParam(':code_tiers', $codeTiers, PDO::PARAM_STR);
            return $insertQuery->execute();
        }
    }

    public function updateUser($id, $name, $email, $password = null, $codeTiers = null)
    {
        // If password is provided, update it too; otherwise just update other fields
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = $this->db->prepare("UPDATE sub_users SET name = :name, username = :username,
                                 email = :email, password = :password WHERE id = :id");
            $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        } else {
            $query = $this->db->prepare("UPDATE sub_users SET name = :name, username = :username,
                                 email = :email WHERE id = :id");
        }

        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->bindParam(':username', $email, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);

        $result = $query->execute();

        // Update code_tiers in the separate table if provided
        if ($result && $codeTiers !== null) {
            $this->saveCodeTiers($id, $codeTiers);
        }

        return $result;
    }

    public function addUserToGroup($userId, $groupId)
    {
        // Check if the relationship already exists
        $checkQuery = $this->db->prepare("SELECT 1 FROM sub_user_usergroup_map 
                                     WHERE user_id = :user_id AND group_id = :group_id");
        $checkQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $checkQuery->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $checkQuery->execute();

        if ($checkQuery->rowCount() > 0) {
            // Relationship already exists, no need to insert
            return true;
        }

        // Insert if not exists
        $query = $this->db->prepare("INSERT INTO sub_user_usergroup_map (user_id, group_id)
                             VALUES (:user_id, :group_id)");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        return $query->execute();
    }

    public function removeUserFromGroup($userId, $groupId)
    {
        $query = $this->db->prepare("DELETE FROM sub_user_usergroup_map
                                 WHERE user_id = :user_id AND group_id = :group_id");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        return $query->execute();
    }

    public function getGroupesById($userId)
    {
        $query = $this->db->prepare("
        SELECT group_id FROM sub_user_usergroup_map
        WHERE user_id = :user_id
    ");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCodeAnalytique($userId)
    {
        $query = $this->db->prepare("SELECT value FROM sub_fields_values WHERE item_id = :id AND field_id = 1");
        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : null;
    }

    public function saveCodeAnalytique($userId, $matricule)
    {
        // Check if a matricule record already exists for this user
        $checkQuery = $this->db->prepare("SELECT field_id FROM sub_fields_values WHERE item_id = :user_id AND field_id = 1");
        $checkQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $checkQuery->execute();

        if ($checkQuery->rowCount() > 0) {
            // Update existing matricule
            $updateQuery = $this->db->prepare("UPDATE sub_fields_values SET value = :matricule WHERE item_id = :user_id AND field_id = 1");
            $updateQuery->bindParam(':matricule', $matricule, PDO::PARAM_STR);
            $updateQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $updateQuery->execute();
        } else {
            // Insert new matricule
            $insertQuery = $this->db->prepare("INSERT INTO sub_fields_values (field_id, item_id, value) VALUES (1, :user_id, :matricule)");
            $insertQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $insertQuery->bindParam(':matricule', $matricule, PDO::PARAM_STR);
            return $insertQuery->execute();
        }
    }

    public function getCodeTiers($userId)
    {
        $query = $this->db->prepare("SELECT value FROM sub_fields_values WHERE item_id = :id AND field_id = 2");
        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : null;
    }

    /**
     * Get user information associated with a specific expense report
     *
     * @param int $noteDeFraisId Expense report ID
     * @return array|false User data or false if not found
     */
    public function getUserByNoteDeFrais($noteDeFraisId)
    {
        $query = $this->db->prepare("
        SELECT u.* 
        FROM sub_users u
        JOIN NoteDeFrais ndf ON u.name = ndf.LibelleUtilisateur
        WHERE ndf.Identifiant = :noteDeFraisId
    ");

        $query->bindParam(':noteDeFraisId', $noteDeFraisId, \PDO::PARAM_INT);
        $query->execute();

        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve user ID based on the user's display name (name)
     *
     * @param string $libelleUtilisateur The display name of the user
     * @return int|null The user ID if found, null otherwise
     */
    public function getUserIdByLibelle(string $libelleUtilisateur): ?int
    {
        // Normalize input
        $libelleTrimmed = trim($libelleUtilisateur);
        $libelleLower = mb_strtolower($libelleTrimmed);

        // Try direct match
        $query = "SELECT id FROM sub_users WHERE LOWER(TRIM(name)) = :libelle LIMIT 1";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':libelle', $libelleLower);
        $statement->execute();
        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($result) {
            return (int)$result['id'];
        }

        // Try reversed order if two words
        $parts = preg_split('/\s+/', $libelleTrimmed);
        if (count($parts) === 2) {
            $reversed = mb_strtolower(trim($parts[1] . ' ' . $parts[0]));
            $statement = $this->db->prepare($query);
            $statement->bindParam(':libelle', $reversed);
            $statement->execute();
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                return (int)$result['id'];
            }
        }

        // Fallback to LIKE
        $searchTerm = '%' . $libelleTrimmed . '%';
        $query = "SELECT id FROM sub_users WHERE name LIKE :libelle LIMIT 1";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':libelle', $searchTerm);
        $statement->execute();
        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result ? (int)$result['id'] : null;
    }


    public function getLibelleById($id)
    {
        $query = $this->db->prepare("SELECT name FROM sub_users WHERE id = :id");
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['name'] : null;
    }

    // Add this method inside the Utilisateur class
    public function getUtilisateursByRole($roleId)
    {
        $query = $this->db->prepare("
        SELECT u.* FROM sub_users u
        JOIN sub_user_usergroup_map m ON u.id = m.user_id
        WHERE m.group_id = :roleId
    ");
        $query->bindParam(':roleId', $roleId, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}