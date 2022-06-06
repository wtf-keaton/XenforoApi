<?php

namespace CheatSite\Pub\Controller;

use CheatSite\CheatService;
use XF\Db\Exception;
use XF\Pub\Controller\AbstractController;

class PanelController extends AbstractController
{
    public function actionIndex()
    {
        $user = \XF::visitor();
        if(!$user || $user->is_banned) {
            return $this->redirect($this->buildLink('forums/'));
        }

        $cheatService = new CheatService();

        $upgrades = $cheatService->getActiveUserUpgrades($user->user_id);
        $upgradesCount = count($upgrades);

        $_tmp = [];
        foreach($upgrades as $upgrade) {
            $_tmp[$upgrade->user_upgrade_record_id] = $upgrade;
        }
        $upgrades = $_tmp;

        $frozenUpgrades = $cheatService->getFrozenUserUpgrades($user->user_id);
        $_tmp = [];
        foreach($frozenUpgrades as $upgrade) {
            $_tmp[$upgrade->user_upgrade_record_id] = $upgrade;
        }
        $frozenUpgrades = $_tmp;
        $countFrozenUpgrades = count($frozenUpgrades);

        $cheats = $cheatService->getAvailableCheats($user);
        $cheatsCount = count($cheats);

        $hasSubscribe = true;

        if($cheatsCount === 0 && $upgradesCount === 0 && $countFrozenUpgrades === 0) {
            $hasSubscribe = false;
        }

        $hwid = $cheatService->getHwid($user->user_id);
        $canResetHwid = $cheatService->canResetHwid($user->user_id);
        $lastHwidReset = $cheatService->getLastHwidReset($user->user_id);
        $lastHwidResetTimestamp = null;
        if($lastHwidReset) {
            $lastHwidResetTimestamp = strtotime($lastHwidReset->date);
        }

        if(isset($_POST['resetHwid']) && $canResetHwid) {
            $cheatService->resetHwid($hwid);

            return $this->redirect($this->buildLink('cheatsPanel/index'), 'HWID has been reset.');
        }

        $canFreezeUpgrades = [];
        $lastFreezeDates = [];
        foreach($upgrades as $upgrade) {
            $canFreezeUpgrades[$upgrade->user_upgrade_record_id] = $cheatService->canFreezeUpgrade($upgrade);
            $lastFreezeDates[$upgrade->user_upgrade_record_id] = $cheatService->getLastFreeze($upgrade);
        }

        if(isset($_POST['freezeUpgrade'])) {
            $upgradeId = (int) $_POST['freezeUpgrade'];
            if(!empty($canFreezeUpgrades[$upgradeId]) && isset($upgrades[$upgradeId])) {
                $cheatService->freezeUpgrade($upgrades[$upgradeId]);

                return $this->redirect($this->buildLink('cheatsPanel/index'), 'The subscribe has been frozen.');
            }
        }

        if(isset($_POST['unfreezeUpgrade'])) {
            $upgradeId = (int) $_POST['unfreezeUpgrade'];
            if(isset($frozenUpgrades[$upgradeId])) {
                $cheatService->unfreezeUpgrade($frozenUpgrades[$upgradeId]);

                return $this->redirect($this->buildLink('cheatsPanel/index'), 'The subscribe has been unfrozen.');
            }
        }

        $allUpgrades = [];
        foreach($upgrades as $k => $v) {
            $allUpgrades[$k] = [
                'type' => 'active',
                'upgrade' => $v,
            ];
        }
        foreach($frozenUpgrades as $k => $v) {
            $allUpgrades[$k] = [
                'type' => 'frozen',
                'upgrade' => $v,
            ];
        }
        $countAllUpgrades = count($allUpgrades);

        return $this->view('CheatSite:View', 'cheats_index', [
            'cheats' => $cheats,
            'cheatsCount' => $cheatsCount,
            'upgrades' => $upgrades,
            'upgradesCount' => $upgradesCount,
            'hasSubscribe' => $hasSubscribe,
            'hwid' => $hwid,
            'canResetHwid' => $canResetHwid,
            'lastHwidReset' => $lastHwidReset,
            'lastHwidResetTimestamp' => $lastHwidResetTimestamp,
            'canFreezeUpgrades' => $canFreezeUpgrades,
            'lastFreezeDates' => $lastFreezeDates,
            'frozenUpgrades' => $frozenUpgrades,
            'countFrozenUpgrades' => $countFrozenUpgrades,
            'allUpgrades' => $allUpgrades,
            'countAllUpgrades' => $countAllUpgrades
        ]);
    }

    public function actionDownload()
    {
        $user = \XF::visitor();
        if(!$user || $user->is_banned) {
            return $this->redirect($this->buildLink('forums/'));
        }

        $cheatService = new CheatService();
        $cheats = $cheatService->getAvailableCheats(\XF::visitor());
        $cheatsCount = count($cheats);

        if($cheatsCount === 0) {
            return $this->redirect($this->buildLink('forums/'));
        }

        return $this->view('CheatSite:View', 'cheats_download', [
            'cheats' => $cheats,
            'cheatsCount' => $cheatsCount
        ]);
    }

    public function actionDownloadFile()
    {
        $cheatId = $_REQUEST['cheatId'] ?? null;
        $cheatId = (int) $cheatId;
        $file = $_REQUEST['file'] ?? null;

        if(empty($cheatId) || empty($file)) {
            throw new Exception('Request is wrong.');
        }

        $user = \XF::visitor();
        if(!$user || $user->is_banned) {
            return $this->redirect($this->buildLink('forums/'));
        }

        $cheatService = new CheatService();
        $cheats = $cheatService->getAvailableCheats(\XF::visitor());

        foreach($cheats as $cheat) {
            if($cheat->id == $cheatId) {

                $filePath = null;
                $fileName = null;

                if($file == 'dll') {
                    $filePath = \XF::getRootDirectory() . '/' . $cheat->dll_path;
                    $fileName = $cheat->dll_name;
                } else if($file == 'sys') {
                    $filePath = \XF::getRootDirectory() . '/' . $cheat->sys_path;
                    $fileName = $cheat->sys_name;
                }

                if($filePath && $fileName) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . $fileName);
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($filePath));
                    ob_clean();
                    flush();
                    readfile($filePath);
                    exit;
                }
            }
        }

        throw new Exception('There are no available cheats.');
    }
}
