<?php

namespace controllers;

use services\Config;
use services\Groupe;
use services\TypeDepense;
use services\Utilisateur;

class GererUtilisateursController
{

    private $utilisateurService;
    private $groupes;

    public function __construct()
    {
        $this->utilisateurService = new Utilisateur();
        $this->groupes = new Groupe();
    }

    public function post()
    {
        $this->utilisateurService = new Utilisateur();

        // Ajout d'un type de dépense
        if (isset($_POST['action']) && $_POST['action'] === 'add_type_depense') {
            $libelle = trim($_POST['libelle_type_depense'] ?? '');
            $compte = trim($_POST['compte_comptable'] ?? '');

            if ($libelle && $compte) {
                $typeDepenseService = new TypeDepense();
                if ($typeDepenseService->addTypeDepense($libelle, $compte)) {
                    $_SESSION['success'] = "Type de dépense ajouté avec succès";
                } else {
                    $_SESSION['error'] = "Erreur lors de l'ajout du type de dépense";
                }
            } else {
                $_SESSION['error'] = "Veuillez remplir tous les champs";
            }
            header('Location: ' . Config::get("APP_URL") . 'gerer-utilisateurs');
            exit;
        }

        // For user creation
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            $codeTiers = trim($_POST['code_tiers'] ?? '');

            if ($password !== $password_confirm) {
                $_SESSION['error'] = "Les mots de passe ne correspondent pas";
            } elseif ($name && $email && $password && $codeTiers) {
                // Create the user with code_tiers
                if ($userId = $this->utilisateurService->createUser($name, $password, $email, $codeTiers)) {
                    $_SESSION['success'] = "Utilisateur ajouté avec succès";

                    // Save codeAnalytique if provided
                    if (isset($_POST['codeAnalytique']) && !empty($_POST['codeAnalytique'])) {
                        $this->utilisateurService->saveCodeAnalytique($userId, $_POST['codeAnalytique']);
                    }

                    // Add selected groups
                    if (isset($_POST['groupes']) && is_array($_POST['groupes'])) {
                        foreach ($_POST['groupes'] as $groupId) {
                            $this->utilisateurService->addUserToGroup($userId, (int)$groupId);
                        }
                    }
                } else {
                    $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur";
                }
            } else {
                $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires";
            }
        }

        // For user update
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = (int)$_POST['edit_id'];
            $name = trim($_POST['edit_name'] ?? '');
            $email = trim($_POST['edit_email'] ?? '');
            $password = !empty($_POST['edit_password']) ? $_POST['edit_password'] : null;
            $password_confirm = $_POST['edit_password_confirm'] ?? '';
            $codeTiers = trim($_POST['edit_code_tiers'] ?? '');

            if ($password && $password !== $password_confirm) {
                $_SESSION['error'] = "Les mots de passe ne correspondent pas";
            } elseif ($id && $name && $email && $codeTiers) {
                if ($this->utilisateurService->updateUser($id, $name, $email, $password, $codeTiers)) {
                    $_SESSION['success'] = "Utilisateur modifié avec succès";

                    // Update codeAnalytique if provided
                    if (isset($_POST['edit_codeAnalytique'])) {
                        $this->utilisateurService->saveCodeAnalytique($id, $_POST['edit_codeAnalytique']);
                    }

                    $currentGroups = array_column($this->utilisateurService->getGroupesById($id), 'group_id');
                    $newGroups = isset($_POST['edit_groupes']) ? $_POST['edit_groupes'] : [];

                    // Supprimer les groupes qui ne sont plus sélectionnés
                    foreach ($currentGroups as $groupId) {
                        if (!in_array($groupId, $newGroups)) {
                            $this->utilisateurService->removeUserFromGroup($id, (int)$groupId);
                        }
                    }

                    // Ajouter l'utilisateur aux nouveaux groupes
                    foreach ($newGroups as $groupId) {
                        if (!in_array($groupId, $currentGroups)) {
                            $this->utilisateurService->addUserToGroup($id, (int)$groupId);
                        }
                    }
                } else {
                    $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur";
                }
            } else {
                $_SESSION['error'] = "Données invalides pour la modification";
            }
        }

        // Traitement activation/désactivation
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
            $id = (int)$_POST['user_id'];

            if ($this->utilisateurService->toggleUtilisateurStatus($id)) {
                $_SESSION['success'] = "Statut de l'utilisateur modifié avec succès";
            } else {
                $_SESSION['error'] = "Erreur lors de la modification du statut";
            }
        }

        header('Location: ' . Config::get("APP_URL") . 'gerer-utilisateurs');
        exit;
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

        // Check if we're editing a user
        $editUser = null;
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $editUser = $this->utilisateurService->getUserById((int)$_GET['edit']);
            $editUser['groupes'] = $this->utilisateurService->getGroupesById($editUser['id']);
        }

        require 'views/gerer-utilisateurs.php';
    }
}