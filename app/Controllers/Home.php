<?php

namespace App\Controllers;

use App\Models\MemConf_Model;
use App\Models\Domain_Model;
use App\Models\BoardWrite_Model;
use App\Models\BoardPhoto_Model;

class Home extends BaseController
{
    public function index()
    {
        // inner-right iframe용: URI가 frame/dayLog(또는 .../frame/dayLog)이면 반드시 dayLog만 반환 (메인 헤더 중복 방지)
        $path = $this->request->uri->getPath();
        $path = trim($path, '/');
        if (strpos($path, 'frame/dayLog') !== false || preg_match('#^frame/#', $path)) {
            return $this->frameDayLog();
        }

        // dayLog 회차별 분석 데이터: POST view=action, action=ajaxPowerballLog / ajaxPattern
        if ($this->request->getMethod() === 'post') {
            $view   = $this->request->getPost('view');
            $action = $this->request->getPost('action');
            if ($view === 'action' && $action === 'ajaxPowerballLog') {
                return $this->ajaxPowerballLog();
            }
            if ($view === 'action' && $action === 'ajaxPattern') {
                return $this->ajaxPattern();
            }
            if ($view === 'action' && $action === 'ajaxSixPattern') {
                return $this->ajaxSixPattern();
            }
        }

        $this->setLanguage();
        $headInfo = $this->getSiteConf();
        $headInfo['lang'] = $this->session->lang;
        // 1. 도메인 체크 (선배님 로직)
        if($_ENV['app.name'] == APP_ATM && strpos($_SERVER['HTTP_HOST'], "xn--hi5b6a25g9xy.com") === 0){
		    $this->response->redirect(site_furl('/domain'));
        } 
        // 2. 로그인 필수 설정인 경우 로그인 페이지로
        else if(!is_login(true) && array_key_exists('app.login', $_ENV) && $_ENV['app.login'] == 1){
            echo view('home/login', $headInfo);
        }
        // 2-1. mainFrame(핵심 inner-right) 전용: view=dayLog → 별도 파일에서 수행
        else if($this->request->getGet('view') === 'dayLog'){
            $dayLogDate = $this->request->getGet('date');
            if ($dayLogDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dayLogDate)) {
                // 유효한 날짜만 사용
            } else {
                $dayLogDate = date('Y-m-d');
            }
            $dayLogData = array_merge($headInfo, $this->getDrawTimerInfo(), [
                'site_title' => ($headInfo['site_name'] ?? '파워볼게임').' : 실시간 파워볼 분석 커뮤니티',
                'date' => $dayLogDate,
            ]);
            echo view('home/dayLog', $dayLogData);
            return;
        }
        // 2-2. iframe(mainFrame)에서 전체 레이아웃이 아닌 내용만 표시 — 헤더 중복 방지
        else if($this->request->getGet('frame') === 'mainFrame'){
            $dayLogDate = $this->request->getGet('date');
            if (!$dayLogDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dayLogDate)) {
                $dayLogDate = date('Y-m-d');
            }
            $dayLogData = array_merge($headInfo, $this->getDrawTimerInfo(), [
                'site_title' => ($headInfo['site_name'] ?? '파워볼게임').' : 실시간 파워볼 분석 커뮤니티',
                'date' => $dayLogDate,
                'frame_mainFrame' => true,
            ]);
            echo view('home/dayLog', $dayLogData);
            return;
        }
        // 2-3. 미니뷰 iframe 전용 (dayLog 내 "미니뷰 열기" 시 로드)
        else if($this->request->getGet('view') === 'powerballMiniView'){
            $headInfo   = $this->getSiteConf();
            $lastRound  = '';
            $lastResult = '-';
            $time_round = 1;
            $currentBalls = [];
            $prevBalls    = [];
            try {
                $drawModel = new \App\Models\PowerballDraw_Model();
                $latest    = $drawModel->getOrGenerate(time());
                $two       = $drawModel->orderBy('round', 'DESC')->findAll(2);
                $prev      = isset($two[1]) ? $two[1] : null;

                if ($latest) {
                    $lastRound  = (string) ($latest->round ?? '');
                    $time_round = (int) ($latest->round ?? 0) + 1;
                    $nums = [
                        (int)($latest->ball1 ?? 0),
                        (int)($latest->ball2 ?? 0),
                        (int)($latest->ball3 ?? 0),
                        (int)($latest->ball4 ?? 0),
                        (int)($latest->ball5 ?? 0),
                    ];
                    $pb  = (int)($latest->powerball ?? 0);
                    $sum = (int)($latest->ball_sum ?? 0);
                    $lastResult  = implode(', ', $nums) . ', ' . $pb . ', ' . $sum;
                    $currentBalls = array_merge($nums, [$pb]);
                }
                if ($prev) {
                    $pnums = [
                        (int)($prev->ball1 ?? 0),
                        (int)($prev->ball2 ?? 0),
                        (int)($prev->ball3 ?? 0),
                        (int)($prev->ball4 ?? 0),
                        (int)($prev->ball5 ?? 0),
                    ];
                    $ppb = (int)($prev->powerball ?? 0);
                    $prevBalls = array_merge($pnums, [$ppb]);
                }
            } catch (\Throwable $e) {
                // DB/테이블 미생성 시 빈 값 유지
            }
            $miniViewData = array_merge($headInfo, [
                'remain_time'    => $this->getRemainSecondsUntilNextDraw(),
                'time_round'     => $time_round,
                'last_round'     => $lastRound,
                'last_result'    => $lastResult,
                'current_balls'  => $currentBalls,
                'prev_balls'     => $prevBalls,
            ]);
            echo view('home/powerballMiniView', $miniViewData);
            return;
        }
        // 3. 메인 대시보드 화면 띄우기
        else {
            $objMember = null;
            if(is_login(true)){
                $user_id = $this->session->user_id;
                $objMember = $this->modelMember->getByUid($user_id);
                $this->sess_action();                
            }
            // 공지 목록 (메인 롤링용, 선배님 스타일)
            $boards = [];
            try {
                $boards = $this->modelNotice->getBoards();
                $boards = is_array($boards) ? array_slice($boards, 0, 10) : [];
            } catch (\Throwable $e) {
                $boards = [];
            }
            // 리스트박스용 게시 목록 (유머/분석픽공유/자유) - DB 조회
            $boardWriteModel = new BoardWrite_Model();
            $list_humor = $boardWriteModel->getListForMain('humor', 10);
            $list_pick  = $boardWriteModel->getListForMain('pick', 10);
            $list_free  = $boardWriteModel->getListForMain('free', 10);
            $boardPhotoModel = new BoardPhoto_Model();
            $list_photo = $boardPhotoModel->getListForMain(14);
            $navInfo = getNavInfo($objMember);
            $viewData = array_merge($headInfo, $navInfo, [
                'objMember'  => $objMember,
                'boards'     => $boards,
                'list_humor' => $list_humor,
                'list_pick'  => $list_pick,
                'list_free'  => $list_free,
                'list_photo' => $list_photo,
            ]);
            echo view('home/main', $viewData);
        }
    }

    /**
     * 다음 추첨 시각까지 남은 초 (5분 단위: XX:00, XX:05, XX:10, … XX:55 기준)
     * 예: 23:23:32 → 다음 23:25:00 까지 88초
     */
    protected function getRemainSecondsUntilNextDraw(): int
    {
        $now = time();
        $minute = (int) date('i', $now);
        $hour = (int) date('H', $now);
        $month = (int) date('n', $now);
        $day = (int) date('j', $now);
        $year = (int) date('Y', $now);

        $nextSlotMinute = (floor($minute / 5) + 1) * 5;
        if ($nextSlotMinute >= 60) {
            $nextDrawTs = mktime($hour + 1, 0, 0, $month, $day, $year);
        } else {
            $nextDrawTs = mktime($hour, $nextSlotMinute, 0, $month, $day, $year);
        }
        $remain = (int) ($nextDrawTs - $now);
        return max(0, min(300, $remain));
    }

    /**
     * 전체 분석 데이터 JSON (dayLog refreshAnalyse용)
     * GET /json/powerballAnalyse/20260316.json
     * 해당 날짜 draw_results 기준으로 파워볼/숫자합/대중소 집계 및 연속 횟수 반환
     */
    public function powerballAnalyse(string $segment = '')
    {
        $segment = preg_replace('/\.json$/i', '', $segment);
        if (!preg_match('/^\d{8}$/', $segment)) {
            return $this->response->setJSON(['state' => 'error', 'msg' => 'Invalid date']);
        }
        $date = substr($segment, 0, 4) . '-' . substr($segment, 4, 2) . '-' . substr($segment, 6, 2);
        $dateFrom = $date . ' 00:00:00';
        $dateTo   = $date . ' 23:59:59';

        $drawModel = new \App\Models\PowerballDraw_Model();
        $rows = $drawModel
            ->where('drawn_at >=', $dateFrom)
            ->where('drawn_at <=', $dateTo)
            ->orderBy('round', 'ASC')
            ->findAll();

        $total = count($rows);
        $cnt = [
            'powerballOdd' => 0, 'powerballEven' => 0, 'powerballUnder' => 0, 'powerballOver' => 0,
            'numberOdd' => 0, 'numberEven' => 0, 'numberUnder' => 0, 'numberOver' => 0,
            'numberBig' => 0, 'numberMiddle' => 0, 'numberSmall' => 0,
        ];
        $types = []; // 각 회차별 분류 (연속 계산용)
        foreach ($rows as $draw) {
            $pb = (int) ($draw->powerball ?? 0);
            $sum = (int) ($draw->ball_sum ?? 0);
            $pbOdd = ($pb % 2 === 1);
            $pbUnder = ($pb <= 4);
            $numOdd = ($sum % 2 === 1);
            $numUnder = ($sum <= 72);
            if ($sum <= 64) {
                $numSize = 'small';
            } elseif ($sum <= 80) {
                $numSize = 'middle';
            } else {
                $numSize = 'big';
            }
            $cnt['powerballOdd']   += $pbOdd ? 1 : 0;
            $cnt['powerballEven']  += $pbOdd ? 0 : 1;
            $cnt['powerballUnder'] += $pbUnder ? 1 : 0;
            $cnt['powerballOver']  += $pbUnder ? 0 : 1;
            $cnt['numberOdd']      += $numOdd ? 1 : 0;
            $cnt['numberEven']     += $numOdd ? 0 : 1;
            $cnt['numberUnder']    += $numUnder ? 1 : 0;
            $cnt['numberOver']     += $numUnder ? 0 : 1;
            $cnt['numberBig']      += ($numSize === 'big') ? 1 : 0;
            $cnt['numberMiddle']   += ($numSize === 'middle') ? 1 : 0;
            $cnt['numberSmall']    += ($numSize === 'small') ? 1 : 0;
            $types[] = [
                'pbOdd' => $pbOdd, 'pbUnder' => $pbUnder,
                'numOdd' => $numOdd, 'numUnder' => $numUnder, 'numSize' => $numSize,
            ];
        }

        $per = [];
        foreach ($cnt as $k => $v) {
            $per[$k] = $total > 0 ? (string) round($v / $total * 100) : '0';
        }

        // 연속 횟수: 해당 날짜 추첨 자료 가운데 같은 타입이 연속 나온 회수 중 최대값
        $row = [
            'powerballOdd' => 0, 'powerballEven' => 0, 'powerballUnder' => 0, 'powerballOver' => 0,
            'numberOdd' => 0, 'numberEven' => 0, 'numberUnder' => 0, 'numberOver' => 0,
            'numberBig' => 0, 'numberMiddle' => 0, 'numberSmall' => 0,
        ];
        $maxConsecutive = static function (array $types, string $key, $value) {
            $max = 0;
            $cur = 0;
            foreach ($types as $t) {
                $v = $key === 'numSize' ? $t['numSize'] : $t[$key];
                if ($v === $value) {
                    $cur++;
                    $max = max($max, $cur);
                } else {
                    $cur = 0;
                }
            }
            return $max;
        };
        if (count($types) > 0) {
            $row['powerballOdd']   = $maxConsecutive($types, 'pbOdd', true);
            $row['powerballEven']  = $maxConsecutive($types, 'pbOdd', false);
            $row['powerballUnder'] = $maxConsecutive($types, 'pbUnder', true);
            $row['powerballOver']  = $maxConsecutive($types, 'pbUnder', false);
            $row['numberOdd']      = $maxConsecutive($types, 'numOdd', true);
            $row['numberEven']     = $maxConsecutive($types, 'numOdd', false);
            $row['numberUnder']    = $maxConsecutive($types, 'numUnder', true);
            $row['numberOver']     = $maxConsecutive($types, 'numUnder', false);
            $row['numberBig']      = $maxConsecutive($types, 'numSize', 'big');
            $row['numberMiddle']   = $maxConsecutive($types, 'numSize', 'middle');
            $row['numberSmall']    = $maxConsecutive($types, 'numSize', 'small');
        }

        return $this->response->setJSON([
            'state' => 'success',
            'powerballOddCnt'   => (string) $cnt['powerballOdd'],
            'powerballEvenCnt'  => (string) $cnt['powerballEven'],
            'powerballUnderCnt' => (string) $cnt['powerballUnder'],
            'powerballOverCnt'  => (string) $cnt['powerballOver'],
            'numberOddCnt'      => (string) $cnt['numberOdd'],
            'numberEvenCnt'     => (string) $cnt['numberEven'],
            'numberUnderCnt'     => (string) $cnt['numberUnder'],
            'numberOverCnt'     => (string) $cnt['numberOver'],
            'numberBigCnt'      => (string) $cnt['numberBig'],
            'numberMiddleCnt'   => (string) $cnt['numberMiddle'],
            'numberSmallCnt'    => (string) $cnt['numberSmall'],
            'powerballOddPer'   => $per['powerballOdd'],
            'powerballEvenPer'  => $per['powerballEven'],
            'powerballUnderPer' => $per['powerballUnder'],
            'powerballOverPer'  => $per['powerballOver'],
            'numberOddPer'      => $per['numberOdd'],
            'numberEvenPer'     => $per['numberEven'],
            'numberUnderPer'    => $per['numberUnder'],
            'numberOverPer'     => $per['numberOver'],
            'numberBigPer'      => $per['numberBig'],
            'numberMiddlePer'   => $per['numberMiddle'],
            'numberSmallPer'    => $per['numberSmall'],
            'powerballOddRow'   => (string) $row['powerballOdd'],
            'powerballEvenRow'  => (string) $row['powerballEven'],
            'powerballUnderRow' => (string) $row['powerballUnder'],
            'powerballOverRow'  => (string) $row['powerballOver'],
            'numberOddRow'      => (string) $row['numberOdd'],
            'numberEvenRow'     => (string) $row['numberEven'],
            'numberUnderRow'    => (string) $row['numberUnder'],
            'numberOverRow'     => (string) $row['numberOver'],
            'numberBigRow'      => (string) $row['numberBig'],
            'numberMiddleRow'   => (string) $row['numberMiddle'],
            'numberSmallRow'    => (string) $row['numberSmall'],
        ]);
    }

    /**
     * dayLog 회차별 분석 데이터: ajaxPowerballLog (actionType: dayLog 페이지네이션, refreshLog 추첨 후 1건 추가)
     */
    public function ajaxPowerballLog()
    {
        $actionType = $this->request->getPost('actionType');
        $drawModel  = new \App\Models\PowerballDraw_Model();

        if ($actionType === 'dayLog') {
            $date = $this->request->getPost('date');
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $date = date('Y-m-d');
            }
            $page = (int) $this->request->getPost('page');
            if ($page < 0) {
                $page = 0;
            }
            $perPage = 30;
            $offset  = $page * $perPage;

            $dateFrom = $date . ' 00:00:00';
            $dateTo   = $date . ' 23:59:59';
            $rows = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'DESC')
                ->limit($perPage, $offset)
                ->findAll();

            $content = [];
            foreach ($rows as $i => $draw) {
                $content[] = \App\Models\PowerballDraw_Model::formatForDayLogRow($draw, $offset + $i);
            }
            // 한 번에 최대 30개만 반환 (31개 이상 방지)
            $content = \array_slice($content, 0, $perPage);

            $latest = $drawModel->where('drawn_at >=', $dateFrom)->where('drawn_at <=', $dateTo)->orderBy('round', 'DESC')->first();
            $round  = $latest ? (int) $latest->round : 0;

            return $this->response->setJSON([
                'content' => $content,
                'endYN'   => count($content) < $perPage ? 'Y' : 'N',
                'pageVal' => $page,
                'round'   => $round,
            ]);
        }

        if ($actionType === 'refreshLog') {
            $date = $this->request->getPost('date');
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $date = date('Y-m-d');
            }
            $afterRound = (int) $this->request->getPost('round');

            $dateFrom = $date . ' 00:00:00';
            $dateTo   = $date . ' 23:59:59';
            $draw = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->where('round >', $afterRound)
                ->orderBy('round', 'ASC')
                ->first();

            if (!$draw) {
                return $this->response->setJSON(['state' => 'success', 'round' => $afterRound, 'content' => []]);
            }

            $row = \App\Models\PowerballDraw_Model::formatForDayLogRow($draw, 0);
            return $this->response->setJSON([
                'state'   => 'success',
                'round'   => (int) $draw->round,
                'content' => [$row],
                'powerballOddEven'   => $row['powerballOddEven'],
                'powerballUnderOver' => $row['powerballUnderOver'],
                'numberOddEven'      => $row['numberOddEven'],
                'numberUnderOver'    => $row['numberUnderOver'],
            ]);
        }

        return $this->response->setJSON(['state' => 'error', 'msg' => 'Invalid actionType']);
    }

    /**
     * 패턴별 분석 데이터: ajaxPattern (actionType=oddEven 등, division=powerball/number, date=Y-m-d)
     * 파워볼 기준 홀짝 패턴: 해당 날짜 회차별 파워볼 홀/짝을 선배님 페이지와 동일한 테이블 구조로 반환
     */
    public function ajaxPattern()
    {
        $actionType = $this->request->getPost('actionType');
        $division   = $this->request->getPost('division');
        $date       = $this->request->getPost('date');
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        // 홀짝 패턴(oddEven)만 비로그인 허용, 나머지(언더오버·대중소)는 로그인 필수 (선배님 동작)
        if ($actionType !== 'oddEven' && !is_login(true)) {
            return $this->response->setJSON(['content' => 'notlogin']);
        }

        if ($actionType === 'oddEven' && $division === 'powerball') {
            $html = $this->buildPowerballOddEvenPatternHtml($date);
            return $this->response->setJSON(['content' => $html]);
        }
        if ($actionType === 'oddEven' && $division === 'number') {
            $html = $this->buildNumberSumOddEvenPatternHtml($date);
            return $this->response->setJSON(['content' => $html]);
        }

        // TODO: underOver, number 합계 대중소 등
        return $this->response->setJSON(['content' => '']);
    }

    /**
     * 파워볼 기준 홀짝 패턴 HTML (선배님 페이지 구조: patternTable > tr > td > table.innerTable)
     * - 홀/짝이 바뀔 때마다 새 컬럼(블록). 각 블록: th(홀|짝), 회차 div들(최대 11행), sum, order
     */
    protected function buildPowerballOddEvenPatternHtml(string $date): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        $dateFrom  = $date . ' 00:00:00';
        $dateTo    = $date . ' 23:59:59';
        $rows      = $drawModel
            ->where('drawn_at >=', $dateFrom)
            ->where('drawn_at <=', $dateTo)
            ->orderBy('round', 'ASC')
            ->findAll();

        // 연속된 같은 홀/짝끼리 그룹: [ ['odd'|'even', [round1, round2, ...]], ... ]
        $groups = [];
        foreach ($rows as $draw) {
            $round = (int) ($draw->round ?? 0);
            $pb    = (int) ($draw->powerball ?? 0);
            $isOdd = ($pb % 2 === 1);
            $type  = $isOdd ? 'odd' : 'even';
            if (!empty($groups) && $groups[count($groups) - 1][0] === $type) {
                $groups[count($groups) - 1][1][] = $round;
            } else {
                $groups[] = [$type, [$round]];
            }
        }
        return $this->buildOddEvenPatternTable($groups);
    }

    /**
     * 숫자합 기준 홀짝 패턴 HTML (ball_sum 기준, 구조는 파워볼 홀짝과 동일)
     */
    protected function buildNumberSumOddEvenPatternHtml(string $date): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        $dateFrom  = $date . ' 00:00:00';
        $dateTo    = $date . ' 23:59:59';
        $rows      = $drawModel
            ->where('drawn_at >=', $dateFrom)
            ->where('drawn_at <=', $dateTo)
            ->orderBy('round', 'ASC')
            ->findAll();

        $groups = [];
        foreach ($rows as $draw) {
            $round = (int) ($draw->round ?? 0);
            $sum   = (int) ($draw->ball_sum ?? 0);
            $isOdd = ($sum % 2 === 1);
            $type  = $isOdd ? 'odd' : 'even';
            if (!empty($groups) && $groups[count($groups) - 1][0] === $type) {
                $groups[count($groups) - 1][1][] = $round;
            } else {
                $groups[] = [$type, [$round]];
            }
        }

        return $this->buildOddEvenPatternTable($groups);
    }

    /**
     * 홀짝 패턴 공통 테이블 HTML 생성 (그룹 배열 → patternTable 마크업)
     */
    protected function buildOddEvenPatternTable(array $groups): string
    {
        $maxDataRows = 11;
        $cells       = [];
        $order       = 0;
        foreach ($groups as $group) {
            $order++;
            list($type, $rounds) = $group;
            $titleClass = $type === 'odd' ? 'title_odd' : 'title_even';
            $titleText  = $type === 'odd' ? '홀' : '짝';
            $inner = '<tr><th class="' . $titleClass . '">' . $titleText . '</th></tr>';
            foreach ($rounds as $r) {
                $inner .= '<tr><td><div class="' . $type . '">' . ((int) $r % 1000) . '</div></td></tr>';
            }
            $pad = $maxDataRows - count($rounds);
            for ($i = 0; $i < $pad; $i++) {
                $inner .= '<tr><td>&nbsp;</td></tr>';
            }
            $inner .= '<tr><td class="sum">' . count($rounds) . '</td></tr>';
            $inner .= '<tr><td class="order">' . $order . '</td></tr>';
            $cells[] = '<td><table class="innerTable"><tbody>' . $inner . '</tbody></table></td>';
        }
        return '<table class="patternTable"><tbody><tr>' . implode('', $cells) . '</tr></tbody></table>';
    }

    /**
     * 육매 분석 데이터: ajaxSixPattern (patternCnt=1~6, actionType=oddEven 등, division=powerball/number)
     * 파워볼 홀짝만 비로그인 허용 (선배님 동작)
     */
    public function ajaxSixPattern()
    {
        $actionType = $this->request->getPost('actionType');
        $division   = $this->request->getPost('division');
        $patternCnt = (int) $this->request->getPost('patternCnt');
        $date       = $this->request->getPost('date');
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        if ($patternCnt < 1 || $patternCnt > 6) {
            $patternCnt = 6;
        }

        if ($actionType !== 'oddEven' || $division !== 'powerball') {
            if (!is_login(true)) {
                return $this->response->setJSON(['content' => 'notlogin']);
            }
        }

        if ($actionType === 'oddEven' && ($division === 'powerball' || $division === 'number')) {
            $html = $this->buildSixPatternHtml($date, $patternCnt, $division);
            return $this->response->setJSON(['content' => $html]);
        }

        return $this->response->setJSON(['content' => '']);
    }

    /**
     * 육매 패턴 HTML (선배님 구조: N매씩 한 컬럼, 각 셀에 회차 마지막 3자리 + odd/even, 마지막 행 order)
     */
    protected function buildSixPatternHtml(string $date, int $patternCnt, string $division): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        $dateFrom  = $date . ' 00:00:00';
        $dateTo    = $date . ' 23:59:59';
        $rows      = $drawModel
            ->where('drawn_at >=', $dateFrom)
            ->where('drawn_at <=', $dateTo)
            ->orderBy('round', 'ASC')
            ->findAll();

        $cells   = [];
        $chunks  = array_chunk($rows, $patternCnt);
        $order   = 0;
        foreach ($chunks as $chunk) {
            $order++;
            $inner = '';
            foreach ($chunk as $draw) {
                $round = (int) ($draw->round ?? 0);
                if ($division === 'powerball') {
                    $isOdd = ((int) ($draw->powerball ?? 0) % 2 === 1);
                } else {
                    $isOdd = ((int) ($draw->ball_sum ?? 0) % 2 === 1);
                }
                $type   = $isOdd ? 'odd' : 'even';
                $title  = $isOdd ? '홀' : '짝';
                $inner .= '<tr><td><div class="' . $type . '" title="' . esc($title) . '">' . ((int) $round % 1000) . '</div></td></tr>';
            }
            $pad = $patternCnt - count($chunk);
            for ($i = 0; $i < $pad; $i++) {
                $inner .= '<tr><td>&nbsp;</td></tr>';
            }
            $inner .= '<tr><td class="order">' . $order . '</td></tr>';
            $cells[] = '<td><table class="innerTable"><tbody>' . $inner . '</tbody></table></td>';
        }

        return '<table class="patternTable"><tbody><tr>' . implode('', $cells) . '</tr></tbody></table>';
    }

    /**
     * dayLog 타이머용: 다음 회차 번호, 다음 추첨까지 남은 초(0~300, 5분 단위 기준)
     */
    protected function getDrawTimerInfo(): array
    {
        $next_round = 1;
        try {
            $drawModel = new \App\Models\PowerballDraw_Model();
            $latest = $drawModel->getOrGenerate(time());
            if ($latest && isset($latest->round)) {
                $next_round = (int) $latest->round + 1;
            }
        } catch (\Throwable $e) {
            // draw_results 미생성 시 기본값 유지
        }
        $remain_seconds = $this->getRemainSecondsUntilNextDraw();
        return ['next_round' => $next_round, 'remain_seconds' => $remain_seconds];
    }

    /**
     * iframe(mainFrame) 전용 — dayLog만 반환 (메인 헤더/레이아웃 없음)
     * 라우트: get('frame/dayLog', 'Home::frameDayLog')
     */
    public function frameDayLog()
    {
        $this->setLanguage();
        $headInfo = $this->getSiteConf();
        $headInfo['lang'] = $this->session->lang ?? 'ko';
        $dayLogDate = $this->request->getGet('date');
        if (!$dayLogDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dayLogDate)) {
            $dayLogDate = date('Y-m-d');
        }
        $dayLogData = array_merge($headInfo, $this->getDrawTimerInfo(), [
            'site_title' => ($headInfo['site_name'] ?? '파워볼게임') . ' : 실시간 파워볼 분석 커뮤니티',
            'date' => $dayLogDate,
            'frame_mainFrame' => true,
        ]);
        $html = view('home/dayLog', $dayLogData);
        $this->response->setBody($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        return $this->response;
    }

	public function domain(){
        if($_ENV['app.name'] == APP_ATM){
            $headInfo = $this->getSiteConf();
        
            $domainModel = new Domain_Model();
            $domains = [];
            $arrDomain = $domainModel->search();
            foreach($arrDomain as $objDomain){
                array_push($domains, $objDomain->conf_domain);
            }
    
            $headInfo['check_domain'] = "에이티엠.com";
            $headInfo['height'] = count($domains) * 60 + 230;
            $headInfo['domains'] = $domains;
            echo view('home/domain', $headInfo);
        } else 
		    $this->response->redirect(site_furl('/'));
        
	}

	public function getaddr(){
		$ip = $this->request->getIPAddress();
		echo "IP ADDRESS is <".$ip.">.";
	}

	public function logout(){

		$sess_id = $this->session->session_id;
		writeLog("[home] logout (".$sess_id.")");
        
		$this->sess_destroy();
		$this->response->redirect(site_furl('/'));
	}

    public function loginip(){
		$this->setLanguage();
        $headInfo = $this->getSiteConf();

        if(!is_login(true)){
            echo view('home/loginip', $headInfo);
        } else {
            $this->response->redirect(site_furl('/'));
        }
	}


    public function mypage()
    {
		$this->setLanguage();
        if($_ENV['app.name'] == APP_ATM && strpos($_SERVER['HTTP_HOST'], "xn--hi5b6a25g9xy.com") === 0){
		    $this->response->redirect(site_furl('/domain'));
        } else if(!is_login(true)){
            print "<script> alert('".lang("common.session_expired")."'); self.close(); </script>";
        } else{
            $this->sess_action();                

            $tab = $this->request->getVar('tab');
            $user_id = $this->session->user_id;
            $objMember = $this->modelMember->getByUid($user_id);
            $navInfo = getNavInfo($objMember);
            $navInfo['lang'] = $this->session->lang;

            if($tab != "my_qna" && $tab != "my_memo" && $tab != "notice" && $tab != "my_point"){
                $tab = "my_info";
            }
            $navInfo['tab'] = $tab;

            $tmNow = time();
            $navInfo['start_at'] = date('Y-m-d', strtotime("-1 month", $tmNow));
            $navInfo['end_at'] = date('Y-m-d', $tmNow);

            $arrSoundConf = $this->modelConfsite->getSoundConf();  
            $navInfo['alarm_name'] = $arrSoundConf[3]->conf_content;
            $navInfo['alarm_volume'] = $arrSoundConf[3]->conf_active;

            echo view('home/mypage', $navInfo);
        }

    }

	public function pt_login(){
		
        $this->response->redirect(site_furl("/pt"));
        // else {
		// 	$port = intval($_SERVER['SERVER_PORT']);
		// 	if($port > 0)
		// 		$port += 1;
		// 	else $port = '81';
		// 	$this->response->redirect('http://'.$_SERVER['SERVER_NAME'].':'.$port);
		// }
		
	}
    public function chat()
    {
        $headInfo = $this->getSiteConf();
        $objMember = null;
        $userToken = '';
        if (is_login(true)) {
            $user_id = $this->session->user_id;
            $objMember = $this->modelMember->getByUid($user_id);
            $userToken = md5($this->session->session_id . ($objMember->mb_uid ?? ''));
        }
        $data = [
            'site_title'   => $headInfo['site_title'] ?? $headInfo['site_name'] ?? '파워볼 채팅',
            'server_time'  => time(),
            'objMember'    => $objMember,
            'userToken'    => $userToken,
        ];
        return view('home/chat', $data);
    }
}
