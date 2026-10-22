<?php
require_once 'config/constants.php';
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$additionalCSS = ['CSSs/archive.css'];
$data['activePage'] = 'archive';
$data['title'] = $data['title'] ?? 'Archiv článků';

require_once 'views/header.php';
?>

<main>
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Archiv všech článků</h2>

            <?php if (!empty($data['message'])): ?>
                <div class="alert alert-<?php echo htmlspecialchars($data['message_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($data['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($data['articles'])): ?>
                <div class="text-center py-5">
                    <p class="text-muted">Zatím nejsou k dispozici žádné schválené články.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach($data['articles'] as $article): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card shadow-sm h-100 article-card">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                                    <div class="article-meta text-muted small mb-3">
                                        <i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($article['date']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($article['author']); ?>
                                    </div>
                                    <a href="index.php?path=article&id=<?php echo (int)$article['id']; ?>" class="btn btn-outline-primary btn-sm mt-auto">
                                        Číst více
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once 'views/footer.php'; ?>
