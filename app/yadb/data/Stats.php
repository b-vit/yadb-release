<?php


namespace yadb\data;

/**
 * Class Stats
 * ORM F3 Cortex model pro interakci s databází. Mapování třídy na tabulku stat.
 * @package yadb\data
 */
class Stats extends \DB\Cortex
{
    protected $db = 'DB';
    protected $table = 'stat';
    protected $primary = 'id';

    protected $fieldConf = [
        'date' => [
            'type' => 'DT_DATE',
            'nullable' => false
        ],
        'visitors' => [
            'type' => 'INT4',
            'nullable' => false
        ]
    ];
}
