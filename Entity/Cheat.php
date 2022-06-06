<?php

namespace CheatSite\Entity;

use XF\Entity\UserGroup;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * @property int $id
 * @property int $group_id
 * @property string $name
 * @property string $dll_name
 * @property string $dll_path
 * @property string $sys_name
 * @property string $sys_path
 *
 * @property UserGroup $group
 */
class Cheat extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'cheats_cheat';
        $structure->shortName = 'CheatSite:Cheat';
        $structure->primaryKey = 'id';

        $structure->columns = [
            'id' => ['type' => self::UINT, 'autoIncrement' => true],
            'group_id' => ['type' => self::UINT, 'required' => true],
            'name' => ['type' => self::STR, 'required' => true],
            'dll_name' => ['type' => self::STR, 'required' => true],
            'dll_path' => ['type' => self::STR, 'required' => true],
            'sys_name' => ['type' => self::STR, 'required' => true],
            'sys_path' => ['type' => self::STR, 'required' => true],
        ];
        $structure->getters = [];

        $structure->relations = [
            'group' => [
                'entity' => 'XF:UserGroup',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['user_group_id', '=', '$group_id']
                ],
                'primary' => false
            ]
        ];

        return $structure;
    }
}
