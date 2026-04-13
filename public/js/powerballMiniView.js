/**
 * 진단 로그: URL에 mvdbg=1 또는 localStorage MINIVIEW_DEBUG=1 또는 window.MINIVIEW_DEBUG===true
 * 콘솔 필터: [miniview-debug]
 */
function miniviewDebugEnabled() {
	try {
		if (window.MINIVIEW_DEBUG === true) {
			return true;
		}
		if (typeof localStorage !== 'undefined' && localStorage.getItem('MINIVIEW_DEBUG') === '1') {
			return true;
		}
		var q = window.location && window.location.search ? window.location.search : '';
		if (typeof URLSearchParams !== 'undefined') {
			var sp = new URLSearchParams(q);
			if (sp.get('mvdbg') === '1') {
				return true;
			}
		}
		if (q.indexOf('mvdbg=1') !== -1) {
			return true;
		}
	} catch (e) {}
	return false;
}

function miniviewDebugLog() {
	if (!miniviewDebugEnabled() || typeof console === 'undefined' || !console.log) {
		return;
	}
	var args = Array.prototype.slice.call(arguments);
	args.unshift('[miniview-debug]');
	try {
		console.log.apply(console, args);
	} catch (e) {}
}

/**
 * dayLog 메인과 같은 출처일 때만 부모 drawTimerHub(postMessage) 사용.
 * void parent.location.href 로 판별하면 환경에 따라 오판할 수 있어 origin 비교(교차 출처는 접근 시 예외)로 고정.
 */
var miniViewUsesParentHub = false;
var _miniviewHubDetectError = null;
try {
	if (window.parent && window.parent !== window) {
		var childOrigin = window.location.origin || (window.location.protocol + '//' + window.location.host);
		// 교차 출처면 parent.location.* 접근 시 예외 → hub 끔
		var parentOrigin = window.parent.location.origin;
		miniViewUsesParentHub = (String(childOrigin) === String(parentOrigin));
		miniviewDebugLog('hub-detect ok', {
			childOrigin: childOrigin,
			parentOrigin: parentOrigin,
			miniViewUsesParentHub: miniViewUsesParentHub
		});
	} else {
		miniviewDebugLog('hub-detect', { note: 'parent missing or parent===self', miniViewUsesParentHub: false });
	}
} catch (e) {
	_miniviewHubDetectError = e && e.message ? e.message : String(e);
	miniViewUsesParentHub = false;
	miniviewDebugLog('hub-detect exception (cross-origin parent expected)', {
		message: _miniviewHubDetectError,
		miniViewUsesParentHub: false
	});
}

window.__miniviewDebugState = window.__miniviewDebugState || {};
window.__miniviewDebugState.hub = miniViewUsesParentHub;
window.__miniviewDebugState.hubDetectError = _miniviewHubDetectError;

/**
 * iframe 문서만 hidden 인데 부모 탭은 보이는 경우가 있어(로그에 hasFocus:false·visibility 깜빡임 등),
 * 허브 모드에서는 부모 document.hidden 이 false 이면 타이머/허브 UI 를 진행한다.
 */
function miniViewShouldSkipDueToIframeHidden() {
	if (typeof document === 'undefined' || !document.hidden) {
		return false;
	}
	if (miniViewUsesParentHub && window.parent && window.parent !== window) {
		try {
			if (window.parent.document && !window.parent.document.hidden) {
				return false;
			}
		} catch (e) {}
	}
	return true;
}

var _mvLadderTickCount = 0;

/** 같은 회차의 updateResult(공 튀기 애니메이션) 중복 호출 방지 */
var _lastMiniViewAnimatedRound = null;
/** updateResult 내부 setTimeout·showNumber 연쇄 취소용 (새 추첨 시작 시 이전 애니메이션 무시) */
var _updateResultAnimGen = 0;

function ladderResultTimer(divId)
{
	// 백그라운드 탭에서는 타이머가 분 단위로만 돌아가 remainTime이 크게 밀림 → 감춤일 땐 감소 생략, 복귀 시 서버 동기화로 맞춤
	if (typeof document !== 'undefined' && miniViewShouldSkipDueToIframeHidden()) {
		if (miniviewDebugEnabled()) {
			miniviewDebugLog('ladderTimer skipped: document.hidden=true');
		}
		return;
	}
	_mvLadderTickCount++;
	if (miniviewDebugEnabled() && (_mvLadderTickCount <= 15 || _mvLadderTickCount % 30 === 0)) {
		miniviewDebugLog('ladderTimer tick', {
			tick: _mvLadderTickCount,
			remainTimeBefore: remainTime,
			divId: divId,
			timerNodes: $('#' + divId).length
		});
	}
	if(remainTime == 0)
	{
		remainTime = 300;
		var roundNum = parseInt($('#timeRound').text(), 10) + 1;
		$('#timeRound').text(roundNum);
		$('.nextRound').text(roundNum);
		var drawUrl = (window.POWERBALL_BASE_URL || window.POWERBALL_AJAX_URL || '').replace(/\/$/, '') + '/lottery/getDrawResult';
		$.getJSON(drawUrl).done(function(data){
			if(data && (data.round != null || data.ball1 != null)){
				updateResult(data);
			}
		}).fail(function(){
			$('#lotteryBox .play').hide();
			$('#ladderReady').show();
		});
	}

	remainTime--;

	if(remainTime <= 10 && remainTime >= 0){
		$('#lotteryBox .play').css('display', 'block').show();
		$('#ladderReady').hide();
	}

	var remain_i = Math.floor(remainTime / 60);
	var remain_s = Math.floor(remainTime % 60);

	$('#'+divId).find('.minute').text(remain_i);
	$('#'+divId).find('.second').text(remain_s);
}

/** 최근 결과 DOM을 이전 회차로 옮김. lotteryBallSlot_* id를 그대로 복사하면 새 추첨에서 같은 id가 생겨 DOM이 깨지므로 id 제거 */
function miniViewMoveCurrentResultToBefore() {
	var $kids = $('#lotteryResult').children().clone();
	if ($kids.length === 0) {
		/* lotteryResult 가 비어 있으면(동기화 레이스·첫 갱신 등) beforeResult 를 비우지 않음 — PHP 이전 회차 공 유지 */
		return;
	}
	$kids.removeAttr('id');
	$kids.find('[id]').removeAttr('id');
	$('#beforeResult').empty().append($kids);
}

/** 포커스 복귀 동기화용: 같은 회차는 공 애니 없이 즉시 패널만 정합 */
function miniViewRenderResultInstant(data) {
	if (!data) return;
	var numStr = data.number;
	if (!numStr && data.ball1 != null) {
		numStr = [data.ball1, data.ball2, data.ball3, data.ball4, data.ball5].map(function(n){
			var s = '' + n;
			return s.length < 2 ? '0' + s : s;
		}).join('');
	}
	numStr = numStr || '';
	var round = parseInt(data.round, 10);
	if (!isNaN(round)) {
		$('#timeRound').text(round + 1);
		$('.nextRound').text(round + 1);
		$('#lastRound').text(round);
		$('.lastRound').text(round);
		$('#lastRoundTit').text(round);
		_lastMiniViewAnimatedRound = round;
	}
	var html = '';
	var list = '';
	for (var i = 0; i < 5; i++) {
		var two = numStr.substring(i * 2, i * 2 + 2) || ('0' + (data['ball' + (i + 1)] || '')).slice(-2);
		var color = ballColorSel(two);
		html += '<span class="ball_' + color + '"><span class="ballNumber">' + two + '</span></span>';
		list += (i ? ', ' : '') + two;
	}
	var pb = parseInt(data.powerball, 10);
	var pbStr = '' + pb;
	html += '<span class="ball_' + ballColorSel(pbStr) + '"><span class="ballNumber">' + pbStr + '</span></span>';
	$('#lotteryResult').html(html);
	var sum = data.ball_sum != null ? data.ball_sum : data.numberSum;
	list += ', <span style="color:#66ffff;" class="b">' + pbStr + '</span>, <span style="color:#fff;" class="b">' + sum + '</span>';
	$('#lastResult').html(list);
	$('#lotteryBox .play').hide();
	$('#ladderReady').show();
}

// update result
function updateResult(data)
{
	var r = data && data.round != null ? parseInt(data.round, 10) : NaN;
	if (!isNaN(r) && _lastMiniViewAnimatedRound === r) {
		miniviewDebugLog('updateResult skipped (duplicate round animation)', r);
		return;
	}
	/* stale 즉시렌더 등으로 이미 최신 회차가 표시된 뒤 updateResult 가 오면
	   moveCurrent→before 가 같은 공을 복사해 위·아래 동일하게 만듦 → 이동·애니 생략 */
	var domLr = parseInt($('#lastRound').text(), 10);
	if (!isNaN(r) && !isNaN(domLr) && domLr === r) {
		miniviewDebugLog('updateResult: DOM already this round — instant only (no move/re-animate)', r);
		miniViewRenderResultInstant(data);
		return;
	}
	if (!isNaN(r)) {
		_lastMiniViewAnimatedRound = r;
	}

	var animGen = ++_updateResultAnimGen;

	$('#lotteryBox .play').show();
	$('#ladderReady').hide();

	miniViewMoveCurrentResultToBefore();
	$('#lotteryResult').empty();

	var numStr = data.number;
	if(!numStr && data.ball1 != null){
		numStr = [data.ball1,data.ball2,data.ball3,data.ball4,data.ball5].map(function(n){ var s = ''+n; return s.length<2?'0'+s:s; }).join('');
	}
	numStr = numStr || '';

	$('#timeRound').text(parseInt(data.round,10)+1);
	$('#lastRound').text(data.round);

	var numberList = '';
	var ballArr = [];
	for(var i=0;i<5;i++){
		var two = numStr.substring(i*2,i*2+2) || ('0'+ (data['ball'+(i+1)] || '')).slice(-2);
		ballArr[i] = two;
		numberList += (i ? ', ' : '') + two;
	}
	var pb = parseInt(data.powerball,10);
	var sum = data.ball_sum != null ? data.ball_sum : data.numberSum;
	numberList += ', <span style="color:#66ffff;" class="b">'+pb+'</span>, <span style="color:#fff;" class="b">'+sum+'</span>';
	ballArr[5] = ''+pb;

	$('#lastResult').html(numberList);

	$('.nextRound').text(parseInt(data.round,10)+1);
	$('.lastRound').text(data.round);
	$('#lastRoundTit').text(data.round);

	setTimeout(function(){
		if (animGen !== _updateResultAnimGen) {
			return;
		}
		var totalBalls = 6;
		for(var idx = 0; idx < totalBalls; idx++){
			showNumber(ballArr[idx], idx, animGen);
		}
		setTimeout(function(){
			if (animGen !== _updateResultAnimGen) {
				return;
			}
			$('#lotteryBox .play').hide();
			$('#ladderReady').show();
		}, 2000 * totalBalls + 2000);
	}, 2000);
}

function showNumber(num, index, animGen)
{
	index = index == null ? 0 : index;
	/** index 5 = 파워볼(0~9): 0은 "0"으로 표시(00 방지), ballColorSel도 숫자 한 자리로 맞춤 */
	if (index === 5) {
		num = String(parseInt(num, 10));
	}
	var delay = 2000 * index;
	setTimeout(function(){
		if (animGen !== undefined && animGen !== _updateResultAnimGen) {
			return;
		}
		var ballColor = ballColorSel(num);
		var $ball = $('#lotteryBall');
		$ball.show();
		$ball.html('<span class="ball_'+ballColor+'">'+num+'</span>');

		/* 슬롯별 고유 ID (번호 문자열만 쓰면 동일 숫자/표기 충돌 시 jQuery가 첫 요소만 갱신) */
		var ballId = 'lotteryBallSlot_' + index;
		TweenMax.to(document.getElementById('lotteryBall'), 1, {
			bezier: { type: 'cubic', values: [{x:175,y:-5},{x:-50,y:5},{x:-20,y:300},{x:345,y:210}], autoRotate: false },
			ease: Power1.easeInOut,
			onStart: function(){
				if (animGen !== undefined && animGen !== _updateResultAnimGen) {
					return;
				}
				$('#lotteryResult').append('<span id="'+ballId+'" class="ball_'+ballColor+'"><span class="ballNumber">'+num+'</span></span>');
				$('#'+ballId).hide();
			},
			onComplete: function(){
				if (animGen !== undefined && animGen !== _updateResultAnimGen) {
					return;
				}
				$('#'+ballId).show();
				$ball.html('').hide();
			}
		});
	}, delay);
}

function ballColorSel(num)
{
	switch(num)
	{
		case '01':
		case '1':
		case '05':
		case '5':
		case '09':
		case '9':
		case '13':
		case '17':
		case '21':
		case '25':
			var ballColor = 'red';
			break;
		case '02':
		case '2':
		case '06':
		case '6':
		case '10':
		case '14':
		case '18':
		case '22':
		case '26':
			var ballColor = 'yellow';
			break;
		case '03':
		case '3':
		case '07':
		case '7':
		case '11':
		case '15':
		case '19':
		case '23':
		case '27':
			var ballColor = 'green';
			break;
		case '0':
		case '00':
		case '04':
		case '4':
		case '08':
		case '8':
		case '12':
		case '16':
		case '20':
		case '24':
		case '28':
			var ballColor = 'blue';
			break;
	}
	return ballColor;
}

function rebuildBallsNoWhitespace(containerId, includeId) {
	var $container = $('#' + containerId);
	var $spans = $container.children('span[class^="ball_"]');
	if ($spans.length === 0) return;
	var parts = [];
	$spans.each(function(){
		var $s = $(this);
		var id = includeId && $s.attr('id') ? $s.attr('id') : '';
		var cls = $s.attr('class') || '';
		var num = $s.find('.ballNumber').text() || '';
		var html = id ? '<span id="'+id+'" class="'+cls+'"><span class="ballNumber">'+num+'</span></span>' : '<span class="'+cls+'"><span class="ballNumber">'+num+'</span></span>';
		parts.push(html);
	});
	$container.html(parts.join(''));
}

/** 메인·일자별분석과 동일: ajaxChatTimer로 서버 시계 동기화 (iframe별 클라이언트 카운트다운 편차 방지) */
function syncMiniViewDrawTimerFromServer() {
	var base = (window.POWERBALL_BASE_URL || window.ACTION_BASE_URL || '').replace(/\/$/, '') + '/';
	if (!base || base === '/') {
		miniviewDebugLog('syncMiniViewDrawTimerFromServer aborted: empty base', {
			POWERBALL_BASE_URL: window.POWERBALL_BASE_URL,
			ACTION_BASE_URL: window.ACTION_BASE_URL
		});
		return;
	}
	var syncTag = 'sync-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
	miniviewDebugLog('ajaxChatTimer request', { tag: syncTag, url: base });
	$.ajax({
		url: base,
		type: 'POST',
		data: { view: 'action', action: 'ajaxChatTimer' },
		dataType: 'json',
		success: function(resp) {
			if (!resp || resp.state !== 'success') {
				miniviewDebugLog('ajaxChatTimer response not success', { tag: syncTag, resp: resp });
				if (window.__miniviewDebugState) {
					window.__miniviewDebugState.lastAjaxChatTimer = { ok: false, tag: syncTag, resp: resp };
				}
				return;
			}
			var sec = parseInt(resp.remain_seconds, 10);
			if (isNaN(sec)) {
				sec = 0;
			}
			remainTime = sec;
			if (typeof resp.time_round !== 'undefined') {
				$('#timeRound').text(resp.time_round);
				$('.nextRound').text(resp.time_round);
			}
			var remain_i = Math.floor(remainTime / 60);
			var remain_s = remainTime % 60;
			$('#ladderTimer').find('.minute').text(remain_i);
			$('#ladderTimer').find('.second').text(remain_s < 10 ? '0' + remain_s : '' + remain_s);
			miniViewSyncResultPanelsIfStale(typeof resp.time_round !== 'undefined' ? resp.time_round : undefined);
			miniviewDebugLog('ajaxChatTimer OK', { tag: syncTag, remain_seconds: sec, time_round: resp.time_round });
			if (window.__miniviewDebugState) {
				window.__miniviewDebugState.lastAjaxChatTimer = { ok: true, tag: syncTag, remain_seconds: sec };
			}
		},
		error: function(xhr, status, err) {
			var snippet = '';
			try {
				snippet = xhr && xhr.responseText ? String(xhr.responseText).substring(0, 300) : '';
			} catch (e) {}
			miniviewDebugLog('ajaxChatTimer HTTP error', {
				tag: syncTag,
				status: status,
				http: xhr && xhr.status,
				err: err,
				responseSnippet: snippet
			});
			if (window.__miniviewDebugState) {
				window.__miniviewDebugState.lastAjaxChatTimer = { ok: false, tag: syncTag, http: xhr && xhr.status, status: status, err: err };
			}
		}
	});
}

/** 탭/창 복귀 직후 네트워크 지연·스로틀 보정용 연속 동기화 (부모에서도 호출 가능) */
function scheduleMiniViewSyncBurst() {
	if (miniViewUsesParentHub) {
		miniviewDebugLog('scheduleMiniViewSyncBurst: hub mode → postMessage to parent only (no ajaxChatTimer)');
		try {
			window.parent.postMessage({ type: 'drawTimerHubRequestSync' }, '*');
		} catch (e) {
			miniviewDebugLog('scheduleMiniViewSyncBurst postMessage failed', e && e.message ? e.message : e);
		}
		return;
	}
	miniviewDebugLog('scheduleMiniViewSyncBurst: local mode → syncMiniViewDrawTimerFromServer burst');
	try { syncMiniViewDrawTimerFromServer(); } catch (e) {
		miniviewDebugLog('scheduleMiniViewSyncBurst sync throw', e && e.message ? e.message : e);
	}
	setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 0);
	setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 150);
	setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 500);
}
window.syncMiniViewDrawTimerFromServer = syncMiniViewDrawTimerFromServer;
window.scheduleMiniViewSyncBurst = scheduleMiniViewSyncBurst;

var _prevHubRemainMini = null;
/** getDrawResult 호출 쿨다운(허브·로컬 타이머 공통, 탭 복귀 시 중복 요청 완화) */
var _lastDrawResultFetchAt = 0;
/** 탭 복귀 후 timeRound만 맞고 결과 패널이 남을 때 연속 요청 완화 */
var _lastMiniViewStaleResultFetchAt = 0;
var _miniViewDrawResultFetchInFlight = false;

/**
 * 서버/허브가 갱신한 timeRound(다음 회차)와 #lastRound(직전 발표 회차)가 어긋나면 최신 추첨을 가져와 패널·애니메이션을 맞춤.
 * 백그라운드에서 허브만 받고 getDrawResult(sec=0) 전환을 놓친 경우에 필요.
 */
function miniViewSyncResultPanelsIfStale(optTimeRound) {
	var trRaw = (typeof optTimeRound !== 'undefined' && optTimeRound !== null && optTimeRound !== '') ? optTimeRound : $('#timeRound').text();
	var tr = parseInt(trRaw, 10);
	var lr = parseInt($('#lastRound').text(), 10);
	if (isNaN(tr)) {
		return;
	}
	if (!isNaN(lr) && tr === lr + 1) {
		return;
	}
	var nowMs = Date.now();
	if (_miniViewDrawResultFetchInFlight) {
		miniviewDebugLog('miniViewSyncResultPanelsIfStale: skip (in flight)', { tr: tr, lr: lr });
		return;
	}
	if (nowMs - _lastMiniViewStaleResultFetchAt < 2000) {
		miniviewDebugLog('miniViewSyncResultPanelsIfStale: skip (cooldown)', { tr: tr, lr: lr });
		return;
	}
	_lastMiniViewStaleResultFetchAt = nowMs;
	_miniViewDrawResultFetchInFlight = true;
	var drawUrl = (window.POWERBALL_BASE_URL || window.POWERBALL_AJAX_URL || '').replace(/\/$/, '') + '/lottery/getDrawResult';
	miniviewDebugLog('miniViewSyncResultPanelsIfStale: fetch', { tr: tr, lr: lr });
	$.getJSON(drawUrl).done(function(data){
		if (data && (data.round != null || data.ball1 != null)) {
			/* 포커스/가시성 복귀 보정 경로에서는 시간을 무시하고 무애니 즉시 정합만 수행 */
			var fetchedRound = parseInt(data.round, 10);
			var currentRound = parseInt($('#lastRound').text(), 10);
			miniviewDebugLog('miniViewSyncResultPanelsIfStale: instant render (forced no animation)', {
				currentRound: currentRound,
				fetchedRound: fetchedRound
			});
			miniViewRenderResultInstant(data);
		}
	}).fail(function(){
		$('#lotteryBox .play').hide();
		$('#ladderReady').show();
	}).always(function(){
		_miniViewDrawResultFetchInFlight = false;
	});
}

function miniViewApplyDrawTimerFromHub(sec, tr) {
	sec = Math.max(0, parseInt(sec, 10) || 0);
	remainTime = sec;
	if (typeof tr !== 'undefined') {
		$('#timeRound').text(tr);
		$('.nextRound').text(tr);
	}
	var remain_i = Math.floor(sec / 60);
	var remain_s = sec % 60;
	$('#ladderTimer').find('.minute').text(remain_i);
	$('#ladderTimer').find('.second').text(remain_s < 10 ? '0' + remain_s : '' + remain_s);
	if (sec <= 10 && sec >= 0) {
		$('#lotteryBox .play').css('display', 'block').show();
		$('#ladderReady').hide();
	}
	if (_prevHubRemainMini !== null && _prevHubRemainMini > 0 && sec === 0) {
		var nowMs = Date.now();
		if (_miniViewDrawResultFetchInFlight) {
			miniviewDebugLog('miniViewApplyDrawTimerFromHub: skip sec0 getDrawResult (in flight)');
		} else if (nowMs - _lastDrawResultFetchAt < 8000) {
			miniviewDebugLog('miniViewApplyDrawTimerFromHub: skip getDrawResult (cooldown)', { delta: nowMs - _lastDrawResultFetchAt });
		} else {
			_lastDrawResultFetchAt = nowMs;
			_miniViewDrawResultFetchInFlight = true;
			var drawUrl = (window.POWERBALL_BASE_URL || window.POWERBALL_AJAX_URL || '').replace(/\/$/, '') + '/lottery/getDrawResult';
			$.getJSON(drawUrl).done(function(data){
				if(data && (data.round != null || data.ball1 != null)){
					updateResult(data);
				}
			}).fail(function(){
				$('#lotteryBox .play').hide();
				$('#ladderReady').show();
			}).always(function(){
				_miniViewDrawResultFetchInFlight = false;
			});
		}
	}
	_prevHubRemainMini = sec;
	miniViewSyncResultPanelsIfStale(typeof tr !== 'undefined' ? tr : undefined);
}

if (miniViewUsesParentHub) {
	window.addEventListener('message', function(ev) {
		var d = ev.data;
		if (!d || d.type !== 'drawTimerHub') {
			return;
		}
		miniviewDebugLog('drawTimerHub message received', { remainSeconds: d.remainSeconds, timeRound: d.timeRound });
		try {
			if (ev.source !== window.parent) {
				miniviewDebugLog('drawTimerHub ignored: ev.source !== parent');
				return;
			}
		} catch (e) {
			miniviewDebugLog('drawTimerHub parent check exception', e && e.message ? e.message : e);
			return;
		}
		if (miniViewShouldSkipDueToIframeHidden()) {
			miniviewDebugLog('drawTimerHub skipped: document.hidden (iframe)');
			return;
		}
		miniViewApplyDrawTimerFromHub(d.remainSeconds, d.timeRound);
	});
}

$(document).ready(function(){
	rebuildBallsNoWhitespace('lotteryResult', true);
	rebuildBallsNoWhitespace('beforeResult', false);

	try {
		var lrInit = parseInt($('#lastRound').text(), 10);
		if (!isNaN(lrInit)) {
			_lastMiniViewAnimatedRound = lrInit;
		}
	} catch (e) {}

	if (window.__miniviewDebugState) {
		window.__miniviewDebugState.remainTimeInitial = typeof remainTime !== 'undefined' ? remainTime : null;
		window.__miniviewDebugState.POWERBALL_BASE_URL = window.POWERBALL_BASE_URL;
	}
	miniviewDebugLog('document.ready', {
		miniViewUsesParentHub: miniViewUsesParentHub,
		hubDetectError: _miniviewHubDetectError,
		remainTime: typeof remainTime !== 'undefined' ? remainTime : 'undefined',
		willRegisterIntervals: !miniViewUsesParentHub,
		documentHidden: typeof document !== 'undefined' ? document.hidden : 'n/a'
	});

	if(remainTime <= 10 && remainTime >= 0){
		$('#lotteryBox .play').css('display', 'block').show();
		$('#ladderReady').hide();
	}
	if (!miniViewUsesParentHub) {
		miniviewDebugLog('registering setInterval: sync 5s + ladderResultTimer 1s');
		setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 300);
		setInterval(function() {
			try {
				if (!miniViewShouldSkipDueToIframeHidden()) {
					syncMiniViewDrawTimerFromServer();
				} else if (miniviewDebugEnabled()) {
					miniviewDebugLog('5s sync skipped: document.hidden');
				}
			} catch (e) {}
		}, 5000);
	} else {
		miniviewDebugLog('NOT registering local intervals (hub mode). drawTimerHub messages must update UI.');
	}
	$(document).on('visibilitychange.miniviewtimer', function() {
		if (!miniViewShouldSkipDueToIframeHidden()) {
			scheduleMiniViewSyncBurst();
		}
	});
	$(window).on('focus.miniviewtimer', function() {
		scheduleMiniViewSyncBurst();
	});
	$(window).on('pageshow.miniviewtimer', function(ev) {
		if (ev.originalEvent && ev.originalEvent.persisted) {
			scheduleMiniViewSyncBurst();
		}
	});
	if (!miniViewUsesParentHub) {
		setInterval(function(){
			ladderResultTimer('ladderTimer');
		},1000);
	}
	if (miniviewDebugEnabled() && window.__miniviewDebugState) {
		window.__miniviewDebugState.inspect = function() {
			return {
				hub: miniViewUsesParentHub,
				hubDetectError: _miniviewHubDetectError,
				ladderTicks: _mvLadderTickCount,
				remainTime: typeof remainTime !== 'undefined' ? remainTime : null,
				lastAjaxChatTimer: window.__miniviewDebugState.lastAjaxChatTimer,
				documentHidden: document.hidden
			};
		};
		miniviewDebugLog('debug: type __miniviewDebugState.inspect() in console for snapshot');
	}
});

$(document).ready(function(){
	$('#betBox .btn').click(function(){

		var type = $(this).attr('type');
		var val = $(this).attr('val');
		var totalPoint = 0;

		$('#betBox .btn').each(function(){
			if($(this).attr('type') == type)
			{
				$(this).removeClass('on');
			}
		});

		$(this).addClass('on');

		$('#betBox .btn').each(function(){

			if($(this).attr('type') == 'powerballOddEven' || $(this).attr('type') == 'numberOddEven' || $(this).attr('type') == 'powerballUnderOver' || $(this).attr('type') == 'numberUnderOver' || $(this).attr('type') == 'numberPeriod')
			{
				if($(this).hasClass('on'))
				{
					$('#'+$(this).attr('type')).val($(this).attr('val'));
				}
			}
			else if($(this).attr('type') == 'powerballOddEvenP' || $(this).attr('type') == 'numberOddEvenP' || $(this).attr('type') == 'powerballUnderOverP' || $(this).attr('type') == 'numberUnderOverP' || $(this).attr('type') == 'numberPeriodP')
			{
				if($(this).hasClass('on'))
				{
					totalPoint += parseInt($(this).attr('val'));
					$('#point_'+$(this).attr('type')).val(parseInt($(this).attr('val')));
				}
			}
		});

		var btnSelectLength = $('#betBox .btn.on').length;

		$('.totalPoint em').text($.number(parseInt($('#selectPoint').val() * btnSelectLength)));
		$('#point').val(parseInt($('#selectPoint').val()));
	});
});

function powerballBetting()
{
	var fn = document.forms.bettingForm;

	if(!fn.powerballOddEven.value && !fn.numberOddEven.value && !fn.powerballUnderOver.value && !fn.numberUnderOver.value && !fn.numberPeriod.value)
	{
		//alert('다섯개 중 한개 이상을 선택하세요.');
		modalMsg('다섯개 중 한개 이상을 선택하세요.');
		return false;
	}
	else
	{
		$.ajax({
			type:'POST',
			dataType:'json',
			url:'/',
			data:$('#bettingForm').serialize(),
			success:function(data,textStatus){
				if(data.state == 'success')
				{
					//alert(data.msg);
					modalMsg(data.msg);
				}
				else
				{
					if(data.msg == 'CAPTCHA')
					{
						$('#betBox').hide();
						$('#captchaBox').show();
						$('#captchaImg').html('<img src="/captcha.php?type=pointBet&time='+new Date().getTime()+'">');
					}
					else
					{
						//alert(data.msg);
						modalMsg(data.msg);
					}
				}
			},
			error:function (xhr,textStatus,errorThrown){
				//alert('error'+(errorThrown?errorThrown:xhr.status));
			}
		});
	}
}

function resetPowerballBetting()
{
	var fn = document.bettingForm;
	fn.reset();

	$('#powerballOddEven').val('');
	$('#numberOddEven').val('');
	$('#powerballUnderOver').val('');
	$('#numberUnderOver').val('');
	$('#numberPeriod').val('');
	$('#point').val('');

	$('#betBox .btn').each(function(){
		$(this).removeClass('on');
	});
	$('.totalPoint em').text(0);
}

function pointCal()
{
	$('#point').val(parseInt($('#selectPoint').val()));
	$('.totalPoint em').text($.number(parseInt($('#selectPoint').val() * $('#betBox .btn.on').length)));
}

function toggleBetting()
{
	if($('#betBox').css('display') == 'block')
	{
		setCookie('POINTBETLAYER','N');

		$('#betBox').hide();
		$('.bettingBtn a').text('픽 열기');
	}
	else
	{
		setCookie('POINTBETLAYER','Y');

		$('#betBox').show();
		$('.bettingBtn a').text('픽 닫기');
	}
}

function toggleMiniView()
{
	if($('#ladderResultBox').css('display') == 'block')
	{
		setCookie('MINIVIEWLAYER','N');

		$('#ladderResultBox').hide();
		$('.miniViewBtn a').text('미니뷰 열기');

		try{
			parent.miniViewControl('close');
		}
		catch(e){}
	}
	else
	{
		setCookie('MINIVIEWLAYER','Y');

		$('#ladderResultBox').show();
		$('.miniViewBtn a').text('미니뷰 닫기');

		try{
			parent.miniViewControl('open');
		}
		catch(e){}
		try { if (typeof scheduleMiniViewSyncBurst === 'function') { scheduleMiniViewSyncBurst(); } } catch (e2) {}
	}
}

$(document).ready(function(){

	var currentKey = 1;
	var numberArr = [];

	// 키패드 클릭시
	$('.pad li').click(function(){
		
		var isreset = $(this).hasClass('reset');
		var isdelete = $(this).hasClass('delete');

		if (isreset || isdelete)
		{
			return false;
		}
		else
		{
			if (numberArr.length >= 2)
			{
				//alert('로봇 방지 숫자는 2자만 가능합니다.');
				modalMsg('로봇 방지 숫자는 2자만 가능합니다.');
				return false;
			}

			var numberVal = $(this).text();
			$('#captchaNum'+currentKey).val(numberVal);
			
			var captchaNumArr = numberArr.push(numberVal);
			var captchaNum = numberArr.join('');
			$('#captchaNum').val(captchaNum);
			
			currentKey++;
		}
	});
	
	// reset
	$('.pad li.reset').click(function(){
		numberArr = [];
		$('#captchaNum').val('');
		
		for(var i=1;i<=currentKey;i++)
		{
			$('#captchaNum'+i).val('');
		}
		
		currentKey = 1;
	});
	
	// delete
	$('.pad li.delete').click(function(){
		numberArr.pop();
		captchaNum = numberArr.join('');
		$('#captchaNum').val(captchaNum);
		
		if(currentKey > 1)
		{
			$('#captchaNum'+(currentKey-1)).val('');
			currentKey--;
		}
	});
});

function runCaptcha()
{
	var fn = document.forms.captchaForm;

	if(!fn.captchaNum.value || $('#captchaNum').val().length != 2)
	{
		//alert('좌측 숫자를 입력하세요.');
		modalMsg('좌측 숫자를 입력하세요.');
		return false;
	}
	else
	{
		$.ajax({
			type:'POST',
			dataType:'json',
			url:'/',
			data:$('#captchaForm').serialize(),
			success:function(data,textStatus){
				if(data.state == 'success')
				{
					$('.pad li.reset').click();

					$('#betBox').show();
					$('#captchaBox').hide();

					powerballBetting();
				}
				else
				{
					if(data.code == 'MISMATCH')
					{
						//alert(data.msg);
						modalMsg(data.msg);

						$('#captchaImg img').attr('src','/captcha.php?type=pointBet&time='+new Date().getTime());
						$('.pad li.reset').click();
					}
					else
					{
						//alert(data.msg);
						modalMsg(data.msg);
					}
				}
			},
			error:function (xhr,textStatus,errorThrown){
				//alert('error'+(errorThrown?errorThrown:xhr.status));
			}
		});
	}
}

function modalMsg(msg)
{
	if(!$('#dialog').length)
	{
		var dialogLayer = '<div id="dialog" title="안내"><p></p></div>';
		$('body').append(dialogLayer);
	}

	$('#dialog p').html(msg);

	$('#dialog').dialog({
		modal:true,
		buttons:{
			'확인':function(){
				$(this).dialog('close');
			}
		}
	});
}