<?php

namespace App\Controllers;

use App\Models\LionPendingDraw_Model;
use App\Models\PowerballDraw_Model;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * 라이온(별도 도메인) 관리 화면에서 draw_results 해당 슬롯 수동 반영용.
 *
 * 설정: Powerball 프로젝트 .env 에 LION_DRAW_SYNC_KEY 설정 (예: openssl rand -hex 24)
 *
 * 호출 예:
 * POST /lion/syncDraw
 * Header: Content-Type: application/json, X-Lion-Draw-Key: <위 키>
 * Body:
 * {"ball1":1,"ball2":5,"ball3":9,"ball4":12,"ball5":20,"powerball":3,"drawn_at":"2026-05-03 14:05:00" }
 * drawn_at 미지정 시 현재 KST 시각 기준 5분 슬롯.
 *
 * POST /lion/queueConstraint — 추첨 INSERT 전에만 유효. 동일 drawn_at 에 UPSERT(마지막 요청만 유지).
 * Body: {"key":"...","drawn_at":"2026-05-03 14:05:00","rules":{"pb_parity":"even","pb_ou":"under"}}
 * rules 키: pb_parity odd|even, pb_ou under|over, nb_sum_parity odd|even, nb_sum_ou under|over
 */
class LionSync extends Controller
{

    /** @see PowerballDraw_Model::BALL_MIN etc. */
    private function validatePayload(array $b): ?array
    {
        $balls = [];
        for ($k = 1; $k <= 5; $k++) {
            $n = (int) ($b['ball' . $k] ?? 0);
            if ($n < PowerballDraw_Model::BALL_MIN || $n > PowerballDraw_Model::BALL_MAX) {
                return null;
            }
            $balls[] = $n;
        }
        if (count(array_unique($balls)) !== 5) {
            return null;
        }
        $pb = (int) ($b['powerball'] ?? -1);
        if ($pb < PowerballDraw_Model::POWERBALL_MIN || $pb > PowerballDraw_Model::POWERBALL_MAX) {
            return null;
        }

        return ['balls' => $balls, 'powerball' => $pb];
    }

    private function syncKeyMatches(string $bodyKeyLine): bool
    {
        $expected = trim((string) env('LION_DRAW_SYNC_KEY', ''));
        if ($expected === '') {
            return false;
        }
        $hdr = trim((string) $this->request->getHeaderLine('X-Lion-Draw-Key'));
        if ($hdr !== '' && hash_equals($expected, $hdr)) {
            return true;
        }
        if ($bodyKeyLine !== '' && hash_equals($expected, $bodyKeyLine)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $b
     * @return array<string, string>|null null = 충돌·형식 오류
     */
    private function normalizeQueueRulesFromBody(array $b): ?array
    {
        $rules = [];
        if (! empty($b['keys']) && is_array($b['keys'])) {
            $seen = [];
            foreach ($b['keys'] as $raw) {
                $k = trim((string) $raw);
                if ($k === '') {
                    continue;
                }
                $seen[$k] = true;
            }
            $pairs = [
                [['pb_holu', 'pb_jjak'], 'pb_parity', ['pb_holu' => 'odd', 'pb_jjak' => 'even']],
                [['pb_under', 'pb_over'], 'pb_ou', ['pb_under' => 'under', 'pb_over' => 'over']],
                [['nb_holu', 'nb_jjak'], 'nb_sum_parity', ['nb_holu' => 'odd', 'nb_jjak' => 'even']],
                [['nb_under', 'nb_over'], 'nb_sum_ou', ['nb_under' => 'under', 'nb_over' => 'over']],
            ];
            foreach ($pairs as [$group, $outKey, $map]) {
                $hit = null;
                foreach ($group as $g) {
                    if (isset($seen[$g])) {
                        if ($hit !== null) {
                            return null;
                        }
                        $hit = $map[$g];
                    }
                }
                if ($hit !== null) {
                    $rules[$outKey] = $hit;
                }
            }
        } elseif (! empty($b['rules']) && is_array($b['rules'])) {
            $r = $b['rules'];
            foreach (['pb_parity' => ['odd', 'even'], 'pb_ou' => ['under', 'over'], 'nb_sum_parity' => ['odd', 'even'], 'nb_sum_ou' => ['under', 'over']] as $rk => $vals) {
                if (! isset($r[$rk])) {
                    continue;
                }
                $v = strtolower(trim((string) $r[$rk]));
                if (in_array($v, $vals, true)) {
                    $rules[$rk] = $v;
                }
            }
        }

        return $rules === [] ? null : $rules;
    }

    /**
     * 추첨 행이 아직 없을 때만 큐 등록. draw_results 와 동일 advisory lock 으로 경합 방지.
     */
    public function queueConstraint(): ResponseInterface
    {
        $this->response->setHeader('Content-Type', 'application/json; charset=UTF-8');

        if ($this->request->getMethod() !== 'post') {
            log_message('warning', 'LionSync::queueConstraint method_not_post method=' . $this->request->getMethod());

            return $this->response->setStatusCode(405)->setJSON(['status' => 'fail', 'msg' => 'post_only']);
        }

        $rawBody = trim((string) $this->request->getBody());
        $parsed  = $rawBody !== '' ? json_decode($rawBody, true) : [];
        $bodyFromJson = is_array($parsed) ? $parsed : [];
        $lineKey      = isset($bodyFromJson['key']) ? trim((string) $bodyFromJson['key']) : '';

        foreach (['drawn_at', 'key', 'rules', 'keys'] as $f) {
            if (! array_key_exists($f, $bodyFromJson) && ($this->request->getPost($f) !== null)) {
                $bodyFromJson[$f] = $this->request->getPost($f);
            }
        }
        if ($lineKey === '' && isset($bodyFromJson['key'])) {
            $lineKey = trim((string) $bodyFromJson['key']);
        }

        if (! $this->syncKeyMatches($lineKey)) {
            log_message('warning', 'LionSync::queueConstraint forbidden key_hdr_len=' . strlen($this->request->getHeaderLine('X-Lion-Draw-Key')) . ' key_body_len=' . strlen($lineKey) . ' env_key_set=' . (trim((string) env('LION_DRAW_SYNC_KEY', '')) !== '' ? 'yes' : 'no'));

            return $this->response->setStatusCode(403)->setJSON(['status' => 'fail', 'msg' => 'forbidden']);
        }

        $rules = $this->normalizeQueueRulesFromBody($bodyFromJson);
        if ($rules === null) {
            log_message('warning', 'LionSync::queueConstraint invalid_or_conflicting_rules body_keys=' . json_encode(array_keys($bodyFromJson), JSON_UNESCAPED_UNICODE));

            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'fail',
                'msg'    => 'invalid_or_conflicting_rules',
            ]);
        }

        $drawnAt = isset($bodyFromJson['drawn_at']) ? trim((string) $bodyFromJson['drawn_at']) : '';
        if ($drawnAt === '') {
            $unix = isset($bodyFromJson['unix']) ? (int) $bodyFromJson['unix'] : null;
            if ($unix !== null && $unix > 0) {
                $drawnAt = PowerballDraw_Model::kstDrawnAtFromUnixTimestamp($unix);
            } else {
                $drawnAt = PowerballDraw_Model::kstDrawnAtFromUnixTimestamp(time());
            }
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:00$/', $drawnAt)) {
            log_message('warning', 'LionSync::queueConstraint bad_drawn_at drawn_at=' . $drawnAt);

            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'fail',
                'msg'    => 'drawn_at must be Y-m-d H:i:00 five minute slot',
            ]);
        }

        log_message('info', 'LionSync::queueConstraint start drawn_at=' . $drawnAt . ' rules=' . json_encode($rules, JSON_UNESCAPED_UNICODE));

        $lockName = PowerballDraw_Model::advisoryLockNameForDrawnAt($drawnAt);
        $db       = Database::connect();
        $lockHeld = false;
        try {
            $g = $db->query('SELECT GET_LOCK(?, 25) AS g', [$lockName])->getRow();
            $lockHeld = $g && (int) $g->g === 1;
            if (! $lockHeld) {
                log_message('error', 'LionSync::queueConstraint lock_timeout drawn_at=' . $drawnAt);

                return $this->response->setStatusCode(503)->setJSON(['status' => 'fail', 'msg' => 'lock_timeout']);
            }

            $drawModel = new PowerballDraw_Model();
            $drawModel->ensureDailyRoundColumn();
            if ($drawModel->getByDrawnAt($drawnAt) !== null) {
                log_message('notice', 'LionSync::queueConstraint draw_already_exists drawn_at=' . $drawnAt);

                return $this->response->setStatusCode(409)->setJSON([
                    'status'   => 'fail',
                    'msg'      => 'draw_already_exists',
                    'drawn_at' => $drawnAt,
                ]);
            }

            $pending = new LionPendingDraw_Model();
            $pending->upsertRules($drawnAt, $rules);

            log_message('info', 'LionSync::queueConstraint success drawn_at=' . $drawnAt);

            return $this->response->setJSON([
                'status'   => 'success',
                'drawn_at' => $drawnAt,
                'rules'    => $rules,
            ]);
        } finally {
            if ($lockHeld) {
                $db->query('SELECT RELEASE_LOCK(?) AS r', [$lockName]);
            }
        }
    }

    public function syncDraw(): ResponseInterface
    {

        $this->response->setHeader('Content-Type', 'application/json; charset=UTF-8');

        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'fail', 'msg' => 'post_only']);
        }

        $rawBody = trim((string) $this->request->getBody());
        $parsed  = $rawBody !== '' ? json_decode($rawBody, true) : [];

        $bodyFromJson = [];
        $lineKey      = '';

        if (is_array($parsed)) {
            $bodyFromJson = $parsed;
            if (isset($parsed['key'])) {
                $lineKey = trim((string) $parsed['key']);
            }
        }

        // Form POST fallback
        foreach (['ball1', 'ball2', 'ball3', 'ball4', 'ball5', 'powerball', 'drawn_at', 'key'] as $f) {
            if (! array_key_exists($f, $bodyFromJson) && ($this->request->getPost($f) !== null)) {
                $bodyFromJson[$f] = $this->request->getPost($f);
            }
        }

        if ($lineKey === '' && isset($bodyFromJson['key'])) {
            $lineKey = trim((string) $bodyFromJson['key']);
        }

        if (! $this->syncKeyMatches($lineKey)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'fail', 'msg' => 'forbidden']);
        }

        $valid = $this->validatePayload($bodyFromJson);
        if ($valid === null) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'fail', 'msg' => 'invalid_balls']);
        }

        $drawnAt = isset($bodyFromJson['drawn_at']) ? trim((string) $bodyFromJson['drawn_at']) : '';
        if ($drawnAt === '') {
            $unix = isset($bodyFromJson['unix']) ? (int) $bodyFromJson['unix'] : null;
            if ($unix !== null && $unix > 0) {
                $drawnAt = PowerballDraw_Model::kstDrawnAtFromUnixTimestamp($unix);
            } else {
                $drawnAt = PowerballDraw_Model::kstDrawnAtFromUnixTimestamp(time());
            }
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:00$/', $drawnAt)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'fail',
                'msg'    => 'drawn_at must be Y-m-d H:i:00 five minute slot',
            ]);
        }

        $balls = $valid['balls'];
        $pb    = $valid['powerball'];
        $sum   = array_sum($balls);

        $model = new PowerballDraw_Model();
        $model->ensureDailyRoundColumn();

        $row = $model->getByDrawnAt($drawnAt);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'   => 'fail',
                'msg'      => 'no_draw_for_slot',
                'drawn_at' => $drawnAt,
            ]);
        }

        $model->update((int) $row->id, [
            'ball1'       => $balls[0],
            'ball2'       => $balls[1],
            'ball3'       => $balls[2],
            'ball4'       => $balls[3],
            'ball5'       => $balls[4],
            'powerball'   => $pb,
            'ball_sum'    => $sum,
            // round / daily_round / drawn_at 유지
        ]);

        return $this->response->setJSON([
            'status'    => 'success',
            'id'        => (int) $row->id,
            'drawn_at'  => $drawnAt,
            'round'     => (int) ($row->round ?? 0),
            'ball_sum'  => $sum,
            'powerball' => $pb,
        ]);
    }
}
