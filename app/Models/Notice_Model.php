<?php

namespace App\Models;

use CodeIgniter\Model;

class Notice_Model extends Model
{
    /** 고객센터(상단·목록)에 쓰는 타입 — DB notice_type 컬럼 값 */
    public const TYPE_NOTICE = '공지';
    public const TYPE_GUIDE  = '안내';

    /** 구버전 DB에 저장된 값 — 조회·수정 시에만 사용 */
    private const TYPE_GUIDE_LEGACY = '가이드';

    protected $table         = 'board_notice';
    protected $primaryKey    = 'notice_fid';
    protected $returnType    = 'object';
    protected $allowedFields = [
        'notice_type', 'notice_title', 'notice_content', 'notice_answer',
        'notice_mb_uid', 'notice_emp_fid', 'notice_read_count', 'notice_hit',
        'notice_time_create', 'notice_time_update', 'notice_state_active',
        'notice_state_delete', 'notice_client_delete',
    ];

    /**
     * 테이블이 없으면 생성 (기존 테이블 삭제 후 재배포용)
     */
    public function ensureTable(): void
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $sql = "CREATE TABLE `{$this->table}` (
            `notice_fid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `notice_type` VARCHAR(20) NOT NULL DEFAULT '공지' COMMENT '공지|안내 (레거시 가이드·쪽지 등은 별도 문자열)',
            `notice_title` VARCHAR(500) NOT NULL DEFAULT '',
            `notice_content` MEDIUMTEXT NULL,
            `notice_answer` TEXT NULL,
            `notice_mb_uid` VARCHAR(64) NOT NULL DEFAULT '',
            `notice_emp_fid` INT UNSIGNED NOT NULL DEFAULT 0,
            `notice_hit` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '본문 조회수',
            `notice_read_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '레거시(쪽지 읽음 등)',
            `notice_time_create` DATETIME NULL,
            `notice_time_update` DATETIME NULL,
            `notice_state_active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `notice_state_delete` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `notice_client_delete` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`notice_fid`),
            KEY `idx_type_del_time` (`notice_type`, `notice_state_delete`, `notice_fid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /** 폼·신규 저장에 허용되는 값 */
    public static function siteBoardTypes(): array
    {
        return [self::TYPE_NOTICE, self::TYPE_GUIDE];
    }

    /** 목록·상세 조회 WHERE IN (구 DB의 가이드 문자열 포함) */
    public static function siteBoardTypesForQuery(): array
    {
        return [self::TYPE_NOTICE, self::TYPE_GUIDE, self::TYPE_GUIDE_LEGACY];
    }

    public static function isGuideCategory(?string $noticeType): bool
    {
        $t = (string) $noticeType;

        return $t === self::TYPE_GUIDE || $t === self::TYPE_GUIDE_LEGACY;
    }

    /** 수정 폼: 구 가이드 → 안내로 표시 통일 */
    public static function normalizeBoardType(?string $noticeType): string
    {
        $t = (string) $noticeType;
        if ($t === self::TYPE_GUIDE_LEGACY) {
            return self::TYPE_GUIDE;
        }

        return $t;
    }

    /** 실제 DB 테이블명(DBPrefix 반영) — JOIN/WHERE 에 Model 이중 접두사 버그 방지 */
    protected function tablePrefixed(): string
    {
        return $this->db->prefixTable($this->table);
    }

    protected function memberPrefixed(): string
    {
        return $this->db->prefixTable('member');
    }

    /** member 테이블과 board_notice 문자열 컬럼 collation 불일치 시 JOIN 오류 방지 */
    private const JOIN_STRING_COLLATE = 'utf8mb4_unicode_ci';

    public function getBoards()
    {
        $this->ensureTable();

        $tn = $this->tablePrefixed();
        $mn = $this->memberPrefixed();

        log_message('debug', sprintf(
            'Notice_Model::getBoards start table=%s member=%s expect_type=[%s,%s] state_active=%s state_delete=%s',
            $tn,
            $mn,
            self::TYPE_NOTICE,
            self::TYPE_GUIDE,
            (string) STATE_ACTIVE,
            (string) STATE_DISABLE
        ));

        try {
            $diagSql = "SELECT `notice_fid`, `notice_type`, `notice_state_active`, `notice_state_delete`,
                HEX(`notice_type`) AS notice_type_hex, CHAR_LENGTH(`notice_type`) AS notice_type_len
                FROM `{$tn}` ORDER BY `notice_fid` DESC LIMIT 20";
            $diag = $this->db->query($diagSql)->getResultArray();
            log_message('debug', 'Notice_Model::getBoards DB snapshot (last 20, no filter): ' . json_encode($diag, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            log_message('critical', 'Notice_Model::getBoards snapshot query failed: ' . $e->getMessage());
        }

        $collate = self::JOIN_STRING_COLLATE;
        $types   = self::siteBoardTypesForQuery();
        $inHold  = implode(',', array_fill(0, count($types), '?'));
        $sql = "SELECT `{$tn}`.*, `{$mn}`.`mb_grade`, `{$mn}`.`mb_nickname`
            FROM `{$tn}`
            LEFT JOIN `{$mn}` ON `{$mn}`.`mb_uid` COLLATE {$collate} = `{$tn}`.`notice_mb_uid` COLLATE {$collate}
            WHERE `{$tn}`.`notice_type` IN ({$inHold})
            AND `{$tn}`.`notice_state_active` = ?
            AND `{$tn}`.`notice_state_delete` = ?
            ORDER BY `{$tn}`.`notice_fid` DESC";

        $result = $this->db->query($sql, array_merge($types, [
            (int) STATE_ACTIVE,
            (int) STATE_DISABLE,
        ]));
        $rows = $result->getResultObject();
        log_message('debug', 'Notice_Model::getBoards filtered count=' . count($rows) . ' lastQuery=' . (string) $this->db->getLastQuery());

        return $rows;
    }

    public function getBoardById($fid)
    {
        $this->ensureTable();
        $fid = (int) $fid;
        if ($fid <= 0) {
            return null;
        }

        $tn = $this->tablePrefixed();
        $mn = $this->memberPrefixed();

        $collate = self::JOIN_STRING_COLLATE;
        $types   = self::siteBoardTypesForQuery();
        $inHold  = implode(',', array_fill(0, count($types), '?'));
        $sql = "SELECT `{$tn}`.*, `{$mn}`.`mb_grade`, `{$mn}`.`mb_nickname`
            FROM `{$tn}`
            LEFT JOIN `{$mn}` ON `{$mn}`.`mb_uid` COLLATE {$collate} = `{$tn}`.`notice_mb_uid` COLLATE {$collate}
            WHERE `{$tn}`.`notice_fid` = ?
            AND `{$tn}`.`notice_type` IN ({$inHold})
            AND `{$tn}`.`notice_state_active` = ?
            AND `{$tn}`.`notice_state_delete` = ?";

        $row = $this->db->query($sql, array_merge([$fid], $types, [
            (int) STATE_ACTIVE,
            (int) STATE_DISABLE,
        ]))->getFirstRow('object');
        log_message('debug', 'Notice_Model::getBoardById fid=' . $fid . ' found=' . ($row ? 'yes' : 'no') . ' lastQuery=' . (string) $this->db->getLastQuery());

        return $row;
    }

    /** 조회수 +1 (공지/안내·구 가이드만) */
    public function incrementHit(int $fid): void
    {
        $this->ensureTable();
        if ($fid <= 0) {
            return;
        }
        $this->db->table($this->table)
            ->set('notice_hit', 'notice_hit + 1', false)
            ->where('notice_fid', $fid)
            ->whereIn('notice_type', self::siteBoardTypesForQuery())
            ->update();
    }

    public function updateSiteBoard(int $fid, array $data): bool
    {
        $this->ensureTable();
        if ($fid <= 0) {
            return false;
        }
        $data['notice_time_update'] = date('Y-m-d H:i:s');

        return $this->db->table($this->table)
            ->where('notice_fid', $fid)
            ->whereIn('notice_type', self::siteBoardTypesForQuery())
            ->update($data);
    }

    public function softDeleteSiteBoard(int $fid): bool
    {
        $this->ensureTable();
        if ($fid <= 0) {
            return false;
        }

        return $this->db->table($this->table)
            ->where('notice_fid', $fid)
            ->whereIn('notice_type', self::siteBoardTypesForQuery())
            ->update([
                'notice_state_delete' => 1,
                'notice_time_update'  => date('Y-m-d H:i:s'),
            ]);
    }

    public function registerNotice($data)
    {
        $this->ensureTable();
        try {
            return $this->insert($data);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteByClient($reqData)
    {
        $where = ' notice_mb_uid = ' . $this->db->escape($reqData['send_uid']) . ' ';

        if ($reqData['notice_id'] > 0) {
            $where .= ' AND notice_fid = ' . $this->db->escape($reqData['notice_id']) . ' ';
        } else {
            if (array_key_exists('notice_type', $reqData)) {
                $where .= ' AND notice_type = ' . $this->db->escape($reqData['notice_type']) . ' ';
            } else {
                $where .= " AND (notice_type = '" . NOTICE_MSG_ALL . "' OR notice_type = '" . NOTICE_MSG . "') ";
            }
        }

        return $this->set('notice_client_delete', STATE_ACTIVE)
            ->where($where, null, false)
            ->update();
    }

    public function readMsg($reqData)
    {
        $where = ' notice_mb_uid = ' . $this->db->escape($reqData['send_uid']) . ' ';
        if ($reqData['notice_id'] > 0) {
            $where .= ' AND notice_fid = ' . $this->db->escape($reqData['notice_id']) . ' ';
        }
        $where .= " AND (notice_type = '" . NOTICE_MSG_ALL . "' OR notice_type = '" . NOTICE_MSG . "') ";

        return $this->set('notice_read_count', STATE_ACTIVE)
            ->where($where, null, false)
            ->update();
    }

    public function unreadMsg($mb_uid)
    {
        $where = " notice_mb_uid = '" . $mb_uid . "' ";
        $where .= " AND (notice_type = '" . NOTICE_MSG_ALL . "' OR notice_type = '" . NOTICE_MSG . "') ";
        $where .= " AND notice_client_delete = '" . STATE_DISABLE . "' ";
        $where .= " AND notice_state_active = '" . STATE_ACTIVE . "' ";
        $where .= " AND notice_read_count = '0' ";

        $data = $this->where($where, null, false)->findAll();

        return count($data);
    }

    public function readCus($reqData)
    {
        $where = ' notice_fid = ' . $this->db->escape($reqData['notice_id']) . ' ';
        $where .= ' AND notice_mb_uid = ' . $this->db->escape($reqData['send_uid']) . ' ';
        $where .= " AND notice_type = '" . NOTICE_CUSTOMER . "' ";

        return $this->set('notice_state_active', STATE_VERIFY)
            ->where($where, null, false)
            ->update();
    }

    public function unreadCus($mb_uid)
    {
        $where = " notice_mb_uid = '" . $mb_uid . "' ";
        $where .= " AND notice_type = '" . NOTICE_CUSTOMER . "' ";
        $where .= " AND notice_client_delete = '" . STATE_DISABLE . "' ";
        $where .= " AND notice_state_active = '" . STATE_ACTIVE . "' ";

        $data = $this->where($where, null, false)->findAll();

        return count($data);
    }

    public function searchBodCount($reqData)
    {
        $this->ensureTable();

        return (int) $this->db->table($this->table)
            ->whereIn('notice_type', self::siteBoardTypesForQuery())
            ->where('notice_state_active', STATE_ACTIVE)
            ->where('notice_state_delete', STATE_DISABLE)
            ->countAllResults();
    }

    public function searchBodList($reqData)
    {
        $this->ensureTable();

        $page  = $reqData['page'];
        $count = $reqData['count'];
        if ($page < 1 || $count < 1) {
            return null;
        }

        $this->whereIn('notice_type', self::siteBoardTypesForQuery())
            ->where('notice_state_active', STATE_ACTIVE)
            ->where('notice_state_delete', STATE_DISABLE);
        if (array_key_exists('popup', $reqData)) {
            $this->where('notice_read_count', $reqData['popup']);
        }

        return $this->orderBy('notice_fid', 'DESC')->findAll($count, $count * ($page - 1));
    }

    public function searchCusCount($reqData)
    {
        $joinTable = 'member';

        $where = "notice_type = '" . NOTICE_CUSTOMER . "' ";
        $where .= "AND notice_client_delete = '" . STATE_DISABLE . "' ";
        if (array_key_exists('send_uid', $reqData)) {
            $where .= 'AND notice_mb_uid = ' . $this->db->escape($reqData['send_uid']) . ' ';
        }
        $data = $this->join($joinTable, $joinTable . '.mb_uid = ' . $this->table . '.notice_mb_uid', 'left')
            ->where($where, null, false)
            ->findAll();

        return count($data);
    }

    public function searchCusList($reqData)
    {
        $getFields = ['notice_fid', 'notice_type', 'notice_title', 'notice_content', 'notice_answer', 'notice_mb_uid',
            'notice_time_create', 'notice_state_active', 'notice_client_delete', 'mb_grade', 'mb_nickname', ];

        $joinTable = 'member';

        $where = "notice_type = '" . NOTICE_CUSTOMER . "' ";
        $where .= "AND notice_client_delete = '" . STATE_DISABLE . "' ";
        if (array_key_exists('send_uid', $reqData)) {
            $where .= 'AND notice_mb_uid = ' . $this->db->escape($reqData['send_uid']) . ' ';
        }
        $page  = $reqData['page'];
        $count = $reqData['count'];
        if ($page < 1 || $count < 1) {
            return null;
        }

        return $this->select($getFields)
            ->join($joinTable, $joinTable . '.mb_uid = ' . $this->table . '.notice_mb_uid', 'left')
            ->where($where, null, false)
            ->orderBy('notice_fid', 'DESC')
            ->findAll($count, $count * ($page - 1));
    }

    public function searchMsgCount($reqData)
    {
        $joinTable = 'member';

        $where = " (notice_type = '" . NOTICE_MSG_ALL . "' OR notice_type = '" . NOTICE_MSG . "') ";
        $where .= "AND notice_client_delete = '" . STATE_DISABLE . "' ";
        $where .= "AND notice_state_active = '" . STATE_ACTIVE . "' ";
        if (array_key_exists('send_uid', $reqData)) {
            $where .= 'AND notice_mb_uid = ' . $this->db->escape($reqData['send_uid']) . ' ';
        }
        $data = $this->join($joinTable, $joinTable . '.mb_uid = ' . $this->table . '.notice_mb_uid', 'left')
            ->where($where, null, false)
            ->findAll();

        return count($data);
    }

    public function searchMsgList($reqData)
    {
        $joinTable = 'member';

        $where = " (notice_type = '" . NOTICE_MSG_ALL . "' OR notice_type = '" . NOTICE_MSG . "') ";
        $where .= "AND notice_client_delete = '" . STATE_DISABLE . "' ";
        $where .= "AND notice_state_active = '" . STATE_ACTIVE . "' ";
        if (array_key_exists('send_uid', $reqData)) {
            $where .= 'AND notice_mb_uid = ' . $this->db->escape($reqData['send_uid']) . ' ';
        }
        $page  = $reqData['page'];
        $count = $reqData['count'];
        if ($page < 1 || $count < 1) {
            return null;
        }

        return $this->join($joinTable, $joinTable . '.mb_uid = ' . $this->table . '.notice_mb_uid', 'left')
            ->where($where, null, false)
            ->orderBy('notice_fid', 'DESC')
            ->findAll($count, $count * ($page - 1));
    }
}
