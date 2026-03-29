<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 포토 리스트용 모델
 * 실제 이미지 파일은 public/uploads/photos/ 에 저장하고, file_path만 DB에 저장
 */
class BoardPhoto_Model extends Model
{
    protected $table      = 'board_photo';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['wr_id', 'title', 'file_path', 'created_at', 'mb_uid', 'mb_nickname'];

    /**
     * DBPrefix 와 무관하게 물리 테이블명 board_photo 사용
     */
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
            `wr_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `title` VARCHAR(200) NOT NULL DEFAULT '',
            `file_path` VARCHAR(255) NOT NULL DEFAULT '',
            `created_at` DATETIME DEFAULT NULL,
            `mb_uid` INT UNSIGNED DEFAULT NULL,
            `mb_nickname` VARCHAR(64) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->query($sql);
        if (!$db->fieldExists('mb_nickname', $this->table)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `mb_nickname` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '표시 닉네임' AFTER `mb_uid`");
            $db->query("UPDATE `{$table}` SET `mb_nickname` = CAST(`mb_uid` AS CHAR) WHERE (`mb_nickname` = '' OR `mb_nickname` IS NULL) AND `mb_uid` IS NOT NULL");
        }
    }

    /**
     * 메인 포토 리스트박스용 목록 (최신순)
     */
    public function getListForMain(int $limit = 14): array
    {
        $this->ensureTable();
        $rows = $this->orderBy('id', 'DESC')
            ->findAll($limit);

        return is_array($rows) ? $rows : [];
    }

    public function countListFiltered(string $sfl, string $stx): int
    {
        $this->ensureTable();
        $b = $this->builder();
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
        $this->applyListSearch($b, $sfl, $stx);
        $orderField = 'id';
        $dir = strtolower($sod) === 'asc' ? 'ASC' : 'DESC';
        switch ($sst) {
            case 'wr_datetime':
                $orderField = 'created_at';
                break;
            case 'wr_hit':
            case 'wr_good':
            default:
                $orderField = 'id';
                break;
        }
        $b->orderBy($orderField, $dir);

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
                $b->like('title', $stx);
                break;
            case 'wr_subject||wr_content':
                $b->like('title', $stx);
                break;
            case 'mb_id,1':
                if (ctype_digit($stx)) {
                    $b->where('mb_uid', (int) $stx);
                } else {
                    $b->where('id', 0);
                }
                break;
            case 'mb_id,0':
                $b->like('mb_uid', $stx);
                break;
            case 'wr_name,1':
            case 'wr_name,0':
                if (ctype_digit($stx)) {
                    $b->where('mb_uid', (int) $stx);
                } else {
                    $b->groupStart()
                        ->like('mb_nickname', $stx)
                        ->orLike('title', $stx)
                        ->groupEnd();
                }
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
            "SELECT MIN(`id`) AS i FROM `{$t}` WHERE `id` > ?",
            [$id]
        )->getRow();
        $older = $this->db->query(
            "SELECT MAX(`id`) AS i FROM `{$t}` WHERE `id` < ?",
            [$id]
        )->getRow();

        return [
            'newer_id' => ($newer && $newer->i) ? (int) $newer->i : null,
            'older_id' => ($older && $older->i) ? (int) $older->i : null,
        ];
    }
}
