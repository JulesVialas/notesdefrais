<?php

namespace controllers;

use services\Config;
use services\TypeDepense;

class GererTypesDepenseController
{
    private TypeDepense $typeDepense;

    public function __construct()
    {
        $this->typeDepense = new TypeDepense();
    }

    public function get()
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['LibelleUtilisateur'])) {
            header('Location: ' . Config::get("APP_URL") . 'login');
            exit;
        }
        $typesDepenses = $this->typeDepense->getTypesDepenses();
        require __DIR__ . '/../views/gerer-types-depenses.php';
    }

    public function post()
    {
        if (!isset($_POST['action'])) {
            header('Location: ' . Config::get("APP_URL") . 'gerer-types-depense');
            exit;
        }

        $action = $_POST['action'];

        try {
            switch ($action) {
                case 'add':
                    $libelle = trim($_POST['libelle']);
                    $compteComptable = trim($_POST['compte_comptable']);
                    $tva = isset($_POST['tva']) && $_POST['tva'] === '1';

                    if (empty($libelle) || empty($compteComptable)) {
                        $_SESSION['error'] = 'Tous les champs sont obligatoires.';
                        break;
                    }

                    if ($this->typeDepense->addTypeDepense($libelle, $compteComptable, $tva)) {
                        $_SESSION['success'] = 'Type de dépense ajouté avec succès.';
                    } else {
                        $_SESSION['error'] = 'Erreur lors de l\'ajout du type de dépense.';
                    }
                    break;

                case 'edit':
                    $id = intval($_POST['id']);
                    $libelle = trim($_POST['libelle']);
                    $compteComptable = trim($_POST['compte_comptable']);
                    $tva = isset($_POST['tva']) && $_POST['tva'] === '1';

                    if (empty($libelle) || empty($compteComptable)) {
                        $_SESSION['error'] = 'Tous les champs sont obligatoires.';
                        break;
                    }

                    if ($this->typeDepense->updateTypeDepense($id, $libelle, $compteComptable, $tva)) {
                        $_SESSION['success'] = 'Type de dépense modifié avec succès.';
                    } else {
                        $_SESSION['error'] = 'Erreur lors de la modification du type de dépense.';
                    }
                    break;

                case 'delete':
                    $id = intval($_POST['id']);
                    if ($this->typeDepense->deleteTypeDepense($id)) {
                        $_SESSION['success'] = 'Type de dépense supprimé avec succès.';
                    } else {
                        $_SESSION['error'] = 'Erreur lors de la suppression du type de dépense.';
                    }
                    break;

                default:
                    $_SESSION['error'] = 'Action non reconnue.';
                    break;
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Une erreur est survenue : ' . $e->getMessage();
        }

        header('Location: ' . Config::get("APP_URL") . 'gerer-types-depense');
        exit;
    }
}