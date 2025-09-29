<?php

use services\Config;

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">

    <title>Erreur 500</title>

    <!-- Importation des fichiers CSS pour le style -->
    <link href="<?= Config::get("APP_URL") ?>/assets/vendor/bootstrap-5.3.5-dist/css/bootstrap.min.css"
          rel="stylesheet"> <!-- Framework Bootstrap -->
</head>
<body>
<!-- Conteneur principal pour la page -->
<div class="container">
    <div class="row justify-content-center align-items-center vh-100">
        <div class="col-6 col-md-6 col-sm-6 align-self-center">

            <p class="titre-erreur text-center">500</p>

            <p class="titre text-center">Erreur interne du serveur</p>

            <p class="text-center">
                <?php

                if (isset($message)) {
                    echo $message;
                }

                ?>

            </p>
        </div>
    </div>
</div>
</body>
</html>