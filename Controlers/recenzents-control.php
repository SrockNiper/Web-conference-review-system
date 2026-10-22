<?php
require_once __DIR__ . '/../Models/Database.php';
require_once __DIR__ . '/../Models/RecenzentModel.php';

class RecenzentsController {
    private $recenzentModel;
    private $orderBy;
    private $direction;
    private $assignedArticles;
    private $currentUserId;
    private $stats;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!isset($_SESSION['user'])) {
            header('Location: index.php?path=login');
            exit;
        }

        $userRole = $_SESSION['user']['role'] ?? null;
        $isRecenzent = false;

        if (is_numeric($userRole) && (int)$userRole === 3) {
            $isRecenzent = true;
        } elseif (is_string($userRole) && strtolower($userRole) === 'recenzent') {
            $isRecenzent = true;
        }

        if (!$isRecenzent) {
            header('Location: index.php');
            exit;
        }

        $this->currentUserId = $_SESSION['user']['id'] ?? null;
        $this->recenzentModel = new RecenzentModel();
        $this->initSorting();
        $this->loadData();
    }

    private function initSorting() {
        $this->orderBy = $_GET['order'] ?? 'assigned_at';
        $this->direction = $_GET['dir'] ?? 'DESC';
    }

    private function loadData() {
        $this->assignedArticles = $this->recenzentModel->getAssignedArticles(
            $this->currentUserId,
            $this->orderBy,
            $this->direction
        );

        $this->stats = $this->recenzentModel->getReviewerStats($this->currentUserId);
    }

    public function index() {
        $orderBy = $this->orderBy;
        $direction = $this->direction;
        $assignedArticles = $this->assignedArticles;
        $stats = $this->stats;

        require_once __DIR__ . '/../Views/recenzents.php';
    }
}

function getNextDir($currentDir) {
    return strtoupper($currentDir) === 'ASC' ? 'DESC' : 'ASC';
}
