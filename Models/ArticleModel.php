<?php
require_once 'Article.php';

/**
 * Model pro správu článků
 *
 * Zajišťuje operace CRUD nad články v databázi včetně
 * správy statusů a vazeb na recenzenty.
 */
class ArticleModel {
    private $db;
    private $tableName = 'articles';
    private $reviewersTable = 'article_reviewers';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Vytvoří nový článek v databázi
     *
     * @param array $data Asociativní pole s daty článku (title, abstract, content, pdf_filename, author_id)
     * @return bool Úspěch operace
     */
    public function create($data) {
        $sql = "INSERT INTO articles (title, abstract, content, pdf_filename, author_ID, status, created_at, updated_at)
            VALUES (:title, :abstract, :content, :pdf_filename, :author_id, 'draft', NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':title' => $data['title'],
            ':abstract' => $data['abstract'],
            ':content' => $data['content'] ?? null,
            ':pdf_filename' => $data['pdf_filename'],
            ':author_id' => $data['author_id']
        ]);
    }

    /**
     * Získá článek podle ID včetně informací o autorovi
     *
     * @param int $id ID článku
     * @return Article|false Objekt Article nebo false, pokud neexistuje
     */
    public function getById($id) {
        $sql = "SELECT a.ID as id, a.title, a.abstract, a.content, a.pdf_filename, 
                       a.author_ID as author_id, a.status, a.created_at, a.updated_at,
                       u.ID as user_id, u.name, u.surname, u.login
                FROM {$this->tableName} a
                LEFT JOIN users u ON a.author_ID = u.ID
                WHERE a.ID = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Article');
        return $stmt->fetch();
    }

    /**
     * Získá všechny články konkrétního autora
     *
     * @param int $authorId ID autora
     * @return Article[] Pole objektů Article
     */
    public function getByAuthor($authorId) {
        $sql = "SELECT ID as id, title, abstract, content, pdf_filename, 
                       author_ID as author_id, status, created_at, updated_at 
                FROM {$this->tableName} 
                WHERE author_ID = ? 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$authorId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'Article');
    }

    /**
     * Získá všechny schválené články pro veřejné zobrazení
     *
     * @param string $sort Sloupec pro řazení (created_at nebo title)
     * @param string $dir Směr řazení (ASC nebo DESC)
     * @return Article[] Pole schválených článků
     */
    public function getApproved($sort = 'created_at', $dir = 'DESC') {
        $allowedSort = ['created_at', 'title'];
        $allowedDir = ['ASC', 'DESC'];

        if (!in_array($sort, $allowedSort)) $sort = 'created_at';
        if (!in_array($dir, $allowedDir)) $dir = 'DESC';

        $sql = "SELECT a.ID as id, a.title, a.abstract, a.created_at, 
                       a.author_ID as author_id, u.name, u.surname
                FROM {$this->tableName} a
                LEFT JOIN users u ON a.author_ID = u.ID
                WHERE a.status = 'approved'
                ORDER BY a.{$sort} {$dir}";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'Article');
    }

    /**
     * Odešle článek k recenzi (změní status na 'submitted')
     *
     * @param int $id ID článku
     * @return bool Úspěch operace
     */
    public function submitForReview($id) {
        $sql = "UPDATE {$this->tableName} SET status = 'submitted' WHERE ID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Zkontroluje, zda má článek přiřazené recenzenty
     *
     * @param int $articleId ID článku
     * @return bool True pokud má článek recenzenty
     */
    public function hasReviewers($articleId) {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->reviewersTable} WHERE article_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['cnt'] > 0;
    }

    /**
     * Smaže článek včetně souvisejícího PDF souboru
     *
     * @param int $id ID článku
     * @return array Asociativní pole se statusem ['success' => bool, 'message' => string]
     */
    public function delete($id) {
        if ($this->hasReviewers($id)) {
            return ['success' => false, 'message' => 'Nelze smazat článek s přiřazenými recenzenty.'];
        }

        $article = $this->getById($id);
        if ($article && file_exists('uploads/' . $article['pdf_filename'])) {
            unlink('uploads/' . $article['pdf_filename']);
        }

        $sql = "DELETE FROM {$this->tableName} WHERE ID = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        return ['success' => $success, 'message' => $success ? 'Článek smazán.' : 'Chyba při mazání.'];
    }

    /**
     * Aktualizuje data článku
     *
     * @param int $id ID článku
     * @param array $data Asociativní pole se sloupci a hodnotami k aktualizaci
     * @return bool Úspěch operace
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $params[':id'] = $id;
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE ID = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

}
