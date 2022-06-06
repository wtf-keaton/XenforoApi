<?php

namespace CheatSite\Entity;

use XF\Entity\User;
use XF\Mvc\Entity\Structure;
use XF\Mvc\Entity\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $date
 * @property int $timestamp
 * @property string $message
 * @property User $user
 */
class Log extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'cheats_log';
        $structure->shortName = 'CheatSite:Log';
        $structure->primaryKey = 'id';

        $structure->columns = [
            'id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'date' => ['type' => self::STR, 'required' => true],
            'message' => ['type' => self::STR, 'required' => true],
        ];
        $structure->getters = [
            'timestamp' => true
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

    public function getTimestamp()
    {
        return strtotime($this->date);
    }
}
