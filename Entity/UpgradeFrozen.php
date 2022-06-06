<?php

namespace CheatSite\Entity;

use XF\Mvc\Entity\Structure;
use XF\Mvc\Entity\Entity;

use XF\Entity\UserUpgradeActive;

/**
 * COLUMNS
 * @property int|null user_upgrade_record_id
 * @property int user_id
 * @property string|null purchase_request_key
 * @property int user_upgrade_id
 * @property array extra
 * @property int start_date
 * @property int end_date
 *
 * @property int left_time
 * @property int freeze_date
 *
 * RELATIONS
 * @property \XF\Entity\UserUpgrade Upgrade
 * @property \XF\Entity\User User
 * @property \XF\Entity\PurchaseRequest PurchaseRequest
 */
class UpgradeFrozen extends Entity
{

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'cheats_user_upgrade_frozen';
        $structure->shortName = 'CheatSite:UpgradeFrozen';
        $structure->primaryKey = 'user_upgrade_record_id';
        $structure->columns = [
            'user_upgrade_record_id' => ['type' => self::UINT, 'autoIncrement' => false, 'nullable' => false],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'purchase_request_key' => ['type' => self::STR, 'maxLength' => 32, 'nullable' => true],
            'user_upgrade_id' => ['type' => self::UINT, 'required' => true],
            'extra' => ['type' => self::JSON_ARRAY, 'default' => []],
            'start_date' => ['type' => self::UINT, 'default' => 0],
            'end_date' => ['type' => self::UINT, 'default' => 0],
            'left_time' => ['type' => self::UINT, 'required' => true],
            'freeze_date' => ['type' => self::UINT, 'required' => true]
        ];
        $structure->getters = [
            'leftTimeFormatted' => true
        ];
        $structure->relations = [
            'Upgrade' => [
                'entity' => 'XF:UserUpgrade',
                'type' => self::TO_ONE,
                'conditions' => 'user_upgrade_id',
                'primary' => true
            ],
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
            'PurchaseRequest' => [
                'entity' => 'XF:PurchaseRequest',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['request_key', '=', '$purchase_request_key']
                ]
            ]
        ];

        return $structure;
    }

    public function getLeftTimeFormatted()
    {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@" . $this->left_time);
        return $dtF->diff($dtT)->format('%a days, %H hours, %I minutes');
    }
}
