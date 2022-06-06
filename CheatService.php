<?php

namespace CheatSite;

use CheatSite\Entity\Cheat;
use CheatSite\Entity\FreezeHistory;
use CheatSite\Entity\HwidReset;
use CheatSite\Entity\UpgradeFrozen;
use CheatSite\Entity\UserHwid;
use XF\Entity\User;
use XF\Entity\UserUpgradeActive;

class CheatService
{
    const RESET_TIMEOUT = 3600 * 24 * 10; // 10 days
    const FREEZE_TIMEOUT = 3600 * 24 * 10; // 10 days
//    const FREEZE_TIMEOUT = 0; // 10 days

    /**
     * @param User $user
     * @return Cheat[]
     */
    public function getAvailableCheats($user)
    {
        if(is_numeric($user)) {
            $user = \XF::finder('XF:User')->whereId($user)->fetchOne();
        }

        $groupIds = array_merge([$user->user_group_id], $user->secondary_group_ids);

        $groupIdsWithCheats = $this->getGroupIdsWithCheats();

        $intersect = array_intersect($groupIds, $groupIdsWithCheats);
        if(empty($intersect)) {
            return [];
        }

        $cheats = \XF::finder('CheatSite:Cheat')->where('group_id', $intersect)->fetch();

        return $cheats;
    }

    public function getGroupIdsWithCheats(): array
    {
        $ids = [];
        $query = 'SELECT group_id FROM cheats_cheat GROUP BY group_id';
        $rows = \XF::db()->fetchAll($query);
        foreach($rows as $row) {
            $ids[] = $row['group_id'];
        }
        return $ids;
    }

    /**
     * @returns UserUpgradeActive[]
     */
    public function getActiveUserUpgrades(int $userId)
    {
        $finder = \XF::finder('XF:UserUpgradeActive');
        $finder->where('user_id', $userId);
        $finder->with('Upgrade');
        $upgrades = $finder->fetch();

        return $upgrades;
    }

    /**
     * @returns UserUpgradeActive[]
     */
    public function getFrozenUserUpgrades(int $userId)
    {
        $finder = \XF::finder('CheatSite:UpgradeFrozen');
        $finder->where('user_id', $userId);
        $finder->with('Upgrade');
        $upgrades = $finder->fetch();

        return $upgrades;
    }

    public function getHwid(int $userId): ?UserHwid
    {
        $finder = \XF::finder('CheatSite:UserHwid');
        $finder->where('user_id', $userId);

        $hwid = $finder->fetchOne();

        if(!$hwid) {
            return null;
        }

        return $hwid;
    }

    public function canResetHwid(int $userId)
    {
        $lastReset = $this->getLastHwidReset($userId);

        if(!$lastReset) {
            return true;
        }

        $now = time();
        $resetTime = strtotime($lastReset->date);
        if($resetTime < ($now - self::RESET_TIMEOUT)) {
            return true;
        }

        return false;
    }

    public function resetHwid(UserHwid $hwid, bool $saveHistory = true)
    {
        if($saveHistory) {
            $reset = \XF::em()->create('CheatSite:HwidReset');
            $reset->user_id = $hwid->user_id;
            $reset->date = date('Y-m-d H:i:s');
            $reset->save();
        }

        $hwid->hwid = null;
        $hwid->save();
    }

    public function getLastHwidReset(int $userId): ?HwidReset
    {
        $lastReset = \XF::Finder('CheatSite:HwidReset')
            ->where('user_id', $userId)
            ->order('date', 'DESC')
            ->fetchOne();

        if(!$lastReset) {
            return null;
        }

        return $lastReset;
    }

    public function canFreezeUpgrade(UserUpgradeActive $upgrade): bool
    {
        $lastFreeze = $this->getLastFreeze($upgrade);
        if(!$lastFreeze) {
            return true;
        }

        $now = time();
        if($lastFreeze->date < ($now - self::FREEZE_TIMEOUT)) {
            return true;
        }

        return false;
    }

    public function getLastFreeze(UserUpgradeActive $upgrade): ?FreezeHistory
    {
        $lastFreeze = \XF::finder('CheatSite:FreezeHistory')
            ->where([
                ['user_id', $upgrade->user_id],
                ['user_upgrade_record_id', $upgrade->user_upgrade_record_id]
            ])
            ->order('date', 'DESC')
            ->fetchOne();

        if(!$lastFreeze) {
            return null;
        }

        return $lastFreeze;
    }

    public function freezeUpgrade(UserUpgradeActive $upgrade)
    {
        /**
         * @var $frozenUpgrade UpgradeFrozen
         */
        $frozenUpgrade = \XF::em()->create('CheatSite:UpgradeFrozen');

        $now = time();

        \XF::db()->beginTransaction();

        $frozenUpgrade->user_upgrade_record_id = $upgrade->user_upgrade_record_id;
        $frozenUpgrade->user_id = $upgrade->user_id;
        $frozenUpgrade->user_upgrade_id = $upgrade->user_upgrade_id;
        $frozenUpgrade->purchase_request_key = $upgrade->purchase_request_key;
        $frozenUpgrade->extra = $upgrade->extra;
        $frozenUpgrade->start_date = $upgrade->start_date;
        $frozenUpgrade->end_date = $upgrade->end_date;
        $frozenUpgrade->left_time = $frozenUpgrade->end_date - $now;
        $frozenUpgrade->freeze_date = $now;

        $frozenUpgrade->save();
        $upgrade->delete();

        /**
         * @var $freezeHistory FreezeHistory
         */
        $freezeHistory = \XF::em()->create('CheatSite:FreezeHistory');
        $freezeHistory->user_id = $frozenUpgrade->user_id;
        $freezeHistory->user_upgrade_record_id = $frozenUpgrade->user_upgrade_record_id;
        $freezeHistory->date = $now;
        $freezeHistory->save();

        \XF::db()->commit();
    }

    public function unfreezeUpgrade(UpgradeFrozen $frozenUpgrade)
    {
        /**
         * @var $activeUpgrade UserUpgradeActive
         */
        $activeUpgrade = \XF::em()->create('XF:UserUpgradeActive');

        $now = time();

        \XF::db()->beginTransaction();

        $recordId = $frozenUpgrade->user_upgrade_record_id;

//        $activeUpgrade->user_upgrade_record_id = $frozenUpgrade->user_upgrade_record_id;
        $activeUpgrade->user_id = $frozenUpgrade->user_id;
        $activeUpgrade->user_upgrade_id = $frozenUpgrade->user_upgrade_id;
        $activeUpgrade->purchase_request_key = $frozenUpgrade->purchase_request_key;
        $activeUpgrade->extra = $frozenUpgrade->extra;
        $activeUpgrade->start_date = $frozenUpgrade->start_date;
        $activeUpgrade->end_date = $now + $frozenUpgrade->left_time;;
        $activeUpgrade->save();

        $activeUpgrade->fastUpdate('user_upgrade_record_id', $recordId);

        $frozenUpgrade->delete();

        \XF::db()->commit();
    }
}
