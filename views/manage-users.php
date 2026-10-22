<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$users = $data['users'] ?? [];
$flash = $data['flash'] ?? null;
$currSort = $data['sort'] ?? 'id';
$currDir = strtoupper($data['dir'] ?? 'DESC');
function sortLink($col, $label, $currSort, $currDir) {
    $nextDir = 'ASC';
    $symbol = '';
    if ($currSort === $col) {
        if ($currDir === 'ASC') { $nextDir = 'DESC'; $symbol = ' ↑'; }
        else { $nextDir = 'ASC'; $symbol = ' ↓'; }
    }
    return '<a href="index.php?path=manage-users&sort=' . urlencode($col) . '&dir=' . $nextDir . '" class="text-decoration-none">' . htmlspecialchars($label) . $symbol . '</a>';
}

$additionalCSS = ['CSSs/manage-users.css'];
$data['activePage'] = 'manage-users';
$data['title'] = $data['title'] ?? 'Správa uživatelů';

require_once 'views/header.php';
?>

<main class="flex-grow-1 py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 text-white mb-0"><?php echo htmlspecialchars($data['heading'] ?? 'Správa uživatelů'); ?></h1>
                <small class="text-white-50">Přehled uživatelů registrovaných v systému</small>
            </div>

        </div>

        <?php if ($flash): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th style="width:60px"><?php echo sortLink('id','ID',$currSort,$currDir); ?></th>
                        <th><?php echo sortLink('username','Uživatelské jméno',$currSort,$currDir); ?></th>
                        <th><?php echo sortLink('first_name','Jméno',$currSort,$currDir); ?></th>
                        <th><?php echo sortLink('last_name','Příjmení',$currSort,$currDir); ?></th>
                        <th class="d-none d-md-table-cell"><?php echo sortLink('email','E-mail',$currSort,$currDir); ?></th>
                        <th><?php echo sortLink('role','Role',$currSort,$currDir); ?></th>
                        <th style="width:220px">Akce</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Žádní uživatelé k zobrazení</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $idx => $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['id'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($u['username'] ?? ($u['email'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars($u['first_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($u['last_name'] ?? '-'); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-secondary role-badge"><?php echo htmlspecialchars($u['role_label'] ?? '-'); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <?php if (!empty($u['can_edit']) && !empty($u['allowed_roles'])): ?>
                                            <form method="POST" action="index.php?path=manage-users&setRole=<?php echo (int)($u['id'] ?? 0); ?>" class="d-flex align-items-center gap-2">
                                                <select name="role" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                                                    <?php foreach ($u['allowed_roles'] as $rid => $rlabel): ?>
                                                        <option value="<?php echo (int)$rid; ?>" <?php echo ((string)$rid === (string)($u['role'] ?? '')) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($rlabel); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-primary" type="submit">Uložit</button>
                                                <?php if (!empty($u['can_delete'])): ?>
                                                    <a href="index.php?path=manage-users&delete=<?php echo (int)($u['id'] ?? 0); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Opravdu smazat uživatele?');">Smazat</a>
                                                <?php endif; ?>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-secondary fs-6 py-2 px-3">
                                                <i class="bi bi-shield-lock"></i> Nelze upravovat
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-between">
                <div class="text-muted small">Celkem: <?php echo count($users); ?> uživatelů</div>
                <div>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">Zpět na úvod</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once 'views/footer.php'; ?>