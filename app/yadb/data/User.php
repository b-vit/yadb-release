<?php


namespace yadb\data;

/**
 * Class User
 * ORM F3 Cortex model pro interakci s databází. Mapování třídy na tabulku users.
 * @package yadb\data
 */
class User extends \DB\Cortex
{
    protected $db = 'DB';
    protected $table = 'y_user';
    protected $primary = 'id';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR256',
            'nullable' => false,
            'unique' => true
        ],
        'role' => [
            'belongs-to-one' => '\yadb\data\Roles'
        ],
        'password' => [
            'type' => 'VARCHAR256',
            'nullable' => false
        ],
        'image' =>[
            'has-many' => array('\yadb\data\ImageY','owner')
        ]
    ];
}