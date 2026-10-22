<?php
$data['title'] = 'Správa článků';
$data['activePage'] = 'article-maker';
$additionalCSS = ['CSSs/article-maker.css'];
$additionalJS = ['js/article-maker.js'];

require_once 'Views/header.php';
?>


<section class="gradient-bg">
    <div class="container text-center">
        <h2 class="display-4 mb-3"><?= htmlspecialchars($data['heading']) ?></h2>
        <p class="lead">Vytvářejte, spravujte a publikujte své vědecké články</p>
    </div>
</section>

<main class="container my-5">
    <?php if ($data['flash']): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($data['flash']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulář pro nový článek -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">
                <i class="bi bi-file-earmark-plus me-2"></i>
                <?= isset($data['editArticle']) ? 'Upravit článek' : 'Vytvořit nový článek' ?>
            </h3>
        </div>
        <div class="card-body">
            <?php if (isset($data['editArticle'])): ?>
                <!-- EDITAČNÍ REŽIM -->
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="article_id" value="<?= $data['editArticle']->id ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">Název článku</label>
                        <input type="text" class="form-control" id="title" name="title" required
                               value="<?= htmlspecialchars($data['editArticle']->title) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="abstract" class="form-label">Abstrakt</label>
                        <textarea class="form-control" id="abstract" name="abstract" rows="3" required><?= htmlspecialchars($data['editArticle']->abstract) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Obsah článku</label>
                        <textarea id="content" name="content"><?= htmlspecialchars($data['editArticle']->content) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="pdf" class="form-label">PDF dokument (volitelné)</label>
                        <input type="file" class="form-control" id="pdf" name="pdf" accept=".pdf">
                        <div class="form-text">Aktuální: <?= htmlspecialchars($data['editArticle']->pdf_filename) ?></div>
                    </div>

                    <button type="submit" name="update" class="btn btn-warning">
                        <i class="bi bi-save me-2"></i>Uložit úpravy
                    </button>
                    <a href="index.php?path=article-maker" class="btn btn-secondary">Zrušit</a>
                </form>

            <?php else: ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Název článku</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="abstract" class="form-label">Abstrakt</label>
                        <textarea class="form-control" id="abstract" name="abstract" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Obsah článku</label>
                        <textarea id="content" name="content"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="pdf" class="form-label">PDF dokument</label>
                        <input type="file" class="form-control" id="pdf" name="pdf" accept=".pdf" required>
                    </div>

                    <button type="submit" name="create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Vytvořit článek
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>


    <h3 class="mb-4"><i class="bi bi-file-text me-2"></i>Moje články</h3>

    <?php if (empty($data['articles'])): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Zatím nemáte žádné články. Začněte vytvořením nového článku.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($data['articles'] as $article): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card article-card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($article->title) ?></h5>
                                <span class="status <?= htmlspecialchars($article->status) ?>">
                                        <?= htmlspecialchars($article->status) ?>
                                    </span>
                            </div>
                            <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($article->abstract) ?></p>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-calendar3"></i> <?= date('d.m.Y H:i', strtotime($article->created_at)) ?>
                            </div>

                            <div class="btn-group d-flex gap-2 mt-auto" role="group">
                                <button class="btn btn-outline-primary btn-sm flex-fill" onclick="togglePdf(<?= $article->id ?>)">
                                    <i class="bi bi-file-pdf"></i> PDF
                                </button>
                                <?php if (!empty($article->content)): ?>
                                    <button class="btn btn-outline-info btn-sm flex-fill" onclick="toggleContent(<?= $article->id ?>)">
                                        <i class="bi bi-file-text"></i> Obsah
                                    </button>
                                <?php endif; ?>
                                <?php if (in_array($article->status, ['draft', 'returned'])): ?>
                                    <a href="index.php?path=article-maker&edit=<?= $article->id ?>" class="btn btn-warning btn-sm flex-fill">
                                        <i class="bi bi-pencil"></i> Upravit
                                    </a>
                                <?php endif; ?>
                                <?php if ($article->status === 'draft'): ?>
                                    <a href="index.php?path=article-maker&submit=<?= $article->id ?>" class="btn btn-success btn-sm flex-fill">
                                        <i class="bi bi-send"></i> Odeslat
                                    </a>
                                <?php endif; ?>
                                <a href="index.php?path=article-maker&delete=<?= $article->id ?>"
                                   class="btn btn-danger btn-sm flex-fill"
                                   onclick="return confirm('Opravdu smazat článek?')">
                                    <i class="bi bi-trash"></i> Smazat
                                </a>
                            </div>


                            <div id="pdf-<?= $article->id ?>" class="pdf-viewer">
                                <iframe src="index.php?path=view-pdf&id=<?= $article->id ?>" width="100%" height="100%"></iframe>
                            </div>
                            <?php if (!empty($article->content)): ?>
                                <div id="content-<?= $article->id ?>" class="content-viewer" style="display: none;">
                                    <div class="card mt-3">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Obsah článku</h6>
                                        </div>
                                        <div class="card-body">
                                            <?= $article->content ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>



<script>
    function togglePdf(id) {
        const viewer = document.getElementById('pdf-' + id);
        const content = document.getElementById('content-' + id);
        viewer.style.display = viewer.style.display === 'none' ? 'block' : 'none';
        if (content) content.style.display = 'none';
    }
    function toggleContent(id) {
        const content = document.getElementById('content-' + id);
        const viewer = document.getElementById('pdf-' + id);
        content.style.display = content.style.display === 'none' ? 'block' : 'none';
        viewer.style.display = 'none';
    }
</script>
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<script>
    ClassicEditor
        .create(document.querySelector('#content'), {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo']
        })
        .catch(error => console.error(error));
</script>

<?php require_once 'Views/footer.php'; ?>