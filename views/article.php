<?php
require_once 'config/constants.php';

if (!isset($data) || !isset($data['article'])) {
    header('Location: index.php');
    exit;
}

$article = $data['article'];
$user = $data['user'] ?? null;

$userDisplay = null;
if (is_array($user)) {
    if (!empty($user['login'])) {
        $userDisplay = $user['login'];
    } elseif (!empty($user['username'])) {
        $userDisplay = $user['username'];
    } elseif (!empty($user['name']) && !empty($user['surname'])) {
        $userDisplay = trim($user['name'] . ' ' . $user['surname']);
    } elseif (!empty($user['name'])) {
        $userDisplay = $user['name'];
    } else {
        $userDisplay = 'Uživatel';
    }
} elseif (is_string($user)) {
    $userDisplay = $user;
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$additionalCSS = ['CSSs/article.css'];
$data['activePage'] = 'article';
$data['title'] = $data['title'] ?? 'Článek';

require_once 'views/header.php';
?>

<main>
    <div class="container my-5">
        <div class="article-container p-4 shadow-lg">
            <div class="article-header">
                <span class="badge"><?php echo htmlspecialchars($article->status); ?></span>
                <h1><?php echo htmlspecialchars($article->title); ?></h1>
                <div class="article-meta">
                    <i class="bi bi-calendar3"></i> <?php echo date('d.m.Y H:i', strtotime($article->created_at)); ?>
                    <span class="mx-2">•</span>
                    <i class="bi bi-person"></i> <?php echo htmlspecialchars(trim(($article->name ?? '') . ' ' . ($article->surname ?? ''))); ?>
                </div>
            </div>

            <div class="article-body">
                <p class="lead"><?php echo htmlspecialchars($article->abstract); ?></p>
                <?php if (!empty($article->content)): ?>
                    <div class="article-content">
                        <?php echo $article->content; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($article->pdf_filename)): ?>
                <div class="pdf-section mt-5">
                    <h3><i class="bi bi-file-pdf me-2"></i>PDF dokument</h3>
                    <div class="pdf-viewer-container">
                        <iframe src="index.php?path=view-pdf&id=<?php echo $article->id; ?>"
                                width="100%"
                                height="600"></iframe>
                    </div>
                </div>
            <?php endif; ?>

            <div class="article-actions mt-5">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Zpět na úvod
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once 'views/footer.php'; ?>