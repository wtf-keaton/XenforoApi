<?php

namespace CheatSite\Pub\Controller;

use CheatSite\CheatService;
use CheatSite\Subscribe;
use XF\Api\Controller\AbstractController;
use XF\App;
use XF\Entity\User;
use XF\Http\Request;

/**
 * Методы API должен быть метода контроллера с префиксом method (methodAuth, methodDll)
 */
class ApiController extends AbstractController
{
    const AVAILABLE_TYPES = ['cheat', 'loader'];

    private $user;

    /**
     * @var CheatService
     */
    private $cheatService;

    public function __construct(App $app, Request $request)
    {
        parent::__construct($app, $request);

        $this->cheatService = new CheatService();
    }

    public function actionApi($params)
    {
        $request = \XF::app()->request();

        $requestMethod = $_SERVER['REQUEST_METHOD'];

        $method = $_REQUEST['method'] ?? null;
        $methodFunction = null;
        if(!empty($method)) {
            $method = preg_replace('#[^a-z0-9]#ui', '', $method);
            $methodFunction = 'method' . ucfirst($method);
        }

        if(empty($method) || empty($methodFunction) || !method_exists($this, $methodFunction)) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Can\'t find method',
            ]);
            exit;
        }

        if(!$this->checkAuth()) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Data error. Check your username or password!',
            ]);
            exit;
        }

        return $this->$methodFunction();
    }

    /**
     * user={username}&pass={password}&hwid={hwid}&type={cheat|loader}
     */
    private function methodAuth()
    {
        $login = $_REQUEST['user'] ?? null;
        $pass = $_REQUEST['pass'] ?? null;
        $hwid = $_REQUEST['hwid'] ?? null;
        $type = $_REQUEST['type'] ?? null;

        $errors = [];

        if(empty($hwid)) {
            $errors[] = 'Parameter "hwid" must be passed.';
        }
        if(empty($type)) {
            $errors[] = 'Parameter "type" must be passed.';
        } else if(!in_array($type, self::AVAILABLE_TYPES)) {
            $errors[] = 'Parameter "type" is wrong.';
        }

        if(!empty($errors)) {
            $this->outputJson([
                'type' => 'error',
                'msg' => implode(' ', $errors),
            ]);
            exit;
        }

        $user = $this->getUser();
        $userId = $user->user_id;

        $logParams = [
            'method' => 'auth',
            'hwid' => $hwid,
        ];
        $this->log($userId, http_build_query($logParams));

        $this->checkSubscribe();

        $hwidEntity = \XF::finder('CheatSite:UserHwid')->where('user_id', $userId)->fetchOne();
        if(!$hwidEntity || strlen($hwidEntity->hwid) === 0 || $hwidEntity === "NULL") {
            // set new hwid
            if(!$hwidEntity) {
                $hwidEntity = \XF::em()->create('CheatSite:UserHwid');
            }
            $hwidEntity->user_id = $userId;
            $hwidEntity->hwid = $hwid;
            $hwidEntity->last_change_date = date('Y-m-d H:i:s');
            $hwidEntity->save();
        } else {
            // check hwid
            if($hwidEntity->hwid != $hwid) {
                $this->outputJson([
                    'type' => 'error',
                    'msg' => 'Wrong HWID',
                ]);
                exit;
            }
        }

        $group = \XF::finder('XF:UserGroup')->where('user_group_id', $user->user_group_id)->fetchOne();
        $avatarUrl = $user->getAvatarUrl('o', null, true);

        $activeUpgrades = $this->getActiveUpgrades();
        $date = 0;
        foreach($activeUpgrades as $upgrade) {
            $date = max($upgrade->end_date, $date);
        }

        $this->outputJson([
            'type' => 'success',
            'data' => [
                'login' => $user['username'],
                'sub-date' => date('Y-m-d H:i:s', $date),
                'profile-photo' => $avatarUrl,
                'hwid' => $hwidEntity->hwid,
                'user-group' => $group ? $group->title : null
            ],
            'msg' => 'Success authorized in ' . $type,
        ]);
        exit;
    }
    /**
     * user={username}&pass={password}&hwid={hwid}
     */
    private function methodInfo()
    {
        $login = $_REQUEST['user'] ?? null;
        $pass = $_REQUEST['pass'] ?? null;
        $hwid = $_REQUEST['hwid'] ?? null;

        $errors = [];

        if(empty($hwid)) {
            $errors[] = 'Parameter "hwid" must be passed.';
        }

        if(!empty($errors)) {
            $this->outputJson([
                'type' => 'error',
                'msg' => implode(' ', $errors),
            ]);
            exit;
        }

        $user = $this->getUser();
        $userId = $user->user_id;

        $logParams = [
            'method' => 'info',
            'hwid' => $hwid,
        ];
        $this->log($userId, http_build_query($logParams));

        $this->checkSubscribe();

        $hwidEntity = \XF::finder('CheatSite:UserHwid')->where('user_id', $userId)->fetchOne();
        if(!$hwidEntity || strlen($hwidEntity->hwid) === 0 || $hwidEntity === "NULL") {
            // set new hwid
            if(!$hwidEntity) {
                $hwidEntity = \XF::em()->create('CheatSite:UserHwid');
            }
            $hwidEntity->user_id = $userId;
            $hwidEntity->hwid = $hwid;
            $hwidEntity->last_change_date = date('Y-m-d H:i:s');
            $hwidEntity->save();
        } else {
            // check hwid
            if($hwidEntity->hwid != $hwid) {
                $this->outputJson([
                    'type' => 'error',
                    'msg' => 'Wrong HWID',
                ]);
                exit;
            }
        }

        $group = \XF::finder('XF:UserGroup')->where('user_group_id', $user->user_group_id)->fetchOne();
        $avatarUrl = $user->getAvatarUrl('o', null, true);

        $activeUpgrades = $this->getActiveUpgrades();
        $date = 0;
        foreach($activeUpgrades as $upgrade) {
            $date = max($upgrade->end_date, $date);
        }
        if( strlen( $avatarUrl ) === 0 ){
            $avatarUrl = "None";
        }

        $this->outputJson([
            'data' => [
                'login' => $user['username'],
                'sub-date' => date('d/m/Y', $date),
                'profile-photo' => $avatarUrl,
                'hwid' => $hwidEntity->hwid,
                'user-group' => $group ? $group->title : null
            ]
        ]);
        exit;
    }

    private function methodBanUser()
    {
        $user = $this->getUser();
        $userId = $user->user_id;

        \XF::db()->query("
        UPDATE xf_user
        SET is_banned = 1
        WHERE user_id = ? ", [$userId]);

        \XF::db()->insert("xf_user_ban", [
            'user_id' => 8,
            'ban_user_id' => $userId,
            'ban_date' => date('y-m-d'),
            'end_date' => 0,
            'user_reason' => "Malicous activity",
            'triggered' => 1
        ]);
    }
    private function methodDll()
    {
        $hwid = $_REQUEST['hwid'] ?? null;

        $user = $this->getUser();
        $userId = $user->user_id;

        $logParams = [
            'method' => 'dll',
            'hwid' => $hwid,
        ];
        $this->log($userId, http_build_query($logParams));

        $this->checkSubscribe();

        $hwidEntity = \XF::finder('CheatSite:UserHwid')->where('user_id', $userId)->fetchOne();
        if(empty($hwid) || $hwidEntity->hwid != $hwid) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Wrong HWID'
            ]);
            exit;
        }

        $cheatService = new CheatService();
        $cheats = $cheatService->getAvailableCheats($user);
        $cheatsCount = count($cheats);
        if($cheatsCount == 0) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Can\'t find active subsribe'
            ]);
            exit;
        }

        foreach($cheats as $cheat) {
            $filePath = \XF::getRootDirectory() . '/' . $cheat->dll_path;
            $fileName = $cheat->dll_name;

            $this->sendFile($filePath, $fileName);
            exit;
        }
    }

    private function methodSys()
    {
        $hwid = $_REQUEST['hwid'] ?? null;

        $user = $this->getUser();
        $userId = $user->user_id;

        $logParams = [
            'method' => 'sys',
            'hwid' => $hwid,
        ];
        $this->log($userId, http_build_query($logParams));

        $this->checkSubscribe();

        $hwidEntity = \XF::finder('CheatSite:UserHwid')->where('user_id', $userId)->fetchOne();
        if(empty($hwid) || $hwidEntity->hwid != $hwid) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Wrong HWID'
            ]);
            exit;
        }

        $cheatService = new CheatService();
        $cheats = $cheatService->getAvailableCheats($user);
        $cheatsCount = count($cheats);
        if($cheatsCount == 0) {
            if(empty($hwid) || $hwidEntity->hwid != $hwid) {
                $this->outputJson([
                    'type' => 'error',
                    'msg' => 'Can\'t find active subsribe'
                ]);
                exit;
            }
        }

        $cheat = $cheats[0];
        $filePath = \XF::getRootDirectory() . '/' . $cheat->sys_path;
        $fileName = $cheat->sys_name;

        $this->sendFile($filePath, $fileName);
        exit;
    }

    /**
     * user={username}&pass={password}&message={message}
     */
    private function methodLog()
    {
        $message = $_REQUEST['message'] ?? null;

        $errors = [];

        if($message === null || strlen($message) === 0) {
            $errors[] = 'Parameter "message" must be passed.';
        }

        if(!empty($errors)) {
            $this->outputJson([
                'type' => 'error',
                'msg' => implode(' ', $errors),
            ]);
            exit;
        }

        $user = $this->getUser();

        $this->log($user->user_id, $message);

        $this->outputJson([
            'type' => 'success',
            'msg' => 'The message has logged.',
        ]);
        exit;
    }

    private function checkAuth(): bool
    {
        $login = $_REQUEST['user'] ?? null;
        $pass = $_REQUEST['pass'] ?? null;

        if(empty($login) || empty($pass)) {
            return false;
        }

        $db = \XF::db();

        $user = \XF::finder('XF:User')->where('username', $login)->fetchOne();
        if(!$user) {
            return false;
        }

        $this->user = $user;

        $authRecord = $db->fetchRow('SELECT * FROM xf_user_authenticate WHERE user_id = ?', $user['user_id']);
        $data = unserialize($authRecord['data']);
        $hash = $data['hash'] ?? null;
        if(!$hash) {
            return false;
        }
        $isVerify = password_verify($pass, $hash);

        return $isVerify;
    }

    private function checkSubscribe()
    {
        $user = $this->getUser();

        if($user->is_banned) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Account banned',
            ]);
            exit;
        }

        $activeUpgrades = $this->getActiveUpgrades();
        $cheats = $this->cheatService->getAvailableCheats($user);

        $frozenUpgrades = $this->cheatService->getFrozenUserUpgrades($user->user_id);

        if(!count($activeUpgrades) && !count($cheats)) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Don\'t found active subscribe',
            ]);
            exit;
        }

        $isFrozen = !count($activeUpgrades) && !count($cheats) && count($frozenUpgrades);
        
        if($isFrozen) {
            $this->outputJson([
                'type' => 'error',
                'msg' => 'Subscribe freezed',
            ]);
            exit;
        }
    }

    private function sendFile($path, $name = null)
    {
        if($name === null) {
            $name = basename($path);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        ob_clean();
        flush();
        readfile($path);
    }

    private function outputJson($response)
    {
        header('Content-type: application/json');
        echo json_encode($response);
    }

    private function getSubscribe(): ?Subscribe
    {
        $subscribe = new Subscribe();

        return $subscribe;
    }

    private function getUser(): ?User
    {
        return $this->user;
    }

    private $activeUpgrades = null;

    private function getActiveUpgrades()
    {
        if(!$this->getUser()) {
            return [];
        }

        if($this->activeUpgrades === null) {
            $this->activeUpgrades = $this->cheatService->getActiveUserUpgrades($this->getUser()->user_id);
        }

        return $this->activeUpgrades;
    }

    private function log($userId, $message)
    {
        $log = \XF::em()->create('CheatSite:Log');
        $log->user_id = $userId;
        $log->date = date('Y-m-d H:i:s');
        $log->message = $message;
        $log->save();
    }
}
