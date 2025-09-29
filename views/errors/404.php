<?php

use services\Config;

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">

    <title>Erreur 404</title>

    <!-- Importation des fichiers CSS pour le style -->
    <link href="<?= Config::get("APP_URL") ?>/assets/vendor/bootstrap-5.3.5-dist/css/bootstrap.min.css"
          rel="stylesheet"> <!-- Framework Bootstrap -->
</head>
<body>
<!-- Conteneur principal pour la page -->
<div class="container">
    <div class="row justify-content-center align-items-center vh-100">
        <div class="col-6 col-md-6 col-sm-6 align-self-center">

            <p class="titre-erreur text-center">404</p>

            <p class="titre text-center">Page non trouvée</p>

            <p class="text-center">
                La page que vous tentez d'afficher n'existe pas ou une autre erreur s'est produite.
                Vous pouvez revenir à
                <a class="text-primary" href="/">la page d'accueil</a>
                .
            </p>
        </div>
    </div>
</div>
</body>
</html>