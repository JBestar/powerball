<?php

namespace App\Controllers;

use App\Models\PowerballDraw_Model;

/**
 * xservice/reground_LT 가 bepick(bpk/bpk2) 대신 호출하는 호환 JSON.
 * parsePballRound_bpk / fetchPball_bpk2 가 기대하는 필드: round, date(8자), rownum, b1~b5, pb.
 */
class RegroundCompat extends BaseController
{
    /** .env REGROUND_COMPAT_KEY 가 비어 있지 않으면 ?key= 값 필수 */
    private function regroundKeyOk(): bool
    {
        $expected = env('REGROUND_COMPAT_KEY', '');
        if ($expected === '') {
            return true;
        }

        return $this->request->getGet('key') === $expected;
    }

    private function denyUnlessKey(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if (! $this->regroundKeyOk()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'forbidden']);
        }

        return null;
    }

    /**
     * bpk: 구 bepick GET …/live/result/pbgpowerball
     * 응답: JSON 한 덩어리(parsePballRound_bpk 입력과 동일 형태).
     */
    public function bpkLive()
    {
        if ($r = $this->denyUnlessKey()) {
            return $r;
        }

        $model = new PowerballDraw_Model();
        $draw  = $model->getLatest();
        if ($draw === null) {
            return $this->response->setStatusCode(200)->setJSON(['error' => 'no_draw']);
        }

        return $this->response->setJSON($this->drawToBpkArray($draw));
    }

    /**
     * bpk2: 구 bepick GET …/api/get_pattern/pbgpowerball/daily/fd1/20/{YYYYMMDD}
     * 응답: { "update": { … bpk와 동일 … } } — fetchPball_bpk2 가 update 만 사용.
     */
    public function bpk2Pattern(string $ymd)
    {
        if ($r = $this->denyUnlessKey()) {
            return $r;
        }

        $draw = $this->drawForGameDayYmd($ymd);
        if ($draw === null) {
            return $this->response->setJSON(['update' => null]);
        }

        return $this->response->setJSON(['update' => $this->drawToBpkArray($draw)]);
    }

    private function drawForGameDayYmd(string $ymd): ?object
    {
        if (! preg_match('/^\d{8}$/', $ymd)) {
            return null;
        }
        $picker = substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2);
        [$from, $to] = PowerballDraw_Model::gameDayWindowFromPickerDate($picker);
        if ($from === '') {
            return null;
        }

        $model = new PowerballDraw_Model();

        return $model->where('drawn_at >=', $from)->where('drawn_at <=', $to)->orderBy('round', 'DESC')->first();
    }

    /**
     * @return array<string, string>
     */
    private function drawToBpkArray(object $draw): array
    {
        $drawnAt = (string) ($draw->drawn_at ?? '');
        $dk      = PowerballDraw_Model::gameDayKeyKstFromDrawnAt($drawnAt);
        $dateYmd = str_replace('-', '', $dk);

        $dr = (int) ($draw->daily_round ?? 0);
        if ($dr < 1) {
            $dr = 1;
        }

        $rid = (string) ($draw->round ?? 0);
        $sum = (int) ($draw->ball_sum ?? 0);

        // 기성 bepick idx: YY(게임일) + MMDD + 일회차 4자리 — 예: 2604040238
        $idx = substr($dateYmd, 2, 2) . substr($dateYmd, 4, 4) . str_pad((string) $dr, 4, '0', STR_PAD_LEFT);

        $balls = [
            (int) ($draw->ball1 ?? 0),
            (int) ($draw->ball2 ?? 0),
            (int) ($draw->ball3 ?? 0),
            (int) ($draw->ball4 ?? 0),
            (int) ($draw->ball5 ?? 0),
        ];
        // fd1~5: 일반볼 각각 홀짝 (PowerballDraw_Model::bpkFdDigitFromBall)
        $fd = [];
        foreach ($balls as $i => $bv) {
            $fd[$i] = PowerballDraw_Model::bpkFdDigitFromBall($bv);
        }

        $pb = (int) ($draw->powerball ?? 0);
        // btype·ptype: dayLog 회차별 분석과 동일 (bpkBtypeFromBallSum / bpkPtypeFromPowerball)
        $btype = PowerballDraw_Model::bpkBtypeFromBallSum($sum);
        $ptype = PowerballDraw_Model::bpkPtypeFromPowerball($pb);

        return [
            'idx'     => $idx,
            'fd1'     => $fd[0],
            'fd2'     => $fd[1],
            'fd3'     => $fd[2],
            'fd4'     => $fd[3],
            'fd5'     => $fd[4],
            'b1'      => (string) $balls[0],
            'b2'      => (string) $balls[1],
            'b3'      => (string) $balls[2],
            'b4'      => (string) $balls[3],
            'b5'      => (string) $balls[4],
            'bsum'    => (string) $sum,
            'btype'   => $btype,
            'pb'      => (string) $pb,
            'ptype'   => $ptype,
            'round'   => (string) $dr,
            'rownump' => $rid . '/' . $dr,
            'date'    => $dateYmd,
            'rownum'  => $rid,
        ];
    }
}
