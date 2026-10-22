<?php
require_once 'config/constants.php';
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
function currentUserIsAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $u = $_SESSION['user'] ?? null;
    if (!$u) return false;
    if (is_array($u)) {
        $r = $u['role'] ?? null;
        if (is_numeric($r) && in_array((int)$r, [4, 5], true)) return true;
        if (is_string($r) && in_array(strtolower($r), ['admin', 'superadmin'], true)) return true;
    } elseif (is_string($u)) {
        if (strtolower($u) === 'admin') return true;
    }
    return false;
}
$additionalCSS = ['CSSs/uvod.css'];
$data['activePage'] = 'uvod';
$data['title'] ='Úvodní stránka';

require_once 'views/header.php';
?>

    <main>
        <!-- Sekce s články -->
        <section class="py-5 gradient-bg">
            <div class="container">
                <h3 class="text-center mb-5">Nejnovější články</h3>
                <div class="articles-row">
                    <?php foreach($data['articles'] as $article): ?>
                        <div class="card shadow-sm article-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                                <div class="article-meta text-muted small mb-3">
                                    <i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($article['date']); ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($article['author']); ?>
                                </div>
                                <a href="index.php?path=article&id=<?php echo (int)$article['id']; ?>" class="btn btn-outline-primary btn-sm">Číst více</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php?path=archive" class="btn btn-primary">Zobrazit všechny články</a>
                </div>
            </div>
        </section>
    </main>
<?php require_once 'views/footer.php'; ?>