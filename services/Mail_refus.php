<?php

$email= $_GET['email'];
//echo 'Email : '.$email;
//$email= 'informatique@subterra.fr';

if (!empty($email)) {
    $message = "<html lang='fr'>
        <head>
            <title>Notification Notes de frais</title>                                       
        </head>
        <body>
        <p>Bonjour,</p>
        <p>Votre note de frais à été refusée !</p>
        <p>Merci de vous connecter sur votre <a href='https://extranet.subterra.fr/index.php?option=com_users&amp;view=login&amp;Itemid=101'>espace collaborateur &laquo;Notes de frais&nbsp;&raquo;</a></p>
        <p>&nbsp;</p>
        <p>Ceci est un message automatique, merci de ne pas y r&eacute;pondre.</p>
        <p><img src='https://extranet.subterra.fr/notesdefrais/Logo-Subterra.png' alt='' width='270' height='80' /></p>
        </body>
        </html>";

    // To send HTML mail, the Content-type header must be set
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    // Additional headers
    $headers[] = 'From: Note de Frais <nepasrepondre@subterra.fr>';
    mail($email, 'Notification Note de Frais - Subterra', $message, implode("\r\n", $headers));

}