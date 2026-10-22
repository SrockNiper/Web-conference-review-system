<?php

require_once 'config/constants.php';
require_once 'Models/ArticleModel.php';

class ArticleController {

    private $articleModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->articleModel = new ArticleModel();
    }

    public function index() {
        $articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = $this->getCurrentUser();

        $article = $this->articleModel->getById($articleId);

        if (!$article) {
            header('Location: index.php');
            exit;
        }


        $canView = false;
        if ($article->status === 'approved') {
            $canView = true;
        } elseif ($user && isset($user['id']) && (int)$article->author_id === (int)$user['id']) {
            $canView = true;
        } elseif ($user && $this->isReviewer($user)) {
            $canView = true;
        }

        if (!$canView) {
            $_SESSION['flash'] = 'Nemáte oprávnění zobrazit tento článek.';
            header('Location: index.php');
            exit;
        }

        $data = [
            'title' => $article->title,
            'article' => $article,
            'user' => $user
        ];

        require_once 'Views/article.php';
    }

    private function isReviewer($user) {
        if (!$user || !isset($user['role'])) return false;
        $role = $user['role'];
        return (is_int($role) && $role >= 3);
    }

    private function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    public function viewPdf() {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            die("Chybné ID článku.");
        }

        $article = $this->articleModel->getById($id);

        if (!$article) {
            http_response_code(404);
            die("Článek nenalezen.");
        }

        $user = $this->getCurrentUser();

        $canView = false;
        if ($article->status === 'approved') {
            $canView = true;
        } elseif ($user && isset($user['id']) && (int)$article->author_id === (int)$user['id']) {
            $canView = true;
        } elseif ($user && $this->isReviewer($user)) {
            $canView = true;
        }

        if (!$canView) {
            http_response_code(403);
            die("Nemáte oprávnění.");
        }

        $filepath = 'uploads/' . $article->pdf_filename;

        if (!file_exists($filepath)) {
            http_response_code(404);
            die("PDF soubor nenalezen.");
        }

        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }


}
