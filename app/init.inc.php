<?php
/**
 * init.inc.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * This must be included on top of every page.
 * It loads the config file, connects to the database,
 * includes functions and locale, tries to update the db schema and redirects anonymous visitors.
 */
if (!isset($_SESSION)) {
    session_start();
}

// add check for php version here also
if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.6', '<')) {
    $message = "Your version of PHP isn't recent enough. Please update your php version to at least 5.6";
    die($message);
}

// check that the config file is here and readable
if (is_readable('config.php')) {
    require_once 'config.php';
} elseif (is_readable('../config.php')) {
    // we might be called from app folder
    require_once '../config.php';
} elseif (is_readable('../../config.php')) {
    require_once '../../config.php';
} else {
    header('Location: install');
    exit;
}

require_once ELAB_ROOT . 'vendor/autoload.php';

// SQL CONNECT
try {
    $pdo = Db::getConnection();
} catch (Exception $e) {
    die('Error connecting to the database : ' . $e->getMessage());
}
// END SQL CONNECT

// require common stuff
require_once ELAB_ROOT . 'app/functions.inc.php';

// i18n (gettext)
if (isset($_SESSION['prefs']['lang'])) {
    $locale = $_SESSION['prefs']['lang'] . '.utf8';
} else {
    // this will throw an exception if the SQL structure is not imported yet
    // so we redirect to the install folder
    try {
        $locale = get_config('lang') . '.utf8';
    } catch (Exception $e) {
        header('Location: install');
        exit;
    }
}
$domain = 'messages';
putenv("LC_ALL=$locale");
$res = setlocale(LC_ALL, $locale);
bindtextdomain($domain, ELAB_ROOT . "locale");
textdomain($domain);
// END i18n

// run the update script if we have the wrong schema version
$Update = new Update(new Config);
if (get_config('schema') < $Update::REQUIRED_SCHEMA) {
    try {
        $_SESSION['ok'] = $Update->runUpdateScript();
    } catch (Exception $e) {
        $_SESSION['ko'][] = 'Error updating: ' . $e->getMessage();
    }
}

// pages where you don't need to be logged in
// reset.php is in fact app/reset.php but we use basename so...
$nologin_arr = array('login.php', 'login-exec.php', 'register.php', 'register-exec.php', 'change-pass.php', 'reset.php', 'ResetPasswordController.php');

if (!isset($_SESSION['auth']) && !in_array(basename($_SERVER['SCRIPT_FILENAME']), $nologin_arr)) {
    // try to login with the cookie
    $Auth = new Auth();
    if (!$Auth->loginWithCookie()) {
        // maybe we clicked an email link and we want to be redirected to the page upon successful login
        // so we store the url in a cookie expiring in 5 minutes to redirect to it after login
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $params = '?' . $_SERVER['QUERY_STRING'];
        $url = 'https://' . $host . $script . $params;
        // remove trailing ? if there was no query string
        $url = rtrim($url, '?');

        setcookie('redirect', $url, time() + 300, '/', null, true, true);

        header('location: app/logout.php');
        exit;
    }
}
