<?php

namespace CheatSite\Admin\Controller;

use XF\Admin\Controller\AbstractController;

class CheatsController extends AbstractController
{
    public function actionIndex()
    {
        $cheats = \XF::finder('CheatSite:Cheat')
            ->with('group')
            ->order('id', 'ASC')->fetch();

        $cheatsCount = count($cheats);

        return $this->view('CheatSite:View', 'cheats_cheats_index', [
            'cheats' => $cheats,
            'cheatsCount' => $cheatsCount
        ]);
    }

    public function actionCreate()
    {
        $groupOptions = $this->getGroupOptions();

        $cheat = \XF::em()->create('CheatSite:Cheat');

        $errors = [];
        if(!empty($_POST)) {

            $name = $_POST['name'] ?? null;
            $groupId = $_POST['userGroupId'] ?? null;

            $groupId = (int) $groupId;

            if(empty($name)) {
                $errors[] = 'Name cannot be empty.';
            }
            if(empty($groupId)) {
                $errors[] = 'Group cannot be empty.';
            }

            $dllOrigName = null;
            $dllPath = null;

            if(empty($_FILES['dll'])) {
                $errors[] = 'Upload dll file.';
            } else if($_FILES['dll']['error'] != UPLOAD_ERR_OK) {
                $errors[] = 'Error has occurred for dll file.';
            } else {
                $dllOrigName = $_FILES['dll']['name'];
                $relativeDir = 'internal_data/cheats/dll';
                $rootDir = \XF::getRootDirectory();
                $dir = $rootDir . '/' . $relativeDir;
                if(!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                $relativeDestPath = $relativeDir . '/' . uniqid('', true);
                $destPath = $rootDir . '/' . $relativeDestPath;

                $dllPath = $relativeDestPath;

                move_uploaded_file($_FILES['dll']['tmp_name'], $destPath);
            }

            $sysOrigName = null;
            $sysPath = null;

            if(empty($_FILES['sys'])) {
                $errors[] = 'Upload dll file.';
            } else if($_FILES['sys']['error'] != UPLOAD_ERR_OK) {
                $errors[] = 'Error has occurred for sys file.';
            } else {
                $sysOrigName = $_FILES['sys']['name'];
                $relativeDir = 'internal_data/cheats/sys';
                $rootDir = \XF::getRootDirectory();
                $dir = $rootDir . '/' . $relativeDir;
                if(!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                $relativeDestPath = $relativeDir . '/' . uniqid('', true);
                $destPath = $rootDir . '/' . $relativeDestPath;

                $sysPath = $relativeDestPath;

                move_uploaded_file($_FILES['sys']['tmp_name'], $destPath);
            }

            $cheat->group_id = $groupId;
            $cheat->name = $name;
            $cheat->dll_name = $dllOrigName;
            $cheat->dll_path = $dllPath;
            $cheat->sys_name = $sysOrigName;
            $cheat->sys_path = $sysPath;

            if(empty($errors)) {
                $cheat->save();

                return $this->redirect($this->buildLink('cheats/index'));
            }
        }

        return $this->view('CheatSite:View', 'cheats_cheats_create', [
            'groupOptions' => $groupOptions,
            'cheat' => $cheat,
            'errors' => $errors
        ]);
    }

    public function actionEdit()
    {
        $groupOptions = $this->getGroupOptions();

        $id = $_GET['id'] ?? null;
        if(!$id || !($cheat = \XF::finder('CheatSite:Cheat')->whereId($id)->fetchOne())) {
            throw new \Exception('Cheat is not found.');
        }

        $errors = [];
        if(!empty($_POST)) {
            $name = $_POST['name'] ?? null;
            $groupId = $_POST['userGroupId'] ?? null;

            $groupId = (int) $groupId;

            if(empty($name)) {
                $errors[] = 'Name cannot be empty.';
            }
            if(empty($groupId)) {
                $errors[] = 'Group cannot be empty.';
            }

            $dllOrigName = null;
            $dllPath = null;

            if(!empty($_FILES['dll']) && $_FILES['dll']['error'] != UPLOAD_ERR_NO_FILE) {
                if ($_FILES['dll']['error'] != UPLOAD_ERR_OK) {
                    $errors[] = 'Error has occurred for dll file.';
                } else {
                    $dllOrigName = $_FILES['dll']['name'];
                    $relativeDir = 'internal_data/cheats/dll';
                    $rootDir = \XF::getRootDirectory();
                    $dir = $rootDir . '/' . $relativeDir;
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $relativeDestPath = $relativeDir . '/' . uniqid('', true);
                    $destPath = $rootDir . '/' . $relativeDestPath;

                    $dllPath = $relativeDestPath;

                    move_uploaded_file($_FILES['dll']['tmp_name'], $destPath);
                }
            }

            $sysOrigName = null;
            $sysPath = null;

            if(!empty($_FILES['sys']) && $_FILES['sys']['error'] != UPLOAD_ERR_NO_FILE) {
                if ($_FILES['sys']['error'] != UPLOAD_ERR_OK) {
                    $errors[] = 'Error has occurred for sys file.';
                } else {
                    $sysOrigName = $_FILES['sys']['name'];
                    $relativeDir = 'internal_data/cheats/sys';
                    $rootDir = \XF::getRootDirectory();
                    $dir = $rootDir . '/' . $relativeDir;
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $relativeDestPath = $relativeDir . '/' . uniqid('', true);
                    $destPath = $rootDir . '/' . $relativeDestPath;

                    $sysPath = $relativeDestPath;

                    move_uploaded_file($_FILES['sys']['tmp_name'], $destPath);
                }
            }

            $cheat->group_id = $groupId;
            $cheat->name = $name;

            if(empty($errors)) {
                if(!empty($dllPath)) {
                    $cheat->dll_name = $dllOrigName;
                    $cheat->dll_path = $dllPath;
                }
                if(!empty($sysPath)) {
                    $cheat->sys_name = $sysOrigName;
                    $cheat->sys_path = $sysPath;
                }
                $cheat->save();

                return $this->redirect($this->buildLink('cheats/index'));
            }
        }

        return $this->view('CheatSite:View', 'cheats_cheats_edit', [
            'cheat' => $cheat,
            'groupOptions' => $groupOptions,
            'errors' => $errors,
        ]);
    }

    public function actionDelete()
    {
        if($_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new \Exception('Request method is illegal.');
        }

        $id = $_POST['id'] ?? null;
        $id = (int) $id;

        if(!$id || !($cheat = \XF::finder('CheatSite:Cheat')->whereId($id)->fetchOne())) {
            throw new \Exception('Cheat not found.');
        }

        $cheat->delete();

        return $this->redirect($this->buildLink('cheats/index'), 'The cheat has been deleted.');
    }

    public function getGroupOptions()
    {
        $options = [];
        $groups = \XF::finder('XF:UserGroup')->fetch();
        foreach($groups as $group) {
            $options[$group->user_group_id] = $group->title;
        }
        return $options;
    }
}
