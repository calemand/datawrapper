<?php

/**
 * Datawrapper main index
 *
 */



define('DATAWRAPPER_VERSION', '1.10.2');  // must match with package.json

define('ROOT_PATH', '../');

require_once ROOT_PATH . 'vendor/autoload.php';

check_server();

require ROOT_PATH . 'lib/bootstrap.php';

$twig = $app->view()->getEnvironment();
dwInitTwigEnvironment($twig);

require_once ROOT_PATH . 'controller/plugin-templates.php';
require_once ROOT_PATH . 'controller/home.php';
require_once ROOT_PATH . 'controller/login.php';
require_once ROOT_PATH . 'controller/account.php';
require_once ROOT_PATH . 'controller/chart/create.php';
require_once ROOT_PATH . 'controller/chart/edit.php';
require_once ROOT_PATH . 'controller/chart/upload.php';
require_once ROOT_PATH . 'controller/chart/describe.php';
require_once ROOT_PATH . 'controller/chart/visualize.php';
require_once ROOT_PATH . 'controller/chart/data.php';
require_once ROOT_PATH . 'controller/chart/preview.php';
require_once ROOT_PATH . 'controller/chart/embed.php';
require_once ROOT_PATH . 'controller/chart/publish.php';
require_once ROOT_PATH . 'controller/mycharts.php';
require_once ROOT_PATH . 'controller/xhr.php';
require_once ROOT_PATH . 'controller/admin.php';


$app->notFound(function() {
    error_not_found();
});

if ($dw_config['debug']) {
    $app->get('/phpinfo', function() use ($app) {
        phpinfo();
    });
}

/*
 * before processing any other route we check if the
 * user is not logged in and if prevent_guest_access is activated.
 * if both is true we redirect to /login
 */
$app->hook('slim.before.router', function () use ($app, $dw_config) {
    $user = DatawrapperSession::getUser();

    // allow logged-in users
    if ($user->isLoggedIn()) return;

    // allow access if this is a public installation
    if (empty($dw_config['prevent_guest_access'])) return;

    // allow access if a proper secret is given (required for publishing charts
    // (see download()) in private installations)
    $requiredKey = sha1(isset($dw_config['secure_auth_key']) ? $dw_config['secure_auth_key'] : '');
    $givenKey    = isset($_REQUEST['seckey']) ? $_REQUEST['seckey'] : null;

    if ($requiredKey === $givenKey) return;

    $req = $app->request();
    if (UserQuery::create()->filterByRole(array('admin', 'sysadmin'))->count() > 0) {
        if ($req->getResourceUri() != '/login' &&
            strncmp($req->getResourceUri(), '/account/invite/', 16) && // and doesn't start with '/account/invite/'
            strncmp($req->getResourceUri(), '/account/reset-password/', 24)) { // and doesn't start with '/account/reset-password/'
            $app->redirect('/login');
        }
    }
    else {
        if ($req->getResourceUri() != '/setup') {
            $app->redirect('/setup');
        }
    }
});


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This is responsible for executing
 * the Slim application using the settings and routes defined above.
 */

$app->run();

