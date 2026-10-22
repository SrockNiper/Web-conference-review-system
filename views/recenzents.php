<?php
require_once __DIR__ . '/../Controlers/recenzents-control.php';
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$additionalCSS = ['CSSs/recenzents.css'];
$data['activePage'] = 'recenzents';
$data['title'] = $data['title'] ?? 'Recenzenti';

require_once 'views/header.php';
?>

<main class="flex-grow-1 py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 text-white mb-0">Články k recenzování</h1>
                <small class="text-white-50">Přehled přiřazených článků pro recenzování</small>
            </div>
        </div>

        <?php if (isset($stats)): ?>
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Celkem</h5>
                            <p class="display-6"><?= $stats['total'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card border-warning">
                        <div class="card-body text-center">
                            <h5 class="card-title">Čeká</h5>
                            <p class="display-6 text-warning"><?= $stats['pending'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card border-info">
                        <div class="card-body text-center">
                            <h5 class="card-title">Probíhá</h5>
                            <p class="display-6 text-info"><?= $stats['in_progress'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card border-success">
                        <div class="card-body text-center">
                            <h5 class="card-title">Dokončeno</h5>
                            <p class="display-6 text-success"><?= $stats['completed'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($assignedArticles)): ?>
            <div class="card p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width:60px">
                                <a href="?order=article_id&dir=<?= getNextDir($orderBy === 'article_id' ? $direction : 'ASC') ?>" class="text-decoration-none">
                                    ID <?php if($orderBy === 'article_id') echo $direction === 'ASC' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?order=title&dir=<?= getNextDir($orderBy === 'title' ? $direction : 'ASC') ?>" class="text-decoration-none">
                                    Název článku <?php if($orderBy === 'title') echo $direction === 'ASC' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th>Autor</th>
                            <th>
                                <a href="?order=assigned_at&dir=<?= getNextDir($orderBy === 'assigned_at' ? $direction : 'ASC') ?>" class="text-decoration-none">
                                    Přiřazeno <?php if($orderBy === 'assigned_at') echo $direction === 'ASC' ? '↑' : '↓'; ?>
                                </a>
                            </th>
                            <th>Status</th>
                            <th style="width:140px">Akce</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($assignedArticles as $article): ?>
                            <tr>
                                <td><?= htmlspecialchars($article['article_id'] ?? '-') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($article['title'] ?? 'Bez názvu') ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars(mb_substr($article['abstract'] ?? '', 0, 100)) ?>...</small>
                                </td>
                                <td><?= htmlspecialchars($article['author_name'] ?? '-') ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($article['assigned_at'])) ?></td>
                                <td>
                                    <?php if ($article['review_status'] === 'completed'): ?>
                                        <span class="badge bg-success">Dokončeno</span>
                                    <?php elseif ($article['review_status'] === 'in_progress'): ?>
                                        <span class="badge bg-info">Probíhá</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Čeká</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="index.php?path=article&id=<?= $article['article_id'] ?>"
                                           class="btn btn-sm btn-primary"
                                           title="Zobrazit článek">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($article['review_status'] !== 'completed'): ?>
                                            <a href="index.php?path=review&article=<?= $article['article_id'] ?>"
                                               class="btn btn-sm btn-success"
                                               title="Recenzovat">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between">
                    <div class="text-muted small">Celkem: <?= count($assignedArticles) ?> článků</div>
                    <div>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">Zpět na úvod</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="background-color: rgba(255,255,255,0.9); border: none;">
                <i class="bi bi-exclamation-triangle"></i>
                Nemáte přiřazené žádné články k recenzování.
            </div>
        <?php endif; ?>
    </div>
</main>
<?php
require_once 'views/footer.php';
?>