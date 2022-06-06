<?php

namespace CheatSite\Admin\Controller;

use CheatSite\CheatService;
use XF\Admin\Controller\AbstractController;

class UpgradesController extends AbstractController
{
    public function actionIndex()
    {
        $cheatService = new CheatService();

        $page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 0;
        $perPage = 30;

        $order = 'start_date';
        $direction = 'DESC';

        $batchResult = null;

        if(isset($_POST['resetHwid'])) {
            $upgradeIds = $_POST['upgrade_ids'] ?? [];
            foreach($upgradeIds as $id) {
                $id = (int) $id;
                $upgrade = \XF::finder('XF:UserUpgradeActive')->whereId($id)->fetchOne();
                if(!$upgrade) {
                    continue;
                }
                $hwid = \XF::finder('CheatSite:UserHwid')->where('user_id', $upgrade->user_id)->fetchOne();
                if($hwid) {
                    $cheatService->resetHwid($hwid, false);
                    $batchResult = true;
                }
            }
        }

        if(isset($_POST['addDays'])) {
            $upgradeIds = $_POST['upgrade_ids'] ?? [];
            $days = $_POST['days'] ?? 0;
            $days = (int) $days;
            $plus = $days * 3600 * 24;

            foreach($upgradeIds as $id) {
                $id = (int) $id;
                $upgrade = \XF::finder('XF:UserUpgradeActive')->whereId($id)->fetchOne();
                if(!$upgrade) {
                    continue;
                }
                $upgrade->end_date = $upgrade->end_date + $plus;
                $upgrade->save();

                $batchResult = true;
            }
        }


        $finder = \XF::finder('XF:UserUpgradeActive');

        $criteria = [];

        $finder->limitByPage($page, $perPage);
        $finder->with('User');
        $finder->with('Upgrade');
        $finder->order($order, $direction);

        $upgrades = $finder->fetch();

        $total = $finder->total();
        $countUpgrades = count($upgrades);

        $userIds = [];
        foreach($upgrades as $upgrade) {
            $userIds[] = $upgrade->user_id;
        }
        $hwids = \XF::finder('CheatSite:UserHwid')->where('user_id', $userIds)->fetch();
        $_tmp = [];
        foreach($hwids as $hwid) {
            $_tmp[$hwid->user_id] = $hwid;
        }
        $hwids = $_tmp;

        return $this->view('CheatSite:View', 'cheats_upgrades_index', [
            'upgrades' => $upgrades,
            'countUpgrades' => $countUpgrades,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'order' => $order,
            'direction' => $direction,
            'criteria' => $criteria,
            'batchResult' => $batchResult,
            'hwids' => $hwids
        ]);
    }

    public function actionView($params)
    {
        // user upgrade record id

        $id = $_GET['id'] ?? null;
        $id = (int) $id;
        if(!$id) {
            throw new \Exception('Bad request');
        }

        $currentUpgrade = \XF::finder('XF:UserUpgradeActive')->whereId($id)->fetchOne();
        if(!$currentUpgrade) {
            $currentUpgrade = \XF::finder('CheatSite:UpgradeFrozen')->whereId($id)->fetchOne();
        }

        if(!$currentUpgrade) {
            throw new \Exception('Subscription is not found.');
        }

        $userId = $currentUpgrade->user_id;

        $user = \XF::finder('XF:User')->whereId($userId)->fetchOne();

        $cheatService = new CheatService();

        $activeUpgrades = $cheatService->getActiveUserUpgrades($userId);
        $frozenUpgrades = $cheatService->getFrozenUserUpgrades($userId);

        $allUpgrades = [];
        foreach($activeUpgrades as $v) {
            $allUpgrades[$v->user_upgrade_record_id] = [
                'type' => 'active',
                'upgrade' => $v,
            ];
        }
        foreach($frozenUpgrades as $v) {
            $allUpgrades[$v->user_upgrade_record_id] = [
                'type' => 'frozen',
                'upgrade' => $v,
            ];
        }
        $countAllUpgrades = count($allUpgrades);

        $hwid = \XF::finder('CheatSite:UserHwid')->where('user_id', $userId)->fetchOne();

        if(isset($_POST['resetHwid'])) {
            $cheatService->resetHwid($hwid, false);
        }

        $logFinder = \XF::finder('CheatSite:Log');
        $logFinder->where('user_id', $userId);
        $logFinder->order('date', 'DESC');
        $logFinder->limit(15);

        $logs = $logFinder->fetch();
        $countLogs = count($logs);

        return $this->view('CheatSite:View', 'cheats_upgrades_view', [
            'allUpgrades' => $allUpgrades,
            'countAllUpgrades' => $countAllUpgrades,
            'hwid' => $hwid,
            'logs' => $logs,
            'countLogs' => $countLogs,
            'user' => $user
        ]);
    }
}
