<?php

namespace App\Models;

use CodeIgniter\Model;

class Attendance_Model extends Model
{
    protected $table            = 'attendance_log';
    protected $primaryKey       = 'att_id';
    protected $returnType       = 'object';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'mb_fid', 'attend_ymd', 'select_num', 'result_num', 'is_win', 'streak_days', 'comment', 'created_at',
    ];

    public function getTable(): string
    {
        return $this->table;
    }

    public function ensureTable(): void
    {
        $db = \Config\Database::connect();
        $t = $this->getTable();
        $db->query("CREATE TABLE IF NOT EXISTS `{$t}` (
            `att_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `mb_fid` INT UNSIGNED NOT NULL,
            `attend_ymd` DATE NOT NULL,
            `select_num` TINYINT UNSIGNED NOT NULL,
            `result_num` TINYINT UNSIGNED NOT NULL,
            `is_win` TINYINT(1) NOT NULL DEFAULT 0,
            `streak_days` INT UNSIGNED NOT NULL DEFAULT 1,
            `comment` VARCHAR(500) NOT NULL DEFAULT '',
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`att_id`),
            UNIQUE KEY `uk_mb_date` (`mb_fid`, `attend_ymd`),
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function hasAttendedOn(int $mbFid, string $ymd): bool
    {
        $this->ensureTable();
        if ($mbFid <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return false;
        }

        return $this->where('mb_fid', $mbFid)->where('attend_ymd', $ymd)->first() !== null;
    }

    /**
     * @return object|null 이전 날짜(연속 출석 계산용)
     */
    public function getRowForDate(int $mbFid, string $ymd): ?object
    {
        $this->ensureTable();

        return $this->where('mb_fid', $mbFid)->where('attend_ymd', $ymd)->first();
    }

    public function countAllRows(): int
    {
        $this->ensureTable();

        return (int) $this->builder()->countAllResults();
    }

    /**
     * @return object[] att_id, mb_fid, attend_ymd, select_num, result_num, is_win, streak_days, comment, created_at, mb_nickname, mb_color
     */
    public function getListPageJoined(int $page, int $perPage): array
    {
        $this->ensureTable();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $off = ($page - 1) * $perPage;

        $db = \Config\Database::connect();
        $t = $this->getTable();
        $sql = "SELECT a.`att_id`, a.`mb_fid`, a.`attend_ymd`, a.`select_num`, a.`result_num`, a.`is_win`, a.`streak_days`, a.`comment`, a.`created_at`,
                m.`mb_nickname`, m.`mb_color`
            FROM `{$t}` a
            LEFT JOIN `member` m ON m.`mb_fid` = a.`mb_fid`
            ORDER BY a.`att_id` DESC
            LIMIT " . (int) $perPage . ' OFFSET ' . (int) $off;

        $q = $db->query($sql);
        $rows = $q ? $q->getResult() : [];

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<string, true> 'Y-m-d' => true
     */
    public function getAttendedYmdSetForMonth(int $mbFid, int $year, int $month): array
    {
        $this->ensureTable();
        if ($mbFid <= 0 || $month < 1 || $month > 12) {
            return [];
        }
        $start = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = (int) date('t', strtotime($start));
        $end = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);

        $rows = $this->select('attend_ymd')
            ->where('mb_fid', $mbFid)
            ->where('attend_ymd >=', $start)
            ->where('attend_ymd <=', $end)
            ->findAll();

        $out = [];
        foreach ($rows as $r) {
            $d = (string) ($r->attend_ymd ?? '');
            if ($d !== '') {
                $out[$d] = true;
            }
        }

        return $out;
    }

    /**
     * 연속 출석일 수 (해당 출석일 기준).
     */
    public function computeStreakDays(int $mbFid, string $attendYmd): int
    {
        $this->ensureTable();
        $prev = date('Y-m-d', strtotime($attendYmd . ' -1 day'));
        $prevRow = $this->getRowForDate($mbFid, $prev);

        return $prevRow ? (int) ($prevRow->streak_days ?? 0) + 1 : 1;
    }

    public function insertAttendance(
        int $mbFid,
        string $attendYmd,
        int $selectNum,
        int $resultNum,
        bool $isWin,
        string $comment
    ): ?int {
        $this->ensureTable();
        if ($mbFid <= 0) {
            return null;
        }
        $streak = $this->computeStreakDays($mbFid, $attendYmd);
        $now = date('Y-m-d H:i:s');
        $ok = $this->insert([
            'mb_fid'       => $mbFid,
            'attend_ymd'   => $attendYmd,
            'select_num'   => $selectNum,
            'result_num'   => $resultNum,
            'is_win'       => $isWin ? 1 : 0,
            'streak_days'  => $streak,
            'comment'      => $comment,
            'created_at'   => $now,
        ]);

        if (! $ok) {
            return null;
        }

        $id = (int) $this->getInsertID();

        return $id > 0 ? $id : null;
    }

    /**
     * 출석 코멘트 풀 (선배님 페이지 문구 샘플)
     *
     * @return array<int, string>
     */
    public static function commentPool(): array
    {
        return [
            1  => '역경은 어떤 사람은 쓰러지게 하고, 또 어떤 사람은 기록을 세우게 한다.',
            2  => '자신과 타협하지 말라. 당신은 당신이 가진 전부이다.',
            3  => '지금 안 한다면 언제 하겠는가?',
            4  => '내면의 침묵 속으로 들어가 인생의 모든 것에 목적이 있다는 것을 배워라.',
            5  => '하든지 말든지 둘 중의 하나다. 시험 삼아서 해보는 일은 없다.',
            6  => '시간을 보내는 것도 습관이다.',
            7  => '꿈과 현실의 차이를 두려워하지 말아라. 꿈을 꿀 수 있다면 실현할 수 있다.',
            8  => '나는 소녀시절부터 키워온 꿈이 있다. 나는 세상을 지배하고 싶다.',
            9  => '두려움을 느껴라, 그리고 어떻게 해서든 하라.',
            10 => '우리가 알고 있는 유일한 진화는 모호함에서 분명함으로 진화하는 것이다.',
            11 => '실패는 불가능하다.',
            12 => '수정이 불가능한 계획은 나쁜 계획이다.',
            13 => '나는 소녀시절부터 키워온 꿈이 있다. 나는 세상을 지배하고 싶다.',
            14 => '자신과 타협하지 말라. 당신은 당신이 가진 전부이다.',
            15 => '나는 소녀시절부터 키워온 꿈이 있다. 나는 세상을 지배하고 싶다.',
        ];
    }
}
