<?php
$local = rtrim(site_furl(''), '/');
$notices = is_array($notices ?? null) ? $notices : [];
$nickname = trim((string) ($nickname ?? ''));
$classGifId = (string) ($classGifId ?? 'M1');
/** 방채팅 탭 정적 목록 (선배님 DOM, 로컬 이미지 — 기능 연동은 추후) */
$roomProfileImg = $local . '/images/profile.png';
$staticChatRooms = [
    ['li' => 'bgYellow', 'thumbRed' => true, 'rel' => '854d98341d802159d26ee89d4403c2fa', 'winFix' => '2', 'win' => 17, 'lose' => 9, 'tit' => '이긴다', 'titFull' => '이긴다', 'date' => '15시간전', 'cur' => 2, 'max' => 100, 'cid' => 'F20', 'nick' => '패왕색홀현아2', 'urel' => '5e91e1291a5bc200f3cd9b88feacd812', 'utitle' => '패왕색홀현아2'],
    ['li' => '', 'thumbRed' => true, 'rel' => 'a77d801c79c4b03e102df98d893ad89e', 'winFix' => '3', 'win' => 3, 'lose' => 0, 'tit' => '우리모두 같이 수익냅...', 'titFull' => '우리모두 같이 수익냅시다 편하게들 오세요', 'date' => '15분전', 'cur' => 0, 'max' => 100, 'cid' => 'M20', 'nick' => '다로소', 'urel' => '33cee8175080f8647ccab28bced5d884', 'utitle' => '다로소'],
    ['li' => 'bgEven', 'thumbRed' => true, 'rel' => 'c01960d44823b424824f221f2fb4c5ea', 'winFix' => '4', 'win' => 5, 'lose' => 2, 'tit' => '소액마틴 연승', 'titFull' => '소액마틴 연승', 'date' => '40분전', 'cur' => 0, 'max' => 100, 'cid' => 'M18', 'nick' => '불감', 'urel' => 'e5fc06bddcbf13c90c3f00903bccc06b', 'utitle' => '불감'],
    ['li' => '', 'thumbRed' => true, 'rel' => '91ca521eec51cf9e8dccc766d01d5de3', 'winFix' => '1', 'win' => 1, 'lose' => 0, 'tit' => '집중츤사', 'titFull' => '집중츤사', 'date' => '6분전', 'cur' => 3, 'max' => 100, 'cid' => 'M20', 'nick' => '길천사델라', 'urel' => '5df2892923d91f650aa19d5b24bf2940', 'utitle' => '길천사델라'],
    ['li' => 'bgEven', 'thumbRed' => true, 'rel' => '60a90bfc2975f4a7c31ce558ca991bb5', 'winFix' => '1', 'win' => 1, 'lose' => 0, 'tit' => 'ㄸㅂ', 'titFull' => 'ㄸㅂ', 'date' => '4분전', 'cur' => 0, 'max' => 100, 'cid' => 'M20', 'nick' => '딱밤', 'urel' => 'f9b4e685003e9ac98764bf25a81e4524', 'utitle' => '딱밤'],
    ['li' => '', 'thumbRed' => true, 'rel' => '8a254726f5247055052dc11a5fba788e', 'winFix' => '2', 'win' => 2, 'lose' => 1, 'tit' => '검 성', 'titFull' => '검 성', 'date' => '15분전', 'cur' => 1, 'max' => 100, 'cid' => 'M20', 'nick' => '검성', 'urel' => 'e3423b5cee70c7947add19c92e1d4ada', 'utitle' => '검성'],
    ['li' => 'bgEven', 'thumbRed' => false, 'rel' => '3a5333d9c807d63ebd2203f8bec1d8ff', 'winFix' => null, 'win' => 2, 'lose' => 1, 'tit' => 'K A N G C H O K', 'titFull' => 'K A N G C H O K', 'date' => '13분전', 'cur' => 0, 'max' => 100, 'cid' => 'M19', 'nick' => '트리플촉', 'urel' => '982fa809432de76da48e996c41e12615', 'utitle' => '트리플촉'],
    ['li' => '', 'thumbRed' => false, 'rel' => '1045e721be8fd58bfb0f370002433620', 'winFix' => null, 'win' => 2, 'lose' => 2, 'tit' => 'Race', 'titFull' => 'Race', 'date' => '20분전', 'cur' => 0, 'max' => 100, 'cid' => 'M18', 'nick' => '레전드오브샤네르', 'urel' => '360cefcb2fdbcc353eec5fa98308a5c8', 'utitle' => '레전드오브샤네르'],
    ['li' => 'bgEven', 'thumbRed' => false, 'rel' => 'fa18bdbbdc52bb99f0e2b192e5b1cb74', 'winFix' => null, 'win' => 0, 'lose' => 1, 'tit' => 'ㅇㄲ', 'titFull' => 'ㅇㄲ', 'date' => '11분전', 'cur' => 0, 'max' => 100, 'cid' => 'M16', 'nick' => '검은연꽃l', 'urel' => '55de85cac260e084f90067d0f9dfac51', 'utitle' => '검은연꽃l'],
    ['li' => '', 'thumbRed' => false, 'rel' => '4c7bca648b83040bdf74422265c413ed', 'winFix' => null, 'win' => 11, 'lose' => 16, 'tit' => '훈오팔', 'titFull' => '훈오팔', 'date' => '9시간전', 'cur' => 0, 'max' => 100, 'cid' => 'M20', 'nick' => '훈오팔님', 'urel' => '540329828315309fb8ef8581f72cf488', 'utitle' => '훈오팔님'],
    ['li' => 'bgEven', 'thumbRed' => false, 'rel' => '9a38f3c4160319fa5a727ecb168d282a', 'winFix' => null, 'win' => 19, 'lose' => 10, 'tit' => '꾼', 'titFull' => '꾼', 'date' => '12시간전', 'cur' => 0, 'max' => 100, 'cid' => 'M20', 'nick' => '파워볼꾼', 'urel' => 'e985ee06c1eeb8d82ebd07a49d57cdee', 'utitle' => '파워볼꾼'],
    ['li' => '', 'thumbRed' => true, 'rel' => 'f8a765c91866ac8480504c3bf478763a', 'winFix' => '1', 'win' => 15, 'lose' => 11, 'tit' => '모든분들에게 행복이 ...', 'titFull' => '모든분들에게 행복이 가득하길...', 'date' => '14시간전', 'cur' => 0, 'max' => 100, 'cid' => 'M14', 'nick' => '헨젤과그모텔', 'urel' => '6c25298536f09adb30d9791236b7fd0a', 'utitle' => '헨젤과그모텔'],
    ['li' => 'bgEven', 'thumbRed' => false, 'rel' => '6633dc243948654adf7705052fe39712', 'winFix' => null, 'win' => 4, 'lose' => 6, 'tit' => '초보', 'titFull' => '초보', 'date' => '14시간전', 'cur' => 0, 'max' => 100, 'cid' => 'M10', 'nick' => '향교리', 'urel' => '9e84518397c00a5a49e86ef498ecfda2', 'utitle' => '향교리'],
    ['li' => '', 'thumbRed' => false, 'rel' => '22df87faa906e8fc2f6a24cf3a839cec', 'winFix' => null, 'win' => 35, 'lose' => 26, 'tit' => '★엔젤드리븐★프젝3...', 'titFull' => '★엔젤드리븐★프젝3배목표문의방', 'date' => '22시간전', 'cur' => 0, 'max' => 100, 'cid' => 'M19', 'nick' => '엔젤드리븐', 'urel' => 'e4f9647d88c6a7a3f0078008dddc89bd', 'utitle' => '엔젤드리븐'],
    ['li' => 'bgEven', 'thumbRed' => false, 'rel' => '556add3fc75f39d5b0b2f2bc0cf9f807', 'winFix' => null, 'win' => 0, 'lose' => 0, 'tit' => 'ㅇㄱㅇ', 'titFull' => 'ㅇㄱㅇ', 'date' => '방금', 'cur' => 0, 'max' => 100, 'cid' => 'M13', 'nick' => '이강인S', 'urel' => '711b3f09a1678d923238568631ebc36c', 'utitle' => '이강인S'],
];
$chat_popup_mode = !empty($chat_popup_mode);
$room_list_height = $chat_popup_mode ? '460px' : '548px';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <title>파워볼게임 - 방채팅</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="all">
    <meta name="robots" content="index,follow">
    <link rel="stylesheet" href="<?= esc($local) ?>/css/index.css" type="text/css">
    <link rel="stylesheet" href="<?= esc($local) ?>/css/chat.css" type="text/css">
    <?php if ($chat_popup_mode) : ?>
    <link rel="stylesheet" href="<?= esc($local) ?>/css/chat_owner_pick.css" type="text/css">
    <?php endif; ?>
    <link rel="shortcut icon" href="<?= esc($local) ?>/favicon.ico">
    <script src="https://static.powerballgame.co.kr/chat/js/jquery-1.11.2.min.js"></script>
    <script src="<?= esc($local) ?>/js/jquery.simpleTicker.js"></script>
    <style>
        #msgBox li { line-height: 23px; min-height: 23px; }
        #msgBox li strong { font-weight: bold; }
        #msgBox li .time { color: #999; font-size: 11px; margin-left: 6px; }
        #connectList li { line-height: 23px; min-height: 23px; }
    </style>
</head>
<body onload="" class="<?= $chat_popup_mode ? 'chat-popup-window' : 'chat-embed-window' ?>">
    <div style="width:100%;margin-bottom:5px;">
        <div style="height:25px;line-height:25px;background-color:#4C4C4C;color:#fff;text-align:center;border:1px solid #151515;" id="chatTimer"><b class="minute"><?= sprintf('%02d', (int) floor(((int)($remain_time ?? 300)) / 60)) ?></b>분 <b class="second"><?= sprintf('%02d', ((int)($remain_time ?? 300)) % 60) ?></b>초 후 <b><span id="timeRound"><?= (int)($time_round ?? 1) ?></span>회차</b> 결과 발표</div>
        <div style="position:relative;height:40px;border-left:1px solid #CECECE;border-right:1px solid #CECECE;border-bottom:1px solid #676767;">
            <div style="position:absolute;top:0;left:-1px;"><img src="<?= esc(site_furl('images/graphFlag_p.png')) ?>" width="27" height="27"></div>
            <div id="powerballPointBetGraph">
                <div class="oddChart">
                    <span class="oddBar" style="width: 50px;"></span>
                    <span class="oddPer" style="right: 50px;">50%</span>
                </div>
                <div class="vsChart"></div>
                <div class="evenChart">
                    <span class="evenBar" style="width: 50px;"></span>
                    <span class="evenPer" style="left: 50px;">50%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="box-chatting">
        <div class="btn-etc">
            <span class="cnt"><div class="sp-bl_pp"></div><span id="connectUserCnt" rel="<?= (int) ($connect_user_cnt ?? 0) ?>"><?= (int) ($connect_user_cnt ?? 0) ?></span>명 <span id="loginUserCnt"></span></span>
            <ul class="ul-1">
                <li><a href="#" onclick="chatManager('popupChat');return false;" title="새창" class="sp-btn_chat1"></a></li>
                <li><a href="#" onclick="fontZoom(1);return false;" title="글씨크게" class="sp-btn_chat2"></a></li>
                <li><a href="#" onclick="fontZoom(-1);return false;" title="글씨작게" class="sp-btn_chat3"></a></li>
                <li><a href="#" onclick="chatManager('clearChat');return false;" title="채팅창 지우기" class="sp-btn_chat4"></a></li>
                <li><a href="#" onclick="chatManager('refresh');return false;" title="새로고침" class="sp-btn_chat5"></a></li>
                <li><a href="#" onclick="return false;" id="soundBtn" title="소리끄기" class="sp-btn_chat_sound on"></a></li>
            </ul>
        </div>
        <?php if ($chat_popup_mode) : ?>
        <div class="chat-popup-shell">
            <div class="chat-popup-tabs-left table-type-1">
                <ul class="ul-1" id="channelList">
                    <li class="channel1"><a href="#channel1" type="channel1" class="on">연병장</a></li>
                    <li class="roomList"><a href="#roomList" type="roomList">방채팅</a></li>
                    <li class="connectList"><a href="#connectList" type="connectList">접속자</a></li>
                    <li class="rule"><a href="#rule" type="rule">채팅규정</a></li>
                </ul>
            </div>
            <div class="ownerPickStyleRoot chat-popup-owner-head">
                <div class="resultBox">
                    <ul class="tab">
                        <li><a href="#" onclick="return false;" class="result on">방장픽 정보</a></li>
                    </ul>
                </div>
            </div>
            <div class="chat-popup-left">
        <?php else : ?>
        <div class="table-type-1">
                <ul class="ul-1" id="channelList">
                    <li class="channel1"><a href="#channel1" type="channel1" class="on">연병장</a></li>
                    <li class="roomList"><a href="#roomList" type="roomList">방채팅</a></li>
                    <li class="connectList"><a href="#connectList" type="connectList">접속자</a></li>
                    <li class="rule" style="border-right:none;"><a href="#rule" type="rule">채팅규정</a></li>
                </ul>
            </div>
        <?php endif; ?>
        <div id="chatListBox" style="position:relative;">
            <div id="news-ticker-slide" class="ticker" style="height:15px;">
                <ul>
                    <?php if ($notices === []) : ?>
                        <li><a href="<?= esc(site_furl('frame/customerCenter')) ?>" target="mainFrame">공지사항을 확인해주세요.</a></li>
                    <?php else : ?>
                        <?php foreach ($notices as $n) : ?>
                            <li><a href="<?= esc(site_furl('frame/customerCenter?id=' . (int) ($n->notice_fid ?? 0))) ?>" target="mainFrame"><?= esc((string) ($n->notice_title ?? '공지사항')) ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <ul class="list-chatting" id="msgBox"<?= $chat_popup_mode ? '' : ' style="height:419px;"' ?>></ul>
            <p class="input-chatting">
                <label for="msg" class="label">내용을 입력해 주세요.</label>
                <input type="text" name="msg" id="msg" class="input-1" autocomplete="off">
                <input type="button" class="input-2 sp-btn_enter" id="sendBtn">
                <a href="#" class="scrollBottom" id="scrollBottom" style="display:none;"></a>
            </p>
        </div>
        <div id="connectListBox" style="display:none;">
            <ul class="list-connect" id="connectList"<?= $chat_popup_mode ? '' : ' style="height:446px;"' ?>></ul>
        </div>
        <div id="roomListBox" style="display:none;">
            <div style="background-color:#F5F5F5;height:25px;line-height:25px;border:1px solid #CECECE;border-top:none;text-align:right;padding-right:5px;font-weight:bold;font-size:12px;"><a href="#" onclick="openChatRoom();return false;">채팅대기실</a></div>
            <ul class="list-room" id="roomList"<?= $chat_popup_mode ? '' : ' style="height:' . esc($room_list_height, 'attr') . ';"' ?>>
                <?php foreach ($staticChatRooms as $rm) :
                    $liClass = trim((string) ($rm['li'] ?? ''));
                    $thumbClass = 'thumb' . (!empty($rm['thumbRed']) ? ' red' : '');
                    $wf = $rm['winFix'] ?? null;
                ?>
                <li<?= $liClass !== '' ? ' class="' . esc($liClass) . '"' : '' ?>>
                    <div class="<?= esc($thumbClass) ?>" rel="<?= esc($rm['rel'] ?? '') ?>">
                        <img src="<?= esc($roomProfileImg) ?>" class="roomImg" alt="">
                        <?php if ($wf !== null && $wf !== '') : ?>
                        <div class="winFixCnt" style="z-index:100;"><?= esc((string) $wf) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="title">
                        <span class="win"><span><?= (int) ($rm['win'] ?? 0) ?></span>승</span>
                        <span class="lose"><span><?= (int) ($rm['lose'] ?? 0) ?></span>패</span>
                        <span class="bar">|</span>
                        <a href="#" class="tit" rel="<?= esc($rm['rel'] ?? '') ?>" title="<?= esc($rm['titFull'] ?? '') ?>" onclick="return false;"><?= esc($rm['tit'] ?? '') ?></a>
                        <span class="date"><?= esc($rm['date'] ?? '') ?></span>
                    </div>
                    <div class="sub">
                        <span class="b"><?= (int) ($rm['cur'] ?? 0) ?></span> / <span><?= (int) ($rm['max'] ?? 100) ?></span>
                        <span class="opener">
                            <img src="<?= esc($local) ?>/images/class/<?= esc($rm['cid'] ?? 'M1') ?>.gif" width="23" height="23" alt="">
                            <a href="#" onclick="return false;" title="<?= esc($rm['utitle'] ?? '') ?>" rel="<?= esc($rm['urel'] ?? '') ?>" class="uname"><?= esc($rm['nick'] ?? '') ?></a>
                        </span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div id="ruleBox" style="<?= $chat_popup_mode ? 'display:none;' : 'height:445px;display:none;' ?>">
            <div class="borderBox">
                <div class="tit">벙어리 사유</div>
                <ul>
                    <li>- 한 화면에 두번 이상 같은 글 반복 작성</li>
                    <li>- 상대 비방, 반말 또는 욕설</li>
                    <li>- 비매너 채팅</li>
                    <li>- 회원간 싸움 및 분란 조장</li>
                    <li>- 결과 거짓 중계</li>
                    <li>- 운영진의 판단하에 운영정책에 위배되는 행위</li>
                </ul>
            </div>
            <div class="borderBox">
                <div class="tit">접속 차단 사유</div>
                <ul>
                    <li>- 개인정보 발언 및 공유</li>
                    <li>- 타 사이트 홍보 및 발언</li>
                    <li>- 불법 프로그램 홍보</li>
                    <li>- 운영진 및 사이트 비방</li>
                    <li>- 지속적인 비매너 채팅</li>
                    <li>- 부모 및 성적 관련 욕설</li>
                </ul>
            </div>
            <div class="borderBox">
                <div class="tit">파워볼게임 간편주소</div>
                <ul>
                    <li>- powerballgame.co.kr</li>
                    <li>- 파워볼게임.com</li>
                </ul>
            </div>
        </div>
        <?php if ($chat_popup_mode) : ?>
            </div>
            <div class="ownerPickStyleRoot chat-popup-owner-body">
                <div class="resultBox">
                    <div class="content">
                        <div class="userListBox" id="userList" style="display:none;">
                            <ul class="userList" id="connectOpenerList"></ul>
                            <ul class="userList" id="connectManagerList"></ul>
                            <ul class="userList" id="connectUserList"></ul>
                        </div>
                        <div class="chat-popup-result-scroll">
                            <ul class="resultList" id="resultList"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="modalLayer">
        <form>
            <div class="passwdInput" id="modal_passwordInput">
                <div class="tit">
                    <h2>비밀번호입력</h2>
                    <a class="close" href="#" onclick="return false;"></a>
                </div>
                <div class="content">
                    <input type="password" name="roomPasswd" onkeypress="onlyNumber();" maxlength="4" id="roomPasswd" class="input">
                    <p class="tip">비밀번호를 입력하세요.</p>
                </div>
                <div class="btn">
                    <a href="#" onclick="return false;" class="btn_join" rel="" id="btn_joinPasswd">입장하기</a>
                </div>
            </div>

            <div class="passwdInput" id="modal_bulletConfirm">
                <div class="tit">
                    <h2>입장총알 안내</h2>
                    <a class="close" href="#" onclick="return false;"></a>
                </div>
                <div class="content" style="padding:15px 30px;">
                    <p class="tip">입장시 총알 <span style="color:red;"><span id="bullet"></span>개</span>가 소진됩니다.<br>입장하시겠습니까?</p>
                </div>
                <div class="btn">
                    <a href="#" onclick="return false;" class="btn_join" rel="" id="btn_joinBullet">입장하기</a>
                </div>
            </div>
        </form>
    </div>

    <div id="userLayer" style="display:none;">
        <div class="lutop"><span id="unickname"></span></div>
        <div class="game"></div>
    </div>

    <div id="powerballResultSound" style="width:0;height:0;"><audio id="jp_audio_0" preload="metadata" src="https://www.powerballgame.co.kr/sound/powerballResult.wav"></audio></div>

    <script>
    (function() {
        var baseUrl = <?= json_encode(site_furl('/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var classGif = <?= json_encode($local . '/images/class/' . $classGifId . '.gif', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var me = <?= json_encode((string) ($objMember->mb_uid ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var fontSize = 12;
        var isChatPopup = document.body.classList.contains("chat-popup-window");
        /** 방장픽 목록: 마지막으로 그린 최신 회차(null이면 아직 한 번도 안 그림 → 추첨 나올 때만 갱신) */
        var lastOwnerPickRenderedRound = null;
        function showChatPanel($el) {
            if (isChatPopup) {
                $el.css({ display: "flex" });
            } else {
                $el.show();
            }
        }

        function escHtml(s) { return $("<div>").text(s || "").html(); }

        window.chatManager = function(type) {
            if (type === "clearChat") { $("#msgBox").empty(); return; }
            if (type === "refresh") { lastOwnerPickRenderedRound = null; loadChatList(); return; }
            if (type === "popupChat") {
                var u = String(location.href || "");
                if (u.indexOf("view=chatRoom") === -1) {
                    u += (u.indexOf("?") >= 0 ? "&" : "?") + "view=chatRoom";
                }
                window.open(u, "chatPopup", "width=980,height=680,scrollbars=yes");
                return;
            }
        };
        window.fontZoom = function(delta) {
            fontSize = Math.max(10, Math.min(16, fontSize + (delta > 0 ? 1 : -1)));
            $("#msgBox").css("font-size", fontSize + "px");
        };
        window.openChatRoom = function() {
            $("#channelList a").removeClass("on");
            $("#channelList a[type='channel1']").addClass("on");
            showChatPanel($("#chatListBox"));
            $("#connectListBox,#roomListBox,#ruleBox").hide();
        };
        window.onlyNumber = function() { return true; };

        function renderTimer(remainSec, round) {
            remainSec = Math.max(0, parseInt(remainSec, 10) || 0);
            var m = Math.floor(remainSec / 60);
            var s = remainSec % 60;
            $("#chatTimer .minute").text((m < 10 ? "0" : "") + m);
            $("#chatTimer .second").text((s < 10 ? "0" : "") + s);
            if (round) $("#timeRound").text(String(round));
        }

        var chatTimerFromParentHub = false;
        try {
            chatTimerFromParentHub = window.parent && window.parent !== window;
        } catch (e) {}

        function syncTimer() {
            $.post(baseUrl, { view: "action", action: "ajaxChatTimer" }, function(resp) {
                if (!resp || resp.state !== "success") return;
                renderTimer(resp.remain_seconds, resp.time_round);
                if (typeof resp.connectUserCnt !== "undefined") {
                    $("#connectUserCnt").text(resp.connectUserCnt || 0).attr("rel", resp.connectUserCnt || 0);
                }
            }, "json");
        }

        if (chatTimerFromParentHub) {
            window.addEventListener("message", function(ev) {
                var d = ev.data;
                if (!d || d.type !== "drawTimerHub") return;
                try {
                    if (ev.source !== window.parent) return;
                } catch (e) { return; }
                renderTimer(d.remainSeconds, d.timeRound);
                if (typeof d.connectUserCnt !== "undefined") {
                    $("#connectUserCnt").text(d.connectUserCnt || 0).attr("rel", d.connectUserCnt || 0);
                }
            });
        }

        /** 파워볼 → 숫자합 순서. 줄별 class는 각각 홀→msg-odd, 짝→msg-even (선배님 페이지와 동일). */
        function buildDrawResultParts(ld) {
            if (!ld || !ld.round) return null;
            var dl = escHtml(String(ld.date_label || ""));
            var rno = escHtml(String(ld.round));
            var pb = escHtml(String(ld.powerball));
            var poe = escHtml(String(ld.powerball_odd_even || ""));
            var puo = escHtml(String(ld.powerball_under_over || ""));
            var sm = escHtml(String(ld.ball_sum));
            var soe = escHtml(String(ld.sum_odd_even || ""));
            var suo = escHtml(String(ld.sum_under_over || ""));
            var sz = escHtml(String(ld.sum_size || ""));
            var pbNum = parseInt(ld.powerball, 10); if (isNaN(pbNum)) pbNum = 0;
            var sumNum = parseInt(ld.ball_sum, 10); if (isNaN(sumNum)) sumNum = 0;
            var pbClass = (pbNum % 2 === 1) ? "msg-odd" : "msg-even";
            var sumClass = (sumNum % 2 === 1) ? "msg-odd" : "msg-even";
            var powerballLi = '<li><p class="' + pbClass + '"><span>[' + dl + '-' + rno + '회]</span> 파워볼 결과 [<span class="b">' + pb + '</span>][<span class="b">' + poe + '</span>][<span class="b">' + puo + '</span>]</p></li>';
            var sumLi = '<li><p class="' + sumClass + '"><span>[' + dl + '-' + rno + '회]</span> 숫자합 결과 [<span class="b">' + sm + '</span>][<span class="b">' + soe + '</span>][<span class="b">' + suo + '</span>][<span class="b">' + sz + '</span>]</p></li>';
            return { powerball: powerballLi, sum: sumLi };
        }

        /** 방장픽 .rs 배경이 CSS 기본값(68px)에 머물면 해당 스프라이트 규칙이 안 먹은 것 */
        function probeOwnerPickRsSprites() {
            if (!window.console) return;
            var nodes = document.querySelectorAll("#resultList li .rs");
            if (!nodes.length) return;
            var issues = [];
            for (var i = 0; i < nodes.length; i++) {
                var el = nodes[i];
                if (el.classList.contains("ready")) continue;
                var li = el.closest ? el.closest("li") : null;
                var liId = li && li.id ? li.id : "";
                var cls = [];
                for (var j = 0; j < el.classList.length; j++) {
                    if (el.classList[j] !== "rs") cls.push(el.classList[j]);
                }
                var spriteClass = cls.join(" ");
                var cs = window.getComputedStyle(el);
                var drx = (cs.getPropertyValue("background-position-x") || cs.backgroundPositionX || "").toString().trim();
                var dry = (cs.getPropertyValue("background-position-y") || cs.backgroundPositionY || "").toString().trim();
                var x = drx || "";
                var xNum = parseFloat(x);
                var isDefault = (x === "68px" || x === "68.0000px" || (!isNaN(xNum) && xNum === 68));
                if (isDefault) {
                    issues.push({
                        liId: liId,
                        spriteClass: spriteClass,
                        dataKey: li ? li.getAttribute("data-pick-sprite-key") : "",
                        bgPosX: x,
                        bgPosY: dry
                    });
                }
            }
            if (issues.length) {
                console.warn("[ownerPickSprite][DOM_DEFAULT_BG] .rs가 기본 좌표(68px) → 선택자 미일치 또는 스프라이트 미적용 의심", issues);
            } else if (window.__OWNER_PICK_DEBUG && nodes.length) {
                console.log("[ownerPickSprite][DOM_PROBE_OK] .rs " + nodes.length + "개 — 기본 68px 아님(스프라이트 적용됨)");
            }
        }

        /** 이상 시 경고는 항상. 진단용 log/table은 window.__OWNER_PICK_DEBUG === true 일 때만 */
        function logOwnerPickSpriteDiag(resp, opts) {
            opts = opts || {};
            if (!resp || !window.console) return;
            var miss = resp.pick_sprite_css_missing_keys;
            if (miss && miss.length) {
                console.warn("[ownerPickSprite][MISSING_CSS_RULE]", miss);
                if (resp.pick_sprite_audit) console.table(resp.pick_sprite_audit);
            }
            if (!window.__OWNER_PICK_DEBUG) return;
            var nAudit = (resp.pick_sprite_audit && resp.pick_sprite_audit.length) ? resp.pick_sprite_audit.length : 0;
            var nMiss = (miss && miss.length) ? miss.length : 0;
            if (opts.summary) {
                console.log("[ownerPickSprite][diag]", "audit행=" + nAudit + ", missingCss=" + nMiss);
            }
            if (resp.pick_sprite_audit) {
                console.table(resp.pick_sprite_audit);
                var focus = [];
                for (var a = 0; a < resp.pick_sprite_audit.length; a++) {
                    var row = resp.pick_sprite_audit[a];
                    if (!row) continue;
                    if ((row.pb_even && row.sum_even) || row.sum_small) focus.push(row);
                }
                if (focus.length) console.log("[ownerPickSprite][audit 짝·짝 또는 숫자합 소]", focus);
            }
        }

        /** 발표된 한 행 HTML (대기행 → 결과+패스 치환용과 전체 렌더 공통) */
        function ownerPickCompletedRowHtml(d) {
            var pickResultDash = '<div class="pickResultText"><div style="width:60px;text-align:center;">-</div></div>';
            d = d || {};
            var r = parseInt(d.round, 10) || 0;
            var dl = escHtml(String(d.date_label || ""));
            var pbRaw = d.powerball;
            var sumRaw = d.ball_sum;
            var rawSk = String(d.pick_sprite_key != null ? d.pick_sprite_key : "oouus");
            var sk = rawSk;
            if (!/^[oeumsb]{5}$/.test(sk)) {
                if (window.console && console.warn) {
                    console.warn("[ownerPickSprite][KEY_FALLBACK] 스프라이트 키 비정상 → oouus 대체", { round: r, rawKey: rawSk, powerball: pbRaw, ball_sum: sumRaw });
                }
                sk = "oouus";
            }
            return '<li id="pick-' + r + '" regdate="0" class="" style="display:list-item;"'
                + ' data-pick-sprite-key="' + escHtml(sk) + '"'
                + ' data-powerball="' + escHtml(String(pbRaw != null ? pbRaw : "")) + '"'
                + ' data-ball-sum="' + escHtml(String(sumRaw != null ? sumRaw : "")) + '">'
                + '<div class="num">' + dl + '<br>' + r + '회</div>'
                + '<div class="rs ' + escHtml(sk) + '"></div>'
                + '<div class="blank"></div>'
                + '<div class="pick pass"></div>'
                + pickResultDash
                + '</li>';
        }

        function ownerPickWaitingRowHtml(nr, dateLabelEscaped) {
            nr = parseInt(nr, 10) || 0;
            return '<li id="pick-' + nr + '" class="" style="display:list-item;">'
                + '<div class="num">' + dateLabelEscaped + '<br>' + nr + '회</div>'
                + '<div class="rs ready"></div>'
                + '<div class="blank"></div>'
                + '<div class="pick"></div>'
                + '<div class="pickResultText"></div>'
                + '</li>';
        }

        /** 맨 위 대기 행(li)을 떼지 않고 제자리에서 결과+패스로만 바꿈 — replaceWith보다 백판/깜빡임이 적음 */
        function ownerPickMorphWaitingLiToCompleted($li, d) {
            var dashInner = '<div style="width:60px;text-align:center;">-</div>';
            d = d || {};
            var r = parseInt(d.round, 10) || 0;
            var dl = escHtml(String(d.date_label || ""));
            var pbRaw = d.powerball;
            var sumRaw = d.ball_sum;
            var rawSk = String(d.pick_sprite_key != null ? d.pick_sprite_key : "oouus");
            var sk = rawSk;
            if (!/^[oeumsb]{5}$/.test(sk)) {
                if (window.console && console.warn) {
                    console.warn("[ownerPickSprite][KEY_FALLBACK] 스프라이트 키 비정상 → oouus 대체", { round: r, rawKey: rawSk, powerball: pbRaw, ball_sum: sumRaw });
                }
                sk = "oouus";
            }
            $li.attr("id", "pick-" + r);
            $li.attr("regdate", "0");
            $li.attr("data-pick-sprite-key", escHtml(sk));
            $li.attr("data-powerball", escHtml(String(pbRaw != null ? pbRaw : "")));
            $li.attr("data-ball-sum", escHtml(String(sumRaw != null ? sumRaw : "")));
            $li.find(".num").first().html(dl + "<br>" + r + "회");
            $li.find(".rs").first().attr("class", "rs " + sk);
            $li.find(".pick").first().attr("class", "pick pass");
            $li.find(".pickResultText").first().html(dashInner);
        }

        /** 추첨 반영 행(결과+패스)에만 붙임 — 전체 목록 비우기로 인한 깜빡임 시 첫 행이 대기로 잡히는 것 방지 */
        function ownerPickAnimateTargetRow($list) {
            var $done = $list.find("li .pick.pass").first().closest("li");
            if ($done.length) return $done;
            var $first = $list.children("li").first();
            if ($first.length && !$first.hasClass("resultList-empty")) return $first;
            return $();
        }

        function scheduleOwnerPickAnimatePush($row) {
            if (!$row || !$row.length) return;
            var el = $row[0];
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    setTimeout(function() {
                        $row.addClass("animate-push");
                        function clearAnim(ev) {
                            if (ev && ev.animationName && ev.animationName !== "slideDownPush") return;
                            el.removeEventListener("animationend", clearAnim);
                            $row.removeClass("animate-push");
                        }
                        el.addEventListener("animationend", clearAnim);
                        setTimeout(function() {
                            if ($row.hasClass("animate-push")) {
                                $row.removeClass("animate-push");
                            }
                        }, 650);
                    }, 0);
                });
            });
        }

        /** 방장픽 우측 #resultList — 선배님 DOM과 동일 (num → rs → blank → pick|pick pass → pickResultText). 발표됨: pick pass, 다음 회차 대기: pick만 */
        function renderOwnerPickResultList(draws, nextRound, nextDateLabel, lastDraw, shouldAnimatePush) {
            var $list = $("#resultList");
            if (!$list.length) return;
            draws = draws || [];
            nextRound = parseInt(nextRound, 10) || 0;
            nextDateLabel = escHtml(String(nextDateLabel || ""));
            var newestCompleted = 0;
            if (draws.length > 0) {
                newestCompleted = parseInt(draws[0].round, 10) || 0;
            } else if (lastDraw && lastDraw.round) {
                newestCompleted = parseInt(lastDraw.round, 10) || 0;
            }

            var $top = $list.children("li").first();
            var topId = String($top.attr("id") || "");
            var idMatch = /^pick-(\d+)$/.exec(topId);
            var topRound = idMatch ? parseInt(idMatch[1], 10) : 0;
            var d0Round = draws.length > 0 ? (parseInt(draws[0].round, 10) || 0) : 0;
            var canMorphInPlace = shouldAnimatePush
                && draws.length > 0
                && $top.length
                && !$top.hasClass("resultList-empty")
                && topRound > 0
                && topRound === d0Round
                && topRound === newestCompleted
                && $top.find(".rs").first().hasClass("ready");

            if (canMorphInPlace) {
                var d0 = draws[0];
                /* ① 맨 위 대기 행을 제자리에서 결과+패스로 갱신 → ② 그 다음에만 다음 회차 대기 행을 위에 붙임 (prepend 먼저 하면 갱신이 “아래 줄”로 보임) */
                ownerPickMorphWaitingLiToCompleted($top, d0);
                var insertedWaiting = false;
                if (nextRound > 0 && nextRound > newestCompleted) {
                    $list.prepend(ownerPickWaitingRowHtml(nextRound, nextDateLabel));
                    insertedWaiting = true;
                }
                var tailFrom = insertedWaiting ? 2 : 1;
                var $kids = $list.children("li");
                if ($kids.length > tailFrom) {
                    $kids.slice(tailFrom).remove();
                }
                for (var t = 1; t < draws.length; t++) {
                    $list.append(ownerPickCompletedRowHtml(draws[t]));
                }
                var $animFallback = insertedWaiting ? $list.children("li").eq(1) : $list.children("li").eq(0);
                var $animRow = ownerPickAnimateTargetRow($list);
                scheduleOwnerPickAnimatePush($animRow.length ? $animRow : $animFallback);
                return;
            }

            var html = "";
            if (nextRound > 0 && nextRound > newestCompleted) {
                html += ownerPickWaitingRowHtml(nextRound, nextDateLabel);
            }
            for (var i = 0; i < draws.length; i++) {
                html += ownerPickCompletedRowHtml(draws[i] || {});
            }
            if (html === "") {
                html = '<li class="resultList-empty">등록된 추첨 결과가 없습니다.</li>';
            }
            var prevListH = $list.outerHeight();
            if (prevListH > 0) {
                $list.css("min-height", prevListH + "px");
            }
            $list.html(html);
            if (prevListH > 0) {
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        $list.css("min-height", "");
                    });
                });
            }
            if (shouldAnimatePush) {
                scheduleOwnerPickAnimatePush(ownerPickAnimateTargetRow($list));
            }
        }

        var lastMsgId = 0;
        var isFirstLoad = true;

        function loadChatList() {
            $.post(baseUrl, { view: "action", action: "ajaxChatList" }, function(resp) {
                if (!resp || resp.state !== "success") return;
                var rows = resp.messages || [];
                var $msg = $("#msgBox");
                var $con = $("#connectList");

                $con.empty();
                var users = {};

                // Find only new messages
                var newMsgs = [];
                for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    users[r.uid] = true;
                    if (r.id > lastMsgId) {
                        newMsgs.push(r);
                    }
                }

                // newMsgs is in DESC order. Reverse to append in ASC order (oldest new -> newest new)
                newMsgs.reverse();

                var appendedSomething = false;

                if (isFirstLoad) {
                    $msg.empty();
                }

                var nickMap = (resp.nicknames && typeof resp.nicknames === "object") ? resp.nicknames : {};
                for (var j = 0; j < newMsgs.length; j++) {
                    var r2 = newMsgs[j];
                    var uid = String(r2.uid || "");
                    var displayName = String(r2.nickname || nickMap[uid] || uid || "");
                    var txt = String(r2.message || "");
                    var time = String(r2.time || "");
                    var li = '<li><span style="position:relative;"><img src="' + escHtml(classGif) + '" width="23" height="23"></span> '
                        + '<strong><a href="#" onclick="return false;" class="uname">' + escHtml(displayName) + '</a></strong> '
                        + escHtml(txt) + '<span class="time">' + escHtml(time) + '</span></li>';
                    $msg.append(li);
                    lastMsgId = Math.max(lastMsgId, r2.id);
                    appendedSomething = true;
                }

                var ld = resp.lastDraw;
                var rNew = ld && ld.round ? parseInt(ld.round, 10) : 0;
                var prevR = window.__chatLastDrawRound;
                var newRoundJust = (prevR !== undefined && prevR !== null && rNew > prevR);

                if (isFirstLoad) {
                    if (ld && ld.round) {
                        var parts = buildDrawResultParts(ld);
                        if (parts) {
                            $msg.append(parts.powerball);
                            $msg.append(parts.sum);
                            window.__chatLastDrawRound = rNew;
                        }
                    }
                    $msg.append('<li><p class="msg-guide"><span>연병장</span>에 입장 하셨습니다.</p></li>');
                    $msg.scrollTop($msg[0].scrollHeight);
                    isFirstLoad = false;
                } else {
                    if (newRoundJust && ld && ld.round) {
                        var parts2 = buildDrawResultParts(ld);
                        if (parts2) {
                            $msg.append(parts2.powerball);
                            try {
                                var a0 = document.getElementById("jp_audio_0");
                                if (a0) {
                                    a0.currentTime = 0;
                                    var p = a0.play();
                                    if (p !== undefined) { p.catch(function(){}); }
                                }
                            } catch (e0) {}
                            setTimeout(function() {
                                $msg.append(parts2.sum);
                                $msg.scrollTop($msg[0].scrollHeight);
                            }, 380);
                            window.__chatLastDrawRound = rNew;
                            appendedSomething = true;
                        }
                    }
                    if (appendedSomething) {
                        $msg.scrollTop($msg[0].scrollHeight);
                    }
                }

                // Prevent infinite DOM bloat
                var currentItems = $msg.children('li');
                if (currentItems.length > 200) {
                    currentItems.slice(0, currentItems.length - 200).remove();
                }

                Object.keys(users).forEach(function(u) {
                    var un = nickMap[u] || u;
                    $con.append('<li><span style="position:relative;"><img src="' + escHtml(classGif) + '" width="23" height="23"></span> <strong>'
                        + '<a href="#" onclick="return false;" class="uname">' + escHtml(un) + '</a></strong></li>');
                });
                $("#connectUserCnt").text(resp.connectUserCnt || 0).attr("rel", resp.connectUserCnt || 0);
                if (isChatPopup) {
                    if (window.__OWNER_PICK_DEBUG && !window.__ownerPickSpriteInitLogged) {
                        window.__ownerPickSpriteInitLogged = true;
                        console.log("[ownerPickSprite][init] body=" + (document.body && document.body.className) + " #resultList=" + (document.getElementById("resultList") ? "1" : "0") + " " + String(location.href || ""));
                    }
                    logOwnerPickSpriteDiag(resp, { summary: true });
                    setTimeout(probeOwnerPickRsSprites, 0);
                } else if (window.__OWNER_PICK_DEBUG && !window.__ownerPickEmbedNoted) {
                    window.__ownerPickEmbedNoted = true;
                    console.log("[ownerPickSprite] embed 모드 — 팝업은 view=chatRoom");
                }
                if (isChatPopup && $("#resultList").length) {
                    var dr = resp.recentDraws || [];
                    var newestRound = 0;
                    if (dr.length > 0) {
                        newestRound = parseInt(dr[0].round, 10) || 0;
                    } else if (resp.lastDraw && resp.lastDraw.round) {
                        newestRound = parseInt(resp.lastDraw.round, 10) || 0;
                    }
                    var needOwnerPick =
                        lastOwnerPickRenderedRound === null
                        || newestRound > lastOwnerPickRenderedRound;
                    if (needOwnerPick) {
                        var prevPickRound = lastOwnerPickRenderedRound;
                        var pushAnimate = prevPickRound !== null && newestRound > prevPickRound;
                        renderOwnerPickResultList(dr, resp.time_round, resp.next_draw_date_label, resp.lastDraw, pushAnimate);
                        lastOwnerPickRenderedRound = newestRound;
                    }
                }
            }, "json");
        }

        function sendMessage() {
            var msg = $.trim($("#msg").val());
            if (!msg) return;
            $.post(baseUrl, { view: "action", action: "ajaxChatSend", message: msg }, function(resp) {
                if (!resp || resp.state !== "success") {
                    alert("로그인 후 이용가능합니다.");
                    return;
                }
                $("#msg").val("");
                loadChatList();
            }, "json");
        }

        $("#sendBtn").on("click", sendMessage);
        $("#msg").on("keydown", function(e) { if (e.keyCode === 13) { e.preventDefault(); sendMessage(); } });

        // 내용 입력 라벨 처리 (클릭/포커스 시 사라짐)
        var $msgLabel = $("label[for='msg']");
        $msgLabel.on("click", function() {
            $("#msg").focus();
        });
        $("#msg").on("focus", function() {
            $msgLabel.hide();
        }).on("blur", function() {
            if ($.trim($(this).val()) === "") {
                $msgLabel.show();
            }
        }).on("keyup change input", function() {
            if ($.trim($(this).val()) !== "") {
                $msgLabel.hide();
            }
        });

        $("#channelList a").on("click", function(e) {
            e.preventDefault();
            var type = $(this).attr("type");
            $("#channelList a").removeClass("on");
            $(this).addClass("on");
            $("#chatListBox,#connectListBox,#roomListBox,#ruleBox").hide();
            if (type === "channel1") showChatPanel($("#chatListBox"));
            if (type === "connectList") showChatPanel($("#connectListBox"));
            if (type === "roomList") showChatPanel($("#roomListBox"));
            if (type === "rule") showChatPanel($("#ruleBox"));
        });

        $("#news-ticker-slide").simpleTicker({ speed : 600, delay : 4000, easing : 'swing', effectType : 'slide' });
        loadChatList();
        if (!chatTimerFromParentHub) {
            syncTimer();
            setInterval(syncTimer, 1000);
        }
        setInterval(loadChatList, 2500);
    })();
    </script>
</body>
</html>
