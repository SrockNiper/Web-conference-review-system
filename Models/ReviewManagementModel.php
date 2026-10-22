<?php

/**
 * Model pro správu recenzního procesu článků
 *
 * Zajišťuje přiřazování recenzentů k článkům, sledování průběhu recenzí
 * a správu statusů článků (schválení, zamítnutí, vrácení).
 */
class ReviewManagementModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Přiřadí recenzenty k článku a změní jeho status
     *
     * @param int $articleId ID článku
     * @param int[] $reviewerIds Pole ID recenzentů (min. 3)
     * @return array Asociativní pole ['success' => bool, 'message' => string]
     */
    public function assignReviewers($articleId, array $reviewerIds) {
        if (count($reviewerIds) < 3) {
            return ['success' => false, 'message' => 'Musíte vybrat alespoň 3 recenzenty'];
        }

        try {
            $this->db->beginTransaction();

            // Smazat stávající přiřazení
            $sql = "DELETE FROM article_reviewers WHERE article_ID = :article_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':article_id' => $articleId]);

            // Přidat nové recenzenty
            $sql = "INSERT INTO article_reviewers (article_ID, reviewer_ID, assigned_at) 
                    VALUES (:article_id, :reviewer_id, NOW())";
            $stmt = $this->db->prepare($sql);

            foreach ($reviewerIds as $reviewerId) {
                $stmt->execute([
                    ':article_id' => $articleId,
                    ':reviewer_id' => $reviewerId
                ]);
            }

            $sqlUpdate = "UPDATE articles SET status = 'in_review' WHERE ID = :article_id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([':article_id' => $articleId]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Recenzenti úspěšně přiřazeni'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Chyba při přiřazování: ' . $e->getMessage()];
        }
    }

    /**
     * Získá všechny uživatele s rolí recenzenta
     *
     * @return array[] Pole recenzentů s id, full_name a email
     */
    public function getAllReviewers() {
        $sql = "SELECT ID as id, CONCAT(name, ' ', surname) as full_name, email
                FROM users WHERE role = 3 ORDER BY surname, name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Získá všechny články včetně statistik recenzí
     *
     * @return array[] Pole článků s agregovanými daty recenzí a možnostmi automatického schválení/zamítnutí
     */
    public function getAllArticlesWithReviewStats() {
        $sql = "SELECT 
                    a.ID as id, 
                    a.title, 
                    a.status, 
                    a.created_at,
                    CONCAT(u.name, ' ', u.surname) as author_name,
                    COUNT(DISTINCT ar.reviewer_ID) as total_reviewers,
                    SUM(CASE WHEN ar.decision = 'approved' THEN 1 ELSE 0 END) as accepted_count,
                    SUM(CASE WHEN ar.decision = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN ar.reviewed_at IS NOT NULL THEN 1 ELSE 0 END) as completed_reviews,
                    GROUP_CONCAT(
                        CONCAT(rev.name, ' ', rev.surname, ':', 
                        COALESCE(ar.decision, 'pending'), ':', 
                        COALESCE(ar.review_note, ''))
                        SEPARATOR '|'
                    ) as reviews_data
                FROM articles a
                LEFT JOIN users u ON a.author_ID = u.ID
                LEFT JOIN article_reviewers ar ON a.ID = ar.article_ID
                LEFT JOIN users rev ON ar.reviewer_ID = rev.ID
                GROUP BY a.ID
                ORDER BY a.created_at DESC";

        $stmt = $this->db->query($sql);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);


        foreach ($articles as &$article) {
            $article['reviews'] = $this->parseReviewsData($article['reviews_data']);
            $article['can_auto_approve'] = $this->canAutoApprove($article);
            $article['can_auto_reject'] = $this->canAutoReject($article);
        }

        return $articles;
    }

    /**
     * Parsuje data recenzí z GROUP_CONCAT formátu
     *
     * @param string|null $reviewsData Data v formátu "jméno:rozhodnutí:poznámka|..."
     * @return array[] Pole asociativních polí s klíči reviewer, decision, note
     */
    private function parseReviewsData($reviewsData) {
        if (!$reviewsData) return [];

        $reviews = [];
        foreach (explode('|', $reviewsData) as $reviewStr) {
            $parts = explode(':', $reviewStr);
            if (count($parts) >= 3) {
                $reviews[] = [
                    'reviewer' => $parts[0],
                    'decision' => $parts[1],
                    'note' => $parts[2]
                ];
            }
        }
        return $reviews;
    }

    /**
     * Kontroluje, zda lze článek automaticky schválit
     *
     * @param array $article Asociativní pole s daty článku včetně statistik recenzí
     * @return bool True pokud většina dokončených recenzí doporučuje schválení
     */
    private function canAutoApprove($article) {
        $total = (int)$article['total_reviewers'];
        $completed = (int)$article['completed_reviews'];
        $accepted = (int)$article['accepted_count'];

        return $total > 0 && $completed === $total && $accepted > ($total / 2);
    }

    /**
     * Kontroluje, zda lze článek automaticky zamítnout
     *
     * @param array $article Asociativní pole s daty článku včetně statistik recenzí
     * @return bool True pokud alespoň polovina dokončených recenzí doporučuje zamítnutí
     */
    private function canAutoReject($article) {
        $total = (int)$article['total_reviewers'];
        $completed = (int)$article['completed_reviews'];
        $rejected = (int)$article['rejected_count'];

        return $total > 0 && $completed === $total && $rejected >= ($total / 2);
    }

    /**
     * Schválí článek (nastaví status na 'approved')
     *
     * @param int $articleId ID článku
     * @return array Asociativní pole ['success' => bool, 'message' => string]
     */
    public function approveArticle($articleId) {
        $sql = "UPDATE articles SET status = 'approved' WHERE ID = :article_id";
        $stmt = $this->db->prepare($sql);

        if ($stmt->execute([':article_id' => $articleId])) {
            return ['success' => true, 'message' => 'Článek byl schválen'];
        }
        return ['success' => false, 'message' => 'Chyba při schvalování'];
    }

    /**
     * Zamítne článek (nastaví status na 'rejected')
     *
     * @param int $articleId ID článku
     * @return array Asociativní pole ['success' => bool, 'message' => string]
     */
    public function rejectArticle($articleId) {
        $sql = "UPDATE articles SET status = 'rejected' WHERE ID = :article_id";
        $stmt = $this->db->prepare($sql);

        if ($stmt->execute([':article_id' => $articleId])) {
            return ['success' => true, 'message' => 'Článek byl zamítnut'];
        }
        return ['success' => false, 'message' => 'Chyba při zamítání'];
    }

    /**
     * Vrátí článek autorovi k přepracování (nastaví status na 'returned')
     *
     * @param int $articleId ID článku
     * @return array Asociativní pole ['success' => bool, 'message' => string]
     */
    public function returnArticle($articleId) {
        $sql = "UPDATE articles SET status = 'returned' WHERE ID = :article_id";
        $stmt = $this->db->prepare($sql);

        if ($stmt->execute([':article_id' => $articleId])) {
            return ['success' => true, 'message' => 'Článek byl vrácen autorovi'];
        }
        return ['success' => false, 'message' => 'Chyba při vracení'];
    }
}
