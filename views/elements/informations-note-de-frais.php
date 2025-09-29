<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Informations</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Date de
                                demande:</strong> <?= date('d/m/Y', strtotime($_SESSION['temp_note_frais']['DateDemande'])) ?>
                        </p>
                        <p>
                            <strong>Statut:</strong>
                            <span class="badge <?= isset($_SESSION['temp_note_frais']['Statut']) && $_SESSION['temp_note_frais']['Statut'] === 'Refusée' ? 'bg-danger' : 'bg-primary' ?>">
                                <?= $_SESSION['temp_note_frais']['Statut'] ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong>Total
                                TTC:</strong> <?= number_format($_SESSION['temp_note_frais']['TotalTTC'], 2, ',', ' ') ?>
                            €</p>
                        <p><strong>Total
                                TVA:</strong> <?= number_format($_SESSION['temp_note_frais']['TotalTVA'], 2, ',', ' ') ?>
                            €</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>