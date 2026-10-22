<?php
require_once 'config/constants.php';
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
function currentUserIsAdmin() {
    $user = $_SESSION['user'] ?? null;
    if (!$user || !is_array($user)) return false;
    $role = $user['role'] ?? null;
    return is_numeric($role) && in_array((int)$role, [4, 5], true);
}

$additionalCSS = ['CSSs/manage-recenzents.css'];
$data['activePage'] = 'manage-recenzents';
$data['title'] = $data['title'] ?? 'Přiřazení recenzentů';

require_once 'views/header.php';
?>

<main class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Seznam článků</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Název článku</th>
                                <th>Autor</th>
                                <th>Status</th>
                                <th>Recenze</th>
                                <th>Akce</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($data['articles'] as $article): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($article['title']); ?></td>
                                    <td><?php echo htmlspecialchars($article['author_name']); ?></td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'draft' => 'bg-secondary',
                                            'submitted' => 'bg-info',
                                            'in_review' => 'bg-warning',
                                            'approved' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            'returned' => 'bg-primary'
                                        ];
                                        $statusLabels = [
                                            'draft' => 'Koncept',
                                            'submitted' => 'Odesláno',
                                            'in_review' => 'V recenzi',
                                            'approved' => 'Schváleno',
                                            'rejected' => 'Zamítnuto',
                                            'returned' => 'Vráceno'
                                        ];
                                        $badgeClass = $statusClasses[$article['status']] ?? 'bg-secondary';
                                        $statusLabel = $statusLabels[$article['status']] ?? $article['status'];
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if (!empty($article['reviews'])): ?>
                                                <?php foreach($article['reviews'] as $review): ?>
                                                    <div class="mb-1">
                                                        <strong><?php echo htmlspecialchars($review['reviewer']); ?>:</strong>
                                                        <?php if($review['decision'] === 'approved'): ?>
                                                            <span class="badge bg-success">Přijmout</span>
                                                        <?php elseif($review['decision'] === 'rejected'): ?>
                                                            <span class="badge bg-danger">Zamítnout</span>

                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Čeká</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="mt-2">
                                                    <strong>Celkem:</strong>
                                                    <?php echo $article['completed_reviews']; ?>/<?php echo $article['total_reviewers']; ?>
                                                </div>
                                            <?php else: ?>
                                                <em class="text-muted">Žádní recenzenti</em>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary btn-assign mb-1"
                                                data-article-id="<?php echo $article['id']; ?>"
                                                data-article-title="<?php echo htmlspecialchars($article['title']); ?>">
                                            <i class="bi bi-person-plus"></i> Přiřadit
                                        </button>

                                        <?php if($article['can_auto_approve'] && $article['status'] === 'in_review'): ?>
                                            <button class="btn btn-sm btn-success btn-action mb-1"
                                                    data-action="approve"
                                                    data-article-id="<?php echo $article['id']; ?>">
                                                <i class="bi bi-check-circle"></i> Schválit
                                            </button>
                                        <?php endif; ?>

                                        <?php if($article['can_auto_reject'] && $article['status'] === 'in_review'): ?>
                                            <button class="btn btn-sm btn-danger btn-action mb-1"
                                                    data-action="reject"
                                                    data-article-id="<?php echo $article['id']; ?>">
                                                <i class="bi bi-x-circle"></i> Zamítnout
                                            </button>
                                        <?php endif; ?>

                                        <?php if($article['status'] === 'in_review'): ?>
                                            <button class="btn btn-sm btn-warning btn-action mb-1"
                                                    data-action="return"
                                                    data-article-id="<?php echo $article['id']; ?>">
                                                <i class="bi bi-arrow-return-left"></i> Vrátit
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Přiřadit recenzenty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Článek:</strong> <span id="modalArticleTitle"></span></p>
                <input type="hidden" id="articleId">

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Vyberte minimálně 3 recenzenty
                </div>

                <div class="reviewer-list">
                    <?php foreach($data['reviewers'] as $reviewer): ?>
                        <div class="form-check">
                            <input class="form-check-input reviewer-checkbox" type="checkbox"
                                   value="<?php echo $reviewer['id']; ?>"
                                   id="reviewer<?php echo $reviewer['id']; ?>">
                            <label class="form-check-label" for="reviewer<?php echo $reviewer['id']; ?>">
                                <?php echo htmlspecialchars($reviewer['full_name']); ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($reviewer['email']); ?>)</small>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="validationMessage" class="alert alert-danger mt-3" style="display:none;">
                    Musíte vybrat alespoň 3 recenzenty!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                <button type="button" class="btn btn-primary" id="btnAssign">Přiřadit recenzenty</button>
            </div>
        </div>
    </div>
</div>

<script>
    const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));


    document.querySelectorAll('.btn-assign').forEach(btn => {
        btn.addEventListener('click', function() {
            const articleId = this.getAttribute('data-article-id');
            const articleTitle = this.getAttribute('data-article-title');

            document.getElementById('articleId').value = articleId;
            document.getElementById('modalArticleTitle').textContent = articleTitle;
            document.querySelectorAll('.reviewer-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('validationMessage').style.display = 'none';

            assignModal.show();
        });
    });


    document.getElementById('btnAssign').addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.reviewer-checkbox:checked');
        const validationMsg = document.getElementById('validationMessage');

        if (checkedBoxes.length < 3) {
            validationMsg.style.display = 'block';
            return;
        }

        validationMsg.style.display = 'none';

        const formData = new FormData();
        formData.append('action', 'assign_reviewers');
        formData.append('article_id', document.getElementById('articleId').value);

        checkedBoxes.forEach(cb => {
            formData.append('reviewers[]', cb.value);
        });

        fetch('index.php?path=manage-recenzents', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Chyba: ' + data.message);
                }
            })
            .catch(error => {
                alert('Chyba při komunikaci se serverem');
            });
    });


    document.querySelectorAll('.btn-action').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const articleId = this.getAttribute('data-article-id');

            const confirmMsg = {
                'approve': 'Opravdu chcete schválit tento článek?',
                'reject': 'Opravdu chcete zamítnout tento článek?',
                'return': 'Opravdu chcete vrátit tento článek autorovi?'
            };

            if (!confirm(confirmMsg[action])) return;

            const formData = new FormData();
            formData.append('action', action + '_article');
            formData.append('article_id', articleId);

            fetch('index.php?path=manage-recenzents', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Chyba: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Chyba při komunikaci se serverem');
                });
        });
    });
</script>
<?php
require_once 'views/footer.php';
?>
