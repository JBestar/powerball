<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 자주묻는질문 — 테이블 faq_post (스키마는 분석픽 pick_post 와 동일)
 */
class FaqPost_Model extends Model
{
    protected $table = 'faq_post';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['mb_uid', 'mb_nickname', 'title', 'content', 'comment_count', 'wr_hit', 'wr_good', 'is_notice', 'created_at'];
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
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_mb_uid` (`mb_uid`),
            KEY `idx_notice` (`is_notice`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $db->query($sql);

        if (!$db->fieldExists('content', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `content` MEDIUMTEXT NULL COMMENT '본문(HTML)' AFTER `title`");
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
    }

    /**
     * 선배 사이트 FAQ 본문 — 테이블이 비어 있을 때만 삽입 (app/Database/faq_seed_data.php)
     */
    public function seedFaqIfEmpty(): void
    {
        $this->ensureTable();
        $n = (int) $this->db->table($this->getTable())->countAllResults();
        if ($n > 0) {
            return;
        }
        $path = __DIR__ . '/../Database/faq_seed_data.php';
        if (! is_file($path)) {
            return;
        }
        /** @var array<int, array{title:string, content:string}> $items */
        $items = require $path;
        $now = date('Y-m-d H:i:s');
        foreach ($items as $row) {
            $this->insert([
                'mb_uid' => 'operator',
                'mb_nickname' => '운영자',
                'title' => $row['title'],
                'content' => $row['content'],
                'comment_count' => 0,
                'wr_hit' => 0,
                'wr_good' => 0,
                'is_notice' => 0,
                'created_at' => $now,
            ]);
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
        $dir = strtolower($sod) === 'asc' ? 'ASC' : 'DESC';
        $b->orderBy('id', $dir);

        return $b->get($perPage, $off)->getResultObject() ?: [];
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
}
