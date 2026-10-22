<?php

require_once 'Models/UserModel.php';
require_once 'Models/ArticleModel.php';
require_once 'Models/ReviewManagementModel.php';
require_once 'Models/Database.php';

class ManageRecenzentsController {

    private $userModel;
    private $articleModel;
    private $reviewManagementModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $this->userModel = new UserModel();
        $this->articleModel = new ArticleModel();
        $this->reviewManagementModel = new ReviewManagementModel();

        if (!$this->isAdmin()) {
            header('Location: index.php');
            exit;
        }
    }

    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
            $this->handleAjaxRequest();
            return;
        }

        $currentUser = $_SESSION['user'] ?? null;
        $articles = $this->reviewManagementModel->getAllArticlesWithReviewStats();
        $reviewers = $this->reviewManagementModel->getAllReviewers();

        $data = [
            'title' => 'Přiřazení recenzentů',
            'user' => $this->getUserDisplay($currentUser),
            'articles' => $articles,
            'reviewers' => $reviewers
        ];

        require_once 'views/manage-recenzents.php';
    }

    private function handleAjaxRequest() {
        header('Content-Type: application/json');
        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'assign_reviewers':
                    echo json_encode($this->reviewManagementModel->assignReviewers(
                        (int)($_POST['article_id'] ?? 0),
                        $_POST['reviewers'] ?? []
                    ));
                    break;

                case 'approve_article':
                    echo json_encode($this->reviewManagementModel->approveArticle(
                        (int)($_POST['article_id'] ?? 0)
                    ));
                    break;

                case 'reject_article':
                    echo json_encode($this->reviewManagementModel->rejectArticle(
                        (int)($_POST['article_id'] ?? 0)
                    ));
                    break;

                case 'return_article':
                    echo json_encode($this->reviewManagementModel->returnArticle(
                        (int)($_POST['article_id'] ?? 0)
                    ));
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Neznámá akce']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function isAdmin() {
        $user = $_SESSION['user'] ?? null;
        if (!$user || !is_array($user)) return false;
        $role = $user['role'] ?? null;
        return is_numeric($role) && in_array((int)$role, [4, 5], true);
    }

    private function getUserDisplay($user) {
        if (!$user) return null;
        if (is_array($user)) {
            if (!empty($user['username'])) return $user['username'];
            $first = $user['first_name'] ?? $user['name'] ?? '';
            $last = $user['last_name'] ?? $user['surname'] ?? '';
            return trim($first . ' ' . $last) ?: 'Uživatel';
        }
        return is_string($user) ? $user : null;
    }
}
