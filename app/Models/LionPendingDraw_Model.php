<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 라이온에서 보낸 “다음 추첨 슬롯” 조건 큐 (drawn_at당 1행, UPSERT 시 마지막 요청만 유효).
 * 추첨 시 PowerballDraw_Model::getOrGenerate() 안에서 읽은 뒤 삭제한다.
 */
class LionPendingDraw_Model extends Model
{
    protected $table          = 'lion_pending_draw';
    protected $primaryKey   = 'drawn_at';
    protected $useAutoIncrement = false;
    protected $allowedFields  = ['drawn_at', 'rules_json', 'updated_at'];
    protected $useTimestamps  = false;
    protected $returnType     = 'object';

    public function ensureTable(): void
    {
        $table = $this->db->prefixTable($this->table);
        if ($this->db->tableExists($table)) {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$table}` (
            `drawn_at` VARCHAR(19) NOT NULL COMMENT 'KST 5분 슬롯 Y-m-d H:i:00',
            `rules_json` TEXT NOT NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`drawn_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * @param array<string, string|null> $rules
     */
    public function upsertRules(string $drawnAt, array $rules): void
    {
        $this->ensureTable();
        $table = $this->db->prefixTable($this->table);
        $json  = json_encode($rules, JSON_UNESCAPED_UNICODE);
        $this->db->query(
            "INSERT INTO `{$table}` (`drawn_at`, `rules_json`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `rules_json` = VALUES(`rules_json`)",
            [$drawnAt, $json]
        );
    }

    /**
     * @return array<string, string>|null 비어 있거나 없으면 null (= 무제한 추첨)
     */
    public function consumeRulesForDrawnAt(string $drawnAt): ?array
    {
        $this->ensureTable();
        $table = $this->db->prefixTable($this->table);
        $row   = $this->db->query("SELECT `rules_json` FROM `{$table}` WHERE `drawn_at` = ?", [$drawnAt])->getRow();
        if ($row === null) {
            return null;
        }
        $this->db->query("DELETE FROM `{$table}` WHERE `drawn_at` = ?", [$drawnAt]);
        $decoded = json_decode((string) ($row->rules_json ?? ''), true);
        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        return $decoded;
    }
}
