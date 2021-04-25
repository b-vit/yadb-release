<?php


namespace yadb\data;

/**
 * Class Roles
 * ORM F3 Cortex model pro interakci s databází. Mapování třídy na tabulku role.
 * @package yadb\data
 */
class Roles extends \DB\Cortex
{
    protected $db = 'DB';
    protected $table = 'role';
    protected $primary = 'id';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR256',
            'nullable' => false
        ],
        'permissions' => [
            'type' => 'INT4',
            'nullable' => false
        ],
        'user'=>[
            'has-many' => array('\yadb\data\User','role')
        ]
    ];
}
