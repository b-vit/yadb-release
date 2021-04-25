<?php


namespace yadb\data;

/**
 * Class ImageY
 * ORM F3 Cortex model pro interakci s databází. Mapování třídy na tabulku image
 * @package yadb\data
 */
class ImageY extends \DB\Cortex
{
    protected $db = 'DB';
    protected $table = 'image';
    protected $primary = 'id';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR256',
            'nullable' => false,
            'unique' => true
        ],
        'owner' => [
            'belongs-to-one' => '\yadb\data\User'
        ],
        'size'=>[
            'type'=>'INT4',
            'nullable'=>false
        ],
        'uploaded'=>[
            'type'=>'DT_DATE',
            'nullable'=>false
        ]
    ];
}