<?php

require_once 'config/constants.php';
require_once 'Models/ArticleModel.php';
require_once 'Models/Database.php';

class ArticleMakerController {

    private $articleModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->articleModel = new ArticleModel();
    }

    public function index() {
        $user = $this->getCurrentUser();
        if (!$user || !isset($user['id'])) {
            header('Location: index.php?path=login');
            exit;
        }

        //Vytvoření nového článku
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
            $title = trim($_POST['title'] ?? '');
            $abstract = trim($_POST['abstract'] ?? '');

            if (!$title || !$abstract) {
                $_SESSION['flash'] = 'Vyplňte všechna pole.';
                header('Location: index.php?path=article-maker');
                exit;
            }

            if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash'] = 'Musíte nahrát PDF soubor.';
                header('Location: index.php?path=article-maker');
                exit;
            }

            $file = $_FILES['pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $_SESSION['flash'] = 'Povolený formát je pouze PDF.';
                header('Location: index.php?path=article-maker');
                exit;
            }

            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = uniqid() . '_' . time() . '.pdf';
            $destination = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $_SESSION['flash'] = 'Chyba při nahrávání PDF.';
                header('Location: index.php?path=article-maker');
                exit;
            }
            $content = trim($_POST['content'] ?? '');

            $this->articleModel->create([
                'title' => $title,
                'abstract' => $abstract,
                'content' => $content,
                'pdf_filename' => $filename,
                'author_id' => $user['id']
            ]);

            $_SESSION['flash'] = 'Článek vytvořen.';
            header('Location: index.php?path=article-maker');
            exit;
        }

        //Odeslání k recenzi
        if (isset($_GET['submit'])) {
            $id = (int)$_GET['submit'];
            $article = $this->articleModel->getById($id);

            if ($article && (int)$article->author_id === (int)$user['id'] && $article->status === 'draft') {
                $this->articleModel->submitForReview($id);
                $_SESSION['flash'] = 'Článek odeslán k recenzi.';
            }
            header('Location: index.php?path=article-maker');
            exit;
        }

        //Smazání
        if (isset($_GET['delete'])) {
            $id = (int)$_GET['delete'];
            $article = $this->articleModel->getById($id);

            if ($article && (int)$article->author_id === (int)$user['id']) {
                $result = $this->articleModel->delete($id);
                $_SESSION['flash'] = $result['message'];
            } else {
                $_SESSION['flash'] = 'Nemáte oprávnění smazat tento článek.';
            }

            header('Location: index.php?path=article-maker');
            exit;
        }

        //Uložení úprav článku
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
            $id = (int)($_POST['article_id'] ?? 0);
            $article = $this->articleModel->getById($id);

            if (!$article || (int)$article->author_id !== (int)$user['id']) {
                $_SESSION['flash'] = 'Nemáte oprávnění upravit tento článek.';
                header('Location: index.php?path=article-maker');
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $abstract = trim($_POST['abstract'] ?? '');
            $content = trim($_POST['content'] ?? '');

            if (!$title || !$abstract) {
                $_SESSION['flash'] = 'Vyplňte všechna povinná pole.';
                header('Location: index.php?path=article-maker&edit=' . $id);
                exit;
            }
            $updateData = [
                'title' => $title,
                'abstract' => $abstract,
                'content' => $content,
                'status' => 'draft'
            ];


            if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['pdf'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if ($ext !== 'pdf') {
                    $_SESSION['flash'] = 'Povolený formát je pouze PDF.';
                    header('Location: index.php?path=article-maker&edit=' . $id);
                    exit;
                }

                $uploadDir = 'uploads/';
                $filename = uniqid() . '_' . time() . '.pdf';
                $destination = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    if (file_exists($uploadDir . $article->pdf_filename)) {
                        unlink($uploadDir . $article->pdf_filename);
                    }
                    $updateData['pdf_filename'] = $filename;
                }
            }

            $this->articleModel->update($id, $updateData);
            $_SESSION['flash'] = 'Článek byl upraven, recenzenti resetováni a stav změněn na koncept.';
            header('Location: index.php?path=article-maker');
            exit;
        }

        //Zobrazení formuláře pro editaci
        if (isset($_GET['edit'])) {
            $id = (int)$_GET['edit'];
            $article = $this->articleModel->getById($id);

            if ($article && (int)$article->author_id === (int)$user['id'] &&
                in_array($article->status, ['draft', 'returned'])) {

                $data = [
                    'title' => 'Upravit článek',
                    'heading' => 'Úprava článku',
                    'user' => $user,
                    'articles' => $this->articleModel->getByAuthor($user['id']),
                    'flash' => $_SESSION['flash'] ?? null,
                    'editArticle' => $article,
                    'activePage' => 'article-maker'
                ];
                unset($_SESSION['flash']);

                require_once 'views/article-maker.php';
                exit;
            }
        }

        //Výpis seznamu článků
        $myArticles = $this->articleModel->getByAuthor($user['id']);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $data = [
            'title' => 'Moje články',
            'heading' => 'Správa mých článků',
            'user' => $user,
            'articles' => $myArticles,
            'flash' => $flash,
            'activePage' => 'article-maker'
        ];

        require_once 'views/article-maker.php';
    }

    public function viewPdf() {
        $id = (int)($_GET['id'] ?? 0);
        $article = $this->articleModel->getById($id);

        if (!$article) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $filepath = 'uploads/' . $article->pdf_filename;
        if (file_exists($filepath)) {
            header('Content-Type: application/pdf');
            readfile($filepath);
        }
        exit;
    }

    private function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
}