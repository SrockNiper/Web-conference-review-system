<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$additionalCSS = ['CSSs/review.css'];
$data['activePage'] = 'review';
$data['title'] = $data['title'] ?? 'Recenze článku';

require_once 'views/header.php';
?>

<main class="py-5">
    <div class="container" style="max-width: 1100px;">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Článek -->
        <div class="article-container p-4 shadow-lg mb-4 bg-white rounded">
            <div class="article-header">
                <span class="badge"><?= htmlspecialchars($article->status) ?></span>
                <h1><?= htmlspecialchars($article->title ?? 'Bez názvu') ?></h1>
                <div class="article-meta">
                    <i class="bi bi-calendar3"></i> <?= date('d.m.Y', strtotime($article->created_at)) ?>
                    <span class="mx-2">•</span>
                    <i class="bi bi-person"></i> <?= htmlspecialchars(($article->name ?? '') . ' ' . ($article->surname ?? '')) ?>
                </div>
            </div>

            <div class="article-body">
                <h5 class="mt-4">Abstrakt</h5>
                <p class="lead"><?= nl2br(htmlspecialchars($article->abstract ?? '')) ?></p>

                <?php if (!empty($article->content)): ?>
                    <div class="article-content mt-4">
                        <?= $article->content ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($article->pdf_filename)): ?>
                <div class="pdf-section mt-5">
                    <h3><i class="bi bi-file-pdf me-2"></i>PDF dokument</h3>
                    <div class="pdf-viewer-container">
                        <iframe src="index.php?path=view-pdf&id=<?= $article->id ?>"
                                width="100%"
                                height="600"></iframe>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recenzní formulář -->
        <div class="card shadow-lg">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="bi bi-star me-2"></i>Recenze
                </h4>

                <?php if (!empty($review['reviewed_at'])): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Recenze byla dokončena <?= date('d.m.Y H:i', strtotime($review['reviewed_at'])) ?>
                    </div>

                    <div class="mb-3">
                        <strong>Rozhodnutí:</strong>
                        <?php if ($review['decision'] === 'accept'): ?>
                            <span class="badge bg-success fs-6">Přijmout</span>
                        <?php else: ?>
                            <span class="badge bg-danger fs-6">Zamítnout</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <strong>Poznámka:</strong>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($review['review_note'] ?? '-')) ?></p>
                    </div>

                    <a href="index.php?path=recenzents" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Zpět na přehled
                    </a>
                <?php else: ?>
                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Rozhodnutí *</label>
                            <select name="decision" class="form-select form-select-lg" required>
                                <option value="">-- Vyberte --</option>
                                <option value="approved" <?= ($review['decision'] ?? '') === 'approved' ? 'selected' : '' ?>>
                                    ✓ Přijmout článek
                                </option>
                                <option value="rejected" <?= ($review['decision'] ?? '') === 'rejected' ? 'selected' : '' ?>>
                                    ✗ Zamítnout článek
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Poznámka k recenzi *</label>
                            <textarea name="review_note" class="form-control" rows="10" required
                                      placeholder="Uveďte důvody svého rozhodnutí, návrhy na úpravy, silné a slabé stránky článku..."><?= htmlspecialchars($review['review_note'] ?? '') ?></textarea>
                            <small class="text-muted">Minimálně popište důvody svého rozhodnutí</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">
                                <i class="bi bi-save"></i> Uložit koncept
                            </button>
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Odeslat recenzi
                            </button>
                            <a href="index.php?path=recenzents" class="btn btn-outline-secondary ms-auto">
                                Zrušit
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require_once 'views/footer.php'; ?>