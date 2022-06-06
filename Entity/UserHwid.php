<?php

namespace CheatSite\Entity;

use XF\Mvc\Entity\Structure;
use XF\Mvc\Entity\Entity;

/**
 * @property int id
 * @property int user_id
 * @property string hwid
 * @property string last_change_date
 */
class UserHwid extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'cheats_user_hwid';
        $structure->shortName = 'CheatSite:UserHwid';
        $structure->primaryKey = 'id';

        $structure->columns = [
            'id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'hwid' => ['type' => self::STR, 'required' => false, 'nullable' => true],
            'last_change_date' => ['type' => self::STR, 'required' => false, 'nullable' => true]
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
