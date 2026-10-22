<?php
require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/RecenzentModel.php';
require_once __DIR__ . '/../Models/ArticleModel.php';

class ReviewController {
    private $recenzentModel;
    private $articleModel;
    private $currentUserId;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($_SESSION['user'])) {
            header('Location: index.php?path=login');
            exit;
        }

        $this->currentUserId = $_SESSION['user']['id'] ?? null;
        $this->recenzentModel = new RecenzentModel();
        $this->articleModel = new ArticleModel();
    }

    public function index() {
        $articleId = $_GET['article'] ?? null;

        if (!$articleId || !is_numeric($articleId)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Neplatné ID článku'];
            header('Location: index.php?path=recenzents');
            exit;
        }

        // Kontrola oprávnění
        if (!$this->recenzentModel->hasArticleAssigned($this->currentUserId, $articleId)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nemáte přístup k tomuto článku'];
            header('Location: index.php?path=recenzents');
            exit;
        }


        $article = $this->articleModel->getById($articleId);
        $review = $this->recenzentModel->getReview($this->currentUserId, $articleId);


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleReviewSubmit($articleId);
        }

        require_once __DIR__ . '/../Views/review.php';
    }


    private function handleReviewSubmit($articleId) {
        $action = $_POST['action'] ?? '';
        $decision = $_POST['decision'] ?? null;
        $note = $_POST['review_note'] ?? '';

        if ($action === 'save_draft') {
            // Uložit koncept
            $result = $this->recenzentModel->saveReviewDraft($this->currentUserId, $articleId, $decision, $note);
            $_SESSION['flash'] = $result
                ? ['type' => 'success', 'message' => 'Koncept uložen']
                : ['type' => 'danger', 'message' => 'Chyba při ukládání'];
        } elseif ($action === 'submit') {
            // Dokončit recenzi
            if (empty($decision) || empty($note)) {
                $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Vyplňte rozhodnutí i poznámku'];
            } else {
                $result = $this->recenzentModel->completeReview($this->currentUserId, $articleId, $decision, $note);
                $_SESSION['flash'] = $result
                    ? ['type' => 'success', 'message' => 'Recenze odeslána']
                    : ['type' => 'danger', 'message' => 'Chyba při odesílání'];

                if ($result) {
                    header('Location: index.php?path=recenzents');
                    exit;
                }
            }
        }

        header("Location: index.php?path=review&article={$articleId}");
        exit;
    }
}
