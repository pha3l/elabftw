<?php
/**
 * app/controllers/SchedulerController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Controller for the scheduler
 *
 */
try {
    require_once '../../app/init.inc.php';
    $Scheduler = new Scheduler($_SESSION['team_id']);

    // CREATE
    if (isset($_POST['create'])) {
        if ($Scheduler->create($_POST['item'], $_POST['start'], $_POST['end'], $_POST['title'], $_SESSION['userid'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // UPDATE START
    if (isset($_POST['updateStart'])) {
        $Scheduler->setId($_POST['id']);
        if ($Scheduler->updateStart($_POST['start'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }
    // UPDATE END
    if (isset($_POST['updateEnd'])) {
        $Scheduler->setId($_POST['id']);
        if ($Scheduler->updateEnd($_POST['end'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }
    // DESTROY
    if (isset($_POST['destroy'])) {
        $Scheduler->setId($_POST['id']);
        if ($Scheduler->destroy()) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Event deleted successfully')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
