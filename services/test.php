<?php
$mail='informatique@subterra.fr';
$url='https://extranet.subterra.fr/notesdefrais/services/Mail_refus.php?email='.$mail;
header('Location: '.$url);
exit;