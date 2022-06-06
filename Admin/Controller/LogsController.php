<?php

namespace CheatSite\Admin\Controller;

use XF\Admin\Controller\AbstractController;

class LogsController extends AbstractController
{
    public function actionIndex()
    {
        $criteria = isset($_GET['criteria']) ? (int) $_GET['criteria'] : null;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 0;

        $perPage = 50;

        $order = 'id';
        $direction = 'desc';

        $finder = \XF::finder('CheatSite:Log');
        if(!empty($criteria)) {
            $finder->where('user_id', $criteria);
        }
        $finder->limitByPage($page, $perPage);
        $finder->with('user');
        $finder->order($order, $direction);
        $logs = $finder->fetch();

        $total = $finder->total();
        $countLogs = $logs->count();

        $user = null;
        if($criteria) {
            $user = \XF::finder('XF:User')->whereId($criteria)->fetchOne();
        }

        return $this->view('CheatSite:View', 'cheats_logs_index', [
            'logs' => $logs,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'criteria' => $criteria,
            'order' => $order,
            'direction' => $direction,
            'countLogs' => $countLogs,
            'user' => $user
        ]);
    }
}
