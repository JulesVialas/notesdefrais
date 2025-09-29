<?php

namespace services;

class MotifRejet
{
    /** @var array Tableau contenant tous les motifs de refus chargés depuis la base de données */
    private array $motifsRejet;

    /**
     * Constructeur de la classe MotifRefus
     *
     * Initialise la liste des motifs de refus en les récupérant depuis la base de données.
     */
    public function __construct()
    {
        $pdo = Database::getPDO();
        $sql = "SELECT * FROM MotifRejet";
        $req = $pdo->prepare($sql);
        $req->execute();
        $this->motifsRejet = $req->fetchAll();
    }

    /**
     * Récupère tous les motifs de refus
     *
     * @return array Tableau des motifs de refus
     */
    public function getMotifsRejet(): array
    {
        return $this->motifsRejet;
    }
}