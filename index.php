<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// Chargement des dépendances
require 'vendor/autoload.php';
require 'services/Database.php';
require 'services/Config.php';
require 'services/NoteDeFrais.php';
require 'services/Ticket.php';
require 'services/Groupe.php';
require 'services/TypeDepense.php';
require 'services/MotifRejet.php';
require 'services/Utilisateur.php';
require 'controllers/AccueilController.php';
require 'controllers/TicketController.php';
require 'controllers/CreerNoteDeFraisController.php';
require 'controllers/ModifierNoteDeFraisController.php';
require 'controllers/TraitementNotesDeFraisController.php';
require 'controllers/TraitementNoteDeFraisController.php';
require 'controllers/GererUtilisateursController.php';
require 'controllers/NotesDeFraisArchiveesController.php';
require 'controllers/SyntheseNotesFraisController.php';
require 'controllers/GererTypesDepenseController.php';
require 'services/Pdf.php';
require 'services/Mail.php';

use Bramus\Router\Router;
use controllers\AccueilController;
use controllers\CreerNoteDeFraisController;
use controllers\GererTypesDepenseController;
use controllers\GererUtilisateursController;
use controllers\ModifierNoteDeFraisController;
use controllers\NotesDeFraisArchiveesController;
use controllers\SyntheseNotesFraisController;
use controllers\TicketController;
use controllers\TraitementNoteDeFraisController;
use controllers\TraitementNotesDeFraisController;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$router = new Router();

session_start();
// Configuration de la base de données Joomla
define('_JEXEC', 1);

if (file_exists(dirname(__FILE__) . '/../../configuration.php')) {
    define('JPATH_BASE', dirname(__FILE__) . '/../..');
} elseif (file_exists(dirname(__FILE__) . '/../configuration.php')) {
    define('JPATH_BASE', dirname(__FILE__) . '/..');
} else {
    die('Impossible de trouver le fichier de configuration Joomla');
}

// Inclusion de la configuration Joomla
require_once JPATH_BASE . '/configuration.php';
$config = new JConfig();

// Fonction pour récupérer l'utilisateur depuis la session Joomla
function getJoomlaUserFromSession()
{
    global $config;

    // Configuration de la connexion BDD
    $host = $config->host;
    $database = $config->db;
    $username = $config->user;
    $password = $config->password;
    $prefix = $config->dbprefix;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sessionName = md5($config->secret . 'site');
        $sessionId = $_COOKIE[$sessionName] ?? null;

        if (!$sessionId) {
            foreach ($_COOKIE as $name => $value) {
                if (strpos($name, 'joomla') !== false || strlen($name) == 32) {
                    $sessionId = $value;
                    break;
                }
            }
        }

        if ($sessionId) {
            $sql = "SELECT session_id, data, userid FROM {$prefix}session WHERE session_id = :session_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId);
            $stmt->execute();
            $sessionData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sessionData) {
                if ($sessionData['userid'] > 0) {
                    $userSql = "SELECT * FROM {$prefix}users WHERE id = :userid";
                    $userStmt = $pdo->prepare($userSql);
                    $userStmt->bindParam(':userid', $sessionData['userid']);
                    $userStmt->execute();

                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                    if ($user) {
                        $groupsSql = "SELECT group_id FROM {$prefix}user_usergroup_map WHERE user_id = :userid";
                        $groupsStmt = $pdo->prepare($groupsSql);
                        $groupsStmt->bindParam(':userid', $sessionData['userid']);
                        $groupsStmt->execute();

                        $groups = $groupsStmt->fetchAll(PDO::FETCH_COLUMN);

                        // Supprimer cet appel à session_start() car la session est déjà active
                        $_SESSION['LibelleUtilisateur'] = $user['name'];
                        $_SESSION['Role'] = $groups;
                        $_SESSION['id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];

                        return [
                            'id' => $user['id'],
                            'name' => $user['name'],
                            'username' => $user['username'],
                            'email' => $user['email'],
                            'groups' => $groups,
                            'guest' => false
                        ];
                    }
                }
            } else {
                echo "################  Vous êtes déconnecté du serveur ! Veuillez vous reconnecter d'abord !  ################<br>";
            }
        }

        // Si aucune session valide trouvée
        return [
            'id' => 0,
            'name' => '',
            'username' => '',
            'email' => '',
            'groups' => [],
            'guest' => true
        ];

    } catch (Exception $e) {
        echo "Erreur connexion BDD: " . $e->getMessage() . "<br>";
        return null;
    }
}

getJoomlaUserFromSession();

// Route pour la page d'accueil
$router->get('/', [new AccueilController(), 'get']);
$router->post('/', [new AccueilController(), 'post']);

// Routes pour la création de notes de frais
$router->get('/creer-note-frais', [new CreerNoteDeFraisController(), 'get']);
$router->post('/creer-note-frais', [new CreerNoteDeFraisController(), 'post']);

/**
 * Route pour visualiser une note de frais
 * @param int $id Identifiant de la note de frais
 */
$router->get('/voir-note-frais/(\d+)', function ($id) {
    $controller = new TicketController();
    $controller->get($id);
});

/**
 * Routes pour modifier une note de frais
 * @param int $id Identifiant de la note de frais
 */
$router->get('/modifier-note-frais/(\d+)', function ($id) {
    $controller = new ModifierNoteDeFraisController();
    $controller->get($id);
});
$router->post('/modifier-note-frais/(\d+)', function ($id) {
    $controller = new ModifierNoteDeFraisController();
    $controller->post($id);
});
// Routes existantes pour la vérification
$router->get('/verifier-notes-frais', [new TraitementNotesDeFraisController('verification'), 'get']);
$router->post('/verifier-notes-frais/(\d+)', [new TraitementNotesDeFraisController('verification'), 'post']);

// Nouvelles routes pour la validation
$router->get('/valider-notes-frais', function () {
    $controller = new TraitementNotesDeFraisController('validation');
    $controller->get();
});
$router->post('/valider-notes-frais/(\d+)', function ($id) {
    $controller = new TraitementNotesDeFraisController('validation');
    $controller->post($id);
});

// Route existante pour vérifier un ticket
$router->get('/verifier-ticket/(\d+)', function ($id) {
    $controller = new TraitementNoteDeFraisController('verification');
    $controller->get($id);
});
$router->post('/verifier-ticket/(\d+)', function ($id) {
    $controller = new TraitementNoteDeFraisController('verification');
    $controller->post($id);
});

// Nouvelles routes pour valider un ticket
$router->get('/valider-ticket/(\d+)', function ($id) {
    $controller = new TraitementNoteDeFraisController('validation');
    $controller->get($id);
});
$router->post('/valider-ticket/(\d+)', function ($id) {
    $controller = new TraitementNoteDeFraisController('validation');
    $controller->post($id);
});

$router->get('/traitement-comptable', function () {
    $controller = new TraitementNotesDeFraisController('comptable');
    $controller->get();
});

$router->post('/traitement-comptable/(\d+)', function ($id) {
    $controller = new TraitementNotesDeFraisController('comptable');
    $controller->post($id);
});

$router->get('/exporter-note-frais/', function ($id) {
    $controller = new TraitementNotesDeFraisController('comptable');
    $controller->get();
});

$router->get('/gerer-utilisateurs', [new GererUtilisateursController(), 'get']);
$router->post('/gerer-utilisateurs', [new GererUtilisateursController(), 'post']);

// Add this route to handle POST requests to /verifier-notes-frais (without ID)
$router->post('/verifier-notes-frais', function () {
    $controller = new TraitementNotesDeFraisController('verification');
    $controller->post();
});

// Same for validation route
$router->post('/valider-notes-frais', function () {
    $controller = new TraitementNotesDeFraisController('validation');
    $controller->post();
});

// And for comptable route
$router->post('/traitement-comptable', function () {
    $controller = new TraitementNotesDeFraisController('comptable');
    $controller->post();
});

// In your index.php or routes file
$router->get('/notes-frais-archives', [new NotesDeFraisArchiveesController(), 'get']);
$router->post('/notes-frais-archives', [new NotesDeFraisArchiveesController(), 'post']);

// Route pour la synthèse des notes de frais
$router->get('/synthese-notes-frais', [new SyntheseNotesFraisController(), 'get']);
$router->post('/synthese-notes-frais', [new SyntheseNotesFraisController(), 'post']);

// Gestion des types de dépense (affichage et modification)
$router->get('/gerer-types-depense', [new GererTypesDepenseController(), 'get']);
$router->post('/gerer-types-depense', [new GererTypesDepenseController(), 'post']);

/**
 * Configuration de la page 404
 */
$router->set404(function () {
    header('HTTP/1.1 404 Not Found');
    require 'views/errors/404.php';
});

// Exécution du routeur
$router->run();