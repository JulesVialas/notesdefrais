<?php

namespace services;

/**
 * Classe utilitaire pour la gestion des types de dépenses.
 */
class TypeDepense
{
    private array $typesDepenses;

    public function __construct()
    {
        $pdo = Database::getPDO();
        $sql = "SELECT * FROM TypeDepense";
        $req = $pdo->prepare($sql);
        $req->execute();
        $this->typesDepenses = $req->fetchAll();
    }

    public function getTypesDepenses(): array
    {
        $pdo = Database::getPDO();
        $sql = "SELECT Identifiant, Libelle, CompteComptable, tva FROM TypeDepense";
        $req = $pdo->prepare($sql);
        $req->execute();
        return $req->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function hasTva(string $typeDepense): bool
    {
        $pdo = Database::getPDO();
        $sql = "SELECT tva FROM TypeDepense WHERE Libelle = :libelle";
        $req = $pdo->prepare($sql);
        $req->execute(['libelle' => $typeDepense]);
        $result = $req->fetch();
        return $result ? (bool)$result['tva'] : false;
    }

    public function getTauxTVA(string $type_depense)
    {
        foreach ($this->typesDepenses as $type) {
            if ($type['Libelle'] === $type_depense) {
                return $type['TauxTVA'];
            }
        }
        return null;
    }

    public function getCompteComptable(string $type_depense)
    {
        foreach ($this->typesDepenses as $type) {
            if ($type['Libelle'] === $type_depense) {
                return $type['CompteComptable'];
            }
        }
        return null;
    }

    public function addTypeDepense(string $libelle, string $compteComptable, bool $tva = false): bool
    {
        $pdo = Database::getPDO();
        $sql = "INSERT INTO TypeDepense (Libelle, CompteComptable, tva) VALUES (:libelle, :compte, :tva)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':libelle' => $libelle,
            ':compte' => $compteComptable,
            ':tva' => $tva ? 1 : 0
        ]);
    }

    public function updateTypeDepense(int $id, string $libelle, string $compteComptable, bool $tva = false): bool
    {
        $pdo = Database::getPDO();
        $sql = "UPDATE TypeDepense SET Libelle = :libelle, CompteComptable = :compte, tva = :tva WHERE Identifiant = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':libelle' => $libelle,
            ':compte' => $compteComptable,
            ':tva' => $tva ? 1 : 0
        ]);
    }

    public function deleteTypeDepense($id)
    {
        $pdo = Database::getPDO();
        $sql = "DELETE FROM TypeDepense WHERE Identifiant = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}