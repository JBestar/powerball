<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?=$site_name?></title>
    <meta name="robots" content="index, follow" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="파워볼게임(PBG) : 실시간 파워볼 분석 커뮤니티" />
    <meta property="og:description" content="파워볼 커뮤니티, 홀짝, 출줄, 패턴, 분석기, 실시간 통계" />
    <meta property="og:image" content="<?php echo site_furl('images/main_logo.gif'); ?>" />
    <meta property="og:url" content="<?php echo site_furl(''); ?>" />
    <link rel="canonical" href="<?php echo site_furl(''); ?>" />
    <?php $local = rtrim(site_furl(''), '/'); $cssVer = ($_ENV['CI_ENVIRONMENT'] ?? '') == (defined('ENV_PRODUCTION') ? ENV_PRODUCTION : 'production') ? '1' : time(); ?>
    <!-- 로컬 common CSS (로그인 여부에 따라 common / common_logged) -->
    <?php if (is_login(false)) : ?>
    <link rel="stylesheet" href="<?php echo $local; ?>/css/common_logged.css?v=<?php echo $cssVer; ?>" />
    <?php else : ?>
    <link rel="stylesheet" href="<?php echo $local; ?>/css/common.css?v=<?php echo $cssVer; ?>" />
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo $local; ?>/css/font-local.css?v=<?php echo $cssVer; ?>" />
    <style>
        /* 보드 메뉴 4개(유머/포토/분석픽공유/자유) 한 줄 유지 - float 줄바꿈 방지 */
        .boardBox ul.menu { display: flex; flex-wrap: nowrap; }
        .boardBox ul.menu li { float: none; flex: none; width: 155px; }
        .boardBox ul.menu li.none { width: 154px; }
        /* 유머 목록: flex 행이 좌/우 311px 칸 안에서 넘치지 않도록 */
        .boardBox #list_humor ul.list li { min-width: 0; }
        /* timeBox / shareBox / shareDiv (선배님 스타일) */
        div.timeBox {
            position: absolute; top: 17px; right: 125px;
            width: 402px; height: 30px; line-height: 30px;
            border: 1px solid #D5D5D5; text-align: center;
            background-color: #F1F1F1; font-size: 11px; font-family: tahoma, dotum;
        }
        div.shareBox {
            position: absolute; top: 17px; right: 15px;
            width: 100px; height: 30px; line-height: 30px;
            border: 1px solid #921417; text-align: center;
            background-color: #C11A20; font-size: 11px; font-family: tahoma, dotum;
            color: #fff;
        }
        div.shareBox a { display: block; color: #fff; }
        #shareDiv {
            position: absolute; background-color: #333; width: 790px; padding: 20px;
            display: none; z-index: 99;
        }
        #shareDiv .tit { color: #fff; padding: 10px 0; }
        #shareDiv .text { padding-bottom: 10px; }
        #shareDiv .text textarea {
            background-color: #000; border: 1px solid #000; color: #fff;
            width: 600px; padding: 10px; resize: none;
        }
        #shareDiv .text .btn {
            position: absolute; background-color: #C11A20; color: #fff;
            width: 50px; height: 50px; text-align: center; line-height: 50px;
            margin-left: 5px; border: 1px solid #921417;
        }
        .txTooltip {
            background-color: #C11A20; border: 1px solid #921417; color: #fff;
            width: 16px; height: 16px; text-align: center;
            display: inline-block; line-height: 16px;
        }
        .txIcon { width: 20px; height: 20px; }
        #txTooltipDiv {
            position: absolute; margin-top: 5px; background-color: #333;
            width: 200px; height: 30px; color: #fff; padding: 20px;
            border-radius: 2px; text-align: left; display: none;
        }
        .powerballBox a { color: #5F6164; }
        /* inner-right(일자별 분석 영역)가 메인 헤더에 가리지 않도록 */
        #wrap #topArea { position: relative; z-index: 1; overflow: hidden; }
        #wrap #container { position: relative; z-index: 2; }
        #wrap .inner-right { position: relative; z-index: 3; }
    </style>
    <!-- Google Analytics (analytics.js, 선배님과 동일) -->
    <script async src="https://www.google-analytics.com/analytics.js"></script>
    <script>
        window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments);};ga.l=+new Date;
        ga('create', 'UA-149467684-1', 'auto');
        ga('send', 'pageview');
    </script>
</head>

<body>
    <div id="wrap">
        <!-- 2. 이미지 소스에서 확인된 상단 영역(topArea) 구조 구현 -->
        <div id="topArea">
            <!-- 로고 영역 -->
            <div class="logo">
                <a href="/" class="none">
                    <img src="<?php echo site_furl('images/main_logo.gif'); ?>" width="163" height="60" alt="로고">
                </a>
            </div>

            <!-- 로고 옆 텍스트 영역 (공지 & 안내) -->
            <div class="text">
                <!-- 공지사항 (notice) -->
                <?php
                    $is_notice_admin = $is_notice_admin ?? false;
                    $boardsNoticeOnly = array_values(array_filter($boards ?? [], static function ($r) {
                        return ($r->notice_type ?? '') === \App\Models\Notice_Model::TYPE_NOTICE;
                    }));
                    $boardsGuideOnly = array_values(array_filter($boards ?? [], static function ($r) {
                        return \App\Models\Notice_Model::isGuideCategory((string) ($r->notice_type ?? ''));
                    }));
                    $topNoticeHref = site_furl('frame/customerCenter');
                    $topNoticeTitle = '[제재] 계좌, 톡, 연락처 및 개인정보 공유 시 경고없이 영구 차단조치 합니다.';
                    if (!empty($boardsNoticeOnly[0])) {
                        $topNoticeHref = site_furl('frame/customerCenter?id=' . (int) ($boardsNoticeOnly[0]->notice_fid ?? 0));
                        $topNoticeTitle = (string) ($boardsNoticeOnly[0]->notice_title ?? $topNoticeTitle);
                    }
                    $topGuideHref = site_furl('frame/customerCenter');
                    $topGuideTitle = '[업데이트] 베스트 픽스터 추가 및 선정 기준 안내';
                    if (!empty($boardsGuideOnly[0])) {
                        $topGuideHref = site_furl('frame/customerCenter?id=' . (int) ($boardsGuideOnly[0]->notice_fid ?? 0));
                        $topGuideTitle = (string) ($boardsGuideOnly[0]->notice_title ?? $topGuideTitle);
                    }
                ?>
                <div class="notice">
                    <img src="<?php echo site_furl('images/bl_notice.png'); ?>" width="46" height="13" alt="NOTICE">
                    <a href="<?= esc($topNoticeHref) ?>" target="mainFrame"><?= esc($topNoticeTitle) ?></a>
                    <?php if ($is_notice_admin): ?>
                        <a href="#" onclick="window.open('<?= site_furl('/?view=noticeBoardRegister') ?>','noticeBoardRegister','width=600,height=650'); return false;"
                           style="margin-left:10px; display:inline-block; padding:2px 10px; border:1px solid #0e609c; background:#127CCB; color:#fff; font-weight:bold; border-radius:3px; font-size:11px; line-height:16px; vertical-align:middle;">등록</a>
                    <?php endif; ?>
                </div>

                <!-- 안내 (guide 영역) -->
                <div class="guide">
                    <img src="<?php echo site_furl('images/bl_guide.png'); ?>" width="46" height="13" alt="GUIDE">
                    <a href="<?= esc($topGuideHref) ?>" target="mainFrame"><?= esc($topGuideTitle) ?></a>
                </div>
            </div>
            <div style="position:absolute; top:0; right:2px;">
                <a href="<?= esc(site_furl('frame/customerCenter?id=147')) ?>" target="mainFrame">
                    <img src="<?php echo site_furl('images/banner_bullet.png'); ?>" alt="총알선물">
                </a>
            </div>
            <div class="gnb">
                <ul>
                    <!-- 1. 파워볼게임 (활성화 상태 'on') -->
                    <li><a href="<?php echo site_furl('frame/dayLog'); ?>" target="mainFrame" class="on">파워볼2게임(PBG2)</a></li>
                    
                    <!-- 2. 픽 (로그인 체크 로직 포함) -->
                    <li>
                        <a href="#" onclick="<?php echo is_login(false) ? 'location.href=\'/pick\'' : 'alert(\'로그인 후 이용가능합니다.\');'; ?> return false;">픽</a>
                    </li>

                    <!-- 3. 커뮤니티 (활성 시 a.on → #topArea .gnb ul li a.on 배경 #0d568c) -->
                    <li><a id="gnbCommunity" href="<?= esc(site_furl('frame/communityBoard?bo_table=humor')) ?>" target="mainFrame">커뮤니티</a></li>

                    <!-- 4. 마켓 (로그인 체크) -->
                    <li>
                        <a href="#" onclick="<?php echo is_login(false) ? 'location.href=\'/market\'' : 'alert(\'로그인 후 이용가능합니다.\');'; ?> return false;">마켓</a>
                    </li>

                    <!-- 5. 방채팅 -->
                    <li><a href="#" onclick="openChatRoom(); return false;">방채팅</a></li>

                    <!-- 6. 고객 관련 메뉴들 -->
                    <li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=qna')) ?>" target="mainFrame">1:1문의사항</a></li>
                    <li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=faq')) ?>" target="mainFrame">자주묻는질문</a></li>
                    <li><a href="<?= esc(site_furl('frame/communityBoard?bo_table=request')) ?>" target="mainFrame">기능개선요청</a></li>
                    <li><a href="<?= esc(site_furl('frame/customerCenter')) ?>" target="mainFrame">고객센터</a></li>
                    <li><a href="<?php echo site_furl('frame/attendance'); ?>" target="mainFrame">출석체크</a></li>
                </ul>
            </div>
        </div>

        <div id="container">
            <!-- 메인 콘텐츠 및 사이드바 박스 -->
            <div id="box-ct">             
                <!-- [inner-left] 메인 분석판 및 게시판이 들어갈 자리 -->
                <div class="inner-left">
                    <!-- [box-login] 로그인 버튼 영역 (선배님 구조: 로그인 시 padding 없이 height:150px 블록 + notice 직속) -->
                    <div class="box-login">
                            <?php if(!is_login(false)) : ?>
                        <div style="padding:15px 25px 10px 15px;">
                                <a href="/login" class="btn_login">파워볼게임 로그인</a>
                                <div class="login_menu">
                                    <a href="/?view=simpleJoin">회원가입</a>
                                    <span class="right">
                                        <a href="<?php echo site_furl('frame/dayLog'); ?>" target="mainFrame">아이디</a>
                                        <span>&middot</span>
                                        <a href="<?php echo site_furl('frame/dayLog'); ?>" target="mainFrame">비밀번호 찾기</a>
                                    </span>
                                </div>
                        </div>
                            <?php else : ?>
                                <?php
                                    $uid = isset($objMember) ? (string)($objMember->mb_uid ?? '') : '';
                                    $nickname = isset($objMember) ? (string)($objMember->mb_nickname ?? '') : '';
                                    $grade = isset($objMember) ? (int)($objMember->mb_grade ?? 1) : 1;
                                    if ($grade < 0) $grade = 0;
                                    if ($grade > 20) $grade = 20;
                                    $classImg = site_furl('images/class/M' . $grade . '.gif');

                                    $coin = isset($objMember) ? (int)allMoney($objMember) : 0;
                                    $bullet = isset($objMember) ? (int)($objMember->mb_point ?? 0) : 0;
                                    $egg = isset($objMember) ? (int)allEgg($objMember) : 0;

                                    $expNow = $bullet;
                                    $expMax = max(100, (int)(ceil($expNow / 100) * 100));
                                    $expPct = $expMax > 0 ? min(100, max(0, ($expNow / $expMax) * 100)) : 0;
                                ?>
                        <div style="height:150px;">
                            <div class="loginUserInfo">
                                <ul>
                                    <li class="b" style="position:relative;"><img src="<?= esc($classImg) ?>" width="23" height="23" alt=""> <?= esc($uid !== '' ? $uid : $nickname) ?> <a href="/logout" class="logout">로그아웃</a></li>
                                    <li class="level">계급 : <a href="/mypage?tab=level" target="mainFrame" style="color:#7F7F7F;"><?= esc($nickname !== '' ? $nickname : '마이페이지') ?></a></li>
                                    <li class="exp">경험치 :
                                        <div style="position:absolute;top:58px;left:58px;">
                                            <div style="background:#aaa url('<?= site_furl('images/lv_line.png') ?>') no-repeat;width:100px;height:16px;">
                                                <div style="background:url('<?= site_furl('images/lv_bar.png') ?>') no-repeat;width:<?= esc(number_format($expPct, 2)) ?>%;height:16px;line-height:19px;padding-left:6px;color:#000;" class="numberFont">
                                                    <div style="position:absolute;top:0;left:5px;"><?= esc($expNow) ?> / <?= esc($expMax) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <div class="loginCoinInfo">
                                <ul>
                                    <li>코인 <a href="/market" target="mainFrame" class="charge">충전</a> <a href="/market" target="mainFrame"><?= esc($coin) ?></a></li>
                                    <li>총알 <a href="/mypage" target="mainFrame"><span><?= esc($bullet) ?></span>개</a></li>
                                    <li class="none">건빵 <a href="/mypage" target="mainFrame"><span><?= esc($egg) ?></span>개</a></li>
                                </ul>
                            </div>

                            <div style="position:absolute;top:89px;left:-1px;width:314px;height:25px;line-height:25px;border-bottom:1px solid #CECECE;background-color:#127CCB;color:#747379;padding-left:20px;font-size:11px;color:#fff;border:1px solid #0D568C;"><span style="color:yellow;">마켓에서 다양한 아이템을 확인하세요. </span><a href="/market" target="mainFrame" style="color:#fff;">[구매하러 가기]</a></div>

                            <ul class="btn">
                                <li><a href="/mypage" target="mainFrame" style="background:url(<?= site_furl('images/icon_myhome.png') ?>) center top no-repeat;">마이홈</a></li>
                                <li><a href="#" onclick="windowOpen('<?= site_furl('/') ?>?view=memo','memo',600,600,'auto');return false;" style="background:url(<?= site_furl('images/icon_memo.png') ?>) center top no-repeat;">쪽지</a><span class="memoCntBox" id="memoCnt" style="display: none;"></span></li>
                                <li class="none"><a href="/mypage" target="mainFrame" style="background:url(<?= site_furl('images/icon_item.png') ?>) center top no-repeat;">아이템</a><span class="itemCntBox" id="itemCnt" style="display: none;"></span></li>
                            </ul>
                        </div>
                            <?php endif; ?>

                        <div class="notice">
                            <!-- 공지 아이콘 이미지 (이미지 소스 경로) -->
                            <img src="<?php echo site_furl('images/text_notice.jpg'); ?>" alt="공지">
                            
                            <div style="position:absolute;top:0;left:50px;right:0;" id="scrollNotice">
                                <ul style="margin:0;padding:0;list-style:none;">
                                    <!-- 실제로는 선배님의 $boards 데이터를 반복문으로 돌리면 됩니다 -->
                                    <?php if(!empty($boards)) : foreach($boards as $row) :
                                        $snTitle = (string) ($row->notice_title ?? '');
                                        $snTitleShow = mb_strlen($snTitle) > 25 ? mb_substr($snTitle, 0, 25) . '…' : $snTitle;
                                    ?>
                                        <li><a href="<?= esc(site_furl('frame/customerCenter?id=' . (int) $row->notice_fid)) ?>" target="mainFrame" title="<?= esc($snTitle) ?>"><?= esc($snTitleShow) ?></a></li>
                                    <?php endforeach; else :
                                        $snDef1 = '[안내] 건전하고 매너 있는 게임 문화를 만들어갑니다.';
                                        $snDef2 = '[제재] 타인 비방 및 욕설 시 이용이 제한될 수 있습니다.';
                                        $snD1 = mb_strlen($snDef1) > 25 ? mb_substr($snDef1, 0, 25) . '…' : $snDef1;
                                        $snD2 = mb_strlen($snDef2) > 25 ? mb_substr($snDef2, 0, 25) . '…' : $snDef2;
                                    ?>
                                        <li><a href="<?= esc(site_furl('frame/customerCenter')) ?>" target="mainFrame" title="<?= esc($snDef1) ?>"><?= esc($snD1) ?></a></li>
                                        <li><a href="<?= esc(site_furl('frame/customerCenter')) ?>" target="mainFrame" title="<?= esc($snDef2) ?>"><?= esc($snD2) ?></a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>           
                    <!-- [banner_left1_area] 상단 광고 영역 -->
                    <div id="banner_left1_area" style="height:50px; text-align:center;">
                        <div style="text-align:center;">
                            <!-- 이미지 소스에 있는 실제 광고 링크와 이미지 주소 적용 -->
                            <a href="http://qootoon.com/f4c21cda" target="_blank">
                                <img src="<?php echo site_furl('images/toptoon_320_50_1.jpg'); ?>" alt="상단광고">
                            </a>
                        </div>
                    </div>
                    <!-- [guide_banner] 메인 분석판 상단 안내(채팅) 영역 -->
                    <div id="guide_banner">
                        <!-- 실제 분석판이 들어가는 iframe (선배님 소스 구조 그대로) -->
                        <iframe scrolling="no" frameborder="0" width="100%" height="575" 
                            src="<?php echo site_furl('home/chat'); ?>" id="chatFrame">
                        </iframe>
                        <!-- 분석판 위에 떠 있는 퀵 메뉴 버튼들 -->
                        <div class="top_banner" style="top:-280px; left:-46px;">
                            <div class="lb1">
                                <a href="<?= esc(site_furl('frame/customerCenter?id=4')) ?>" target="mainFrame">매뉴얼</a>
                            </div>
                            <div class="lb2">
                                <a href="<?php echo site_furl('frame/dayLog'); ?>" target="mainFrame">랭킹</a>
                            </div>
                            <div class="lb3">
                                <a href="<?php echo site_furl('frame/attendance'); ?>" target="mainFrame">출석</a>
                            </div>
                            <div class="lb4">
                                <a href="<?= esc(site_furl('frame/customerCenter?id=537')) ?>" target="mainFrame">
                                    <img src="<?php echo site_furl('images/banner_security.png'); ?>" width="44" height="75" alt="보안접속">
                                </a>
                            </div>
                        </div>
                        <div class="bottom_banner">
                            <!-- 베스트 픽스터 헤더 -->
                            <div class="bestpickster">
                                <div class="inner">
                                    <a href="#" onclick="ajaxBestPickster(); return false;"
                                        style="color:#fff; display:block; fontsize:11px;">
                                        <img src="/images/bestpickster.gif" width="46" height="101" alt="금일베스트픽터">
                                    </a>
                                </div>
                            </div>

                            <!-- 베스트 픽스터 상세 내용 (이미지 소스 그대로) -->
                            <div id="bestPicksterList">
                                <div class="title"><?php echo date('Y년 m월 d일'); ?> 베스트 픽스터</div>
                                <div class="content" style="background-color:#F8F8F8; min-height:50px;">
                                    <!-- 선배님의 픽스터 랭킹 데이터가 PHP 반복문으로 들어올 자리입니다 -->
                                    <p style="text-align:center; padding-top:15px; color:#999;">데이터를 불러오는 중...</p>
                                </div>
                                <div class="guide">
                                    <span class="highlight">매일 0시 기준</span>으로 집계되며<br>
                                    <span class="highlight">월요일에 1~3등까지 시상</span>합니다.
                                </div>
                            </div>

                            <!-- 방채팅 대기실 버튼 (이미지 소스 그대로) -->
                            <div style="margin-top:4px;">
                                <div class="chatRoomLobby"><a href="#" onclick="openChatRoom();return false;">방채팅<br>대기실</a></div>
                                <!-- 채팅방 리스트 (스페셜 & 일반) -->
                                <ul class="chatRoomList" id="speciallist"></ul>
                                <ul class="chatRoomList" id="chatRoomList">
                                    <li>
                                        <a href="#" onclick="openChatRoom(); return false;">
                                            <img src="<?php echo site_furl('images/room_1.gif'); ?>" 
                                                     style="width:44px; height:44px;">
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="openChatRoom(); return false;">
                                            <img src="<?php echo site_furl('images/room_2.gif'); ?>" 
                                                     style="width:42px; height:42px;">
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="openChatRoom(); return false;">
                                            <img src="<?php echo site_furl('images/room_3.gif'); ?>" 
                                                     style="width:42px; height:42px;">
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="openChatRoom(); return false;">
                                            <img src="<?php echo site_furl('images/room_4.gif'); ?>" 
                                                     style="width:42px; height:42px;">
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="openChatRoom(); return false;">
                                            <img src="<?php echo site_furl('images/room_5.gif'); ?>" 
                                                     style="width:42px; height:42px;">
                                        </a>
                                    </li>
                                </ul>
                                
                            </div>                               
                        </div>
                    </div>
                    <div id="banner_left2_area" style="text-align:center;">
                    </div>
                    
                </div>
                <div class="inner-right">
                    <?= view('home/board_box', [
                        'list_humor' => $list_humor ?? [],
                        'list_pick'  => $list_pick ?? [],
                        'list_free'  => $list_free ?? [],
                        'list_photo' => $list_photo ?? [],
                        'is_humor_admin' => $is_humor_admin ?? false,
                    ]) ?>
                </div>

            </div>


        </div>

        <!-- 푸터 영역 연결 -->
        <div id="footer">
            <?php echo view('mini/footer'); ?>
        </div>
        
    </div>
    
    <!-- jQuery 로드 (로컬) -->
    <script type="text/javascript" src="<?php echo $local; ?>/js/jquery-1.11.2.min.js"></script>
    <script>
    (function(){
        "use strict";
        // ladderTimer (메인 페이지용 변수)
        var remainTime = 85;
        // 쿠키 읽기
        function getCookie(name) {
            var v = document.cookie.match("(^|;)\\s*" + name + "\\s*=\\s*([^;]+)");
            return v ? v.pop() : "";
        }
        $(window).load(function(){
            var frameEl = document.getElementById("miniViewFrame");
            if (frameEl && frameEl.contentWindow) {
                try {
                    if (getCookie("MINIVIEWLAYER") == "Y" && typeof window.miniViewControl === "function") {
                        window.miniViewControl("open");
                    }
                    if (getCookie("POINTBETLAYER") == "Y" && typeof frameEl.contentWindow.toggleBetting === "function") {
                        frameEl.contentWindow.toggleBetting();
                    }
                } catch (e) {}
            }
            // ajaxPattern('oddEven', '2026-03-07', 'powerball');
        });
        // 공지 롤링 (scrollNotice) — #scrollNotice top 애니메이션 후 첫 li 를 맨 뒤로 이동 (이동량은 scrollHeight 반영)
        var $scrollNotice = $("#scrollNotice");
        if ($scrollNotice.length && $scrollNotice.find("ul li").length > 1) {
            var $ul = $scrollNotice.find("ul");
            var rolling = false;
            var tickNotice = function() {
                if (rolling) return;
                var $first = $ul.find("li").first();
                var el0 = $first[0];
                var aEl = $first.find("a").get(0);
                var outerT = $first.outerHeight(true) || 0;
                var scLi = el0 ? el0.scrollHeight : 0;
                var scA = aEl ? aEl.scrollHeight : 0;
                var lineH = Math.max(outerT, scLi, scA) || 23;
                rolling = true;
                var dur = Math.min(700, Math.max(350, Math.round(lineH * 12)));
                $scrollNotice.animate({ top: -lineH }, dur, function() {
                    $ul.append($first);
                    $scrollNotice.css("top", "0");
                    rolling = false;
                });
            };
            setInterval(tickNotice, 3000);
        }
        // 방채팅 열기 (메인/iframe 공통)
        window.openChatRoom = function(){
            window.open("<?php echo site_furl(''); ?>?view=chatRoom", "chatRoom", "width=400,height=500,scrollbars=yes");
        };
        // 베스트 픽스터 AJAX (90% 구현: 목록 갱신)
        window.ajaxBestPickster = function(){
            var $content = $("#bestPicksterList .content");
            if (!$content.length) return;
            $content.html("<p style='text-align:center;padding-top:15px;color:#999;'>데이터를 불러오는 중...</p>");
            $.ajax({
                url: "<?php echo site_furl(''); ?>",
                type: "GET",
                data: { view: "ranking", ajax: 1 },
                dataType: "json"
            }).done(function(data){
                if (data && data.list && data.list.length) {
                    var html = "<ul style='list-style:none;padding:5px;margin:0;'>";
                    for (var i = 0; i < Math.min(5, data.list.length); i++) {
                        var r = data.list[i];
                        html += "<li style='padding:3px 0;'>" + (i+1) + ". " + (r.nick || r.mb_nickname || "-") + "</li>";
                    }
                    html += "</ul>";
                    $content.html(html);
                } else {
                    $content.html("<p style='text-align:center;padding-top:15px;color:#999;'>금일 베스트 픽스터가 없습니다.</p>");
                }
            }).fail(function(){
                $content.html("<p style='text-align:center;padding-top:15px;color:#999;'>금일 베스트 픽스터가 없습니다.</p>");
            });
        };
        $(document).ready(function(){
            try { if (typeof top.initAd === "function") top.initAd(); } catch(e) {}
            setTimeout(function(){ if (typeof heightResize === "function") heightResize(); }, 500);
            if (typeof $.fn.qtip === "function") {
                $("[title!='']").qtip({
                    position: { my: "top center", at: "bottom center" },
                    style: { classes: "tooltip_dark" }
                });
            }
            $(window).resize(function(){ if (typeof heightResize === "function") heightResize(); });
            var sixPatternCnt = 6, sixPatternType = "oddEven", sixDivision = "powerball";
            $("#sixBox .patternCnt .btn a").on("click", function(){
                $("#sixBox .patternCnt .btn a").removeClass("on1");
                $(this).addClass("on1");
                $("#sixBox .patternType .btn a").removeClass("on2");
                $("#sixBox .patternType .btn").find("[sixType=" + sixDivision + "_" + sixPatternType + "]").addClass("on2");
                sixPatternCnt = $(this).attr("rel");
                if (typeof window.ajaxSixPattern === "function") {
                    window.ajaxSixPattern(sixPatternCnt, sixPatternType, "<?= date('Y-m-d') ?>", sixDivision);
                }
            });
            $("#sixBox .patternType .btn a").on("click", function(){
                $("#sixBox .patternType .btn a").removeClass("on2");
                $(this).addClass("on2");
                $("#sixBox .patternCnt .btn a").removeClass("on1");
                $("#sixBox .patternCnt .btn").find("[rel=" + sixPatternCnt + "]").addClass("on1");
                sixPatternType = $(this).attr("rel");
                sixDivision = $(this).attr("division") || sixDivision;
                if (typeof window.ajaxSixPattern === "function") {
                    window.ajaxSixPattern(sixPatternCnt, sixPatternType, "<?= date('Y-m-d') ?>", sixDivision);
                }
            });
            $("#bestPicksterList .content").on("click", function(){ ajaxBestPickster(); });
            // 상단 GNB: mainFrame 을 여는 링크 중 하나만 .on (배경 #0d568c — common.css .gnb a.on)
            $("#topArea .gnb a[target=\"mainFrame\"]").on("click", function() {
                $("#topArea .gnb a").removeClass("on");
                $(this).addClass("on");
            });
            // boardBox 탭 전환 (유머/포토/분석픽공유/자유) — 유머/포토 선택 시 mainFrame에 해당 게시판 로드 + GNB 커뮤니티 활성
            var frameCommunityHumor = "<?= esc(site_furl('frame/communityBoard?bo_table=humor'), 'js') ?>";
            var frameCommunityPhoto = "<?= esc(site_furl('frame/communityBoard?bo_table=photo'), 'js') ?>";
            var frameCommunityPick = "<?= esc(site_furl('frame/communityBoard?bo_table=pick'), 'js') ?>";
            var frameCommunityFree = "<?= esc(site_furl('frame/communityBoard?bo_table=free'), 'js') ?>";
            $(".boardBox ul.menu li").on("click", function(e){
                if ($(e.target).closest("a").length) return;
                var rel = $(this).attr("rel");
                if (!rel) return;
                $(".boardBox ul.menu li").removeClass("on");
                $(this).addClass("on");
                $(".boardBox .listBox").hide();
                $("#list_" + rel).show().css("display", "block");
                var $mf = $("#mainFrame");
                if ($mf.length && rel === "humor") {
                    $mf.attr("src", frameCommunityHumor);
                    $("#topArea .gnb a").removeClass("on");
                    $("#gnbCommunity").addClass("on");
                } else if ($mf.length && rel === "photo") {
                    $mf.attr("src", frameCommunityPhoto);
                    $("#topArea .gnb a").removeClass("on");
                    $("#gnbCommunity").addClass("on");
                } else if ($mf.length && rel === "pick") {
                    $mf.attr("src", frameCommunityPick);
                    $("#topArea .gnb a").removeClass("on");
                    $("#gnbCommunity").addClass("on");
                } else if ($mf.length && rel === "free") {
                    $mf.attr("src", frameCommunityFree);
                    $("#topArea .gnb a").removeClass("on");
                    $("#gnbCommunity").addClass("on");
                }
            });
        });
        // 미니뷰 높이 제어 (선배님 스크립트)
        window.miniViewControl = function(type) {
            var $frame = $("#powerballMiniViewDiv #miniViewFrame");
            if (type === "open") $frame.css("height", "400px");
            else if (type === "close") $frame.css("height", "117px");
        };
        // 툴팁 표시/숨김 토글
        window.txTooltip = function() {
            if ($("#txTooltipDiv").is(":visible")) $("#txTooltipDiv").hide();
            else $("#txTooltipDiv").show();
        };
        // 공유 영역 표시/숨김 토글
        window.toggleShare = function() {
            if ($("#shareDiv").is(":visible")) $("#shareDiv").hide();
            else $("#shareDiv").show();
        };
        // 공유용 코드/링크 복사 (type: 'link' 등 → share_link 요소)
        window.copyShare = function(type) {
            var url = document.getElementById("share_" + type);
            if (url) {
                url.select();
                document.execCommand("copy");
                alert("코드가 복사되었습니다. 원하시는 곳에 Ctrl + v로 붙여넣기 하세요.");
            }
        };
    })();
    </script>
</body>
</html>
