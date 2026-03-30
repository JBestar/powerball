<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 1:1 문의사항 — 테이블 qna_post (qna_post.sql), 스키마는 자유게시판(free_post)과 동일
 */
class QnaPost_Model extends Model
{
    protected $table = 'qna_post';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['mb_uid', 'mb_nickname', 'title', 'content', 'comment_count', 'wr_hit', 'wr_good', 'is_notice', 'is_secret', 'parent_id', 'created_at'];
    protected $useTimestamps = false;

    public function getTable(): string
    {
        return $this->table;
    }

    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        $table = $this->getTable();
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `mb_uid` VARCHAR(64) NOT NULL,
            `mb_nickname` VARCHAR(64) NOT NULL DEFAULT '',
            `title` VARCHAR(200) NOT NULL,
            `content` MEDIUMTEXT NULL,
            `comment_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `wr_hit` INT UNSIGNED NOT NULL DEFAULT 0,
            `wr_good` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_notice` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `is_secret` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `parent_id` INT UNSIGNED NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_mb_uid` (`mb_uid`),
            KEY `idx_notice` (`is_notice`),
            KEY `idx_parent` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $db->query($sql);

        if (!$db->fieldExists('content', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `content` MEDIUMTEXT NULL COMMENT '본문' AFTER `title`");
        }
        if (!$db->fieldExists('wr_hit', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `wr_hit` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `comment_count`");
        }
        if (!$db->fieldExists('wr_good', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `wr_good` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `wr_hit`");
        }
        if (!$db->fieldExists('is_notice', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `is_notice` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `wr_good`");
        }
        if (!$db->fieldExists('mb_nickname', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `mb_nickname` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '표시 닉네임' AFTER `mb_uid`");
            $db->query("UPDATE `{$table}` SET `mb_nickname` = `mb_uid` WHERE `mb_nickname` = ''");
        }
        if (!$db->fieldExists('is_secret', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `is_secret` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '비밀글' AFTER `is_notice`");
        }
        if (!$db->fieldExists('parent_id', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `parent_id` INT UNSIGNED NULL DEFAULT NULL COMMENT '답변 원글 id' AFTER `is_secret`");
        }
        $idxParent = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = 'idx_parent'")->getFirstRow();
        if ($idxParent === null) {
            $db->query("ALTER TABLE `{$table}` ADD KEY `idx_parent` (`parent_id`)");
        }
    }

    public function countListFiltered(string $sfl, string $stx): int
    {
        $this->ensureTable();
        $b = $this->builder();
        $b->where('is_notice', 0);
        $this->applyListSearch($b, $sfl, $stx);

        return (int) $b->countAllResults();
    }

    /**
     * @return object[]
     */
    public function getListPage(int $page, int $perPage, string $sfl, string $stx, string $sst, string $sod): array
    {
        $this->ensureTable();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $off = ($page - 1) * $perPage;

        $b = $this->builder();
        $b->where('is_notice', 0);
        $this->applyListSearch($b, $sfl, $stx);
        $orderField = 'id';
        $dir = strtolower($sod) === 'asc' ? 'ASC' : 'DESC';
        switch ($sst) {
            case 'wr_datetime':
                $orderField = 'created_at';
                break;
            case 'wr_hit':
                $orderField = 'wr_hit';
                break;
            case 'wr_good':
                $orderField = 'wr_good';
                break;
            default:
                $orderField = 'id';
                break;
        }
        $b->orderBy($orderField, $dir);

        return $b->get($perPage, $off)->getResultObject() ?: [];
    }

    public function getNotices(): array
    {
        $this->ensureTable();
        $rows = $this->where('is_notice', 1)->orderBy('id', 'ASC')->findAll();

        return is_array($rows) ? $rows : [];
    }

    protected function applyListSearch($b, string $sfl, string $stx): void
    {
        $stx = trim($stx);
        if ($stx === '') {
            return;
        }
        switch ($sfl) {
            case 'wr_content':
                $b->like('content', $stx);
                break;
            case 'wr_subject||wr_content':
                $b->groupStart()
                    ->like('title', $stx)
                    ->orLike('content', $stx)
                    ->groupEnd();
                break;
            case 'mb_id,1':
                $b->where('mb_uid', $stx);
                break;
            case 'mb_id,0':
                $b->like('mb_uid', $stx);
                break;
            case 'wr_name,1':
            case 'wr_name,0':
                $b->groupStart()
                    ->like('mb_uid', $stx)
                    ->orLike('mb_nickname', $stx)
                    ->groupEnd();
                break;
            case 'wr_subject':
            default:
                $b->like('title', $stx);
                break;
        }
    }

    /**
     * @return array{newer_id:int|null,older_id:int|null}
     */
    public function getNeighborIds(int $id): array
    {
        $this->ensureTable();
        if ($id <= 0) {
            return ['newer_id' => null, 'older_id' => null];
        }
        $t = $this->getTable();
        $newer = $this->db->query(
            "SELECT MIN(`id`) AS i FROM `{$t}` WHERE `id` > ? AND `is_notice` = 0",
            [$id]
        )->getRow();
        $older = $this->db->query(
            "SELECT MAX(`id`) AS i FROM `{$t}` WHERE `id` < ? AND `is_notice` = 0",
            [$id]
        )->getRow();

        return [
            'newer_id' => ($newer && $newer->i) ? (int) $newer->i : null,
            'older_id' => ($older && $older->i) ? (int) $older->i : null,
        ];
    }
}
