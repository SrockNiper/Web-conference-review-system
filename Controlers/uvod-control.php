<?php

require_once 'Models/ArticleModel.php';

class UvodController {

    private $articleModel;

    public function __construct() {
        $this->articleModel = new ArticleModel();
    }

    public function index() {
        $user = $this->getCurrentUser();
        $userDisplay = null;
        if (is_array($user)) {
            if (!empty($user['username'])) {
                $userDisplay = $user['username'];
            } elseif (!empty($user['name'])) {
                $userDisplay = $user['name'];
            } else {
                $first = $user['first_name'] ?? $user['firstname'] ?? null;
                $last = $user['last_name'] ?? $user['lastname'] ?? null;
                if ($first || $last) {
                    $userDisplay = trim(($first ?? '') . ' ' . ($last ?? ''));
                } else {
                    $userDisplay = 'Uživatel';
                }
            }
        } elseif (is_string($user)) {
            $userDisplay = $user;
        } else {
            $userDisplay = null;
        }


        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $flash = null;
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }


        $sort = $_GET['sort'] ?? 'created_at';
        $dir = $_GET['dir'] ?? 'DESC';

        $allowedSort = ['created_at', 'title'];
        $allowedDir = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSort)) $sort = 'created_at';
        if (!in_array($dir, $allowedDir)) $dir = 'DESC';

        $dbArticles = $this->articleModel->getApproved($sort, $dir);


        $articles = [];
        foreach ($dbArticles as $article) {
            $articles[] = [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => $article->abstract ?? '',
                'date' => date('d.m.Y', strtotime($article->created_at)),
                'author' => trim(($article->name ?? '') . ' ' . ($article->surname ?? ''))
            ];
        }


        $data = [
            'title' => 'WEB CONFERENCE',
            'heading' => '',
            'description' => '',
            'user' => $userDisplay,
            'flash' => $flash,
            'articles' => $articles
        ];

        
        require_once 'Views/uvod.php';
    }

    private function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return $_SESSION['user'] ?? null;
    }
}

?>
