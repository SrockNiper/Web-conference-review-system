<?php

require_once 'Models/ArticleModel.php';

class ArchiveController {

    private $articleModel;

    public function __construct() {
        $this->articleModel = new ArticleModel();
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $user = $_SESSION['user'] ?? null;
        $message = $_SESSION['message'] ?? null;
        $messageType = $_SESSION['message_type'] ?? null;
        unset($_SESSION['message'], $_SESSION['message_type']);

        // Získání všech schválených článků
        $sort = 'created_at';
        $dir = 'DESC';
        $dbArticles = $this->articleModel->getApproved($sort, $dir);

        // Formátování článků pro zobrazení
        $formattedArticles = [];
        foreach ($dbArticles as $article) {
            $formattedArticles[] = [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => $article->abstract ?? mb_substr(strip_tags($article->text ?? ''), 0, 150) . '...',
                'author' => trim(($article->name ?? '') . ' ' . ($article->surname ?? '')),
                'date' => date('d.m.Y', strtotime($article->created_at))
            ];
        }

        $data = [
            'title' => 'Archiv článků',
            'user' => $user,
            'articles' => $formattedArticles,
            'message' => $message,
            'message_type' => $messageType,
            'activePage' => 'archive'
        ];

        require_once 'views/archive.php';
    }
}

?>
