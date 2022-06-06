<?php

namespace CheatSite;

class Listener
{
    public static function navigationSetup(\XF\Pub\App $app, array &$navigationFlat, array &$navigationTree)
    {
        $user = \XF::visitor();
        if(!$user) {
            return;
        }

        $cheatService = new CheatService();
        $cheats = $cheatService->getAvailableCheats($user);
        $upgrades = $cheatService->getActiveUserUpgrades($user->user_id);
        $frozenUpgrades = $cheatService->getFrozenUserUpgrades($user->user_id);

        if(!count($cheats) && !count($upgrades) && !count($frozenUpgrades)) {
            return;
        }

        $navigationFlat['cheatsPanel'] = [
            'title' => 'Cheats panel',
            'href' => \XF::app()->router('public')->buildLink('cheatsPanel/index'),
            'attributes' => []
        ];

        $navigationTree['cheatsPanel'] = $navigationFlat['cheatsPanel'];
    }
}
