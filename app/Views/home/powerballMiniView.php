<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>실시간 파워볼게임 미니뷰</title>
    <?php $local = rtrim(site_furl(''), '/'); ?>
    <link rel="stylesheet" href="<?php echo $local; ?>/css/common.css?v=<?php echo time(); ?>" type="text/css"/>
    <link rel="stylesheet" href="<?php echo $local; ?>/css/sprites.css?v=<?php echo time(); ?>" type="text/css"/>
    <link rel="stylesheet" href="<?php echo $local; ?>/css/font-local.css" type="text/css"/>
    <link rel="stylesheet" href="<?php echo $local; ?>/css/jquery-ui.css?v=<?= @filemtime(FCPATH.'css/jquery-ui.css') ?: time() ?>" type="text/css"/>
</head>
<body>
<div id="ladderResultBox" style="display:none;">
    <div class="title">
        <div>실시간</div>
        <div>파워볼게임</div>
        <div>미니뷰</div>
        <a href="#" onclick="toggleMiniView(); return false;" class="close"></a>
    </div>
    <div class="content">
        <div class="leftBox">
            <div style="position:absolute;top:4px;left:219px;z-index:99;">
                <a href="<?php echo site_furl(''); ?>?referer=miniViewBtn" target="_blank" class="link"></a>
            </div>
            <div id="ladderReady" style="display:block;">
                <div class="box">
                    <div class="time" id="ladderTimer">
                        <?php $remain_min = (int)floor(($remain_time ?? 0) / 60); $remain_sec = (int)(($remain_time ?? 0) % 60); ?>
                        <em class="minute"><?= $remain_min ?></em>분 <em class="second"><?= $remain_sec ?></em>초 후 <span id="timeRound"><?= (int)($time_round ?? 0) ?></span> 회차 결과 발표
                    </div>
                    <div class="result">
                        [<span id="lastRound"><?= esc($last_round ?? '') ?></span>회차] 결과는<br>
                        [<span id="lastResult" style="font-size:15px;"><?php
                            $lrh = $last_result_html ?? '';
                            if ($lrh !== '') {
                                echo $lrh;
                            } else {
                                echo esc($last_result ?? '-');
                            }
                        ?></span>] 입니다.
                    </div>
                </div>               
            </div>
            <!-- 추첨결과에 따라 공 번호·색상은 JS(updateResult/showNumber/ballColorSel)에서 동적 생성 -->
            <div id="lotteryBox">
                <div id="lotteryBall"></div>
                <div class="play" style="display:none;"><img src="<?php echo rtrim(site_furl(""), "/"); ?>/images/lottery_play.gif" height="265" alt=""></div>
            </div>
        </div>
        <?php
        // PHP에서도 선배님 JS(ballColorSel)와 동일한 색상 매핑을 사용해 초기 화면을 그린다.
        if (!function_exists('mini_ball_color')) {
            function mini_ball_color(int $n): string {
                switch ($n) {
                    case 1: case 5: case 9: case 13: case 17: case 21: case 25:
                        return 'red';
                    case 2: case 6: case 10: case 14: case 18: case 22: case 26:
                        return 'yellow';
                    case 3: case 7: case 11: case 15: case 19: case 23: case 27:
                        return 'green';
                    default:
                        return 'blue';
                }
            }
        }
        $currentBalls = isset($current_balls) && is_array($current_balls) ? $current_balls : [];
        $prevBalls    = isset($prev_balls) && is_array($prev_balls) ? $prev_balls : [];
        ?>
        <div class="rightBox">
            <div class="tit"><span class="lastRound" id="lastRoundTit"><?= esc($last_round ?? '') ?></span> 회차 결과</div>
            <div id="lotteryResultBox">
                <div id="lotteryResult">
                    <?php if (!empty($currentBalls)): ?>
                        <?php foreach ($currentBalls as $idx => $n): ?>
                            <?php $nInt = (int)$n; ?>
                            <?php
                            /** 일반볼 5개: 2자리 · 파워볼(0~9)만 1자리 — 0은 "0" (00 아님) */
                            $ballLabel = ($idx < 5) ? sprintf('%02d', $nInt) : sprintf('%d', $nInt);
                            ?>
                            <span class="ball_<?= mini_ball_color($nInt) ?>"><span class="ballNumber"><?= esc($ballLabel) ?></span></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>            
                <div class="tit2">이전 회차 결과</div>
                <div id="beforeResult">
                    <?php if (!empty($prevBalls)): ?>
                        <?php foreach ($prevBalls as $idx => $n): ?>
                            <?php $nInt = (int)$n; ?>
                            <?php
                            $ballLabelPrev = ($idx < 5) ? sprintf('%02d', $nInt) : sprintf('%d', $nInt);
                            ?>
                            <span class="ball_<?= mini_ball_color($nInt) ?>"><span class="ballNumber"><?= esc($ballLabelPrev) ?></span></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toggle_in" style="position:relative;width:830px;">
    <div class="bettingBtn">
        <a href="#" onclick="toggleBetting(); return false;" class="betting">픽 열기</a>
    </div>
    <div class="miniViewBtn">
        <a href="#" class="miniView">미니뷰 열기</a>
    </div>
    <div class="betBox" id="betBox" style="display:none;">
        <div class="title">픽</div>
        <form name="bettingForm" id="bettingForm" method="post" action="<?php echo site_furl(''); ?>">
            <input type="hidden" name="view" value="action">
            <input type="hidden" name="action" value="betting">
            <input type="hidden" name="actionType" value="bet">
            <input type="hidden" name="powerballOddEven" id="powerballOddEven" value="">
            <input type="hidden" name="numberOddEven" id="numberOddEven" value="">
            <input type="hidden" name="powerballUnderOver" id="powerballUnderOver" value="">
            <input type="hidden" name="numberUnderOver" id="numberUnderOver" value="">
            <input type="hidden" name="numberPeriod" id="numberPeriod" value="">
            <input type="hidden" name="point" id="point" value="">
            <ul class="betting">
                <li>
                    <span class="titleBox">파워볼</span>
                    <div style="margin-top:5px;"><span class="titleBox">숫자합</span></div>
                </li>
                <li>
                    <span class="btn" type="powerballOddEven" val="odd">홀</span>
                    <span class="btn" type="powerballOddEven" val="even">짝</span>
                    <div style="margin-top:5px;">
                        <span class="btn" type="numberOddEven" val="odd">홀</span>
                        <span class="btn" type="numberOddEven" val="even">짝</span>
                    </div>
                </li>
                <li>
                    <span class="btn" type="powerballUnderOver" val="under">언더</span>
                    <span class="btn" type="powerballUnderOver" val="over">오버</span>
                    <div style="margin-top:5px;">
                        <span class="btn" type="numberUnderOver" val="under">언더</span>
                        <span class="btn" type="numberUnderOver" val="over">오버</span>
                    </div>
                </li>
                <li>
                    <div style="margin-top:5px;">
                        <span class="btn" type="numberPeriod" val="big">대</span>
                        <span class="btn" type="numberPeriod" val="middle">중</span>
                        <span class="btn" type="numberPeriod" val="small">소</span>
                    </div>
                </li>
                <li class="btnBox" style="position:relative;">
                    <div class="left">
                        <span class="pick" onclick="powerballBetting();">픽</span>
                    </div>
                    <div class="right">
                        <div class="reset" onclick="resetPowerballBetting();">리셋</div>
                        <div class="point">
                            <span class="totalPoint">총 포인트 <em>0</em></span>
                            <select id="selectPoint"><option value="100">100</option><option value="500">500</option><option value="1000">1000</option></select>
                            <a href="<?php echo site_furl(''); ?>?view=bettingLog" target="mainFrame" class="log">픽 내역</a>
                        </div>
                    </div>
                </li>
            </ul>
        </form>
    </div>
    <div class="bet_captchaBox" id="captchaBox" style="display:none;">
        <div class="title">로봇 <span>방지</span></div>
        <form name="captchaForm" id="captchaForm" method="post" action="<?php echo site_furl(''); ?>">
            <input type="hidden" name="view" value="action">
            <input type="hidden" name="action" value="betting">
            <input type="hidden" name="actionType" value="captcha">
            <input type="hidden" name="captchaNum" id="captchaNum" value="">
            <div class="captchaImg" id="captchaImg"></div>
            <div class="inputBox">
                <p class="form_notice" style="margin-top:15px;"><strong>좌측 숫자</strong> 입력</p>
                <div class="auth_guide">
                    <input type="text" maxlength="1" name="captchaNum1" id="captchaNum1" readonly>
                    <input type="text" maxlength="1" name="captchaNum2" id="captchaNum2" readonly>
                </div>
                <p class="form_notice">키패드를 마우스로 클릭해 입력해 주세요.</p>
            </div>
            <div class="keypadBox">
                <div class="keypad">
                    <ul class="pad">
                        <li class="num1">1</li><li class="num7">7</li><li class="num3">3</li><li class="num8">8</li><li class="num9">9</li><li class="num4">4</li>
                        <li class="reset">모두 지우기</li>
                        <li class="num2">2</li><li class="num5">5</li><li class="num6">6</li><li class="num0">0</li>
                        <li class="delete">삭제</li>
                    </ul>
                </div>
            </div>
            <div class="btnBox">
                <div class="btn" onclick="runCaptcha();">확인</div>
            </div>
        </form>
    </div>
</div>

<?php
$mvdbg = isset($_GET['mvdbg']) && (string) $_GET['mvdbg'] === '1';
/** 라이온 등 외부 팝업 iframe: ?openMini=1 이면 초기 로드 시 미니뷰(추첨 패널) 펼침 */
$openMiniEmbed = isset($_GET['openMini']) && (string) $_GET['openMini'] === '1';
$miniViewJsPath = FCPATH . 'js' . DIRECTORY_SEPARATOR . 'powerballMiniView.js';
$miniViewJsVer = @filemtime($miniViewJsPath) ?: time();
?>
<?php /* 타이머/허브 진단: ?mvdbg=1 또는 localStorage MINIVIEW_DEBUG=1. 콘솔 필터 [miniview-debug] */ ?>
<script>
<?php if ($mvdbg): ?>
window.MINIVIEW_DEBUG = true;
if (typeof console !== 'undefined' && console.info) {
	console.info('[miniview-debug] probe: server saw mvdbg=1 → MINIVIEW_DEBUG=true (before powerballMiniView.js)');
}
<?php endif; ?>
window.POWERBALL_AJAX_URL = '<?php echo site_furl(''); ?>';
window.POWERBALL_BASE_URL = '<?php echo site_furl(''); ?>';
window.CI_APP_DEBUG = <?= json_encode(function_exists('ci_app_debug') ? ci_app_debug() : (string) ($_ENV['CI_ENVIRONMENT'] ?? '') === 'development', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.PB_OPEN_MINI_EMBED = <?= !empty($openMiniEmbed) ? 'true' : 'false' ?>;
var remainTime = <?= (int)($remain_time ?? 300) ?>;
function setCookie(n,v){ try{ document.cookie = n+'='+v+'; path=/'; } catch(e){} }
function getCookie(n){ var m = document.cookie.match(new RegExp('(^| )'+n+'=([^;]+)')); return m ? m[2] : ''; }
</script>
<script src="<?php echo $local; ?>/js/jquery-1.11.2.min.js"></script>
<script src="<?php echo $local; ?>/js/jquery-ui.js"></script>
<script>window.jQuery && !$.fn.number && ($.number = function(n){ return n == null ? '0' : String(n); });</script>
<script src="<?php echo $local; ?>/js/TweenMax.min.js"></script>
<script src="<?php echo $local; ?>/js/powerballMiniView.js?v=<?= (int) $miniViewJsVer ?>"></script>
<script>
$(function(){
    window.showLadderResultBox = function(){
        setCookie('MINIVIEWLAYER','Y');
        $('#ladderResultBox').show();
        $('.miniViewBtn a.miniView').text('미니뷰 닫기');
        try {
            if (typeof window.scheduleMiniViewSyncBurst === 'function') {
                window.scheduleMiniViewSyncBurst();
            }
        } catch (e) {}
    };
    window.hideLadderResultBox = function(){
        setCookie('MINIVIEWLAYER','N');
        $('#ladderResultBox').hide();
        $('.miniViewBtn a.miniView').text('미니뷰 열기');
    };
    if (typeof window.PB_OPEN_MINI_EMBED !== 'undefined' && window.PB_OPEN_MINI_EMBED) {
        showLadderResultBox();
    } else if (getCookie('MINIVIEWLAYER') == 'Y') {
        showLadderResultBox();
    }
    /** 부모가 다른 도메인이면 접근 시 SecurityError → try/catch 로 null */
    function getMiniViewControlHub() {
        try {
            if (window.parent && window.parent !== window) {
                var p = window.parent.miniViewControl;
                if (typeof p === 'function') {
                    return { fn: p, ctx: window.parent };
                }
            }
        } catch (e) {}
        try {
            if (window.top && window.top !== window) {
                var t = window.top.miniViewControl;
                if (typeof t === 'function') {
                    return { fn: t, ctx: window.top };
                }
            }
        } catch (e) {}
        return null;
    }
    $('.miniViewBtn a.miniView').on('click', function(e){
        e.preventDefault();
        var isVisible = $('#ladderResultBox').is(':visible');
        var hub = getMiniViewControlHub();
        if (window.CI_APP_DEBUG && console && console.log) {
            console.log('[미니뷰] 버튼 클릭, ladderResultBox visible:', isVisible, '| same-origin miniViewControl:', !!hub);
        }
        if (isVisible) {
            hideLadderResultBox();
            try { if (hub) { hub.fn.call(hub.ctx, 'close'); } } catch (err) { if (window.CI_APP_DEBUG && console) { console.error('[미니뷰] miniViewControl(close)', err); } }
        } else {
            showLadderResultBox();
            try { if (hub) { hub.fn.call(hub.ctx, 'open'); } } catch (err) { if (window.CI_APP_DEBUG && console) { console.error('[미니뷰] miniViewControl(open)', err); } }
        }
        return false;
    });
});
</script>
</body>
</html>
