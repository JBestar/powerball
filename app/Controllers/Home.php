<?php

namespace App\Controllers;

use App\Models\MemConf_Model;
use App\Models\Domain_Model;
use App\Models\BoardWrite_Model;
use App\Models\BoardPhoto_Model;
use App\Models\HumorPost_Model;
use App\Models\PickPost_Model;
use App\Models\FreePost_Model;
use App\Models\QnaPost_Model;
use App\Models\FaqPost_Model;
use App\Models\RequestPost_Model;
use App\Models\Attendance_Model;

class Home extends BaseController
{
    private function redirectWithMessage(string $url, string $message)
    {
        $this->session->setFlashdata('message', $message);
        return $this->response->redirect($url);
    }

    /**
     * 유머·자유·분석픽 등 (mb_uid 가 로그인 아이디 문자열) — 등록/수정 시 항상 쌍으로 저장
     *
     * @return array{mb_uid:string, mb_nickname:string}|null
     */
    protected function communityAuthorUidNick(object $objMember): ?array
    {
        $mb_uid = trim((string) ($objMember->mb_uid ?? ''));
        if ($mb_uid === '') {
            return null;
        }
        $mb_nickname = trim((string) ($objMember->mb_nickname ?? ''));
        if ($mb_nickname === '') {
            $mb_nickname = $mb_uid;
        }

        return ['mb_uid' => $mb_uid, 'mb_nickname' => $mb_nickname];
    }

    /**
     * 포토(board_photo): mb_uid=회원번호(fid), mb_nickname=닉 → 없으면 로그인아이디 → 없으면 #fid
     *
     * @return array{mb_uid:int, mb_nickname:string}|null
     */
    protected function communityPhotoAuthorFields(object $objMember): ?array
    {
        $fid = (int) ($objMember->mb_fid ?? 0);
        if ($fid <= 0) {
            return null;
        }
        $loginUid = trim((string) ($objMember->mb_uid ?? ''));
        $mb_nickname = trim((string) ($objMember->mb_nickname ?? ''));
        if ($mb_nickname === '') {
            $mb_nickname = $loginUid !== '' ? $loginUid : ('#' . $fid);
        }

        return ['mb_uid' => $fid, 'mb_nickname' => $mb_nickname];
    }

    public function index()
    {
        // Debug Toolbar가 ?debugbar_time= 요청으로 데이터를 가져올 때 메인 로직을 타지 않도록 (500 방지)
        if ($this->request->getGet('debugbar_time') !== null) {
            return $this->response->setStatusCode(200)->setBody('');
        }

        // inner-right iframe용: URI가 frame/dayLog(또는 .../frame/dayLog)이면 반드시 dayLog만 반환 (메인 헤더 중복 방지)
        $path = $this->request->uri->getPath();
        $path = trim($path, '/');
        if (strpos($path, 'frame/customerCenter') !== false) {
            return $this->frameCustomerCenter();
        }
        if (strpos($path, 'frame/attendance') !== false) {
            return $this->frameAttendance();
        }
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
            if ($view === 'action' && $action === 'ajaxPowerballAnalyse') {
                return $this->ajaxPowerballAnalyse();
            }
            if ($view === 'action' && $action === 'ajaxPattern') {
                return $this->ajaxPattern();
            }
                if ($view === 'action' && $action === 'ajaxPatternSet') {
                    return $this->ajaxPatternSet();
                }
                if ($view === 'action' && $action === 'ajaxPatternSearch') {
                    return $this->ajaxPatternSearch();
                }
            if ($view === 'action' && $action === 'ajaxSixPattern') {
                return $this->ajaxSixPattern();
            }
            if ($view === 'action' && $action === 'ajaxChatList') {
                return $this->ajaxChatList();
            }
            if ($view === 'action' && $action === 'ajaxChatSend') {
                return $this->ajaxChatSend();
            }
            if ($view === 'action' && $action === 'ajaxChatDelete') {
                return $this->ajaxChatDelete();
            }
            if ($view === 'action' && $action === 'ajaxChatTimer') {
                return $this->ajaxChatTimer();
            }
            if ($view === 'action' && $action === 'login') {
                return $this->doLogin();
            }
        }

        $this->setLanguage();
        $headInfo = $this->getSiteConf();
        $headInfo['lang'] = $this->session->lang;
        // 1. 도메인 체크 (선배님 로직)
        if($_ENV['app.name'] == APP_ATM && strpos($_SERVER['HTTP_HOST'], "xn--hi5b6a25g9xy.com") === 0){
		    $this->response->redirect(site_furl('/domain'));
        }
        // 열람은 비로그인 허용. 로그인은 글쓰기·등록·수정·삭제·출석처리·채팅전송 등 액션에서만 요구한다.
        // (구 app.login 전역 강제는 제거됨. 사이트 전체 잠금이 필요하면 별도 리버스프록시/인증으로 처리.)
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
                'can_access_analysis' => true,
                'flash_message' => $this->session->getFlashdata('message'),
            ]);
            echo view('home/dayLog', $dayLogData);
            return;
        }
        // 2-1-2. 최근 분석 (열람 비로그인 허용)
        else if ($this->request->getGet('view') === 'latestLog') {
            $roundCnt = (int) $this->request->getGet('roundCnt');
            if ($roundCnt < 50 || $roundCnt > 2000) {
                $roundCnt = 300;
            } else {
                $roundCnt = (int) (round($roundCnt / 50) * 50); // 50 단위
            }
            $latestLogData = array_merge($headInfo, $this->getDrawTimerInfo(), [
                'site_title' => ($headInfo['site_name'] ?? '파워볼게임') . ' : 최근 분석',
                'can_access' => true,
                'roundCnt' => $roundCnt,
            ]);
            echo view('home/latestLog', $latestLogData);
            return;
        }
        // 2-1-3. 기간별 분석 (열람 비로그인 허용)
        else if ($this->request->getGet('view') === 'periodLog') {
            $today = date('Y-m-d');
            $dateType = (string) $this->request->getGet('dateType');
            $startDate = (string) $this->request->getGet('startDate');
            $endDate = (string) $this->request->getGet('endDate');

            if ($endDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                $endDate = $today;
            }
            if ($startDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                // 기본: 15일
                $startDate = date('Y-m-d', strtotime($endDate . ' -14 day'));
            }

            $map = [
                '2day' => 2,
                '4day' => 4,
                '7day' => 7,
                '15day' => 15,
                '30day' => 30,
            ];
            if (isset($map[$dateType])) {
                $days = (int) $map[$dateType];
                $endDate = $today;
                $startDate = date('Y-m-d', strtotime($endDate . ' -' . ($days - 1) . ' day'));
            }

            // 날짜 범위 안전장치 (역전/과도)
            if (strtotime($startDate) > strtotime($endDate)) {
                $tmp = $startDate;
                $startDate = $endDate;
                $endDate = $tmp;
            }
            $maxDays = 60; // 과도한 조회 방지
            $diffDays = (int) floor((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
            if ($diffDays > $maxDays) {
                $startDate = date('Y-m-d', strtotime($endDate . ' -' . ($maxDays - 1) . ' day'));
                $diffDays = $maxDays;
            }

            $periodStats = $this->computePeriodLogStats($startDate, $endDate);
            $periodLogData = array_merge($headInfo, [
                'site_title' => ($headInfo['site_name'] ?? '파워볼게임') . ' : 기간별 분석',
                'can_access' => true,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dateType' => $dateType,
                'periodStats' => $periodStats,
            ]);
            echo view('home/periodLog', $periodLogData);
            return;
        }
        // 2-1-4. 패턴별 분석 (열람 비로그인 허용)
        else if ($this->request->getGet('view') === 'patternAnalyze') {
            $patternAnalyzeData = array_merge($headInfo, [
                'site_title' => ($headInfo['site_name'] ?? '파워볼게임') . ' : 패턴별 분석',
            ]);
            echo view('home/patternAnalyze', $patternAnalyzeData);
            return;
        }
        // 2-1-4-1. 유머 등록 (관리자)
        else if ($this->request->getGet('view') === 'humorRegister') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {}

            if (!$objMember || (int)($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 등록 가능합니다.');
            }

            $humorModel = new HumorPost_Model();
            $humorModel->ensureTable();

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));

                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=humorRegister'), '제목과 내용을 입력해주세요.');
                }

                if (mb_strlen($title) > 200) $title = mb_substr($title, 0, 200);
                if (mb_strlen($content) > 5000) $content = mb_substr($content, 0, 5000);

                $auth = $this->communityAuthorUidNick($objMember);
                if ($auth === null) {
                    return $this->redirectWithMessage(site_furl('/?view=humorRegister'), '등록자 정보가 없습니다.');
                }

                $data = [
                    'mb_uid' => $auth['mb_uid'],
                    'mb_nickname' => $auth['mb_nickname'],
                    'title' => $title,
                    'content' => $content,
                    'comment_count' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $humorModel->insert($data);
                $newId = (int) $humorModel->getInsertID();
                return $this->response->redirect(site_furl('/?view=humorDetail&id=' . $newId));
            }

            return view('home/humorRegister');
        }

        // 2-1-4-1b. 상단 공지(고객센터) 등록 (관리자)
        else if ($this->request->getGet('view') === 'noticeBoardRegister') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {}

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 등록 가능합니다.');
            }

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));
                $noticeType = trim((string) $this->request->getPost('notice_type'));
                if (!in_array($noticeType, \App\Models\Notice_Model::siteBoardTypes(), true)) {
                    $noticeType = \App\Models\Notice_Model::TYPE_NOTICE;
                }

                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=noticeBoardRegister'), '제목과 내용을 입력해주세요.');
                }

                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 100000) {
                    $content = mb_substr($content, 0, 100000);
                }

                $mbUid = (string) ($objMember->mb_uid ?? '');
                if ($mbUid === '') {
                    return $this->redirectWithMessage(site_furl('/?view=noticeBoardRegister'), '등록자 정보가 없습니다.');
                }

                $now = date('Y-m-d H:i:s');
                $data = [
                    'notice_type' => $noticeType,
                    'notice_title' => $title,
                    'notice_content' => $content,
                    'notice_answer' => '',
                    'notice_mb_uid' => $mbUid,
                    'notice_emp_fid' => 0,
                    'notice_hit' => 0,
                    'notice_read_count' => 0,
                    'notice_time_create' => $now,
                    'notice_time_update' => $now,
                    'notice_state_active' => STATE_ACTIVE,
                    'notice_state_delete' => STATE_DISABLE,
                    'notice_client_delete' => STATE_DISABLE,
                ];

                $ok = $this->modelNotice->registerNotice($data);
                if (!$ok) {
                    return $this->redirectWithMessage(site_furl('/?view=noticeBoardRegister'), '등록에 실패했습니다.');
                }

                return view('home/noticeBoardRegisterSuccess');
            }

            return view('home/noticeBoardRegister');
        }

        // 2-1-4-1c. 상단 공지(고객센터) 수정 (관리자)
        else if ($this->request->getGet('view') === 'noticeBoardEdit') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {}

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 수정 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $post = $id > 0 ? $this->modelNotice->getBoardById($id) : null;
            if (!$post) {
                return $this->redirectWithMessage(site_furl('/'), '글을 찾을 수 없습니다.');
            }

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));
                $noticeType = trim((string) $this->request->getPost('notice_type'));
                if (!in_array($noticeType, \App\Models\Notice_Model::siteBoardTypes(), true)) {
                    $noticeType = \App\Models\Notice_Model::TYPE_NOTICE;
                }

                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=noticeBoardEdit&id=' . $id), '제목과 내용을 입력해주세요.');
                }

                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 100000) {
                    $content = mb_substr($content, 0, 100000);
                }

                $ok = $this->modelNotice->updateSiteBoard($id, [
                    'notice_type' => $noticeType,
                    'notice_title' => $title,
                    'notice_content' => $content,
                ]);
                if (!$ok) {
                    return $this->redirectWithMessage(site_furl('/?view=noticeBoardEdit&id=' . $id), '수정에 실패했습니다.');
                }

                return view('home/noticeBoardRegisterSuccess');
            }

            return view('home/noticeBoardEdit', ['post' => $post]);
        }

        // 2-1-4-1d. 상단 공지(고객센터) 삭제 (관리자)
        else if ($this->request->getGet('view') === 'noticeBoardDelete') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {}

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 삭제 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            if ($id > 0) {
                $this->modelNotice->softDeleteSiteBoard($id);
            }

            return $this->response->redirect(site_furl('frame/customerCenter'));
        }

        // 2-1-4-2. 유머 상세 (모두)
        else if ($this->request->getGet('view') === 'humorDetail') {
            $id = (int) $this->request->getGet('id');
            $humorModel = new HumorPost_Model();
            $post = $humorModel->findById($id);

            $isHumorAdmin = false;
            try {
                if (is_login(false)) {
                    $uid = $this->session->user_id ?? '';
                    $adm = $uid !== '' ? $this->modelMember->getByUid($uid) : null;
                    $isHumorAdmin = $adm && (int) ($adm->mb_level ?? 0) >= 100;
                }
            } catch (\Throwable $e) {
            }

            return view('home/humorDetail', [
                'post' => $post,
                'is_humor_admin' => $isHumorAdmin,
            ]);
        }
        // 2-1-4-3. 유머 수정 (관리자)
        else if ($this->request->getGet('view') === 'humorEdit') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {}

            if (!$objMember || (int)($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 수정 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $humorModel = new HumorPost_Model();
            $post = $humorModel->findById($id);
            if (!$post) {
                return $this->redirectWithMessage(site_furl('/'), '유머 글을 찾을 수 없습니다.');
            }

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));
                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=humorEdit&id=' . $id), '제목/내용을 입력하세요.');
                }
                if (mb_strlen($title) > 200) $title = mb_substr($title, 0, 200);
                if (mb_strlen($content) > 5000) $content = mb_substr($content, 0, 5000);

                $mbUidKeep = (string) ($post->mb_uid ?? ($objMember->mb_uid ?? ''));
                $mbNickKeep = trim((string) ($post->mb_nickname ?? ''));
                if ($mbNickKeep === '') {
                    $auth = $this->communityAuthorUidNick($objMember);
                    $mbNickKeep = $auth['mb_nickname'] ?? $mbUidKeep;
                }
                if ($mbNickKeep === '') {
                    $mbNickKeep = $mbUidKeep;
                }
                $ok = $humorModel->updateById($id, [
                    'title' => $title,
                    'content' => $content,
                    'comment_count' => (int) ($post->comment_count ?? 0),
                    'mb_uid' => $mbUidKeep,
                    'mb_nickname' => $mbNickKeep,
                    'created_at' => (string) ($post->created_at ?? date('Y-m-d H:i:s')),
                ]);

                return $this->response->redirect(site_furl('/?view=humorDetail&id=' . $id));
            }

            return view('home/humorEdit', ['post' => $post]);
        }

        // 2-1-4-4. 유머 삭제 (관리자)
        else if ($this->request->getGet('view') === 'humorDelete') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {}

            if (!$objMember || (int)($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 삭제 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $humorModel = new HumorPost_Model();
            $humorModel->deleteById($id);
            return $this->response->redirect(site_furl('/'));
        }

        // 자유게시판 등록 (로그인 회원)
        else if ($this->request->getGet('view') === 'freeRegister') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember) {
                return $this->redirectWithMessage(site_furl('/'), '회원 정보를 확인할 수 없습니다.');
            }

            $freeModel = new FreePost_Model();
            $freeModel->ensureTable();

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));

                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=freeRegister'), '제목과 내용을 입력해주세요.');
                }

                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 50000) {
                    $content = mb_substr($content, 0, 50000);
                }

                $auth = $this->communityAuthorUidNick($objMember);
                if ($auth === null) {
                    return $this->redirectWithMessage(site_furl('/?view=freeRegister'), '등록자 정보가 없습니다.');
                }

                $now = date('Y-m-d H:i:s');
                $data = [
                    'mb_uid' => $auth['mb_uid'],
                    'mb_nickname' => $auth['mb_nickname'],
                    'title' => $title,
                    'content' => $content,
                    'comment_count' => 0,
                    'wr_hit' => 0,
                    'wr_good' => 0,
                    'is_notice' => 0,
                    'created_at' => $now,
                ];
                $freeModel->insert($data);
                $newId = (int) $freeModel->getInsertID();

                return $this->response->redirect(site_furl('frame/communityBoard?bo_table=free&wr_id=' . $newId));
            }

            return view('home/freeRegister');
        }

        // 자유게시판 수정 (관리자)
        else if ($this->request->getGet('view') === 'freeEdit') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 수정 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $freeModel = new FreePost_Model();
            $freeModel->ensureTable();
            $post = $freeModel->find($id);
            if (!$post) {
                return $this->redirectWithMessage(site_furl('/'), '글을 찾을 수 없습니다.');
            }

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));
                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=freeEdit&id=' . $id), '제목/내용을 입력하세요.');
                }
                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 50000) {
                    $content = mb_substr($content, 0, 50000);
                }

                $mbUidKeep = (string) ($post->mb_uid ?? ($objMember->mb_uid ?? ''));
                $mbNickKeep = trim((string) ($post->mb_nickname ?? ''));
                if ($mbNickKeep === '') {
                    $auth = $this->communityAuthorUidNick($objMember);
                    $mbNickKeep = $auth['mb_nickname'] ?? $mbUidKeep;
                }
                if ($mbNickKeep === '') {
                    $mbNickKeep = $mbUidKeep;
                }

                $freeModel->update($id, [
                    'title' => $title,
                    'content' => $content,
                    'mb_uid' => $mbUidKeep,
                    'mb_nickname' => $mbNickKeep,
                    'comment_count' => (int) ($post->comment_count ?? 0),
                    'wr_hit' => (int) ($post->wr_hit ?? 0),
                    'wr_good' => (int) ($post->wr_good ?? 0),
                    'is_notice' => (int) ($post->is_notice ?? 0),
                    'created_at' => (string) ($post->created_at ?? date('Y-m-d H:i:s')),
                ]);

                return $this->response->redirect(site_furl('frame/communityBoard?bo_table=free&wr_id=' . $id));
            }

            return view('home/freeEdit', ['post' => $post]);
        }

        // 자유게시판 삭제 (관리자)
        else if ($this->request->getGet('view') === 'freeDelete') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 삭제 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $freeModel = new FreePost_Model();
            $freeModel->ensureTable();
            $freeModel->delete($id);

            return $this->response->redirect(site_furl('frame/communityBoard?bo_table=free'));
        }

        // 1:1문의사항 등록 (로그인 회원)
        else if ($this->request->getGet('view') === 'qnaRegister') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember) {
                return $this->redirectWithMessage(site_furl('/'), '회원 정보를 확인할 수 없습니다.');
            }

            $qnaModel = new QnaPost_Model();
            $qnaModel->ensureTable();

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));

                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=qnaRegister'), '제목과 내용을 입력해주세요.');
                }

                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 50000) {
                    $content = mb_substr($content, 0, 50000);
                }

                $auth = $this->communityAuthorUidNick($objMember);
                if ($auth === null) {
                    return $this->redirectWithMessage(site_furl('/?view=qnaRegister'), '등록자 정보가 없습니다.');
                }

                $now = date('Y-m-d H:i:s');
                $wrSecret = $this->request->getPost('wr_secret');
                $isSecret = ($wrSecret === '1' || $wrSecret === 'on' || $wrSecret === 'Y');
                $data = [
                    'mb_uid' => $auth['mb_uid'],
                    'mb_nickname' => $auth['mb_nickname'],
                    'title' => $title,
                    'content' => $content,
                    'comment_count' => 0,
                    'wr_hit' => 0,
                    'wr_good' => 0,
                    'is_notice' => 0,
                    'is_secret' => $isSecret ? 1 : 0,
                    'parent_id' => null,
                    'created_at' => $now,
                ];
                $qnaModel->insert($data);
                $newId = (int) $qnaModel->getInsertID();

                return $this->response->redirect(site_furl('frame/communityBoard?bo_table=qna&wr_id=' . $newId));
            }

            return view('home/qnaRegister');
        }

        // 1:1문의사항 수정 (관리자)
        else if ($this->request->getGet('view') === 'qnaEdit') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 수정 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $qnaModel = new QnaPost_Model();
            $qnaModel->ensureTable();
            $post = $qnaModel->find($id);
            if (!$post) {
                return $this->redirectWithMessage(site_furl('/'), '글을 찾을 수 없습니다.');
            }

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));
                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=qnaEdit&id=' . $id), '제목/내용을 입력하세요.');
                }
                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 50000) {
                    $content = mb_substr($content, 0, 50000);
                }

                $mbUidKeep = (string) ($post->mb_uid ?? ($objMember->mb_uid ?? ''));
                $mbNickKeep = trim((string) ($post->mb_nickname ?? ''));
                if ($mbNickKeep === '') {
                    $auth = $this->communityAuthorUidNick($objMember);
                    $mbNickKeep = $auth['mb_nickname'] ?? $mbUidKeep;
                }
                if ($mbNickKeep === '') {
                    $mbNickKeep = $mbUidKeep;
                }

                $noticeFlag = (int) ($post->is_notice ?? 0);
                $secPost = $this->request->getPost('is_secret');
                $isSecretUp = $noticeFlag === 1 ? 0 : (($secPost === '1' || $secPost === 'on') ? 1 : 0);
                $parentKeep = null;
                if (isset($post->parent_id) && $post->parent_id !== null && $post->parent_id !== '') {
                    $parentKeep = (int) $post->parent_id;
                }
                $qnaModel->update($id, [
                    'title' => $title,
                    'content' => $content,
                    'mb_uid' => $mbUidKeep,
                    'mb_nickname' => $mbNickKeep,
                    'comment_count' => (int) ($post->comment_count ?? 0),
                    'wr_hit' => (int) ($post->wr_hit ?? 0),
                    'wr_good' => (int) ($post->wr_good ?? 0),
                    'is_notice' => $noticeFlag,
                    'is_secret' => $isSecretUp,
                    'parent_id' => $parentKeep !== null && $parentKeep !== '' ? (int) $parentKeep : null,
                    'created_at' => (string) ($post->created_at ?? date('Y-m-d H:i:s')),
                ]);

                return $this->response->redirect(site_furl('frame/communityBoard?bo_table=qna&wr_id=' . $id));
            }

            return view('home/qnaEdit', ['post' => $post]);
        }

        // 1:1문의사항 삭제 (관리자)
        else if ($this->request->getGet('view') === 'qnaDelete') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 삭제 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $qnaModel = new QnaPost_Model();
            $qnaModel->ensureTable();
            $qnaModel->delete($id);

            return $this->response->redirect(site_furl('frame/communityBoard?bo_table=qna'));
        }

        // 기능개선요청 등록 (로그인 회원)
        else if ($this->request->getGet('view') === 'requestRegister') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember) {
                return $this->redirectWithMessage(site_furl('/'), '회원 정보를 확인할 수 없습니다.');
            }

            $requestModel = new RequestPost_Model();
            $requestModel->ensureTable();

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));

                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=requestRegister'), '제목과 내용을 입력해주세요.');
                }

                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 50000) {
                    $content = mb_substr($content, 0, 50000);
                }

                $auth = $this->communityAuthorUidNick($objMember);
                if ($auth === null) {
                    return $this->redirectWithMessage(site_furl('/?view=requestRegister'), '등록자 정보가 없습니다.');
                }

                $now = date('Y-m-d H:i:s');
                $data = [
                    'mb_uid' => $auth['mb_uid'],
                    'mb_nickname' => $auth['mb_nickname'],
                    'title' => $title,
                    'content' => $content,
                    'comment_count' => 0,
                    'wr_hit' => 0,
                    'wr_good' => 0,
                    'is_notice' => 0,
                    'created_at' => $now,
                ];
                $requestModel->insert($data);
                $newId = (int) $requestModel->getInsertID();

                return $this->response->redirect(site_furl('frame/communityBoard?bo_table=request&wr_id=' . $newId));
            }

            return view('home/requestRegister');
        }

        // 기능개선요청 수정 (관리자)
        else if ($this->request->getGet('view') === 'requestEdit') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 수정 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $requestModel = new RequestPost_Model();
            $requestModel->ensureTable();
            $post = $requestModel->find($id);
            if (!$post) {
                return $this->redirectWithMessage(site_furl('/'), '글을 찾을 수 없습니다.');
            }

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                $content = trim((string) $this->request->getPost('content'));
                if ($title === '' || $content === '') {
                    return $this->redirectWithMessage(site_furl('/?view=requestEdit&id=' . $id), '제목/내용을 입력하세요.');
                }
                if (mb_strlen($title) > 200) {
                    $title = mb_substr($title, 0, 200);
                }
                if (mb_strlen($content) > 50000) {
                    $content = mb_substr($content, 0, 50000);
                }

                $mbUidKeep = (string) ($post->mb_uid ?? ($objMember->mb_uid ?? ''));
                $mbNickKeep = trim((string) ($post->mb_nickname ?? ''));
                if ($mbNickKeep === '') {
                    $auth = $this->communityAuthorUidNick($objMember);
                    $mbNickKeep = $auth['mb_nickname'] ?? $mbUidKeep;
                }
                if ($mbNickKeep === '') {
                    $mbNickKeep = $mbUidKeep;
                }

                $requestModel->update($id, [
                    'title' => $title,
                    'content' => $content,
                    'mb_uid' => $mbUidKeep,
                    'mb_nickname' => $mbNickKeep,
                    'comment_count' => (int) ($post->comment_count ?? 0),
                    'wr_hit' => (int) ($post->wr_hit ?? 0),
                    'wr_good' => (int) ($post->wr_good ?? 0),
                    'is_notice' => (int) ($post->is_notice ?? 0),
                    'created_at' => (string) ($post->created_at ?? date('Y-m-d H:i:s')),
                ]);

                return $this->response->redirect(site_furl('frame/communityBoard?bo_table=request&wr_id=' . $id));
            }

            return view('home/requestEdit', ['post' => $post]);
        }

        // 기능개선요청 삭제 (관리자)
        else if ($this->request->getGet('view') === 'requestDelete') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {
            }

            if (!$objMember || (int) ($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 삭제 가능합니다.');
            }

            $id = (int) $this->request->getGet('id');
            $requestModel = new RequestPost_Model();
            $requestModel->ensureTable();
            $requestModel->delete($id);

            return $this->response->redirect(site_furl('frame/communityBoard?bo_table=request'));
        }

        // 2-1-5. 채팅방
        else if ($this->request->getGet('view') === 'chatRoom') {
            return $this->chat();
        }
        // 2-1-6. 포토 등록 (관리자)
        else if ($this->request->getGet('view') === 'photoRegister') {
            if (!is_login(false)) {
                return $this->redirectWithMessage(site_furl('/login'), '로그인 후 이용가능합니다.');
            }

            $objMember = null;
            try {
                $userId = $this->session->user_id ?? '';
                $objMember = $userId !== '' ? $this->modelMember->getByUid($userId) : null;
            } catch (\Throwable $e) {}

            if (!$objMember || (int)($objMember->mb_level ?? 0) < 100) {
                return $this->redirectWithMessage(site_furl('/'), '관리자만 등록 가능합니다.');
            }

            $photoModel = new BoardPhoto_Model();
            $photoModel->ensureTable();

            if ($this->request->getMethod() === 'post') {
                $title = trim((string) $this->request->getPost('title'));
                if ($title === '') $title = '포토';
                if (mb_strlen($title) > 200) $title = mb_substr($title, 0, 200);

                $file = $this->request->getFile('photo_file');
                if (!$file || !$file->isValid()) {
                    return $this->redirectWithMessage(site_furl('/?view=photoRegister'), '이미지 파일을 선택해주세요.');
                }

                $ext = strtolower((string)$file->getExtension());
                $allowExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowExt, true)) {
                    return $this->redirectWithMessage(site_furl('/?view=photoRegister'), '지원하지 않는 이미지 형식입니다.');
                }

                $tmpPath = $file->getTempName();
                $imgInfo = @getimagesize($tmpPath);
                if (!$imgInfo || !isset($imgInfo[0], $imgInfo[1])) {
                    return $this->redirectWithMessage(site_furl('/?view=photoRegister'), '이미지 정보를 읽을 수 없습니다.');
                }
                $w = (int)$imgInfo[0];
                $h = (int)$imgInfo[1];

                $uploadDir = FCPATH . 'uploads/photos';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }

                $newName = $file->getRandomName();
                if (!$file->move($uploadDir, $newName, true)) {
                    return $this->redirectWithMessage(site_furl('/?view=photoRegister'), '파일 저장에 실패했습니다.');
                }
                // 어떤 비율/크기의 이미지든 서버에서 200x200으로 중앙 크롭+리사이즈
                try {
                    $savedPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;
                    \Config\Services::image()
                        ->withFile($savedPath)
                        ->fit(200, 200, 'center')
                        ->save($savedPath);
                } catch (\Throwable $e) {
                    @unlink($uploadDir . DIRECTORY_SEPARATOR . $newName);
                    return $this->redirectWithMessage(site_furl('/?view=photoRegister'), '이미지 변환에 실패했습니다.');
                }

                $photoAuth = $this->communityPhotoAuthorFields($objMember);
                if ($photoAuth === null) {
                    return $this->redirectWithMessage(site_furl('/?view=photoRegister'), '회원 번호(mb_fid)를 확인할 수 없습니다.');
                }
                $photoModel->insert([
                    'wr_id' => 0,
                    'title' => $title,
                    'file_path' => $newName,
                    'created_at' => date('Y-m-d H:i:s'),
                    'mb_uid' => $photoAuth['mb_uid'],
                    'mb_nickname' => $photoAuth['mb_nickname'],
                ]);
                $newId = (int)$photoModel->getInsertID();
                if ($newId > 0) {
                    $photoModel->update($newId, ['wr_id' => $newId]);
                }
                // 등록 직후 메인(부모) 페이지를 갱신해서 포토 탭에 즉시 반영
                return view('home/photoRegisterSuccess', [
                    'title' => $title,
                ]);
            }

            return view('home/photoRegister');
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
                'can_access_analysis' => true,
            ]);
            echo view('home/dayLog', $dayLogData);
            return;
        }
        // 2-3. 미니뷰 iframe 전용 (dayLog 내 "미니뷰 열기" 시 로드)
        else if($this->request->getGet('view') === 'powerballMiniView'){
            $headInfo   = $this->getSiteConf();
            $lastRound  = '';
            $lastResult = '-';
            /** @var string JS updateResult()와 동일 마크업 — .result 노란색 상속을 피하기 위한 인라인 스타일 */
            $lastResultHtml = '';
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
                    $parts = [];
                    foreach ($nums as $n) {
                        $parts[] = sprintf('%02d', (int) $n);
                    }
                    $lastResultHtml = implode(', ', $parts)
                        . ', <span style="color:#66ffff;" class="b">' . $pb . '</span>, <span style="color:#fff;" class="b">' . $sum . '</span>';
                    $lastResult  = implode(', ', $parts) . ', ' . $pb . ', ' . $sum;
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
                'remain_time'      => $this->getRemainSecondsUntilNextDraw(),
                'time_round'       => $time_round,
                'last_round'       => $lastRound,
                'last_result'      => $lastResult,
                'last_result_html' => $lastResultHtml,
                'current_balls'    => $currentBalls,
                'prev_balls'       => $prevBalls,
            ]);
            echo view('home/powerballMiniView', $miniViewData);
            return;
        }
        // 3. 메인 대시보드 화면 띄우기
        else {
            $objMember = null;
            // 메인/box-login은 세션만으로 판단 (쿠키 미설정 시에도 로그인 UI 표시)
            $loggedIn = is_login(false);
            writeLog("[index] main dashboard is_login(false)=" . ($loggedIn ? '1' : '0'));
            if ($loggedIn) {
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
            $humorModel = new HumorPost_Model();
            $freePostModel = new FreePost_Model();
            $list_humor = $humorModel->getLatest(12);
            $list_pick  = $boardWriteModel->getListForMain('pick', 10);
            $list_free  = $freePostModel->getLatestForMain(10);
            $boardPhotoModel = new BoardPhoto_Model();
            $list_photo = $boardPhotoModel->getListForMain(14);

            $is_humor_admin = false;
            $is_notice_admin = false;
            if ($objMember && isset($objMember->mb_level) && (int) $objMember->mb_level >= 100) {
                $is_humor_admin = true;
                $is_notice_admin = true;
            }
            $navInfo = getNavInfo($objMember);
            $viewData = array_merge($headInfo, $navInfo, [
                'objMember'  => $objMember,
                'boards'     => $boards,
                'list_humor' => $list_humor,
                'list_pick'  => $list_pick,
                'list_free'  => $list_free,
                'list_photo' => $list_photo,
                'is_humor_admin' => $is_humor_admin,
                'is_notice_admin' => $is_notice_admin,
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
        [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);

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
     * 최근 N회 기준 전체 분석 데이터 (latestLog refreshAnalyse용)
     * POST view=action, action=ajaxPowerballAnalyse, roundCnt=50~2000(50단위)
     */
    public function ajaxPowerballAnalyse()
    {
        $roundCnt = (int) $this->request->getPost('roundCnt');
        if ($roundCnt < 50 || $roundCnt > 2000) {
            $roundCnt = 300;
        } else {
            $roundCnt = (int) (round($roundCnt / 50) * 50);
        }

        $drawModel = new \App\Models\PowerballDraw_Model();
        $rows = $drawModel->orderBy('round', 'DESC')->findAll($roundCnt);
        usort($rows, static fn($a, $b) => ((int) ($a->round ?? 0)) <=> ((int) ($b->round ?? 0)));

        $total = count($rows);
        $cnt = [
            'powerballOdd' => 0, 'powerballEven' => 0, 'powerballUnder' => 0, 'powerballOver' => 0,
            'numberOdd' => 0, 'numberEven' => 0, 'numberUnder' => 0, 'numberOver' => 0,
            'numberBig' => 0, 'numberMiddle' => 0, 'numberSmall' => 0,
        ];
        $types = [];
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
            $per[$k] = $total > 0 ? (string) round($v / $total * 100, 2) : '0';
        }
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
        $drawModel->ensureDailyRoundColumn();

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

            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $rows = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'DESC')
                ->findAll($perPage, $offset);

            $content = [];
            foreach ($rows as $i => $draw) {
                $content[] = \App\Models\PowerballDraw_Model::formatForDayLogRow($draw, $offset + $i, null);
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

            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $draw = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->where('round >', $afterRound)
                ->orderBy('round', 'ASC')
                ->first();

            if (!$draw) {
                return $this->response->setJSON(['state' => 'success', 'round' => $afterRound, 'content' => []]);
            }

            $row = \App\Models\PowerballDraw_Model::formatForDayLogRow($draw, 0, null);
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

        if ($actionType === 'latestLog') {
            $page = (int) $this->request->getPost('page');
            if ($page < 0) {
                $page = 0;
            }
            $roundCnt = (int) $this->request->getPost('roundCnt');
            if ($roundCnt < 50 || $roundCnt > 2000) {
                $roundCnt = 300;
            } else {
                $roundCnt = (int) (round($roundCnt / 50) * 50);
            }
            $perPage = $roundCnt;
            $offset  = $page * $perPage;

            $rows = $drawModel
                ->orderBy('round', 'DESC')
                ->findAll($perPage, $offset);

            $content = [];
            foreach ($rows as $i => $draw) {
                $content[] = \App\Models\PowerballDraw_Model::formatForDayLogRow($draw, $offset + $i, null);
            }
            $content = \array_slice($content, 0, $perPage);

            $latest = $drawModel->orderBy('round', 'DESC')->first();
            $round  = $latest ? (int) $latest->round : 0;

            return $this->response->setJSON([
                'content' => $content,
                'endYN'   => count($content) < $perPage ? 'Y' : 'N',
                'pageVal' => $page,
                'round'   => $round,
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
        $mode       = (string) $this->request->getPost('mode');
        $roundCnt   = (int) $this->request->getPost('roundCnt');
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        if ($roundCnt < 50 || $roundCnt > 2000) {
            $roundCnt = 300;
        } else {
            $roundCnt = (int) (round($roundCnt / 50) * 50);
        }

        if ($actionType === 'oddEven' && $division === 'powerball') {
            $html = $this->buildPowerballOddEvenPatternHtml($date, $mode, $roundCnt);
            return $this->response->setJSON(['content' => $html]);
        }
        if ($actionType === 'oddEven' && $division === 'number') {
            $html = $this->buildNumberSumOddEvenPatternHtml($date, $mode, $roundCnt);
            return $this->response->setJSON(['content' => $html]);
        }

        if ($actionType === 'underOver' && $division === 'powerball') {
            $html = $this->buildPowerballUnderOverPatternHtml($date, $mode, $roundCnt);
            return $this->response->setJSON(['content' => $html]);
        }
        if ($actionType === 'underOver' && $division === 'number') {
            $html = $this->buildNumberSumUnderOverPatternHtml($date, $mode, $roundCnt);
            return $this->response->setJSON(['content' => $html]);
        }

        if ($actionType === 'period' && $division === 'number') {
            $html = $this->buildNumberSumPeriodPatternHtml($date, $mode, $roundCnt);
            return $this->response->setJSON(['content' => $html]);
        }

        return $this->response->setJSON(['content' => '']);
    }

    /**
     * 패턴별 분석 페이지용: 최근 회차들의 패턴 이미지 셋 반환
     * - actionType: powerballOddEven | powerballUnderOver | numberOddEven | numberUnderOver | numberPeriod
     * - maxCnt: 기본 26
     */
    public function ajaxPatternSet()
    {
        $actionType = (string) $this->request->getPost('actionType');
        $maxCnt = (int) $this->request->getPost('maxCnt');
        if ($maxCnt < 1 || $maxCnt > 26) $maxCnt = 26;

        $drawModel = new \App\Models\PowerballDraw_Model();
        $rows = $drawModel->orderBy('round', 'DESC')->findAll($maxCnt);
        $rows = array_reverse($rows); // oldest -> newest for UI

        $items = [];
        foreach ($rows as $draw) {
            $value = $this->getPatternValueForAnalyzeType($actionType, $draw);
            if ($value === null) continue;
            $items[] = [
                'round' => (int) ($draw->round ?? 0),
                'value' => $value,
                'img'   => 'sp-' . $value,
            ];
        }

        return $this->response->setJSON([
            'state' => 'success',
            'items' => $items,
        ]);
    }

    /**
     * 패턴별 분석 페이지용: 특정 패턴 시퀀스 검색 결과 반환
     * - patternSeq: ['odd'|'even'|'under'|'over'|'small'|'middle'|'big', ...] 길이 = patternCnt
     * - page: 0 기반 페이지
     */
    public function ajaxPatternSearch()
    {
        $actionType = (string) $this->request->getPost('actionType');
        $patternCnt = (int) $this->request->getPost('patternCnt');
        if ($patternCnt < 1 || $patternCnt > 26) $patternCnt = 10;

        $page = (int) $this->request->getPost('page');
        if ($page < 0) $page = 0;

        $perPage = (int) $this->request->getPost('perPage');
        if ($perPage < 5 || $perPage > 50) $perPage = 20;

        $patternSeqRaw = $this->request->getPost('patternSeq');
        $patternSeq = [];
        if (is_string($patternSeqRaw)) {
            $decoded = json_decode($patternSeqRaw, true);
            if (is_array($decoded)) $patternSeq = $decoded;
        } elseif (is_array($patternSeqRaw)) {
            $patternSeq = $patternSeqRaw;
        }
        $patternSeq = array_values(array_map(static function ($v) {
            return is_string($v) ? trim($v) : '';
        }, $patternSeq));
        $patternSeq = array_values(array_filter($patternSeq, static fn($v) => $v !== ''));

        if ($patternSeq === [] || $patternCnt <= 0) {
            return $this->response->setJSON([
                'state' => 'success',
                'titleYN' => $page === 0 ? 'Y' : 'N',
                'content' => null,
                'endYN' => 'Y',
            ]);
        }
        $patternCnt = min($patternCnt, count($patternSeq));

        // 검색 성능을 위해 최근 데이터 범위만 사용 (DB 크기에 따라 조절 가능)
        $searchLimit = 3000;
        $drawModel = new \App\Models\PowerballDraw_Model();
        $rows = $drawModel->orderBy('round', 'DESC')->findAll($searchLimit);
        $rows = array_reverse($rows); // oldest -> newest for index scanning

        $rowCount = count($rows);
        if ($rowCount < $patternCnt + 1) {
            return $this->response->setJSON([
                'state' => 'success',
                'titleYN' => $page === 0 ? 'Y' : 'N',
                'content' => null,
                'endYN' => 'Y',
            ]);
        }

        $patternVals = [];
        foreach ($rows as $draw) {
            $patternVals[] = $this->getPatternValueForAnalyzeType($actionType, $draw);
        }

        $offset = $page * $perPage;
        $matchIdx = 0;
        $results = [];

        // 최신 결과부터 보여주기 위해 역순 스캔
        for ($i = $rowCount - $patternCnt - 1; $i >= 0; $i--) {
            $matched = true;
            for ($j = 0; $j < $patternCnt; $j++) {
                if (($patternVals[$i + $j] ?? null) !== $patternSeq[$j]) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                if ($matchIdx >= $offset && count($results) < $perPage) {
                    $nextDraw = $rows[$i + $patternCnt];
                    $nextValue = $patternVals[$i + $patternCnt] ?? null;

                    $subList = [];
                    for ($j = 0; $j < $patternCnt; $j++) {
                        $d = $rows[$i + $j];
                        $v = $patternVals[$i + $j];
                        $subRound = (int) ($d->round ?? 0);
                        $subList[] = [
                            'round' => $subRound % 1000,
                            'img'   => $v ? ('sp-' . $v) : '',
                        ];
                    }

                    $nextRound = (int) ($nextDraw->round ?? 0);
                    $results[] = [
                        'trClass' => ($matchIdx % 2 === 0) ? 'trOdd' : 'trEven',
                        'date' => $nextDraw->drawn_at ? \App\Models\PowerballDraw_Model::gameDayKeyKstFromDrawnAt((string) $nextDraw->drawn_at) : '',
                        'subList' => $subList,
                        'nextResult_round' => $nextRound % 1000,
                        'nextResult_img' => $nextValue ? ('sp-' . $nextValue) : '',
                    ];
                }
                $matchIdx++;
            }
        }

        $totalMatches = $matchIdx;
        $endYN = ($offset + $perPage >= $totalMatches) ? 'Y' : 'N';

        return $this->response->setJSON([
            'state' => 'success',
            'titleYN' => $page === 0 ? 'Y' : 'N',
            'content' => count($results) ? $results : null,
            'endYN' => $endYN,
        ]);
    }

    /**
     * 패턴별 분석 페이지에서 사용하는 패턴 값 계산
     * 반환값은 template/jS에서 쓰는 'odd'|'even'|'under'|'over'|'small'|'middle'|'big'
     */
    protected function getPatternValueForAnalyzeType(string $actionType, object $draw): ?string
    {
        $pb = (int) ($draw->powerball ?? 0);
        $sum = (int) ($draw->ball_sum ?? 0);

        if ($actionType === 'powerballOddEven') {
            return ($pb % 2 === 1) ? 'odd' : 'even';
        }
        if ($actionType === 'powerballUnderOver') {
            return ($pb <= 4) ? 'under' : 'over';
        }
        if ($actionType === 'numberOddEven') {
            return ($sum % 2 === 1) ? 'odd' : 'even';
        }
        if ($actionType === 'numberUnderOver') {
            return ($sum <= 72) ? 'under' : 'over';
        }
        if ($actionType === 'numberPeriod') {
            if ($sum <= 64) return 'small';
            if ($sum <= 80) return 'middle';
            return 'big';
        }

        return null;
    }

    /**
     * 파워볼 기준 홀짝 패턴 HTML (선배님 페이지 구조: patternTable > tr > td > table.innerTable)
     * - 홀/짝이 바뀔 때마다 새 컬럼(블록). 각 블록: th(홀|짝), 회차 div들(연속 개수만큼), sum, order
     */
    protected function buildPowerballOddEvenPatternHtml(string $date, string $mode = '', int $roundCnt = 300): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        if ($mode === 'latestLog') {
            $rows = $drawModel->orderBy('round', 'DESC')->findAll($roundCnt);
            usort($rows, static fn($a, $b) => ((int) ($a->round ?? 0)) <=> ((int) ($b->round ?? 0)));
        } else {
            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $rows      = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'ASC')
                ->findAll();
        }

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
     * 파워볼 기준 언더오버 패턴 HTML (기준 4.5: 0~4 언더, 5~9 오버)
     */
    protected function buildPowerballUnderOverPatternHtml(string $date, string $mode = '', int $roundCnt = 300): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        if ($mode === 'latestLog') {
            $rows = $drawModel->orderBy('round', 'DESC')->findAll($roundCnt);
            usort($rows, static fn($a, $b) => ((int) ($a->round ?? 0)) <=> ((int) ($b->round ?? 0)));
        } else {
            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $rows      = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'ASC')
                ->findAll();
        }

        $groups = [];
        foreach ($rows as $draw) {
            $round = (int) ($draw->round ?? 0);
            $pb   = (int) ($draw->powerball ?? 0);
            $type = $pb <= 4 ? 'under' : 'over';
            if (!empty($groups) && $groups[count($groups) - 1][0] === $type) {
                $groups[count($groups) - 1][1][] = $round;
            } else {
                $groups[] = [$type, [$round]];
            }
        }
        return $this->buildUnderOverPatternTable($groups);
    }

    /**
     * 숫자합 기준 언더오버 패턴 HTML (기준 72.5: 72.5 미만 언더, 72.5 초과 오버)
     */
    protected function buildNumberSumUnderOverPatternHtml(string $date, string $mode = '', int $roundCnt = 300): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        if ($mode === 'latestLog') {
            $rows = $drawModel->orderBy('round', 'DESC')->findAll($roundCnt);
            usort($rows, static fn($a, $b) => ((int) ($a->round ?? 0)) <=> ((int) ($b->round ?? 0)));
        } else {
            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $rows      = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'ASC')
                ->findAll();
        }

        $groups = [];
        foreach ($rows as $draw) {
            $round = (int) ($draw->round ?? 0);
            $sum  = (int) ($draw->ball_sum ?? 0);
            $type = $sum <= 72 ? 'under' : 'over';
            if (!empty($groups) && $groups[count($groups) - 1][0] === $type) {
                $groups[count($groups) - 1][1][] = $round;
            } else {
                $groups[] = [$type, [$round]];
            }
        }
        return $this->buildUnderOverPatternTable($groups);
    }

    /**
     * 숫자합 기준 대/중/소 패턴 HTML
     * - 소: 15~64, 중: 65~80, 대: 81~130
     */
    protected function buildNumberSumPeriodPatternHtml(string $date, string $mode = '', int $roundCnt = 300): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        if ($mode === 'latestLog') {
            $rows = $drawModel->orderBy('round', 'DESC')->findAll($roundCnt);
            usort($rows, static fn($a, $b) => ((int) ($a->round ?? 0)) <=> ((int) ($b->round ?? 0)));
        } else {
            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $rows      = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'ASC')
                ->findAll();
        }

        $groups = [];
        foreach ($rows as $draw) {
            $round = (int) ($draw->round ?? 0);
            $sum   = (int) ($draw->ball_sum ?? 0);
            if ($sum <= 64) {
                $type = 'small';
            } elseif ($sum <= 80) {
                $type = 'middle';
            } else {
                $type = 'big';
            }
            if (!empty($groups) && $groups[count($groups) - 1][0] === $type) {
                $groups[count($groups) - 1][1][] = $round;
            } else {
                $groups[] = [$type, [$round]];
            }
        }

        return $this->buildPeriodPatternTable($groups);
    }

    /**
     * 언더오버 패턴 공통 테이블 HTML (그룹 배열 → patternTable 마크업, title_under/title_over)
     * 모든 컬럼의 데이터 행 수를 동일하게 맞춤 (최대 행 수 기준 패딩)
     */
    protected function buildUnderOverPatternTable(array $groups): string
    {
        $maxRows = 0;
        foreach ($groups as $group) {
            $cnt = count($group[1]);
            if ($cnt > $maxRows) {
                $maxRows = $cnt;
            }
        }
        $cells = [];
        $order = 0;
        foreach ($groups as $group) {
            $order++;
            list($type, $rounds) = $group;
            $titleClass = $type === 'under' ? 'title_under' : 'title_over';
            $titleText  = $type === 'under' ? '언더' : '오버';
            $inner = '<tr><th class="' . $titleClass . '">' . $titleText . '</th></tr>';
            foreach ($rounds as $r) {
                $inner .= '<tr><td><div class="' . $type . '">' . ((int) $r % 1000) . '</div></td></tr>';
            }
            $pad = $maxRows - count($rounds);
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
     * 숫자합 기준 홀짝 패턴 HTML (ball_sum 기준, 구조는 파워볼 홀짝과 동일)
     */
    protected function buildNumberSumOddEvenPatternHtml(string $date, string $mode = '', int $roundCnt = 300): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        if ($mode === 'latestLog') {
            $rows = $drawModel->orderBy('round', 'DESC')->findAll($roundCnt);
            usort($rows, static fn($a, $b) => ((int) ($a->round ?? 0)) <=> ((int) ($b->round ?? 0)));
        } else {
            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $rows      = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'ASC')
                ->findAll();
        }

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
     * 모든 컬럼의 데이터 행 수를 동일하게 맞춤 (최대 행 수 기준 패딩)
     */
    protected function buildOddEvenPatternTable(array $groups): string
    {
        $maxRows = 0;
        foreach ($groups as $group) {
            $cnt = count($group[1]);
            if ($cnt > $maxRows) {
                $maxRows = $cnt;
            }
        }
        $cells = [];
        $order = 0;
        foreach ($groups as $group) {
            $order++;
            list($type, $rounds) = $group;
            $titleClass = $type === 'odd' ? 'title_odd' : 'title_even';
            $titleText  = $type === 'odd' ? '홀' : '짝';
            $inner = '<tr><th class="' . $titleClass . '">' . $titleText . '</th></tr>';
            foreach ($rounds as $r) {
                $inner .= '<tr><td><div class="' . $type . '">' . ((int) $r % 1000) . '</div></td></tr>';
            }
            $pad = $maxRows - count($rounds);
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
     * 대/중/소 패턴 공통 테이블 HTML (그룹 배열 → patternTable 마크업)
     * 모든 컬럼의 데이터 행 수를 동일하게 맞춤 (최대 행 수 기준 패딩)
     */
    protected function buildPeriodPatternTable(array $groups): string
    {
        $maxRows = 0;
        foreach ($groups as $group) {
            $cnt = count($group[1]);
            if ($cnt > $maxRows) {
                $maxRows = $cnt;
            }
        }

        $cells = [];
        $order = 0;
        foreach ($groups as $group) {
            $order++;
            list($type, $rounds) = $group;

            if ($type === 'small') {
                $titleClass = 'title_odd';   // blue
                $titleText  = '소';
            } elseif ($type === 'middle') {
                $titleClass = 'title_middle'; // green
                $titleText  = '중';
            } else {
                $titleClass = 'title_even';  // red
                $titleText  = '대';
            }

            $inner = '<tr><th class="' . $titleClass . '">' . $titleText . '</th></tr>';
            foreach ($rounds as $r) {
                $inner .= '<tr><td><div class="' . $type . '">' . ((int) $r % 1000) . '</div></td></tr>';
            }
            $pad = $maxRows - count($rounds);
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
     */
    public function ajaxSixPattern()
    {
        $actionType = $this->request->getPost('actionType');
        $division   = $this->request->getPost('division');
        $patternCnt = (int) $this->request->getPost('patternCnt');
        $date       = $this->request->getPost('date');
        $mode       = (string) $this->request->getPost('mode');
        $roundCnt   = (int) $this->request->getPost('roundCnt');
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        if ($roundCnt < 50 || $roundCnt > 2000) {
            $roundCnt = 300;
        } else {
            $roundCnt = (int) (round($roundCnt / 50) * 50);
        }
        if ($patternCnt < 1 || $patternCnt > 6) {
            $patternCnt = 6;
        }

        if ((($actionType === 'oddEven' || $actionType === 'underOver') && ($division === 'powerball' || $division === 'number'))
            || ($actionType === 'period' && $division === 'number')) {
            $html = $this->buildSixPatternHtml($date, $patternCnt, $division, $actionType, $mode, $roundCnt);
            return $this->response->setJSON(['content' => $html]);
        }

        return $this->response->setJSON(['content' => '']);
    }

    /**
     * 육매 패턴 HTML (선배님 구조: N매씩 한 컬럼, 각 셀에 회차 마지막 3자리 + odd/even, under/over, 또는 대/중/소, 마지막 행 order)
     * @param string $actionType 'oddEven' | 'underOver' | 'period'(숫자합 대중소만)
     */
    protected function buildSixPatternHtml(string $date, int $patternCnt, string $division, string $actionType = 'oddEven', string $mode = '', int $roundCnt = 300): string
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        if ($mode === 'latestLog') {
            $rows = $drawModel->orderBy('round', 'DESC')->findAll($roundCnt);
            usort($rows, static fn($a, $b) => ((int) ($a->round ?? 0)) <=> ((int) ($b->round ?? 0)));
        } else {
            [$dateFrom, $dateTo] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerDate($date);
            $rows      = $drawModel
                ->where('drawn_at >=', $dateFrom)
                ->where('drawn_at <=', $dateTo)
                ->orderBy('round', 'ASC')
                ->findAll();
        }

        $cells   = [];
        $chunks  = array_chunk($rows, $patternCnt);
        $order   = 0;
        foreach ($chunks as $chunk) {
            $order++;
            $inner = '';
            foreach ($chunk as $draw) {
                $round = (int) ($draw->round ?? 0);
                if ($actionType === 'period' && $division === 'number') {
                    $sum   = (int) ($draw->ball_sum ?? 0);
                    if ($sum <= 64) {
                        $type  = 'small';
                        $title = '소 (15~64)';
                    } elseif ($sum <= 80) {
                        $type  = 'middle';
                        $title = '중 (65~80)';
                    } else {
                        $type  = 'big';
                        $title = '대 (81~130)';
                    }
                } elseif ($actionType === 'underOver') {
                    if ($division === 'powerball') {
                        $pb   = (int) ($draw->powerball ?? 0);
                        $type = $pb <= 4 ? 'under' : 'over';
                        $title = $type === 'under' ? '언더(0~4)' : '오버(5~9)';
                    } else {
                        $sum  = (int) ($draw->ball_sum ?? 0);
                        $type = $sum <= 72 ? 'under' : 'over';
                        $title = $type === 'under' ? '언더(72.5 미만)' : '오버(72.5 초과)';
                    }
                } else {
                    if ($division === 'powerball') {
                        $isOdd = ((int) ($draw->powerball ?? 0) % 2 === 1);
                    } else {
                        $isOdd = ((int) ($draw->ball_sum ?? 0) % 2 === 1);
                    }
                    $type  = $isOdd ? 'odd' : 'even';
                    $title = $isOdd ? '홀' : '짝';
                }
                $inner .= '<tr><td><div class="' . $type . '" title="' . esc($title) . '">' . ((int) $round % 1000) . '</div></td></tr>';
            }
            // 모든 컬럼 행 수 동일: N매일 때 부족한 컬럼은 빈 행으로 패딩
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
     * 기간별 분석 집계
     * - range 전체 집계(전체 분석 데이터)
     * - 일자별 집계(표 하단)
     * - 검색 기간내 최대/최소(무효처리 있는 날짜 제외 → 일자별 총회수 288 미만인 날짜 제외)
     */
    protected function computePeriodLogStats(string $startDate, string $endDate): array
    {
        $drawModel = new \App\Models\PowerballDraw_Model();
        [$from, $to] = \App\Models\PowerballDraw_Model::gameDayWindowFromPickerRange($startDate, $endDate);
        $rows = $drawModel
            ->where('drawn_at >=', $from)
            ->where('drawn_at <=', $to)
            ->orderBy('drawn_at', 'ASC')
            ->findAll();

        $byDate = [];
        foreach ($rows as $draw) {
            $d = $draw->drawn_at ? \App\Models\PowerballDraw_Model::gameDayKeyKstFromDrawnAt((string) $draw->drawn_at) : '';
            if ($d === '') continue;
            if (!isset($byDate[$d])) $byDate[$d] = [];
            $byDate[$d][] = $draw;
        }
        krsort($byDate); // 최신 날짜부터 표에 보여주기 위해

        $overall = $this->computeStatsForDraws($rows);
        $daily = [];
        $validDaily = [];
        foreach ($byDate as $d => $list) {
            $s = $this->computeStatsForDraws($list);
            $s['date'] = $d;
            $daily[] = $s;
            // 무효처리 있는 날짜 제외: 해당 날짜 추첨이 288회 미만이면 제외 (선택적). 데이터가 있으면 모두 포함해 최대/최소 표시
            if (($s['total'] ?? 0) >= 1) {
                $validDaily[] = $s;
            }
        }

        $maxMin = $this->computeMaxMinByDay($validDaily);

        return [
            'overall' => $overall,
            'daily' => $daily,
            'maxMin' => $maxMin,
        ];
    }

    protected function computeStatsForDraws(array $draws): array
    {
        $total = count($draws);
        $cnt = [
            'pbOdd' => 0, 'pbEven' => 0, 'pbUnder' => 0, 'pbOver' => 0,
            'sumOdd' => 0, 'sumEven' => 0, 'sumUnder' => 0, 'sumOver' => 0,
            'big' => 0, 'middle' => 0, 'small' => 0,
        ];

        $seq = [
            'pbOdd' => [],
            'pbUnder' => [],
            'sumOdd' => [],
            'sumUnder' => [],
            'size' => [],
        ];

        foreach ($draws as $draw) {
            $pb = (int) ($draw->powerball ?? 0);
            $sum = (int) ($draw->ball_sum ?? 0);

            $pbOdd = ($pb % 2 === 1);
            $pbUnder = ($pb <= 4);
            $sumOdd = ($sum % 2 === 1);
            $sumUnder = ($sum <= 72);
            if ($sum <= 64) $size = 'small';
            elseif ($sum <= 80) $size = 'middle';
            else $size = 'big';

            $cnt['pbOdd'] += $pbOdd ? 1 : 0;
            $cnt['pbEven'] += $pbOdd ? 0 : 1;
            $cnt['pbUnder'] += $pbUnder ? 1 : 0;
            $cnt['pbOver'] += $pbUnder ? 0 : 1;

            $cnt['sumOdd'] += $sumOdd ? 1 : 0;
            $cnt['sumEven'] += $sumOdd ? 0 : 1;
            $cnt['sumUnder'] += $sumUnder ? 1 : 0;
            $cnt['sumOver'] += $sumUnder ? 0 : 1;

            $cnt[$size] += 1;

            $seq['pbOdd'][] = $pbOdd ? 'odd' : 'even';
            $seq['pbUnder'][] = $pbUnder ? 'under' : 'over';
            $seq['sumOdd'][] = $sumOdd ? 'odd' : 'even';
            $seq['sumUnder'][] = $sumUnder ? 'under' : 'over';
            $seq['size'][] = $size;
        }

        $per = [];
        foreach ($cnt as $k => $v) {
            $per[$k] = $total > 0 ? round($v / $total * 100, 2) : 0;
        }

        $streak = [
            'pbOdd' => $this->maxConsecutive($seq['pbOdd'], 'odd'),
            'pbEven' => $this->maxConsecutive($seq['pbOdd'], 'even'),
            'pbUnder' => $this->maxConsecutive($seq['pbUnder'], 'under'),
            'pbOver' => $this->maxConsecutive($seq['pbUnder'], 'over'),
            'sumOdd' => $this->maxConsecutive($seq['sumOdd'], 'odd'),
            'sumEven' => $this->maxConsecutive($seq['sumOdd'], 'even'),
            'sumUnder' => $this->maxConsecutive($seq['sumUnder'], 'under'),
            'sumOver' => $this->maxConsecutive($seq['sumUnder'], 'over'),
            'big' => $this->maxConsecutive($seq['size'], 'big'),
            'middle' => $this->maxConsecutive($seq['size'], 'middle'),
            'small' => $this->maxConsecutive($seq['size'], 'small'),
        ];

        return [
            'total' => $total,
            'cnt' => $cnt,
            'per' => $per,
            'streak' => $streak,
        ];
    }

    protected function maxConsecutive(array $seq, string $value): int
    {
        $max = 0;
        $cur = 0;
        foreach ($seq as $v) {
            if ($v === $value) {
                $cur++;
                if ($cur > $max) $max = $cur;
            } else {
                $cur = 0;
            }
        }
        return $max;
    }

    protected function computeMaxMinByDay(array $validDaily): array
    {
        $metrics = [
            ['key' => 'pbOdd', 'label' => '파워볼', 'icon' => 'sp-odd'],
            ['key' => 'pbEven', 'label' => '파워볼', 'icon' => 'sp-even'],
            ['key' => 'sumOdd', 'label' => '숫자합', 'icon' => 'sp-odd'],
            ['key' => 'sumEven', 'label' => '숫자합', 'icon' => 'sp-even'],
            ['key' => 'big', 'label' => '숫자합', 'icon' => 'big'],
            ['key' => 'middle', 'label' => '숫자합', 'icon' => 'middle'],
            ['key' => 'small', 'label' => '숫자합', 'icon' => 'small'],
        ];

        $out = [];
        foreach ($metrics as $m) {
            $key = $m['key'];
            $max = null;
            $min = null;
            foreach ($validDaily as $d) {
                $c = (int) ($d['cnt'][$key] ?? 0);
                $p = (float) ($d['per'][$key] ?? 0);
                $row = ['date' => $d['date'], 'cnt' => $c, 'per' => $p];
                if ($max === null || $c > $max['cnt']) $max = $row;
                if ($min === null || $c < $min['cnt']) $min = $row;
            }
            $out[] = [
                'key' => $key,
                'label' => $m['label'],
                'icon' => $m['icon'],
                'max' => $max,
                'min' => $min,
            ];
        }
        return $out;
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
            'can_access_analysis' => true,
            'flash_message' => $this->session->getFlashdata('message'),
        ]);
        $html = view('home/dayLog', $dayLogData);
        $this->response->setBody($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        return $this->response;
    }

    /**
     * 출석 AJAX (선배님 POST: view=action&action=attendance&actionType=insert)
     */
    private function attendanceJsonInsert(Attendance_Model $model): \CodeIgniter\HTTP\ResponseInterface
    {
        if (! is_login(false)) {
            return $this->response->setJSON(['state' => 'error', 'msg' => '로그인 후 이용해 주세요.']);
        }
        $uid = (string) ($this->session->user_id ?? '');
        $member = $this->modelMember->getByUid($uid);
        if (! $member) {
            return $this->response->setJSON(['state' => 'error', 'msg' => '회원 정보를 찾을 수 없습니다.']);
        }
        $mbFid = (int) ($member->mb_fid ?? 0);
        if ($mbFid <= 0) {
            return $this->response->setJSON(['state' => 'error', 'msg' => '회원 정보를 찾을 수 없습니다.']);
        }
        $today = date('Y-m-d');
        if ($model->hasAttendedOn($mbFid, $today)) {
            return $this->response->setJSON(['state' => 'error', 'msg' => '오늘은 이미 출석했습니다.']);
        }
        $select = (int) $this->request->getPost('selectNumber');
        if (! in_array($select, [1, 2, 3], true)) {
            return $this->response->setJSON(['state' => 'error', 'msg' => '사다리 숫자를 선택해주세요.']);
        }
        $comment = trim((string) $this->request->getPost('comment'));
        if ($comment === '') {
            return $this->response->setJSON(['state' => 'error', 'msg' => '출석 코멘트를 입력해주세요.']);
        }
        if (mb_strlen($comment) > 500) {
            return $this->response->setJSON(['state' => 'error', 'msg' => '코멘트가 너무 깁니다.']);
        }
        $resultNum = random_int(1, 3);
        $isWin = ($select === $resultNum);
        try {
            $newId = $model->insertAttendance($mbFid, $today, $select, $resultNum, $isWin, $comment);
        } catch (\Throwable $e) {
            $newId = null;
        }
        if (! $newId) {
            return $this->response->setJSON(['state' => 'error', 'msg' => '출석 처리에 실패했습니다. 잠시 후 다시 시도해 주세요.']);
        }

        return $this->response->setJSON([
            'state'        => 'success',
            'selectNumber' => (string) $select,
            'number'       => (string) $resultNum,
            'ladderResult' => $isWin ? 'win' : 'lose',
        ]);
    }

    /**
     * iframe(mainFrame) 전용 — 선배님 출석체크 (?view=attendance 와 동등 UI)
     * GET|POST frame/attendance?curMonth=Y-m&page=
     */
    public function frameAttendance()
    {
        $this->setLanguage();
        $headInfo = $this->getSiteConf();
        $local = rtrim(site_furl(''), '/');
        $cssVer = ($_ENV['CI_ENVIRONMENT'] ?? '') == (defined('ENV_PRODUCTION') ? ENV_PRODUCTION : 'production') ? '1' : time();

        $model = new Attendance_Model();
        $model->ensureTable();

        if ($this->request->getMethod() === 'post') {
            $view = (string) $this->request->getPost('view');
            $action = (string) $this->request->getPost('action');
            $actionType = (string) $this->request->getPost('actionType');
            if ($view === 'action' && $action === 'attendance' && $actionType === 'insert') {
                return $this->attendanceJsonInsert($model);
            }
        }

        $curMonth = trim((string) $this->request->getGet('curMonth'));
        if (! preg_match('/^\d{4}-\d{2}$/', $curMonth)) {
            $curMonth = date('Y-m');
        }
        $parts = explode('-', $curMonth);
        $calendarYear = (int) ($parts[0] ?? (int) date('Y'));
        $calendarMonth = (int) ($parts[1] ?? (int) date('m'));
        if ($calendarMonth < 1 || $calendarMonth > 12) {
            $calendarMonth = (int) date('m');
            $curMonth = date('Y-m');
        }

        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 20;
        $totalRows = $model->countAllRows();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $list = $model->getListPageJoined($page, $perPage);

        $isLogin = is_login(false);
        $attendedYmd = [];
        $alreadyToday = false;
        if ($isLogin) {
            $uid = (string) ($this->session->user_id ?? '');
            $m = $this->modelMember->getByUid($uid);
            $mbFid = (int) ($m->mb_fid ?? 0);
            if ($mbFid > 0) {
                $attendedYmd = $model->getAttendedYmdSetForMonth($mbFid, $calendarYear, $calendarMonth);
                $alreadyToday = $model->hasAttendedOn($mbFid, date('Y-m-d'));
            }
        }

        $pool = Attendance_Model::commentPool();
        $commentNo = array_rand($pool);
        $commentPreset = $pool[$commentNo];

        $html = view('home/attendance_frame', array_merge($headInfo, [
            'site_title'     => ($headInfo['site_name'] ?? '파워볼게임') . ' : 출석체크',
            'local'          => $local,
            'cssVer'         => $cssVer,
            'curMonth'       => $curMonth,
            'calendarYear'   => $calendarYear,
            'calendarMonth'  => $calendarMonth,
            'page'           => $page,
            'perPage'        => $perPage,
            'totalRows'      => $totalRows,
            'totalPages'     => $totalPages,
            'list'           => $list,
            'isLogin'        => $isLogin,
            'alreadyToday'   => $alreadyToday,
            'attendedYmd'    => $attendedYmd,
            'commentNo'      => $commentNo,
            'commentPreset'  => $commentPreset,
            'simg'           => 'https://simg.powerballgame.co.kr',
            'postUrl'        => site_furl('frame/attendance'),
        ]));
        $this->response->setBody($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $this->response;
    }

    /**
     * iframe(mainFrame) 전용 — 고객센터(공지) 읽기 + 목록 (선배님 bo_v + bo_list 구조, bbs.css)
     * GET frame/customerCenter?id=공지ID&page=페이지&sfl=&stx=
     */
    public function frameCustomerCenter()
    {
        $this->setLanguage();
        $headInfo = $this->getSiteConf();
        $local = rtrim(site_furl(''), '/');
        $cssVer = ($_ENV['CI_ENVIRONMENT'] ?? '') == (defined('ENV_PRODUCTION') ? ENV_PRODUCTION : 'production') ? '1' : time();

        // 진단: 기본 Logger threshold=3 이면 emergency/alert/critical 만 파일에 기록됨 (app/Config/Logger.php)
        $ccLog = static function (string $msg): void {
            log_message('critical', '[frameCustomerCenter] ' . $msg);
        };
        $reqUri = (string) ($this->request->getServer('REQUEST_URI') ?? '');
        $ccLog('request=' . $reqUri);

        $boards = [];
        try {
            $boards = $this->modelNotice->getBoards();
            $boards = is_array($boards) ? $boards : [];
            $ccLog('getBoards ok count=' . count($boards));
        } catch (\Throwable $e) {
            $ccLog('getBoards EXCEPTION: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            $boards = [];
        }

        $stx = trim((string) $this->request->getGet('stx'));
        $sfl = (string) $this->request->getGet('sfl');
        if ($stx !== '') {
            $boards = array_values(array_filter($boards, function ($row) use ($stx, $sfl) {
                $title = (string) ($row->notice_title ?? '');
                $content = (string) ($row->notice_content ?? '');
                $mbUid = (string) ($row->notice_mb_uid ?? '');
                $nick = (string) ($row->mb_nickname ?? '');
                switch ($sfl) {
                    case 'wr_content':
                        return mb_stripos($content, $stx) !== false;
                    case 'wr_subject||wr_content':
                        return mb_stripos($title, $stx) !== false || mb_stripos($content, $stx) !== false;
                    case 'mb_id,1':
                        return $mbUid === $stx;
                    case 'mb_id,0':
                        return mb_stripos($mbUid, $stx) !== false;
                    case 'wr_name,1':
                        return $nick === $stx;
                    case 'wr_name,0':
                        return mb_stripos($nick, $stx) !== false;
                    case 'wr_subject':
                    default:
                        return mb_stripos($title, $stx) !== false;
                }
            }));
            $ccLog('after search filter stx=' . json_encode($stx, JSON_UNESCAPED_UNICODE) . ' sfl=' . json_encode($sfl, JSON_UNESCAPED_UNICODE) . ' count=' . count($boards));
        }

        $id = (int) $this->request->getGet('id');
        if ($id <= 0) {
            $id = (int) $this->request->getGet('wr_id');
        }

        $post = null;
        if ($id > 0) {
            try {
                $post = $this->modelNotice->getBoardById($id);
                if ($post) {
                    $this->modelNotice->incrementHit($id);
                    $post->notice_hit = (int) ($post->notice_hit ?? 0) + 1;
                }
            } catch (\Throwable $e) {
                $post = null;
            }
        }

        $ccLog(
            'resolved post notice_fid=' . ($post ? (string) (int) ($post->notice_fid ?? 0) : 'null')
            . ' boards_empty=' . ($boards === [] ? '1' : '0')
        );

        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 20;
        $total = count($boards);
        $ceilRaw = $perPage > 0 ? ceil($total / $perPage) : 0;
        $totalPages = max(1, (int) $ceilRaw);
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $boardsPage = array_slice($boards, ($page - 1) * $perPage, $perPage);

        $prevId = null;
        $nextId = null;
        if ($post && $boards !== []) {
            foreach ($boards as $i => $b) {
                if ((int) ($b->notice_fid ?? 0) === (int) ($post->notice_fid ?? 0)) {
                    if ($i > 0) {
                        $prevId = (int) ($boards[$i - 1]->notice_fid ?? 0) ?: null;
                    }
                    if (isset($boards[$i + 1])) {
                        $nextId = (int) ($boards[$i + 1]->notice_fid ?? 0) ?: null;
                    }
                    break;
                }
            }
        }

        $objAdm = null;
        try {
            if (is_login(false)) {
                $uid = $this->session->user_id ?? '';
                $objAdm = $uid !== '' ? $this->modelMember->getByUid($uid) : null;
            }
        } catch (\Throwable $e) {}
        $is_notice_admin = $objAdm && (int) ($objAdm->mb_level ?? 0) >= 100;

        $siteTitleCc = ($headInfo['site_name'] ?? '파워볼게임') . ' : 고객센터';
        if ($post) {
            $t = trim((string) ($post->notice_title ?? ''));
            if ($t !== '') {
                $siteTitleCc = ($headInfo['site_name'] ?? '파워볼게임') . ' : ' . $t;
            }
        }

        $viewData = array_merge($headInfo, [
            'site_title' => $siteTitleCc,
            'local' => $local,
            'cssVer' => $cssVer,
            'post' => $post,
            'boards' => $boards,
            'boardsPage' => $boardsPage,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'prevId' => $prevId,
            'nextId' => $nextId,
            'stx' => $stx,
            'sfl' => $sfl,
            'isLogin' => is_login(false),
            'is_notice_admin' => $is_notice_admin,
        ]);

        try {
            $html = view('home/customer_center_frame', $viewData);
            $ccLog('view render ok bytes=' . strlen($html));
        } catch (\Throwable $e) {
            $ccLog('view EXCEPTION: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
        $this->response->setBody($html);
        $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $this->response;
    }

    /**
     * iframe(mainFrame) 전용 — 선배님 커뮤니티 (유머: humor_post / 포토: board_photo + photoList)
     * 그 외 bo_table 은 기존 /bbs/board.php 로 이동
     */
    public function frameCommunityBoard()
    {
        $this->setLanguage();
        $headInfo = $this->getSiteConf();
        $local = rtrim(site_furl(''), '/');
        $cssVer = ($_ENV['CI_ENVIRONMENT'] ?? '') == (defined('ENV_PRODUCTION') ? ENV_PRODUCTION : 'production') ? '1' : time();

        $boTable = trim((string) $this->request->getGet('bo_table'));
        if ($boTable === '') {
            $boTable = 'humor';
        }

        if (! in_array($boTable, ['humor', 'photo', 'pick', 'free', 'qna', 'faq', 'request'], true)) {
            return $this->response->redirect('/bbs/board.php?' . http_build_query(['bo_table' => $boTable]));
        }

        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = $boTable === 'photo' ? 24 : ($boTable === 'faq' ? 50 : (($boTable === 'request' || $boTable === 'qna') ? 20 : 10));
        $sfl = (string) $this->request->getGet('sfl');
        if ($sfl === '') {
            $sfl = 'wr_subject';
        }
        $stx = trim((string) $this->request->getGet('stx'));
        $sst = (string) $this->request->getGet('sst');
        if ($sst === '') {
            $sst = 'wr_datetime';
        }
        $sod = strtolower((string) $this->request->getGet('sod'));
        if ($sod !== 'asc' && $sod !== 'desc') {
            $sod = 'desc';
        }
        $sop = (string) $this->request->getGet('sop');
        if ($sop === '') {
            $sop = 'and';
        }

        if ($boTable === 'photo') {
            $photoModel = new BoardPhoto_Model();
            $photoModel->ensureTable();
            $total = $photoModel->countListFiltered($sfl, $stx);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $rows = $photoModel->getListPage($page, $perPage, $sfl, $stx, $sst, $sod);

            $wrId = (int) $this->request->getGet('wr_id');
            if ($wrId <= 0) {
                $wrId = (int) $this->request->getGet('id');
            }
            $readPost = null;
            $photoNewerId = null;
            $photoOlderId = null;
            $readAuthorNick = '';
            $readAuthorGrade = 2;
            if ($wrId > 0) {
                $readPost = $photoModel->find($wrId);
                if ($readPost) {
                    $neighbors = $photoModel->getNeighborIds($wrId);
                    $photoNewerId = $neighbors['newer_id'];
                    $photoOlderId = $neighbors['older_id'];
                    $readAuthorNick = trim((string) ($readPost->mb_nickname ?? ''));
                    $fid = (int) ($readPost->mb_uid ?? 0);
                    try {
                        $author = $fid > 0 ? $this->modelMember->getByFid($fid) : null;
                        if ($author) {
                            if ($readAuthorNick === '') {
                                $readAuthorNick = (string) ($author->mb_nickname ?? $author->mb_uid ?? '');
                            }
                            $readAuthorGrade = (int) ($author->mb_grade ?? 2);
                        }
                    } catch (\Throwable $e) {
                    }
                    if ($readAuthorNick === '') {
                        $readAuthorNick = $fid > 0 ? ('#' . $fid) : '—';
                    }
                    if ($readAuthorGrade < 0) {
                        $readAuthorGrade = 0;
                    }
                    if ($readAuthorGrade > 20) {
                        $readAuthorGrade = 20;
                    }
                } else {
                    $wrId = 0;
                }
            }

            $loginUid = '';
            $isPhotoAdmin = false;
            try {
                if (is_login(false)) {
                    $loginUid = (string) ($this->session->user_id ?? '');
                    $adm = $loginUid !== '' ? $this->modelMember->getByUid($loginUid) : null;
                    $isPhotoAdmin = $adm && (int) ($adm->mb_level ?? 0) >= 100;
                }
            } catch (\Throwable $e) {
            }

            $siteTitle = ($headInfo['site_name'] ?? '파워볼게임') . ' : 포토게시판 ' . $page . ' 페이지';
            if ($readPost) {
                $siteTitle = ($readPost->title ?? '포토') . ' > 포토게시판 | ' . ($headInfo['site_name'] ?? '파워볼게임');
            }

            $viewData = array_merge($headInfo, [
                'site_title' => $siteTitle,
                'local' => $local,
                'cssVer' => $cssVer,
                'bo_table' => $boTable,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'rows' => $rows,
                'sfl' => $sfl,
                'stx' => $stx,
                'sst' => $sst,
                'sod' => $sod,
                'sop' => $sop,
                'isLogin' => is_login(false),
                'login_uid' => $loginUid,
                'is_photo_admin' => $isPhotoAdmin,
                'wr_id' => $wrId,
                'read_post' => $readPost,
                'photo_newer_id' => $photoNewerId,
                'photo_older_id' => $photoOlderId,
                'read_author_nick' => $readAuthorNick,
                'read_author_grade' => $readAuthorGrade,
            ]);

            $html = view('home/photo_board_frame', $viewData);
            $this->response->setBody($html);
            $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

            return $this->response;
        }

        if ($boTable === 'pick') {
            $pickModel = new PickPost_Model();
            $pickModel->ensureTable();
            $total = $pickModel->countListFiltered($sfl, $stx);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $rows = $pickModel->getListPage($page, $perPage, $sfl, $stx, $sst, $sod);
            $pickNotice = ($page === 1) ? $pickModel->getNotice() : null;

            $wrId = (int) $this->request->getGet('wr_id');
            if ($wrId <= 0) {
                $wrId = (int) $this->request->getGet('id');
            }
            $readPost = null;
            $pickNewerId = null;
            $pickOlderId = null;
            $readAuthorNick = '';
            $readAuthorGrade = 2;
            if ($wrId > 0) {
                $readPost = $pickModel->find($wrId);
                if ($readPost) {
                    $neighbors = $pickModel->getNeighborIds($wrId);
                    $pickNewerId = $neighbors['newer_id'];
                    $pickOlderId = $neighbors['older_id'];
                    $readAuthorNick = trim((string) ($readPost->mb_nickname ?? ''));
                    try {
                        $author = $this->modelMember->getByUid((string) ($readPost->mb_uid ?? ''));
                        if ($author) {
                            if ($readAuthorNick === '') {
                                $readAuthorNick = (string) ($author->mb_nickname ?? $readPost->mb_uid ?? '');
                            }
                            $readAuthorGrade = (int) ($author->mb_grade ?? 2);
                        } elseif ($readAuthorNick === '') {
                            $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                        }
                    } catch (\Throwable $e) {
                        if ($readAuthorNick === '') {
                            $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                        }
                    }
                    if ($readAuthorGrade < 0) {
                        $readAuthorGrade = 0;
                    }
                    if ($readAuthorGrade > 20) {
                        $readAuthorGrade = 20;
                    }
                } else {
                    $wrId = 0;
                }
            }

            $loginUid = '';
            $isPickAdmin = false;
            try {
                if (is_login(false)) {
                    $loginUid = (string) ($this->session->user_id ?? '');
                    $adm = $loginUid !== '' ? $this->modelMember->getByUid($loginUid) : null;
                    $isPickAdmin = $adm && (int) ($adm->mb_level ?? 0) >= 100;
                }
            } catch (\Throwable $e) {
            }

            $siteTitle = ($headInfo['site_name'] ?? '파워볼게임') . ' : 분석픽공유 ' . $page . ' 페이지';
            if ($readPost) {
                $siteTitle = ($readPost->title ?? '분석픽') . ' > 분석픽공유 | ' . ($headInfo['site_name'] ?? '파워볼게임');
            }

            $viewData = array_merge($headInfo, [
                'site_title' => $siteTitle,
                'local' => $local,
                'cssVer' => $cssVer,
                'bo_table' => $boTable,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'rows' => $rows,
                'sfl' => $sfl,
                'stx' => $stx,
                'sst' => $sst,
                'sod' => $sod,
                'sop' => $sop,
                'isLogin' => is_login(false),
                'login_uid' => $loginUid,
                'is_pick_admin' => $isPickAdmin,
                'wr_id' => $wrId,
                'read_post' => $readPost,
                'pick_newer_id' => $pickNewerId,
                'pick_older_id' => $pickOlderId,
                'read_author_nick' => $readAuthorNick,
                'read_author_grade' => $readAuthorGrade,
                'pick_notice' => $pickNotice,
            ]);

            $html = view('home/pick_board_frame', $viewData);
            $this->response->setBody($html);
            $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

            return $this->response;
        }

        if ($boTable === 'free') {
            $freeModel = new FreePost_Model();
            $freeModel->ensureTable();
            $total = $freeModel->countListFiltered($sfl, $stx);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $rows = $freeModel->getListPage($page, $perPage, $sfl, $stx, $sst, $sod);
            $freeNotices = ($page === 1) ? $freeModel->getNotices() : [];

            $wrId = (int) $this->request->getGet('wr_id');
            if ($wrId <= 0) {
                $wrId = (int) $this->request->getGet('id');
            }
            $readPost = null;
            $freeNewerId = null;
            $freeOlderId = null;
            $readAuthorNick = '';
            $readAuthorGrade = 2;
            if ($wrId > 0) {
                $readPost = $freeModel->find($wrId);
                if ($readPost) {
                    $neighbors = $freeModel->getNeighborIds($wrId);
                    $freeNewerId = $neighbors['newer_id'];
                    $freeOlderId = $neighbors['older_id'];
                    $readAuthorNick = trim((string) ($readPost->mb_nickname ?? ''));
                    try {
                        $author = $this->modelMember->getByUid((string) ($readPost->mb_uid ?? ''));
                        if ($author) {
                            if ($readAuthorNick === '') {
                                $readAuthorNick = (string) ($author->mb_nickname ?? $readPost->mb_uid ?? '');
                            }
                            $readAuthorGrade = (int) ($author->mb_grade ?? 2);
                        } elseif ($readAuthorNick === '') {
                            $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                        }
                    } catch (\Throwable $e) {
                        if ($readAuthorNick === '') {
                            $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                        }
                    }
                    if ($readAuthorGrade < 0) {
                        $readAuthorGrade = 0;
                    }
                    if ($readAuthorGrade > 20) {
                        $readAuthorGrade = 20;
                    }
                } else {
                    $wrId = 0;
                }
            }

            $loginUid = '';
            $isFreeAdmin = false;
            try {
                if (is_login(false)) {
                    $loginUid = (string) ($this->session->user_id ?? '');
                    $adm = $loginUid !== '' ? $this->modelMember->getByUid($loginUid) : null;
                    $isFreeAdmin = $adm && (int) ($adm->mb_level ?? 0) >= 100;
                }
            } catch (\Throwable $e) {
            }

            $siteTitle = ($headInfo['site_name'] ?? '파워볼게임') . ' : 자유게시판 ' . $page . ' 페이지';
            if ($readPost) {
                $siteTitle = ($readPost->title ?? '자유') . ' > 자유게시판 | ' . ($headInfo['site_name'] ?? '파워볼게임');
            }

            $viewData = array_merge($headInfo, [
                'site_title' => $siteTitle,
                'local' => $local,
                'cssVer' => $cssVer,
                'bo_table' => $boTable,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'rows' => $rows,
                'sfl' => $sfl,
                'stx' => $stx,
                'sst' => $sst,
                'sod' => $sod,
                'sop' => $sop,
                'isLogin' => is_login(false),
                'login_uid' => $loginUid,
                'is_free_admin' => $isFreeAdmin,
                'wr_id' => $wrId,
                'read_post' => $readPost,
                'free_newer_id' => $freeNewerId,
                'free_older_id' => $freeOlderId,
                'read_author_nick' => $readAuthorNick,
                'read_author_grade' => $readAuthorGrade,
                'free_notices' => $freeNotices,
            ]);

            $html = view('home/free_board_frame', $viewData);
            $this->response->setBody($html);
            $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

            return $this->response;
        }

        if ($boTable === 'qna') {
            $qnaModel = new QnaPost_Model();
            $qnaModel->ensureTable();
            $total = $qnaModel->countListFiltered($sfl, $stx);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $rows = $qnaModel->getListPage($page, $perPage, $sfl, $stx, $sst, $sod);
            $qnaNotices = ($page === 1) ? $qnaModel->getNotices() : [];

            $loginUid = '';
            $isQnaAdmin = false;
            try {
                if (is_login(false)) {
                    $loginUid = (string) ($this->session->user_id ?? '');
                    $adm = $loginUid !== '' ? $this->modelMember->getByUid($loginUid) : null;
                    $isQnaAdmin = $adm && (int) ($adm->mb_level ?? 0) >= 100;
                }
            } catch (\Throwable $e) {
            }

            $wrId = (int) $this->request->getGet('wr_id');
            if ($wrId <= 0) {
                $wrId = (int) $this->request->getGet('id');
            }
            $readPost = null;
            $qnaNewerId = null;
            $qnaOlderId = null;
            $readAuthorNick = '';
            $readAuthorGrade = 2;
            $qnaSecretDenied = false;
            if ($wrId > 0) {
                $readPost = $qnaModel->find($wrId);
                if ($readPost) {
                    $secretRead = (int) ($readPost->is_secret ?? 0) === 1;
                    $authorUid = (string) ($readPost->mb_uid ?? '');
                    $canOpenSecret = !$secretRead || $isQnaAdmin || ($loginUid !== '' && $loginUid === $authorUid);
                    if (!$canOpenSecret) {
                        $qnaSecretDenied = true;
                        $readPost = null;
                        $wrId = 0;
                    } else {
                        $neighbors = $qnaModel->getNeighborIds((int) $readPost->id);
                        $qnaNewerId = $neighbors['newer_id'];
                        $qnaOlderId = $neighbors['older_id'];
                        $readAuthorNick = trim((string) ($readPost->mb_nickname ?? ''));
                        try {
                            $author = $this->modelMember->getByUid($authorUid);
                            if ($author) {
                                if ($readAuthorNick === '') {
                                    $readAuthorNick = (string) ($author->mb_nickname ?? $authorUid);
                                }
                                $readAuthorGrade = (int) ($author->mb_grade ?? 2);
                            } elseif ($readAuthorNick === '') {
                                $readAuthorNick = $authorUid;
                            }
                        } catch (\Throwable $e) {
                            if ($readAuthorNick === '') {
                                $readAuthorNick = $authorUid;
                            }
                        }
                        if ($authorUid === 'operator') {
                            $readAuthorGrade = 30;
                        } elseif (strpos($authorUid, 'anon_qna_') === 0 || trim((string) ($readPost->mb_nickname ?? '')) === '익명') {
                            $readAuthorGrade = min($readAuthorGrade, 1);
                        }
                        if ($readAuthorGrade < 0) {
                            $readAuthorGrade = 0;
                        }
                        if ($readAuthorGrade > 20) {
                            $readAuthorGrade = 20;
                        }
                    }
                } else {
                    $wrId = 0;
                }
            }

            $siteTitle = ($headInfo['site_name'] ?? '파워볼게임') . ' : 1:1문의사항 ' . $page . ' 페이지';
            if ($readPost) {
                $siteTitle = ($readPost->title ?? '1:1문의') . ' > 1:1문의사항 | ' . ($headInfo['site_name'] ?? '파워볼게임');
            } elseif ($qnaSecretDenied) {
                $siteTitle = '1:1문의사항 | ' . ($headInfo['site_name'] ?? '파워볼게임');
            }

            $viewData = array_merge($headInfo, [
                'site_title' => $siteTitle,
                'local' => $local,
                'cssVer' => $cssVer,
                'bo_table' => $boTable,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'rows' => $rows,
                'sfl' => $sfl,
                'stx' => $stx,
                'sst' => $sst,
                'sod' => $sod,
                'sop' => $sop,
                'isLogin' => is_login(false),
                'login_uid' => $loginUid,
                'is_qna_admin' => $isQnaAdmin,
                'wr_id' => $wrId,
                'read_post' => $readPost,
                'qna_newer_id' => $qnaNewerId,
                'qna_older_id' => $qnaOlderId,
                'read_author_nick' => $readAuthorNick,
                'read_author_grade' => $readAuthorGrade,
                'qna_notices' => $qnaNotices,
                'qna_secret_denied' => $qnaSecretDenied,
            ]);

            $html = view('home/qna_board_frame', $viewData);
            $this->response->setBody($html);
            $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

            return $this->response;
        }

        if ($boTable === 'request') {
            $requestModel = new RequestPost_Model();
            $requestModel->ensureTable();
            $total = $requestModel->countListFiltered($sfl, $stx);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $rows = $requestModel->getListPage($page, $perPage, $sfl, $stx, $sst, $sod);
            $requestNotices = ($page === 1) ? $requestModel->getNotices() : [];

            $wrId = (int) $this->request->getGet('wr_id');
            if ($wrId <= 0) {
                $wrId = (int) $this->request->getGet('id');
            }
            $readPost = null;
            $requestNewerId = null;
            $requestOlderId = null;
            $readAuthorNick = '';
            $readAuthorGrade = 2;
            if ($wrId > 0) {
                $readPost = $requestModel->find($wrId);
                if ($readPost) {
                    $neighbors = $requestModel->getNeighborIds($wrId);
                    $requestNewerId = $neighbors['newer_id'];
                    $requestOlderId = $neighbors['older_id'];
                    $readAuthorNick = trim((string) ($readPost->mb_nickname ?? ''));
                    try {
                        $author = $this->modelMember->getByUid((string) ($readPost->mb_uid ?? ''));
                        if ($author) {
                            if ($readAuthorNick === '') {
                                $readAuthorNick = (string) ($author->mb_nickname ?? $readPost->mb_uid ?? '');
                            }
                            $readAuthorGrade = (int) ($author->mb_grade ?? 2);
                        } elseif ($readAuthorNick === '') {
                            $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                        }
                    } catch (\Throwable $e) {
                        if ($readAuthorNick === '') {
                            $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                        }
                    }
                    if ($readAuthorGrade < 0) {
                        $readAuthorGrade = 0;
                    }
                    if ($readAuthorGrade > 20) {
                        $readAuthorGrade = 20;
                    }
                } else {
                    $wrId = 0;
                }
            }

            $loginUid = '';
            $isRequestAdmin = false;
            try {
                if (is_login(false)) {
                    $loginUid = (string) ($this->session->user_id ?? '');
                    $adm = $loginUid !== '' ? $this->modelMember->getByUid($loginUid) : null;
                    $isRequestAdmin = $adm && (int) ($adm->mb_level ?? 0) >= 100;
                }
            } catch (\Throwable $e) {
            }

            $siteTitle = ($headInfo['site_name'] ?? '파워볼게임') . ' : 기능개선요청 ' . $page . ' 페이지';
            if ($readPost) {
                $siteTitle = ($readPost->title ?? '기능개선') . ' > 기능개선요청 | ' . ($headInfo['site_name'] ?? '파워볼게임');
            }

            $viewData = array_merge($headInfo, [
                'site_title' => $siteTitle,
                'local' => $local,
                'cssVer' => $cssVer,
                'bo_table' => $boTable,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'rows' => $rows,
                'sfl' => $sfl,
                'stx' => $stx,
                'sst' => $sst,
                'sod' => $sod,
                'sop' => $sop,
                'isLogin' => is_login(false),
                'login_uid' => $loginUid,
                'is_request_admin' => $isRequestAdmin,
                'wr_id' => $wrId,
                'read_post' => $readPost,
                'request_newer_id' => $requestNewerId,
                'request_older_id' => $requestOlderId,
                'read_author_nick' => $readAuthorNick,
                'read_author_grade' => $readAuthorGrade,
                'request_notices' => $requestNotices,
            ]);

            $html = view('home/request_board_frame', $viewData);
            $this->response->setBody($html);
            $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

            return $this->response;
        }

        if ($boTable === 'faq') {
            $faqModel = new FaqPost_Model();
            $faqModel->ensureTable();
            $faqModel->seedFaqIfEmpty();
            $total = $faqModel->countListFiltered($sfl, $stx);
            $totalPages = max(1, (int) ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $rows = $faqModel->getListPage($page, $perPage, $sfl, $stx, $sst, $sod);

            $loginUid = '';
            try {
                if (is_login(false)) {
                    $loginUid = (string) ($this->session->user_id ?? '');
                }
            } catch (\Throwable $e) {
            }

            $siteTitle = ($headInfo['site_name'] ?? '파워볼게임') . ' : 자주묻는질문 ' . $page . ' 페이지';

            $viewData = array_merge($headInfo, [
                'site_title' => $siteTitle,
                'local' => $local,
                'cssVer' => $cssVer,
                'bo_table' => $boTable,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'rows' => $rows,
                'sfl' => $sfl,
                'stx' => $stx,
                'sst' => $sst,
                'sod' => $sod,
                'sop' => $sop,
                'isLogin' => is_login(false),
                'login_uid' => $loginUid,
            ]);

            $html = view('home/faq_board_frame', $viewData);
            $this->response->setBody($html);
            $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

            return $this->response;
        }

        $latestNotice = null;
        try {
            $latestNotice = $this->modelNotice->getLatestNoticeOnly();
        } catch (\Throwable $e) {
            $latestNotice = null;
        }

        $humorModel = new HumorPost_Model();
        $humorModel->ensureTable();
        $total = $humorModel->countListFiltered($sfl, $stx);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $rows = $humorModel->getListPage($page, $perPage, $sfl, $stx, $sst, $sod);

        $wrId = (int) $this->request->getGet('wr_id');
        if ($wrId <= 0) {
            $wrId = (int) $this->request->getGet('id');
        }
        $readPost = null;
        $humorNewerId = null;
        $humorOlderId = null;
        $readAuthorNick = '';
        $readAuthorGrade = 2;
        if ($wrId > 0) {
            $readPost = $humorModel->find($wrId);
            if ($readPost) {
                $neighbors = $humorModel->getNeighborIds($wrId);
                $humorNewerId = $neighbors['newer_id'];
                $humorOlderId = $neighbors['older_id'];
                $readAuthorNick = trim((string) ($readPost->mb_nickname ?? ''));
                try {
                    $author = $this->modelMember->getByUid((string) ($readPost->mb_uid ?? ''));
                    if ($author) {
                        if ($readAuthorNick === '') {
                            $readAuthorNick = (string) ($author->mb_nickname ?? $readPost->mb_uid ?? '');
                        }
                        $readAuthorGrade = (int) ($author->mb_grade ?? 2);
                    } elseif ($readAuthorNick === '') {
                        $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                    }
                } catch (\Throwable $e) {
                    if ($readAuthorNick === '') {
                        $readAuthorNick = (string) ($readPost->mb_uid ?? '');
                    }
                }
                if ($readAuthorGrade < 0) {
                    $readAuthorGrade = 0;
                }
                if ($readAuthorGrade > 20) {
                    $readAuthorGrade = 20;
                }
            } else {
                $wrId = 0;
            }
        }

        $loginUid = '';
        $isHumorAdmin = false;
        try {
            if (is_login(false)) {
                $loginUid = (string) ($this->session->user_id ?? '');
                $adm = $loginUid !== '' ? $this->modelMember->getByUid($loginUid) : null;
                $isHumorAdmin = $adm && (int) ($adm->mb_level ?? 0) >= 100;
            }
        } catch (\Throwable $e) {
        }

        $siteTitle = ($headInfo['site_name'] ?? '파워볼게임') . ' : 유머게시판';
        if ($readPost) {
            $siteTitle = ($readPost->title ?? '유머') . ' > 유머게시판 | ' . ($headInfo['site_name'] ?? '파워볼게임');
        }

        $viewData = array_merge($headInfo, [
            'site_title' => $siteTitle,
            'local' => $local,
            'cssVer' => $cssVer,
            'bo_table' => $boTable,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'rows' => $rows,
            'sfl' => $sfl,
            'stx' => $stx,
            'sst' => $sst,
            'sod' => $sod,
            'sop' => $sop,
            'isLogin' => is_login(false),
            'login_uid' => $loginUid,
            'is_humor_admin' => $isHumorAdmin,
            'latest_notice' => $latestNotice,
            'wr_id' => $wrId,
            'read_post' => $readPost,
            'humor_newer_id' => $humorNewerId,
            'humor_older_id' => $humorOlderId,
            'read_author_nick' => $readAuthorNick,
            'read_author_grade' => $readAuthorGrade,
        ]);

        $html = view('home/community_board_frame', $viewData);
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

	/**
	 * 로그인 페이지 (GET /login)
	 */
	public function loginPage()
	{
		$this->setLanguage();
		$headInfo = $this->getSiteConf();
		$headInfo['lang'] = $this->session->lang ?? 'ko';
		if (is_login(true)) {
			return $this->response->redirect(site_furl('/'));
		}
		$headInfo['return_url'] = $this->request->getGet('url') ?: site_furl('/');
		$headInfo['login_error'] = $this->session->getFlashdata('login_error');
		echo view('home/login', $headInfo);
	}

	/**
	 * 로그인 처리 (POST view=action, action=login)
	 */
	protected function doLogin()
	{
		$id = trim((string) $this->request->getPost('id'));
		$pw = $this->request->getPost('pw');
		$pw = is_string($pw) ? $pw : '';
		$returnUrl = $this->request->getPost('url');
		if (empty($returnUrl) || !preg_match('#^https?://|^/#', $returnUrl)) {
			$returnUrl = site_furl('/');
		}
		writeLog("[doLogin] start id=" . $id . " returnUrl=" . $returnUrl);

		if ($id === '' || $pw === '') {
			writeLog("[doLogin] empty id or pw");
			$this->session->setFlashdata('login_error', '아이디와 비밀번호를 입력해 주세요.');
			return $this->response->redirect(site_furl('/login?url=' . rawurlencode($returnUrl)));
		}

		$member = $this->modelMember->login($id, $pw);
		if (!$member) {
			writeLog("[doLogin] login fail no member");
			$this->session->setFlashdata('login_error', '아이디 또는 비밀번호가 올바르지 않습니다.');
			return $this->response->redirect(site_furl('/login?url=' . rawurlencode($returnUrl)));
		}

		$this->session->set('logged_in', true);
		$this->session->set('user_id', $member->mb_uid);
		$this->session->set('lang', $this->session->get('lang') ?? 'ko');
		$sessId = $this->session->session_id ?? 'n/a';
		writeLog("[doLogin] session set logged_in=1 user_id=" . ($member->mb_uid ?? '') . " session_id=" . $sessId);

		// 접속자 수(sess 테이블) 집계용 — 기존에는 add 미호출로 connectUserCnt 가 항상 0
		try {
			$sip = (string) $this->request->getIPAddress();
			if ($sip !== '') {
				$member->mb_ip_last = $sip;
			}
			$this->modelSess->add($member, (string) ($this->session->session_id ?? ''));
		} catch (\Throwable $e) {
			writeLog('[doLogin] sess add: ' . $e->getMessage());
		}

		// is_login(true) 는 세션 + 쿠키(logged=yes) 둘 다 필요하므로
		// 리다이렉트 응답에 쿠키를 반드시 포함시킨다.
		$response = $this->response->redirect($returnUrl);
		$response->setCookie('logged', 'yes', [
			'expires'  => time() + 86400 * 30,
			'path'     => '/',
			'httponly' => false,
		]);
		writeLog("[doLogin] redirect to " . $returnUrl . " with cookie logged=yes");
		return $response;
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

    /**
     * sess: 최근 2분 내 sess_action 또는 sess_update 기준 접속 세션 수 (선배님 접속자 집계와 유사).
     */
    protected function getConnectUserCount(): int
    {
        try {
            $sessModel = new \App\Models\Sess_Model();
            $tmLast = date('Y-m-d H:i:s', strtotime('-2 minutes'));

            return (int) $sessModel
                ->groupStart()
                ->where('sess_action >=', $tmLast)
                ->orWhere('sess_update >=', $tmLast)
                ->groupEnd()
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** 채팅 조회 시 현재 세션의 sess_update 갱신 → 접속 집계에 포함 */
    protected function touchSessUpdateForChat(): void
    {
        try {
            $sid = (string) ($this->session->session_id ?? '');
            if ($sid === '') {
                return;
            }
            $sessModel = new \App\Models\Sess_Model();
            $sessModel->updateLast($sid);
            // 로그인만 하고 sess 테이블에 행이 없으면(기존 코드에 add 미호출) updateLast는 무효 → 1회 등록
            if (! is_login(false)) {
                return;
            }
            $row = (new \App\Models\Sess_Model())->getBySess($sid);
            if ($row) {
                return;
            }
            $uid = (string) ($this->session->user_id ?? '');
            if ($uid === '') {
                return;
            }
            $member = $this->modelMember->getByUid($uid);
            if (! $member) {
                return;
            }
            $sip = (string) $this->request->getIPAddress();
            if ($sip !== '') {
                $member->mb_ip_last = $sip;
            }
            $this->modelSess->add($member, $sid);
        } catch (\Throwable $e) {
        }
    }

    public function chat()
    {
        $headInfo = $this->getSiteConf();
        $objMember = null;
        $userToken = '';
        $loginYN = 'N';
        $level = 1;
        $nickname = '';
        $classGifId = 'M1';
        if (is_login(true)) {
            $user_id = $this->session->user_id;
            $objMember = $this->modelMember->getByUid($user_id);
            $userToken = md5($this->session->session_id . ($objMember->mb_uid ?? ''));
            $loginYN = 'Y';
            $level = max(1, min(20, (int) ($objMember->mb_grade ?? 1)));
            $nickname = (string) ($objMember->mb_nickname ?? $objMember->mb_uid ?? '');
            $classGifId = member_class_gif_id_for_display((string) ($objMember->mb_color ?? ''), (int) ($objMember->mb_fid ?? 0));
        }
        $notices = [];
        try {
            $boards = $this->modelNotice->getBoards();
            if (is_array($boards)) {
                $notices = array_slice($boards, 0, 5);
            }
        } catch (\Throwable $e) {}
        $timerInfo = $this->getDrawTimerInfo();
        $this->touchSessUpdateForChat();
        $connect_user_cnt = $this->getConnectUserCount();
        $data = [
            'site_title'   => $headInfo['site_title'] ?? $headInfo['site_name'] ?? '파워볼 채팅',
            'server_time'  => time(),
            'objMember'    => $objMember,
            'userToken'    => $userToken,
            'loginYN'      => $loginYN,
            'level'        => $level,
            'nickname'     => $nickname,
            'classGifId'   => $classGifId,
            'notices'      => $notices,
            'time_round'   => (int) ($timerInfo['next_round'] ?? 1),
            'remain_time'  => (int) ($timerInfo['remain_seconds'] ?? 300),
            'connect_user_cnt' => $connect_user_cnt,
            /** GNB 방채팅 팝업(?view=chatRoom)에서만 우측 방장픽 패널 표시. iframe home/chat 은 false */
            'chat_popup_mode' => ($this->request->getGet('view') === 'chatRoom'),
        ];
        return view('home/chat', $data);
    }

    public function ajaxChatTimer()
    {
        $timerInfo = $this->getDrawTimerInfo();

        return $this->response->setJSON([
            'state' => 'success',
            'time_round' => (int) ($timerInfo['next_round'] ?? 1),
            'remain_seconds' => (int) ($timerInfo['remain_seconds'] ?? 300),
            'connectUserCnt' => $this->getConnectUserCount(),
        ]);
    }

    /**
     * 채팅 연병장용 최근 확정 추첨 1회 (getOrGenerate 호출 없음 — 목록 폴링에서 부작용 방지).
     * 홀짝·언오·대중소 규칙은 일자별 분석(computeStatsForDraws)과 동일.
     *
     * @return array<string, mixed>|null
     */
    protected function buildChatLastDrawPayload(?object $draw): ?array
    {
        if (! $draw || ! isset($draw->round)) {
            return null;
        }
        $ts = ! empty($draw->drawn_at) ? strtotime((string) $draw->drawn_at) : time();
        if ($ts === false) {
            $ts = time();
        }
        $dateLabel = sprintf('%02d월%02d일', (int) date('n', $ts), (int) date('j', $ts));
        $round    = (int) $draw->round;
        $pb       = (int) ($draw->powerball ?? 0);
        $sum      = (int) ($draw->ball_sum ?? 0);
        $pbOddKr  = ($pb % 2 === 1) ? '홀' : '짝';
        $pbUoKr   = ($pb <= 4) ? '언더' : '오버';
        $sumOddKr = ($sum % 2 === 1) ? '홀' : '짝';
        $sumUoKr  = ($sum <= 72) ? '언더' : '오버';
        if ($sum <= 64) {
            $sizeKr = '소';
        } elseif ($sum <= 80) {
            $sizeKr = '중';
        } else {
            $sizeKr = '대';
        }

        return [
            'round'               => $round,
            'date_label'          => $dateLabel,
            'powerball'           => $pb,
            'powerball_odd_even'  => $pbOddKr,
            'powerball_under_over'=> $pbUoKr,
            'ball_sum'            => $sum,
            'sum_odd_even'        => $sumOddKr,
            'sum_under_over'      => $sumUoKr,
            'sum_size'            => $sizeKr,
        ];
    }

    public function ajaxChatList()
    {
        $chatModel = new \App\Models\ChatMessage_Model();
        $chatModel->ensureTable();

        $rows = $chatModel
            ->orderBy('id', 'DESC')
            ->findAll(100);

        $currentUid = (string) ($this->session->user_id ?? '');
        $currentMember = $currentUid !== '' ? $this->modelMember->getByUid($currentUid) : null;
        $isAdmin = $currentMember && (int)($currentMember->mb_level ?? 0) >= 100;

        $uniqueUids = [];
        foreach ($rows as $row) {
            $u = trim((string) ($row->mb_uid ?? ''));
            if ($u !== '') {
                $uniqueUids[$u] = true;
            }
        }
        $uidList = array_keys($uniqueUids);
        $nickByUid = [];
        if ($uidList !== []) {
            $memberRows = $this->modelMember->select('mb_uid, mb_nickname')
                ->whereIn('mb_uid', $uidList)
                ->findAll();
            foreach ($memberRows as $m) {
                $uk = (string) ($m->mb_uid ?? '');
                $nn = trim((string) ($m->mb_nickname ?? ''));
                $nickByUid[$uk] = $nn !== '' ? $nn : $uk;
            }
            foreach ($uidList as $uk) {
                if (! isset($nickByUid[$uk])) {
                    $nickByUid[$uk] = $uk;
                }
            }
        }

        $messages = [];
        foreach ($rows as $row) {
            $uid = (string) ($row->mb_uid ?? '');
            $nickname = $nickByUid[$uid] ?? $uid;
            $messages[] = [
                'id' => (int) ($row->id ?? 0),
                'uid' => $uid,
                'nickname' => $nickname,
                'message' => (string) ($row->message ?? ''),
                'time' => !empty($row->created_at) ? date('H:i:s', strtotime($row->created_at)) : '',
                'canDelete' => $isAdmin || ($currentUid !== '' && $uid === $currentUid),
            ];
        }

        $this->touchSessUpdateForChat();
        $connectCnt = $this->getConnectUserCount();

        $lastDraw = null;
        try {
            $drawModel = new \App\Models\PowerballDraw_Model();
            $lastDraw = $this->buildChatLastDrawPayload($drawModel->getLatest());
        } catch (\Throwable $e) {
            $lastDraw = null;
        }

        return $this->response->setJSON([
            'state' => 'success',
            'connectUserCnt' => $connectCnt,
            'messages' => $messages,
            'nicknames' => $nickByUid,
            'lastDraw' => $lastDraw,
        ]);
    }

    public function ajaxChatSend()
    {
        if (!is_login(false)) {
            return $this->response->setJSON(['state' => 'error', 'message' => 'notlogin']);
        }

        $msg = trim((string) $this->request->getPost('message'));
        if ($msg === '') {
            return $this->response->setJSON(['state' => 'error', 'message' => 'empty']);
        }
        if (mb_strlen($msg) > 300) {
            $msg = mb_substr($msg, 0, 300);
        }

        $uid = (string) ($this->session->user_id ?? '');
        if ($uid === '') {
            return $this->response->setJSON(['state' => 'error', 'message' => 'nouid']);
        }

        $chatModel = new \App\Models\ChatMessage_Model();
        $chatModel->ensureTable();
        $chatModel->insert([
            'mb_uid' => $uid,
            'message' => $msg,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['state' => 'success']);
    }

    public function ajaxChatDelete()
    {
        if (!is_login(false)) {
            return $this->response->setJSON(['state' => 'error', 'message' => 'notlogin']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['state' => 'error', 'message' => 'invalid_id']);
        }

        $uid = (string) ($this->session->user_id ?? '');
        $member = $uid !== '' ? $this->modelMember->getByUid($uid) : null;
        $isAdmin = $member && (int)($member->mb_level ?? 0) >= 100;

        $chatModel = new \App\Models\ChatMessage_Model();
        $chatModel->ensureTable();
        $row = $chatModel->find($id);
        if (!$row) {
            return $this->response->setJSON(['state' => 'error', 'message' => 'not_found']);
        }

        $ownerUid = (string) ($row->mb_uid ?? '');
        if (!$isAdmin && $uid !== $ownerUid) {
            return $this->response->setJSON(['state' => 'error', 'message' => 'forbidden']);
        }

        $chatModel->delete($id);
        return $this->response->setJSON(['state' => 'success']);
    }
}
