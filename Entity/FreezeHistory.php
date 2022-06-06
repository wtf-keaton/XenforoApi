<?php

namespace CheatSite\Entity;

use XF\Mvc\Entity\Structure;
use XF\Mvc\Entity\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property int $user_upgrade_record_id
 * @property int $date
 */
class FreezeHistory extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'cheats_freeze_history';
        $structure->shortName = 'CheatSite:FreezeHistory';
        $structure->primaryKey = 'id';

        $structure->columns = [
            'id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'user_upgrade_record_id' => ['type' => self::UINT, 'required' => true],
            'date' => ['type' => self::UINT, 'required' => true]
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
