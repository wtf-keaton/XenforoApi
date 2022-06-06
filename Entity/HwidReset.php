<?php

namespace CheatSite\Entity;

use XF\Mvc\Entity\Structure;
use XF\Mvc\Entity\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $date
 */
class HwidReset extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'cheats_hwid_reset';
        $structure->shortName = 'CheatSite:HwidReset';
        $structure->primaryKey = 'id';

        $structure->columns = [
            'id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'date' => ['type' => self::STR, 'required' => true]
        ];

        $structure->relations = [
            'user' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
            ]
        ];

        return $structure;
    }
}
