<?php

namespace App\Models;

use App\Libraries\LionDrawAuditLogger;
use CodeIgniter\Model;

/**
 * 추첨 결과 (테이블: draw_results)
 * - 5분마다 1회 추첨, 일반볼 1~28 중 5개(중복 없음), 파워볼 0~9 중 1개
 * - CSPRNG(random_int) + Fisher-Yates 셔플로 추측 불가능하게 구현
 *
 * 컬럼 의미:
 * - ball1~ball5   : 일반볼 5개를 draw_results에 넣을 때 **추첨(셔플)에서 나온 순서 그대로** 저장. 오름차순 정렬하지 않음.
 * - drawn_at     : 추첨 시각. 해당 회차가 “몇 시 몇 분”에 진행된 추첨인지 나타내는 공식 시각.
 *                  반드시 5분 단위(XX:00, XX:05, XX:10, … XX:55)만 저장.
 * - daily_round  : KST 게임일 기준 일회차(1~288). drawn_at 시각으로만 결정(00:00=288, 00:05=1, … 23:55=287). 게임일 = D 00:05 ~ (D+1) 00:00.
 * - created_at   : 레코드가 DB에 실제로 INSERT된 시각(시스템 기록용). 테이블 기본값 CURRENT_TIMESTAMP.
 */
class PowerballDraw_Model extends Model
{
    protected $table         = 'draw_results';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'round', 'daily_round', 'ball1', 'ball2', 'ball3', 'ball4', 'ball5',
        'powerball', 'ball_sum', 'drawn_at', 'created_at'
    ];
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    /** 추첨 주기(초): 5분 = 300초 */
    public const DRAW_INTERVAL = 300;

    /** 일반볼 범위 1~28, 5개 / 파워볼 범위 0~9, 1개 */
    public const BALL_MIN = 1;
    public const BALL_MAX = 28;
    public const BALL_COUNT = 5;
    public const POWERBALL_MIN = 0;
    public const POWERBALL_MAX = 9;

    /**
     * KST 기준 게임일 키(Y-m-d). 00:00:00 추첨은 전날 게임일에 속함(그날 1회는 00:05).
     */
    public static function gameDayKeyKstFromDrawnAt(string $drawnAt): string
    {
        try {
            $dt = new \DateTimeImmutable($drawnAt, new \DateTimeZone('Asia/Seoul'));
        } catch (\Throwable $e) {
            return preg_match('/^(\d{4}-\d{2}-\d{2})/', $drawnAt, $m) ? $m[1] : '';
        }
        if ($dt->format('H:i:s') === '00:00:00') {
            return $dt->modify('-1 day')->format('Y-m-d');
        }

        return $dt->format('Y-m-d');
    }

    /**
     * dayLog·분석에서 선택한 게임일 D에 해당하는 drawn_at 구간 [D 00:05:00, (D+1) 00:00:00] (끝 포함).
     *
     * @return array{0:string,1:string} [$from, $to]
     */
    public static function gameDayWindowFromPickerDate(string $ymd): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return ['', ''];
        }
        $from = $ymd . ' 00:05:00';
        $to   = (new \DateTimeImmutable($ymd))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

        return [$from, $to];
    }

    /**
     * Unix 시각을 KST로 변환한 뒤, 해당 시각이 속한 5분 슬롯 시작(XX:00, XX:05, …)을 drawn_at 문자열로 반환.
     * (date() 기본 타임존에 의존하지 않음)
     */
    public static function kstDrawnAtFromUnixTimestamp(int $unixTs): string
    {
        $tz = new \DateTimeZone('Asia/Seoul');
        $dt = (new \DateTimeImmutable('@' . $unixTs))->setTimezone($tz);
        $totalMins = (int) $dt->format('H') * 60 + (int) $dt->format('i');
        $slotMins  = (int) floor($totalMins / 5) * 5;

        return $dt->setTime(intdiv($slotMins, 60), $slotMins % 60, 0)->format('Y-m-d H:i:s');
    }

    /**
     * KST drawn_at(5분 정렬)만으로 일회차를 결정. 게임일 규칙과 동일:
     * - 00:00:00 은 전날 게임일의 마지막 회차(288).
     * - 그 외에는 floor(자정부터 분)/5 → 00:05=1 … 23:55=287.
     */
    public static function dailyRoundFromDrawnAtKst(string $drawnAt): int
    {
        try {
            $dt = new \DateTimeImmutable($drawnAt, new \DateTimeZone('Asia/Seoul'));
        } catch (\Throwable $e) {
            return 1;
        }
        $h = (int) $dt->format('H');
        $i = (int) $dt->format('i');
        $s = (int) $dt->format('s');
        $nSumMinutes = $h * 60 + $i;
        if ($nSumMinutes === 0 && $s === 0) {
            return 288;
        }

        return (int) floor($nSumMinutes / 5);
    }

    /**
     * 기간 검색: 시작일 게임일 첫 추첨 ~ 종료일 게임일의 마지막(다음날 00:00)까지.
     *
     * @return array{0:string,1:string}
     */
    public static function gameDayWindowFromPickerRange(string $startYmd, string $endYmd): array
    {
        [$from] = self::gameDayWindowFromPickerDate($startYmd);
        $to     = (new \DateTimeImmutable($endYmd))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

        return [$from, $to];
    }

    /**
     * 최신 추첨 1건 조회
     */
    public function getLatest(): ?object
    {
        return $this->orderBy('round', 'DESC')->first();
    }

    /**
     * 최신 추첨 N건 (회차 내림차순)
     *
     * @return list<object>
     */
    public function getRecent(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));

        return $this->orderBy('round', 'DESC')->findAll($limit);
    }

    /**
     * 회차로 1건 조회
     */
    public function getByRound(int $round): ?object
    {
        return $this->where('round', $round)->first();
    }

    /**
     * drawn_at(5분 슬롯)으로 1건 조회 — 동일 슬롯 중복 방지용.
     * 과거 중복 행이 남아 있으면 id가 가장 작은 행을 반환(고정).
     */
    public function getByDrawnAt(string $drawnAt): ?object
    {
        return $this->where('drawn_at', $drawnAt)->orderBy('id', 'ASC')->first();
    }

    /** MySQL GET_LOCK 이름(최대 64자). Lion 큐 upsert와 추첨 직렬화에 동일 문자열 사용. */
    public static function advisoryLockNameForDrawnAt(string $drawnAt): string
    {
        $n = 'pb_' . md5($drawnAt);

        return strlen($n) > 64 ? substr($n, 0, 64) : $n;
    }

    /**
     * Fisher-Yates 셔플 (CSPRNG 사용으로 추측 불가능)
     * random_int()는 암호학적으로 안전한 난수.
     */
    private function secureShuffle(array $items): array
    {
        $n = count($items);
        for ($i = $n - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $t = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $t;
        }
        return $items;
    }

    /**
     * 일반볼 5개 + 파워볼 1개 추첨 (고급 알고리즘)
     * - 1~28 풀을 Fisher-Yates로 셔플 후 앞 5개 사용 (중복 불가)
     * - 셔플을 3회 반복해 예측 불가능성 강화 (Triple Mix)
     * - ball1~ball5는 셔플에서 뽑힌 순서 그대로 저장(정렬하지 않음) — 화면에 낮은 번호부터 나열되는 착시 방지
     * - 파워볼은 0~9에서 random_int로 1개
     */
    public function performDraw(): array
    {
        $balls = range(self::BALL_MIN, self::BALL_MAX);

        for ($mix = 0; $mix < 3; $mix++) {
            $balls = $this->secureShuffle($balls);
        }

        $selected = array_slice($balls, 0, self::BALL_COUNT);

        $powerball = random_int(self::POWERBALL_MIN, self::POWERBALL_MAX);
        $ballSum   = array_sum($selected);

        return [
            'ball1'     => $selected[0],
            'ball2'     => $selected[1],
            'ball3'     => $selected[2],
            'ball4'     => $selected[3],
            'ball5'     => $selected[4],
            'powerball' => $powerball,
            'ball_sum'  => $ballSum,
        ];
    }

    /**
     * 일반볼만 기존과 동일하게 셔플하고, 파워볼은 $allowedPb 중에서만 uniform random.
     *
     * @param list<int> $allowedPb 0~9 부분집합, 비어 있으면 전체 범위
     */
    public function performDrawWithPowerballPool(array $allowedPb = []): array
    {
        $balls = range(self::BALL_MIN, self::BALL_MAX);
        for ($mix = 0; $mix < 3; $mix++) {
            $balls = $this->secureShuffle($balls);
        }
        $selected = array_slice($balls, 0, self::BALL_COUNT);

        $pool = $allowedPb;
        if ($pool === []) {
            $pool = range(self::POWERBALL_MIN, self::POWERBALL_MAX);
        }
        $pool = array_values(array_unique(array_map('intval', $pool)));
        sort($pool);
        if ($pool === []) {
            $pool = range(self::POWERBALL_MIN, self::POWERBALL_MAX);
        }
        $idx       = random_int(0, count($pool) - 1);
        $powerball = $pool[$idx];
        $ballSum   = array_sum($selected);

        return [
            'ball1'     => $selected[0],
            'ball2'     => $selected[1],
            'ball3'     => $selected[2],
            'ball4'     => $selected[3],
            'ball5'     => $selected[4],
            'powerball' => $powerball,
            'ball_sum'  => $ballSum,
        ];
    }

    /**
     * Lion 큐 규칙(null/빈 = 무제한). 파워볼은 조건에 맞는 값만 uniform; 일반볼 합 홀짝·언오버는 거절 샘플링.
     *
     * 규칙 키: pb_parity odd|even, pb_ou under(0~4)|over(5~9),
     * nb_sum_parity odd|even, nb_sum_ou under(합≤72)|over(합≥73)
     *
     * @param array<string, string|null> $rules
     */
    public function performDrawWithRules(?array $rules): array
    {
        if ($rules === null || $rules === []) {
            LionDrawAuditLogger::write('perform_path', [
                'path'  => 'unconstrained_random',
                'rules' => null,
            ]);

            return $this->performDraw();
        }

        $allowedPb = $this->filterPowerballsByRules($rules);
        if ($allowedPb === []) {
            log_message('warning', 'PowerballDraw_Model: impossible pb rules, fallback performDraw');
            LionDrawAuditLogger::write('perform_path', [
                'path'        => 'fallback_impossible_pb_rules',
                'rules'       => $rules,
                'allowed_pb'  => [],
            ]);

            return $this->performDraw();
        }

        $needNb = ! empty($rules['nb_sum_parity']) || ! empty($rules['nb_sum_ou']);
        if (! $needNb) {
            LionDrawAuditLogger::write('perform_path', [
                'path'       => 'pb_pool_only',
                'rules'      => $rules,
                'allowed_pb' => $allowedPb,
            ]);

            return $this->performDrawWithPowerballPool($allowedPb);
        }

        $maxTry = 80000;
        LionDrawAuditLogger::write('perform_path', [
            'path'       => 'nb_rejection_sampling',
            'rules'      => $rules,
            'allowed_pb' => $allowedPb,
            'max_try'    => $maxTry,
        ]);
        for ($t = 0; $t < $maxTry; $t++) {
            $d = $this->performDrawWithPowerballPool($allowedPb);
            if ($this->drawMatchesNbSumRules($d, $rules)) {
                if ($t > 12000) {
                    LionDrawAuditLogger::write('perform_path', [
                        'path'           => 'nb_sample_many_tries',
                        'tries'          => $t + 1,
                        'rules'          => $rules,
                        'matched_sample' => $d['ball_sum'] . '|pb:' . $d['powerball'],
                    ]);
                }

                return $d;
            }
        }
        log_message('warning', 'PowerballDraw_Model: performDrawWithRules nb fallback after ' . $maxTry);
        LionDrawAuditLogger::write('perform_path', [
            'path'   => 'nb_fallback_after_try_cap_uncheck_nb_sum',
            'rules'  => $rules,
            'warn'   => 'ball_sum_constraints_may_fail',
            'cap'    => $maxTry,
        ]);

        return $this->performDrawWithPowerballPool($allowedPb);
    }

    /**
     * @param array<string, string|null> $rules
     * @return list<int>
     */
    private function filterPowerballsByRules(array $rules): array
    {
        $candidates = range(self::POWERBALL_MIN, self::POWERBALL_MAX);
        $parity = $rules['pb_parity'] ?? null;
        if ($parity === 'odd') {
            $candidates = array_values(array_filter($candidates, static fn (int $x): bool => $x % 2 === 1));
        } elseif ($parity === 'even') {
            $candidates = array_values(array_filter($candidates, static fn (int $x): bool => $x % 2 === 0));
        }
        $ou = $rules['pb_ou'] ?? null;
        if ($ou === 'under') {
            $candidates = array_values(array_filter($candidates, static fn (int $x): bool => $x <= 4));
        } elseif ($ou === 'over') {
            $candidates = array_values(array_filter($candidates, static fn (int $x): bool => $x >= 5));
        }

        return $candidates;
    }

    /**
     * @param array<string, mixed>       $d
     * @param array<string, string|null> $rules
     */
    private function drawMatchesNbSumRules(array $d, array $rules): bool
    {
        $sum = (int) $d['ball_sum'];
        $par = $rules['nb_sum_parity'] ?? null;
        if ($par === 'odd' && $sum % 2 === 0) {
            return false;
        }
        if ($par === 'even' && $sum % 2 === 1) {
            return false;
        }
        $ou = $rules['nb_sum_ou'] ?? null;
        if ($ou === 'under' && $sum > 72) {
            return false;
        }
        if ($ou === 'over' && $sum <= 72) {
            return false;
        }

        return true;
    }

    /**
     * 추첨 결과가 라이온 규칙을 만족하는지 검사 (로그·사후 검증용).
     *
     * @param array<string, mixed>              $draw  performDraw 형태
     * @param array<string, string>|null        $rules consume 한 규칙 (null 이면 미적용)
     * @return array{rules_applied:bool, matched:bool, checks:array<string,array<string,mixed>>}
     */
    public static function evaluateLionRulesCompliance(array $draw, ?array $rules): array
    {
        if ($rules === null || $rules === []) {
            return ['rules_applied' => false, 'matched' => true, 'checks' => []];
        }

        $pb  = (int) $draw['powerball'];
        $sum = (int) $draw['ball_sum'];
        $checks  = [];
        $matched = true;

        $pp = $rules['pb_parity'] ?? null;
        if ($pp === 'odd' || $pp === 'even') {
            $wantOdd = ($pp === 'odd');
            $isOdd   = ($pb % 2 === 1);
            $pass    = ($wantOdd === $isOdd);
            $checks['pb_parity'] = ['requested' => $pp, 'powerball' => $pb, 'pass' => $pass];
            $matched = $matched && $pass;
        }

        $pou = $rules['pb_ou'] ?? null;
        if ($pou === 'under' || $pou === 'over') {
            $pass    = ($pou === 'under') ? ($pb <= 4) : ($pb >= 5);
            $checks['pb_ou'] = ['requested' => $pou, 'powerball' => $pb, 'pass' => $pass];
            $matched = $matched && $pass;
        }

        $nsp = $rules['nb_sum_parity'] ?? null;
        if ($nsp === 'odd' || $nsp === 'even') {
            $wantOdd = ($nsp === 'odd');
            $isOdd   = ($sum % 2 === 1);
            $pass    = ($wantOdd === $isOdd);
            $checks['nb_sum_parity'] = ['requested' => $nsp, 'ball_sum' => $sum, 'pass' => $pass];
            $matched = $matched && $pass;
        }

        $nsu = $rules['nb_sum_ou'] ?? null;
        if ($nsu === 'under' || $nsu === 'over') {
            $pass = ($nsu === 'under') ? ($sum <= 72) : ($sum > 72);
            $checks['nb_sum_ou'] = ['requested' => $nsu, 'ball_sum' => $sum, 'pass' => $pass];
            $matched = $matched && $pass;
        }

        return ['rules_applied' => true, 'matched' => $matched, 'checks' => $checks];
    }

    /**
     * 다음 회차 번호 계산 (마지막 round + 1, 없으면 1)
     */
    private function getNextRound(): int
    {
        $row = $this->selectMax('round')->first();
        return $row && isset($row->round) ? (int) $row->round + 1 : 1;
    }

    /**
     * daily_round 컬럼이 없으면 추가하고, daily_round=0 인 행은 drawn_at(KST) 기준으로 백필.
     * 기존에 순번으로만 맞춰 둔 값을 시각 기준으로 다시 쓰려면 UPDATE로 daily_round=0 처리 후 호출.
     */
    public function ensureDailyRoundColumn(): void
    {
        $db    = $this->db;
        $table = $db->prefixTable($this->table);
        if (! $db->fieldExists('daily_round', $table)) {
            try {
                $db->query("ALTER TABLE `{$table}` ADD COLUMN `daily_round` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'KST 당일 일회차(1~)' AFTER `round`");
            } catch (\Throwable $e) {
                // Duplicate column: 경합. 그 외: 공유호스팅 DDL 금지·권한 없음 등 — ajax 열람이 500 나지 않도록 throw 하지 않음(마이그레이션으로 컬럼 추가 필요).
                if (stripos($e->getMessage(), 'Duplicate column') === false) {
                    log_message('critical', 'PowerballDraw_Model ensureDailyRoundColumn ADD daily_round: ' . $e->getMessage());
                }
            }
            if ($db->fieldExists('daily_round', $table)) {
                $this->backfillDailyRound(500);
            }
        } else {
            // 컬럼은 있는데 0인 행만 소량씩 백필 (운영 대용량에서 매 요청 전체 findAll 금지 → ajax 예외 방지)
            $this->backfillDailyRound(500);
        }
        $this->ensureUniqueDrawnAtConstraint();
        // 게임일(00:05 기준) 규칙 변경 후 기존 일회차를 다시 맞추려면 DB에서 한 번 실행:
        // UPDATE `draw_results` SET `daily_round`=0; (프리픽스 있으면 테이블명 조정) 이후 ensure 호출 시 백필.
    }

    /**
     * drawn_at에 UNIQUE 인덱스(이상적). 기존에 같은 drawn_at이 여러 줄이면 ALTER 실패 → 로그만 남김.
     * 중복 데이터를 그대로 둔 채로는 UNIQUE를 붙일 수 없고, getOrGenerate()의 GET_LOCK으로 신규 중복만 막는다.
     */
    public function ensureUniqueDrawnAtConstraint(): void
    {
        $table = $this->db->prefixTable($this->table);
        try {
            $this->db->query("ALTER TABLE `{$table}` ADD UNIQUE KEY `uk_drawn_at` (`drawn_at`)");
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate key name') !== false || stripos($msg, 'already exists') !== false) {
                return;
            }
            log_message(
                'warning',
                'PowerballDraw_Model: uk_drawn_at 추가 실패(동일 drawn_at 중복 행 삭제·병합 후 재시도): ' . $msg
            );
        }
    }

    /**
     * daily_round=0 인 행에 대해 drawn_at(KST) 시각 기준 일회차로 백필.
     * 운영에서 draw_results가 크면 전체 스캔 시 요청 타임아웃·메모리 초과 → 한 요청당 최대 $maxRows 행만 처리.
     */
    public function backfillDailyRound(int $maxRows = 500): void
    {
        if ($maxRows < 1) {
            return;
        }
        $rows = $this->where('daily_round', 0)
            ->orderBy('id', 'ASC')
            ->findAll($maxRows);
        foreach ($rows as $draw) {
            $at = $draw->drawn_at ?? '';
            if ($at === '') {
                continue;
            }
            $id = (int) ($draw->id ?? 0);
            if ($id < 1) {
                continue;
            }
            $want = self::dailyRoundFromDrawnAtKst((string) $at);
            $cur  = (int) ($draw->daily_round ?? 0);
            if ($cur !== $want) {
                $this->update($id, ['daily_round' => $want]);
            }
        }
    }

    /**
     * 현재 시각이 속한 5분 슬롯(XX:00, XX:05, XX:10, … XX:55) 기준으로,
     * 해당 슬롯에 추첨이 없으면 새로 추첨, 있으면 기존 결과 반환.
     * @param int|null $currentTime unix timestamp (null이면 time() 사용)
     * @return object 추첨 결과 (round, ball1~5, powerball, ball_sum, drawn_at 등)
     */
    public function getOrGenerate(?int $currentTime = null): object
    {
        $this->ensureDailyRoundColumn();

        $currentTime = $currentTime ?? time();
        $drawnAt     = self::kstDrawnAtFromUnixTimestamp($currentTime);

        $existing = $this->getByDrawnAt($drawnAt);
        if ($existing !== null) {
            return $existing;
        }

        $lockName = self::advisoryLockNameForDrawnAt($drawnAt);
        $lockHeld = false;
        try {
            $g = $this->db->query('SELECT GET_LOCK(?, 30) AS g', [$lockName])->getRow();
            $lockHeld = $g && (int) $g->g === 1;
            if (! $lockHeld) {
                LionDrawAuditLogger::write('draw_lock_miss', [
                    'drawn_at' => $drawnAt,
                    'unix'     => $currentTime,
                ]);
                $wait = $this->getByDrawnAt($drawnAt);
                if ($wait !== null) {
                    return $wait;
                }
                throw new \RuntimeException('PowerballDraw_Model: GET_LOCK 실패/타임아웃 drawn_at=' . $drawnAt);
            }

            $existing = $this->getByDrawnAt($drawnAt);
            if ($existing !== null) {
                return $existing;
            }

            $pendingModel = new LionPendingDraw_Model();
            $pendingModel->ensureTable();
            $rules = $pendingModel->consumeRulesForDrawnAt($drawnAt);

            $dailyRound = self::dailyRoundFromDrawnAtKst($drawnAt);
            $nextRound  = $this->getNextRound();

            LionDrawAuditLogger::write('draw_generate_start', [
                'drawn_at'       => $drawnAt,
                'unix_used'      => $currentTime,
                'daily_round'    => $dailyRound,
                'assigned_round' => $nextRound,
                'queued_rules'   => $rules,
            ]);

            $draw = $this->performDrawWithRules($rules);

            try {
                // draw_results: ball1~ball5는 performDraw()가 반환한 추첨 순서 그대로(정렬 없음)
                $this->insert([
                    'round'       => $nextRound,
                    'daily_round' => $dailyRound,
                    'ball1'       => $draw['ball1'],
                    'ball2'       => $draw['ball2'],
                    'ball3'       => $draw['ball3'],
                    'ball4'       => $draw['ball4'],
                    'ball5'       => $draw['ball5'],
                    'powerball'   => $draw['powerball'],
                    'ball_sum'    => $draw['ball_sum'],
                    'drawn_at'    => $drawnAt,
                ]);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'Duplicate') !== false || stripos($msg, 'uk_drawn_at') !== false) {
                    $dup = $this->getByDrawnAt($drawnAt);
                    if ($dup !== null) {
                        LionDrawAuditLogger::write('draw_insert_race_duplicate', [
                            'drawn_at'     => $drawnAt,
                            'unix_used'    => $currentTime,
                            'used_round'   => (int) ($dup->round ?? 0),
                            'planned_round'=> $nextRound,
                            'note'         => 'another_worker_inserted_first',
                        ]);

                        return $dup;
                    }
                }
                LionDrawAuditLogger::write('draw_insert_exception', [
                    'drawn_at' => $drawnAt,
                    'message'  => substr($msg, 0, 500),
                ]);
                throw $e;
            }

            $id = $this->getInsertID();

            $compliance = self::evaluateLionRulesCompliance($draw, $rules);
            LionDrawAuditLogger::write('draw_inserted', [
                'drawn_at'           => $drawnAt,
                'unix_used'          => $currentTime,
                'round'              => $nextRound,
                'id'                 => $id,
                'daily_round'        => $dailyRound,
                'balls'              => [
                    $draw['ball1'], $draw['ball2'], $draw['ball3'], $draw['ball4'], $draw['ball5'],
                ],
                'powerball'          => $draw['powerball'],
                'ball_sum'           => $draw['ball_sum'],
                'queued_rules'       => $rules,
                'rules_applied_flag' => $compliance['rules_applied'],
                'rules_all_pass'    => $compliance['matched'],
                'rule_checks'       => $compliance['checks'],
            ]);

            if ($compliance['rules_applied'] && ! $compliance['matched']) {
                $payload = json_encode([
                    'drawn_at'     => $drawnAt,
                    'round'        => $nextRound,
                    'draw'         => $draw,
                    'rules'        => $rules,
                    'rule_checks'  => $compliance['checks'],
                ], JSON_UNESCAPED_UNICODE);
                log_message('critical', 'lion_draw_audit RULE_MISMATCH ' . $payload);
                LionDrawAuditLogger::write('RULE_MISMATCH', [
                    'drawn_at' => $drawnAt,
                    'round'    => $nextRound,
                    'draw'     => $draw,
                    'rules'    => $rules,
                    'checks'   => $compliance['checks'],
                ]);
            }

            return (object) array_merge(
                [
                    'id' => $id, 'round' => $nextRound, 'drawn_at' => $drawnAt,
                    'daily_round' => $dailyRound,
                ],
                $draw
            );
        } finally {
            if ($lockHeld) {
                $this->db->query('SELECT RELEASE_LOCK(?) AS r', [$lockName]);
            }
        }
    }

    /**
     * API/뷰용 공통 포맷 (기존 draw 형식 호환: number 문자열, round, powerball, numberSum)
     */
    public function formatForApi(object $draw): array
    {
        $n = [
            (string) $draw->ball1,
            (string) $draw->ball2,
            (string) $draw->ball3,
            (string) $draw->ball4,
            (string) $draw->ball5,
        ];
        $number = sprintf(
            '%02d%02d%02d%02d%02d',
            (int) $draw->ball1,
            (int) $draw->ball2,
            (int) $draw->ball3,
            (int) $draw->ball4,
            (int) $draw->ball5
        );
        return [
            'round'       => (int) ($draw->round ?? 0),
            'daily_round' => (int) ($draw->daily_round ?? 0),
            'number'      => $number,
            'powerball'   => (int) ($draw->powerball ?? 0),
            'numberSum'   => (int) ($draw->ball_sum ?? 0),
            'drawn_at'    => $draw->drawn_at ?? null,
        ];
    }

    /**
     * KST 기준 해당 날짜의 추첨을 round 오름차순으로 매겨 누적 round → 당일 일회차(1부터) 맵.
     */
    public static function dailyRoundMapForDate(self $model, string $ymd): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return [];
        }
        $model->ensureDailyRoundColumn();
        [$from, $to] = self::gameDayWindowFromPickerDate($ymd);
        if ($from === '') {
            return [];
        }
        $dayRows = $model->where('drawn_at >=', $from)
            ->where('drawn_at <=', $to)
            ->orderBy('round', 'ASC')
            ->findAll();
        $map  = [];
        $rank = 0;
        foreach ($dayRows as $dr) {
            $rank++;
            $r = (int) ($dr->round ?? 0);
            $d = (int) ($dr->daily_round ?? 0);
            $map[$r] = $d > 0 ? $d : $rank;
        }

        return $map;
    }

    /**
     * 여러 날짜가 섞인 draw 목록에 대해 일회차 맵 (누적 round → 일회차).
     */
    public static function dailyRoundMapForDraws(self $model, array $draws): array
    {
        $dates = [];
        foreach ($draws as $d) {
            $at = $d->drawn_at ?? '';
            if ($at === '') {
                continue;
            }
            $key = self::gameDayKeyKstFromDrawnAt((string) $at);
            if ($key !== '') {
                $dates[$key] = true;
            }
        }
        $map = [];
        foreach (array_keys($dates) as $ymd) {
            $map = array_merge($map, self::dailyRoundMapForDate($model, $ymd));
        }

        return $map;
    }

    /**
     * 숫자합 대/중/소 → bepick `btype` 한 글자 (formatForDayLogRow 의 numberPeriod·ajax 집계와 동일)
     * 소(15~64)=B, 중(65~80)=M, 대(81~130)=F
     */
    public static function bpkBtypeFromBallSum(int $sum): string
    {
        if ($sum <= 64) {
            return 'B';
        }
        if ($sum <= 80) {
            return 'M';
        }

        return 'F';
    }

    /**
     * 파워볼 구간 A~D → bepick `ptype` (formatForDayLogRow 의 powerballPeriod 와 동일)
     */
    public static function bpkPtypeFromPowerball(int $pb): string
    {
        if ($pb <= 2) {
            return 'A';
        }
        if ($pb <= 4) {
            return 'B';
        }
        if ($pb <= 6) {
            return 'C';
        }

        return 'D';
    }

    /**
     * bepick fd 슬롯용: 일반볼 하나의 홀짝 → "1"|"2" (회차별표는 합 단위만 보여 주지만 reground 해시용으로 각 볼 홀짝 사용)
     */
    public static function bpkFdDigitFromBall(int $ball): string
    {
        return ($ball % 2 === 1) ? '1' : '2';
    }

    /**
     * 회차별 분석 데이터 한 행 포맷 (dayLog tmpl_dayLog용)
     * 파워볼 구간 A(0~2) B(3~4) C(5~6) D(7~9), 숫자합 구간 A~F, 대/중/소
     *
     * @param int|null $dailyRound 당일 일회차(1~). null 이면 누적 round와 동일하게 둠(하위 호환)
     */
    public static function formatForDayLogRow(object $draw, int $rowIndex, ?int $dailyRound = null): array
    {
        $pb   = (int) ($draw->powerball ?? 0);
        $sum  = (int) ($draw->ball_sum ?? 0);
        $time = $draw->drawn_at ? date('H:i', strtotime($draw->drawn_at)) : '';

        if ($pb <= 2) {
            $powerballPeriod = 'A (0~2)';
        } elseif ($pb <= 4) {
            $powerballPeriod = 'B (3~4)';
        } elseif ($pb <= 6) {
            $powerballPeriod = 'C (5~6)';
        } else {
            $powerballPeriod = 'D (7~9)';
        }

        if ($sum <= 35) {
            $numberSumPeriod = 'A (15~35)';
        } elseif ($sum <= 49) {
            $numberSumPeriod = 'B (36~49)';
        } elseif ($sum <= 57) {
            $numberSumPeriod = 'C (50~57)';
        } elseif ($sum <= 65) {
            $numberSumPeriod = 'D (58~65)';
        } elseif ($sum <= 78) {
            $numberSumPeriod = 'E (66~78)';
        } else {
            $numberSumPeriod = 'F (79~130)';
        }

        if ($sum <= 64) {
            $numberPeriod = '소 (15~64)';
        } elseif ($sum <= 80) {
            $numberPeriod = '중 (65~80)';
        } else {
            $numberPeriod = '대 (81~130)';
        }

        $round = (int) ($draw->round ?? 0);
        $storedDaily = (int) ($draw->daily_round ?? 0);
        if ($storedDaily > 0) {
            $todayRound = $storedDaily;
        } elseif ($dailyRound !== null && $dailyRound > 0) {
            $todayRound = $dailyRound;
        } else {
            $todayRound = $round;
        }
        $blockNumber = (string) $round;
        $blockHashKey = substr(md5($round . ($draw->drawn_at ?? '')), 0, 5);

        $numberStr = sprintf(
            '%02d, %02d, %02d, %02d, %02d',
            (int) $draw->ball1,
            (int) $draw->ball2,
            (int) $draw->ball3,
            (int) $draw->ball4,
            (int) $draw->ball5
        );

        return [
            'trClass'             => ($rowIndex % 2 === 0) ? 'trOdd' : 'trEven',
            'round'               => $round,
            'todayRound'          => $todayRound,
            'time'                => $time,
            'blockNumber'         => $blockNumber,
            'blockHashKey'        => $blockHashKey,
            'powerball'           => $pb,
            'powerballPeriod'     => $powerballPeriod,
            'powerballOddEven'    => ($pb % 2 === 1) ? 'odd' : 'even',
            'powerballUnderOver'  => $pb <= 4 ? 'under' : 'over',
            'number'              => $numberStr,
            'numberSum'           => $sum,
            'numberSumPeriod'     => $numberSumPeriod,
            'numberPeriod'        => $numberPeriod,
            'numberOddEven'       => ($sum % 2 === 1) ? 'odd' : 'even',
            'numberUnderOver'     => $sum <= 72 ? 'under' : 'over',
        ];
    }
}
