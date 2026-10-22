<?php

class ManageUsersController {


    private function findRoleIdByNames(array $rolesMap, array $names) {
        foreach ($rolesMap as $id => $label) {
            $lowLabel = mb_strtolower((string)$label);
            foreach ($names as $n) {
                if ($n === null) continue;
                $low = mb_strtolower((string)$n);
                if ($low === $lowLabel || mb_strpos($lowLabel, $low) !== false || mb_strpos($low, $lowLabel) !== false) {
                    return (int)$id;
                }
            }
        }
        return null;
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $current = $_SESSION['user'] ?? null;
        $currentRole = null;
        if (is_array($current)) {
            $currentRole = $current['role'] ?? null;
        } elseif (is_string($current)) {
            $currentRole = $current;
        }

        $isSuperadmin = (is_numeric($currentRole) && (int)$currentRole === 5) || (is_string($currentRole) && strtolower($currentRole) === 'superadmin');
        $isAdmin = (is_numeric($currentRole) && (int)$currentRole === 4) || (is_string($currentRole) && strtolower($currentRole) === 'admin');

        if (!($isAdmin || $isSuperadmin)) {
            header('Location: index.php');
            exit;
        }

        $userModel = new UserModel();
        $rolesMap = $userModel->getRoles();


        if (isset($_GET['delete'])) {
            $tid = (int)$_GET['delete'];
            $target = $userModel->getById($tid);

            if ($target) {
                $result = $userModel->deleteById($tid);
                $_SESSION['flash'] = $result['message'];
            } else {
                $_SESSION['flash'] = 'Cílový uživatel nebyl nalezen.';
            }

            header('Location: index.php?path=manage-users');
            exit;
        }

        // SET ROLE (POST)
        if (isset($_GET['setRole']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $tid = (int)$_GET['setRole'];
            $newRoleRaw = $_POST['role'] ?? null;
            $target = $userModel->getById($tid);


            if (!$target) {
                $_SESSION['flash'] = 'Cílový uživatel nebyl nalezen.';
                header('Location: index.php?path=manage-users');
                exit;
            }
            if ($newRoleRaw === null || $newRoleRaw === '') {
                $_SESSION['flash'] = 'Role nebyla odeslána.';
                header('Location: index.php?path=manage-users');
                exit;
            }


            $newRoleId = null;
            if (is_numeric($newRoleRaw)) {
                $cand = (int)$newRoleRaw;
                if (isset($rolesMap[$cand])) $newRoleId = $cand;
            }
            if ($newRoleId === null) {
                $newRoleId = $this->findRoleIdByNames($rolesMap, [(string)$newRoleRaw]);
            }

            if ($newRoleId === null) {
                $_SESSION['flash'] = 'Cílová role nebyla nalezena v číselníku rolí.';
                header('Location: index.php?path=manage-users');
                exit;
            }


            $allowed = [];
            foreach ($rolesMap as $rid => $label) {
                $low = mb_strtolower($label);
                if ($isSuperadmin) {
                    if (mb_strpos($low, 'super-admin') !== false) continue;
                    $allowed[(int)$rid] = $label;
                } elseif ($isAdmin) {
                    if (mb_strpos($low, 'admin') !== false) continue;
                    if (mb_strpos($low, 'super-admin') !== false) continue;
                    $allowed[(int)$rid] = $label;
                }
            }

            if (!array_key_exists($newRoleId, $allowed)) {

                $_SESSION['flash'] = 'Nemáte oprávnění nastavit tuto roli.';
                header('Location: index.php?path=manage-users');
                exit;
            }

            // Ochranná pravidla adminů/superadminů
            $targetRole = $target['role'] ?? null;
            $targetIsSuper = (is_numeric($targetRole) && (int)$targetRole === 5) || (is_string($targetRole) && strtolower($targetRole) === 'superadmin');
            $targetIsAdmin = (is_numeric($targetRole) && (int)$targetRole === 4) || (is_string($targetRole) && strtolower($targetRole) === 'admin');

            if ($isAdmin && ($targetIsAdmin || $targetIsSuper)) {
                $_SESSION['flash'] = 'Admin nemůže upravovat adminy ani superadminy.';
                header('Location: index.php?path=manage-users');
                exit;
            }
            if ($isSuperadmin && $targetIsSuper && (!is_array($current) || ((int)($current['id'] ?? 0) !== $tid))) {
                $_SESSION['flash'] = 'Superadmin nemůže upravovat jiného superadmina.';
                header('Location: index.php?path=manage-users');
                exit;
            }

            // Provedeme update (uložíme ID role)
            $ok = $userModel->updateRole($tid, $newRoleId);

            $_SESSION['flash'] = $ok ? 'Role uložena.' : 'Chyba při ukládání role.';
            header('Location: index.php?path=manage-users');
            exit;
        }

        // --- Výpis uživatelů s řazením ---
        $sort = $_GET['sort'] ?? 'id';
        $dir = $_GET['dir'] ?? 'DESC';
        $allowedSorts = ['id','username','first_name','last_name','email','role','created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'id';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $users = [];
        try {
            $users = $userModel->getAll($sort, $dir);
        } catch (Exception $e) {
            $users = [];
        }

        foreach ($users as &$u) {
            $origRole = $u['role'] ?? null;
            $roleId = null;
            if (is_numeric($origRole)) {
                $roleId = (int)$origRole;
            } elseif (is_string($origRole) && $origRole !== '') {

                $roleId = $this->findRoleIdByNames($rolesMap, [$origRole]);
            }
            $u['role'] = $roleId !== null ? $roleId : $origRole;

            $targetRole = $u['role'] ?? null;
            $targetIsSuper = (is_numeric($targetRole) && (int)$targetRole === 5) || (is_string($targetRole) && strtolower($targetRole) === 'superadmin');
            $targetIsAdmin = (is_numeric($targetRole) && (int)$targetRole === 4) || (is_string($targetRole) && strtolower($targetRole) === 'admin');

            $canEdit = false;
            if ($isSuperadmin) {
                if (!$targetIsSuper) $canEdit = true;
            } elseif ($isAdmin) {
                if (!$targetIsAdmin && !$targetIsSuper) $canEdit = true;
            }

            $canDelete = false;
            if ($isSuperadmin) {
                if (!$targetIsSuper) $canDelete = true;
            } elseif ($isAdmin) {
                if (!$targetIsAdmin && !$targetIsSuper) $canDelete = true;
            }

            $u['can_edit'] = $canEdit;
            $u['can_delete'] = $canDelete;
            $u['role_label'] = $userModel->resolveRoleLabel($u['role'], $rolesMap);

            $allowed = [];
            foreach ($rolesMap as $rid => $label) {
                $low = mb_strtolower($label);
                if ($isSuperadmin) {
                    if (mb_strpos($low, 'super-admin') !== false) continue;
                    $allowed[(int)$rid] = $label;
                } elseif ($isAdmin) {
                    if (mb_strpos($low, 'admin') !== false) continue;
                    if (mb_strpos($low, 'super-admin') !== false) continue;
                    $allowed[(int)$rid] = $label;
                }
            }
            $u['allowed_roles'] = $allowed;
        }
        unset($u);

        $flash = null;
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        $data = [
            'title' => 'Správa uživatelů',
            'heading' => 'Správa uživatelů',
            'user' => $current,
            'users' => $users,
            'flash' => $flash,
            'roles_map' => $rolesMap,
            'sort' => $sort,
            'dir' => $dir
        ];

        require_once 'Views/manage-users.php';
    }
}
?>
