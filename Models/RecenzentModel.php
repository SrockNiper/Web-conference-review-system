<?php

/**
 * Model pro správu recenzentů a jejich přiřazených článků
 *
 * Zajišťuje operace související s recenzenty včetně získávání
 * přiřazených článků, ukládání recenzí a statistik.
 */
class RecenzentModel
{
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Získá všechny články přiřazené recenzentovi
     *
     * @param int $reviewerId ID recenzenta
     * @param string $orderBy Sloupec pro řazení (article_id, title, assigned_at)
     * @param string $direction Směr řazení (ASC nebo DESC)
     * @return array[] Pole článků s detaily recenze
     */
    public function getAssignedArticles($reviewerId, $orderBy = 'assigned_at', $direction = 'DESC') {
        $allowedColumns = ['article_id', 'title', 'assigned_at'];
        $allowedDirs = ['ASC', 'DESC'];

        if (!in_array($orderBy, $allowedColumns)) $orderBy = 'assigned_at';
        if (!in_array(strtoupper($direction), $allowedDirs)) $direction = 'DESC';

        // Mapování pro ORDER BY
        $orderMap = [
            'article_id' => 'a.ID',
            'title' => 'a.title',
            'assigned_at' => 'ar.assigned_at'
        ];

        $orderCol = $orderMap[$orderBy] ?? 'ar.assigned_at';

        $sql = "SELECT 
                    a.ID as article_id,
                    a.title,
                    a.abstract,
                    a.status as article_status,
                    ar.assigned_at,
                    ar.reviewed_at,
                    ar.decision,
                    ar.review_note,
                    CASE 
                        WHEN ar.reviewed_at IS NOT NULL THEN 'completed'
                        WHEN ar.decision IS NOT NULL OR ar.review_note IS NOT NULL THEN 'in_progress'
                        ELSE 'pending'
                    END as review_status,
                    CONCAT(u.name, ' ', u.surname) as author_name,
                    u.ID as author_id
                FROM article_reviewers ar
                JOIN articles a ON ar.article_ID = a.ID
                LEFT JOIN users u ON a.author_ID = u.ID
                WHERE ar.reviewer_ID = :reviewer_id
                ORDER BY {$orderCol} {$direction}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reviewer_id' => $reviewerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Zkontroluje, zda má recenzent přiřazen konkrétní článek
     *
     * @param int $reviewerId ID recenzenta
     * @param int $articleId ID článku
     * @return bool True pokud má recenzent článek přiřazen
     */
    public function hasArticleAssigned($reviewerId, $articleId) {
        $sql = "SELECT COUNT(*) as cnt 
                FROM article_reviewers 
                WHERE reviewer_ID = :reviewer_id AND article_ID = :article_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':reviewer_id' => $reviewerId,
            ':article_id' => $articleId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['cnt'] > 0;
    }

    /**
     * Získá statistiky recenzenta
     *
     * @param int $reviewerId ID recenzenta
     * @return array Asociativní pole se statistikami (total, completed, in_progress, pending)
     */
    public function getReviewerStats($reviewerId) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN reviewed_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN (decision IS NOT NULL OR review_note IS NOT NULL) AND reviewed_at IS NULL THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN decision IS NULL AND review_note IS NULL AND reviewed_at IS NULL THEN 1 ELSE 0 END) as pending
                FROM article_reviewers
                WHERE reviewer_ID = :reviewer_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reviewer_id' => $reviewerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Uloží částečnou recenzi (draft)
     *
     * @param int $reviewerId ID recenzenta
     * @param int $articleId ID článku
     * @param string|null $decision Rozhodnutí (approved/rejected) nebo null
     * @param string|null $note Poznámka recenzenta nebo null
     * @return bool Úspěch operace
     */
    public function saveReviewDraft($reviewerId, $articleId, $decision = null, $note = null) {
        $sql = "UPDATE article_reviewers 
                SET decision = :decision,
                    review_note = :note
                WHERE reviewer_ID = :reviewer_id AND article_ID = :article_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':decision' => $decision,
            ':note' => $note,
            ':reviewer_id' => $reviewerId,
            ':article_id' => $articleId
        ]);
    }

    /**
     * Dokončí recenzi a nastaví timestamp
     *
     * @param int $reviewerId ID recenzenta
     * @param int $articleId ID článku
     * @param string $decision Rozhodnutí (approved/rejected)
     * @param string $note Poznámka recenzenta
     * @return bool Úspěch operace
     */
    public function completeReview($reviewerId, $articleId, $decision, $note) {
        $sql = "UPDATE article_reviewers 
                SET decision = :decision,
                    review_note = :note,
                    reviewed_at = NOW()
                WHERE reviewer_ID = :reviewer_id AND article_ID = :article_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':decision' => $decision,
            ':note' => $note,
            ':reviewer_id' => $reviewerId,
            ':article_id' => $articleId
        ]);
    }

    /**
     * Získá detail recenze pro konkrétní článek a recenzenta
     *
     * @param int $reviewerId ID recenzenta
     * @param int $articleId ID článku
     * @return array|false Asociativní pole s daty recenze nebo false
     */
    public function getReview($reviewerId, $articleId) {
        $sql = "SELECT * 
                FROM article_reviewers 
                WHERE reviewer_ID = :reviewer_id AND article_ID = :article_id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':reviewer_id' => $reviewerId,
            ':article_id' => $articleId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
