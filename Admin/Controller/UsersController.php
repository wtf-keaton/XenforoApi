<?php

namespace CheatSite\Admin\Controller;

use XF\Admin\Controller\AbstractController;

class UsersController extends AbstractController
{
    public function actionIndex()
    {
        $users = \XF::finder('CheatSite:UserHwid')
            ->with('user')
            ->order('last_change_date', 'DESC')
            ->fetch();

        $countUsers = count($users);

        return $this->view('CheatSite:View', 'cheats_users_index', [
            'users' => $users,
            'countUsers' => $countUsers
        ]);
    }
}
