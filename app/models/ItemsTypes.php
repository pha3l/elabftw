<?php
/**
 * \Elabftw\Elabftw\ItemsTypes
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends Entity
{
    /** The PDO object */
    protected $pdo;

    /**
     * Constructor
     *
     * @param int $team
     * @throws Exception if user is not admin
     */
    public function __construct($team)
    {
        $this->pdo = Db::getConnection();
        $this->team = $team;
    }

    /**
     * Create an item type
     *
     * @param string $name New name
     * @param string $color hexadecimal color code
     * @param int $bookable
     * @param string $template html for new body
     * @return bool true if sql success
     */
    public function create($name, $color, $bookable, $template)
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if (strlen($name) < 1) {
            $name = 'Unnamed';
        }

        $color = filter_var(substr($color, 0, 6), FILTER_SANITIZE_STRING);
        $template = Tools::checkBody($template);
        $sql = "INSERT INTO items_types(name, bgcolor, bookable, template, team) VALUES(:name, :bgcolor, :bookable, :template, :team)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':bgcolor', $color);
        $req->bindParam(':bookable', $bookable, PDO::PARAM_INT);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $this->team);

        return $req->execute();
    }

    /**
     * Read from an id
     *
     * @param int $id
     * @return array
     */
    public function read($id)
    {
        $sql = "SELECT template FROM items_types WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $this->team);
        $req->execute();

        if ($req->rowCount() === 0) {
            throw new Exception(_('Nothing to show with this id'));
        }

        return $req->fetchColumn();
    }

    /**
     * SQL to get all items type
     *
     * @return array all the items types for the team
     */
    public function readAll()
    {
        $sql = "SELECT * from items_types WHERE team = :team ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Update an item type
     *
     * @param int $id The ID of the item type
     * @param string $name name
     * @param string $color hexadecimal color
     * @param int $bookable
     * @param string $template html for the body
     * @return bool true if sql success
     */
    public function update($id, $name, $color, $bookable, $template)
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $color = filter_var($color, FILTER_SANITIZE_STRING);
        $template = Tools::checkBody($template);
        $sql = "UPDATE items_types SET
            name = :name,
            team = :team,
            bgcolor = :bgcolor,
            bookable = :bookable,
            template = :template
            WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':bgcolor', $color);
        $req->bindParam(':bookable', $bookable, PDO::PARAM_INT);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Count all items of this type
     *
     * @param int $id of the type
     * @return int
     */
    private function countItems($id)
    {
        $sql = "SELECT COUNT(*) FROM items WHERE type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':type', $id);
        $req->execute();
        return (int) $req->fetchColumn();
    }

    /**
     * Destroy an item type
     *
     * @param int $id
     * @return bool
     */
    public function destroy($id)
    {
        // don't allow deletion of an item type with items
        if ($this->countItems($id) > 0) {
            throw new Exception(_("Remove all database items with this type before deleting this type."));
        }
        $sql = "DELETE FROM items_types WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $this->team);

        return $req->execute();
    }
}
