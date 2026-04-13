
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>파워볼게임(PBG) : 실시간 파워볼 분석 커뮤니티</title>
			<meta http-equiv="content-type" content="text/html; charset=utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="description" content="파워볼 커뮤니티, 홀짝, 출줄, 패턴, 분석기, 실시간 통계">
			<meta name="keywords" content="파워볼,파워볼게임,홀짝,출줄,패턴분석,픽공유">
			<meta name="robots" content="all">
			<meta name="robots" content="index,follow">
			<meta property="og:type" content="website">
			<meta property="og:title" content="파워볼게임(PBG) : 실시간 파워볼 분석 커뮤니티">
			<meta property="og:description" content="파워볼 커뮤니티, 홀짝, 출줄, 패턴, 분석기, 실시간 통계">
			<meta property="og:image" content="https://www.powerballgame.co.kr/images/logo.gif">
			<meta property="og:url" content="https://www.powerballgame.co.kr/">
			<link rel="canonical" href="http://www.powerballgame.co.kr/">
			<?php $local = rtrim(site_furl(''), '/'); ?>
			<link rel="stylesheet" href="<?php echo $local; ?>/css/jquery.qtip.min.css" type="text/css"/>
			<link rel="stylesheet" href="<?php echo $local; ?>/css/common.css?v=<?php echo time(); ?>" type="text/css"/>
			<link rel="stylesheet" href="<?php echo $local; ?>/css/sprites.css?v=<?php echo time(); ?>" type="text/css"/>
			<link rel="stylesheet" href="<?php echo $local; ?>/css/jquery-ui.css?v=<?= @filemtime(FCPATH.'css/jquery-ui.css') ?: time() ?>" type="text/css"/>
			<link rel="shortcut icon" href="favicon.ico"/>
			<script type="text/javascript" src="<?php echo $local; ?>/js/jquery-1.11.2.min.js"></script>
			<script type="text/javascript" src="<?php echo $local; ?>/js/jquery-ui.js"></script>
			<script type="text/javascript" src="<?php echo $local; ?>/js/jquery.qtip.min.js"></script>
			<script type="text/javascript" src="<?php echo $local; ?>/js/default.js?v=<?php echo time(); ?>"></script>
			<script type="text/javascript" src="<?php echo $local; ?>/js/jquery.number.min.js"></script>
			<script type="text/javascript" src="<?php echo $local; ?>/js/jquery.tmpl.min.js"></script>
			<script type="text/javascript">
				//<![CDATA[
				
	$(document).ready(function(){
		$('.defaultTable .menu').click(function(){
			$('.defaultTable .menu').each(function(){
				$(this).removeClass('on');
			});

			$(this).addClass('on');
		});
	});

	var curDate = '<?= isset($date) ? esc($date) : date('Y-m-d') ?>';
	var today = '<?= date('Y-m-d') ?>';
	var dateDiff = <?= isset($date) ? (strtotime($date) - strtotime('today')) / 86400 : 0 ?>;
	// iframe에서도 AJAX가 앱 루트로 가도록 (회차별 분석·육매 패턴 등 초기 로드 정상화)
	var actionBaseUrl = '<?= rtrim(esc(site_furl("")), "/") ?>/';
	window.ACTION_BASE_URL = actionBaseUrl;

	/** 미니뷰와 동일: 부모가 있어도 교차 출처이면 허브(postMessage) 없음 → ladder·ajax 동기화 필요 */
	var dayLogUsesParentHub = false;
	var _dayLogHubDetectError = null;
	try {
		if (window.parent && window.parent !== window) {
			var _chO = window.location.origin || (window.location.protocol + '//' + window.location.host);
			var _prO = window.parent.location.origin;
			dayLogUsesParentHub = (String(_chO) === String(_prO));
		}
	} catch (e) {
		_dayLogHubDetectError = e && e.message ? e.message : String(e);
		dayLogUsesParentHub = false;
	}

	// mainFrame 높이: 실제 문서 높이로 조절 (육매/패턴 영역·더보기 포함해 잘리지 않도록)
	var DAYLOG_MIN_HEIGHT = 500;
	/** 서버 갱신 이슈 추적: URL ?daylogdbg=1 또는 localStorage/sessionStorage DAYLOG_DBG=1 */
	var _dayLogDebug = (function () {
		try {
			if (/[?&]daylogdbg=1(?:&|$)/.test(window.location.search || '')) {
				try { sessionStorage.setItem('DAYLOG_DBG', '1'); } catch (e0) {}
				return true;
			}
		} catch (e) {}
		try { if (sessionStorage.getItem('DAYLOG_DBG') === '1') return true; } catch (e1) {}
		try { if (localStorage.getItem('DAYLOG_DBG') === '1') return true; } catch (e2) {}
		return false;
	})();
	function dayLogDbgLog() {
		if (!_dayLogDebug) return;
		var a = ['[dayLog][sync]'];
		for (var i = 0; i < arguments.length; i++) a.push(arguments[i]);
		try { console.log.apply(console, a); } catch (e) {}
	}
	var _dayLogSyncStats = { lastChatTimer: null, lastRefreshLog: null, refreshLogErrors: 0, analyseErrors: 0 };
	function heightResize() {
		function setFrameHeight(h) {
			if (_dayLogDebug) console.log('[dayLog] heightResize setFrameHeight', h);
			try {
				if (window.parent && window.parent.frameAutoResize) {
					window.parent.frameAutoResize('mainFrame', h);
				} else if (window.parent && window.parent.document) {
					var frame = window.parent.document.getElementById('mainFrame');
					if (frame) frame.style.height = h + 'px';
				}
			} catch (e) {}
		}
		function measureAndSet() {
			var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
			var totalHeight = DAYLOG_MIN_HEIGHT;
			var moreBox = document.querySelector('.moreBox');
			// .moreBox가 보일 때만 그 위치 사용 (숨기면 getBoundingClientRect가 0이라 500으로 고정되는 문제 방지)
			if (moreBox) {
				var style = window.getComputedStyle(moreBox);
				var isVisible = style.display !== 'none' && style.visibility !== 'hidden' && style.height !== '0px';
				if (isVisible) {
					var rect = moreBox.getBoundingClientRect();
					if (rect.height > 0) {
						totalHeight = rect.bottom + scrollTop;
						try {
							var mb = parseFloat(style.marginBottom) || 0;
							totalHeight += mb;
						} catch (e) {}
					}
				}
			}
			// 문서 전체 높이와 비교해 더 큰 값 사용 (회차별 분석 테이블이 항상 보이도록)
			var docHeight = document.body.scrollHeight || document.documentElement.scrollHeight || document.body.offsetHeight || 0;
			// 회차별 분석 테이블(#powerballLogBox)의 실제 바닥까지 포함
			// (행이 1개로 매우 짧을 때 iframe 높이가 딱 떨어져 테두리 하단이 잘리는 현상 방지)
			var powerballLogBox = document.getElementById('powerballLogBox');
			if(powerballLogBox && powerballLogBox.getBoundingClientRect)
			{
				var boxRect = powerballLogBox.getBoundingClientRect();
				if(boxRect && typeof boxRect.bottom === 'number')
					totalHeight = Math.max(totalHeight, boxRect.bottom + scrollTop);
			}
			totalHeight = Math.max(DAYLOG_MIN_HEIGHT, totalHeight, docHeight);
			// 테이블 border/레이아웃 반올림 오차 보정
			totalHeight = Math.ceil(totalHeight) + 6;
			setFrameHeight(totalHeight);
		}
		if (window.requestAnimationFrame) {
			requestAnimationFrame(function() { requestAnimationFrame(measureAndSet); });
		} else {
			setTimeout(measureAndSet, 80);
		}
	}

	$(document).ready(function(){
		if (_dayLogDebug) {
			dayLogDbgLog('boot', {
				actionBaseUrl: actionBaseUrl,
				origin: (function () { try { return window.location.origin; } catch (e) { return ''; } })(),
				dayLogUsesParentHub: dayLogUsesParentHub,
				hubDetectError: _dayLogHubDetectError,
				curDate: curDate,
				today: today,
				pageDivRound: (function () { try { return $('#pageDiv').attr('round'); } catch (e) { return null; } })()
			});
			setInterval(function () {
				try {
					dayLogDbgLog('heartbeat', {
						hidden: document.hidden,
						remainVar: remainTime,
						timerUi: (function () { try { return $('#dayLogTimer .minute').text() + ':' + $('#dayLogTimer .second').text(); } catch (e) { return ''; } })(),
						timeRoundUi: (function () { try { return $('#timeRound').text(); } catch (e) { return ''; } })(),
						pageDivRound: (function () { try { return $('#pageDiv').attr('round'); } catch (e) { return null; } })(),
						dataRefreshBusy: !!dataRefresh_process,
						stats: _dayLogSyncStats
					});
				} catch (e) {}
			}, 60000);
		}
		if (_dayLogDebug) console.log('[dayLog] document.ready, scheduling moreClick(50ms)');
		// 회차별 분석 데이터: iframe 로드 직후에도 DOM/템플릿 준비되도록 약간 지연 후 요청
		setTimeout(function(){ moreClick(); }, 50);
		// 전체 분석 데이터 (해당 날짜 집계) 초기 로드
		refreshAnalyse();

		if (!dayLogUsesParentHub) {
			setInterval(function(){
				ladderTimer('dayLogTimer');
			},1000);
		}

		// 미니뷰가 닫혀 있어도 신규 회차를 빠르게 감지해 회차별 분석 데이터 갱신
		// (dataRefresh()는 round 비교로 신규 데이터만 prepend 하므로 주기 호출해도 안전)
		setInterval(function(){
			try{
				if(curDate == today && !document.hidden){
					dataRefresh();
				}
			}catch(e){}
		},10000);

		// 탭이 백그라운드로 가면 브라우저가 timer/ajax를 스로틀링해서 누락이 발생한다.
		// 다시 돌아왔을 때(visible) 빠르게 따라잡기 위해 짧은 주기로 dataRefresh()를 연속 호출한다.
		var catchUpTimer = 0;
		function stopCatchUp() {
			if (catchUpTimer) {
				clearInterval(catchUpTimer);
				catchUpTimer = 0;
			}
		}
		function startCatchUp() {
			stopCatchUp();
			if (!(curDate == today)) return;
			if (document.hidden) return;
			var ticks = 0;
			catchUpTimer = setInterval(function() {
				try {
					if (document.hidden) { stopCatchUp(); return; }
					dataRefresh();
					ticks++;
					// 최대 60회(약 18초)만 빠른 추격 후 종료 (무한 루프 방지)
					if (ticks >= 60) stopCatchUp();
				} catch(e) {
					stopCatchUp();
				}
			}, 300);
		}
		$(document).on('visibilitychange', function() {
			if (!document.hidden) {
				if (dayLogUsesParentHub) {
					try {
						window.parent.postMessage({ type: 'drawTimerHubRequestSync' }, '*');
					} catch(e0) {}
				} else {
					try { syncDayLogDrawTimerFromServer(); } catch(e) {}
				}
				try {
					var _mv = document.getElementById('miniViewFrame');
					if (_mv && _mv.contentWindow && typeof _mv.contentWindow.scheduleMiniViewSyncBurst === 'function') {
						_mv.contentWindow.scheduleMiniViewSyncBurst();
					}
				} catch(e2) {}
				// 돌아오는 즉시 1회 + 빠른 추격
				try { dataRefresh(); } catch(e) {}
				startCatchUp();
			} else {
				stopCatchUp();
			}
		});

		/* 허브만 쓰는 iframe에서 drawTimerHub 메시지를 놓치면 remainTime·추첨 0초 갱신이 안 됨 → ajaxChatTimer는 항상 보조 */
		setTimeout(function() { try { syncDayLogDrawTimerFromServer(); } catch(e) {} }, 300);
		setInterval(function() {
			try {
				if (!document.hidden) syncDayLogDrawTimerFromServer();
			} catch(e) {}
		}, 5000);

/*
		$('.defaultTable .menu').mouseover(function(){
			$(this).find('a').addClass('on');
			$(this).addClass('on');
		}).mouseout(function(){
			$(this).find('a').removeClass('on');
			$(this).removeClass('on');
		});
*/

		$('#datepicker').datepicker({
			dateFormat: 'yy-mm-dd',
			prevText: '이전 달',
			nextText: '다음 달',
			monthNames: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
			monthNamesShort: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
			dayNames: ['일','월','화','수','목','금','토'],
			dayNamesShort: ['일','월','화','수','목','금','토'],
			dayNamesMin: ['일','월','화','수','목','금','토'],
			showMonthAfterYear: true,
			yearSuffix: '년',
			defaultDate: dateDiff,
			maxDate: '+0d'
		}).change(function(){
			document.location.href = '/?view=dayLog&date='+this.value;
		});
	});

	function ladderTimer(divId)
	{
		if(remainTime == 0)
		{
			remainTime = 300;

			var roundNum = parseInt($('#timeRound').text())+1;
			if(roundNum == 289) roundNum = 1;
			
			$('#timeRound').text(roundNum);

			if(curDate == today)
			{
				dataRefresh();
			}
		}

		remainTime--;

		var remain_i = Math.floor(remainTime / 60);
		var remain_s = Math.floor(remainTime % 60);

		//if(remain_i < 10) remain_i = '0' + remain_i;
		if(remain_s < 10) remain_s = '0' + remain_s;

		$('#'+divId).find('.minute').text(remain_i);
		$('#'+divId).find('.second').text(remain_s);
	}


	var loading = false;

	function moreClick()
	{
		var page = parseInt($('#pageDiv').attr('pageVal'), 10);
		if(isNaN(page)) page = 0;
		if (_dayLogDebug) console.log('[dayLog] moreClick called', { loading: loading, page: page });

		if(loading == false)
		{
			loading = true;
			$('#pageDiv').show();

			$.ajax({
				type:'POST',
				dataType:'json',
				url: actionBaseUrl,
				data:{
					view:'action',
					action:'ajaxPowerballLog',
					actionType:'dayLog',
					date:curDate,
					page:page
				},
				success:function(data,textStatus){
					var $tbody = $('#powerballLogBox tbody.content');
					var contentLen = (data && data.content) ? data.content.length : -1;
					if (_dayLogDebug) console.log('[dayLog] ajax success', { page: page, hasData: !!data, contentLen: contentLen, endYN: data && data.endYN });

					// 첫 페이지(0)일 때만 기존 내용 비우고 채움 (비우는 건 채울 내용이 확정된 뒤에만 해서 깜빡임/사라짐 방지)
					if(page === 0 && data && data.content)
					{
						$tbody.empty();
						if(data.content.length)
							$tbody.append($('#tmpl_dayLog').tmpl(data));
						else
							$tbody.append('<tr class="trOdd powerballLog-empty-placeholder"><td colspan="12" height="50" align="center" style="color:#888;">당일 추첨 결과가 없습니다.</td></tr>');
						var $content = $('#powerballLogBox tbody.content');
						if($content.find('tr').length > 30)
							$content.find('tr').slice(30).remove();
						if (_dayLogDebug) console.log('[dayLog] after page0 fill, tr count=', $content.find('tr').length);
					}
					else if(page !== 0 && data && data.content && data.content.length)
					{
						$tbody.append($('#tmpl_dayLog').tmpl(data));
					}
					if(data && data.endYN == 'Y')
						$('.moreBox').hide();
					else if(data && data.endYN == 'N')
						$('.moreBox').show();
					if(data && data.round != null)
						$('#pageDiv').attr('round', data.round);

					$('#pageDiv').hide();
					$('#pageDiv').attr('pageVal', page + 1);
					loading = false;
					heightResize();
				},
				error:function (xhr,textStatus,errorThrown){
					if (_dayLogDebug) console.log('[dayLog] ajax error', { status: xhr && xhr.status, statusText: textStatus, error: errorThrown });
					loading = false;
					$('#pageDiv').hide();
					var pageErr = parseInt($('#pageDiv').attr('pageVal'), 10);
					if(isNaN(pageErr)) pageErr = 0;
					// 첫 로드(page 0) 실패 시에도 회차별 분석 영역 보이게 빈 테이블 + heightResize
					if(pageErr === 0){
						var $tbody = $('#powerballLogBox tbody.content');
						if($tbody.find('tr').length === 0)
							$tbody.append('<tr class="trOdd"><td colspan="12" height="50" align="center" style="color:#888;">데이터를 불러올 수 없습니다.</td></tr>');
						heightResize();
					}
				}
			});
		}
	}

	// data refresh
	var dataRefresh_process = false;

	function dataRefresh()
	{
		dayLogDbgLog('dataRefresh called', { dataRefresh_process: dataRefresh_process, curDate: curDate, today: today });
		if(dataRefresh_process == false)
		{
			// auto refresh
			if(beforeType && beforeDivision)
			{
				ajaxPattern(beforeType,curDate,beforeDivision,true);
			}

			// auto refresh
			if(sixBeforeCnt && sixBeforeType && sixBeforeDivision)
			{
				ajaxSixPattern(sixBeforeCnt,sixBeforeType,curDate,sixBeforeDivision,true);
			}

			dataRefresh_process = true;

			var round = $('#pageDiv').attr('round');
			dayLogDbgLog('dataRefresh POST refreshLog', { clientRoundAttr: round, date: curDate });
			$.ajax({
				type:'POST',
				dataType:'json',
				url: actionBaseUrl,
				data:{
					view:'action',
					action:'ajaxPowerballLog',
					actionType:'refreshLog',
					date:curDate,
					round:round,
					daylog_sync_debug: _dayLogDebug ? '1' : '0'
				},
				success:function(data,textStatus){
					_dayLogSyncStats.lastRefreshLog = { t: Date.now(), state: data && data.state, respRound: data && data.round, contentLen: (data && data.content) ? data.content.length : -1 };
					dayLogDbgLog('dataRefresh ajax success', _dayLogSyncStats.lastRefreshLog);

					dataRefresh_process = false;

					if(data.state == 'success')
					{
						// 전체 분석 데이터 갱신
						refreshAnalyse();

						if(data && $('#pageDiv').attr('round') != data.round)
						{
							dayLogDbgLog('dataRefresh prepend new round', data.round);
							$('#pageDiv').attr('round',data.round);

							$('#powerballLogBox tbody.content tr.powerballLog-empty-placeholder').remove();
							$('#powerballLogBox tbody.content').prepend($('#tmpl_dayLog').tmpl(data));

							var $content = $('#powerballLogBox tbody.content');
							if($content.find('tr').length > 30){
								$content.find('tr').slice(30).remove();
							}

							if(data.powerballOddEven)
							{
								if(data.powerballOddEven == 'odd')
								{
									$('#resultBox').show();
									$('#resultBox .bar').addClass('odd').fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300);
									$('#resultBox .bar').removeClass('even');
									$('#resultBox .bar .oddIcon').show().fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300);
									$('#resultBox .bar .evenIcon').hide();
									setTimeout(function(){
										$('#resultBox').hide();
									},3000);
								}
								else if(data.powerballOddEven == 'even')
								{
									$('#resultBox').show();
									$('#resultBox .bar').addClass('even').fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300);
									$('#resultBox .bar').removeClass('odd');
									$('#resultBox .bar .oddIcon').hide();
									$('#resultBox .bar .evenIcon').show().fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300);
									setTimeout(function(){
										$('#resultBox').hide();
									},3000);
								}
							}

							heightResize();
						}
						else
						{
							dayLogDbgLog('dataRefresh success but no new row (pageDiv.round === resp.round or empty)', {
								pageDivRound: $('#pageDiv').attr('round'),
								respRound: data && data.round,
								contentLen: (data && data.content) ? data.content.length : -1
							});
						}
					}
					else
					{
						dayLogDbgLog('dataRefresh unexpected state', { state: data && data.state, data: data });
					}
				},
				error:function (xhr,textStatus,errorThrown){
					_dayLogSyncStats.refreshLogErrors++;
					_dayLogSyncStats.lastRefreshLog = { t: Date.now(), error: true, status: xhr && xhr.status, textStatus: textStatus, errorThrown: String(errorThrown || '') };
					dayLogDbgLog('dataRefresh ajax ERROR (dataRefresh_process cleared)', _dayLogSyncStats.lastRefreshLog);
					dataRefresh_process = false;
				}
			});
		}
		else
		{
			dayLogDbgLog('dataRefresh skipped (already in flight)');
		}
	}

	function refreshAnalyse()
	{
		var dateStr = curDate.replace(/-/g, '');
		var _anUrl = actionBaseUrl + 'json/powerballAnalyse/'+dateStr+'.json?_='+(Date.now());
		$.ajax({
			type:'GET',
			cache:false,
			url: _anUrl,
			dataType:'json',
			timeout:1000,
			success:function(data,textStatus){
				if(data.state == 'success')
				{
					$('#powerballOddCnt').text(data.powerballOddCnt);
					$('#powerballEvenCnt').text(data.powerballEvenCnt);
					$('#powerballUnderCnt').text(data.powerballUnderCnt);
					$('#powerballOverCnt').text(data.powerballOverCnt);
					$('#numberOddCnt').text(data.numberOddCnt);
					$('#numberEvenCnt').text(data.numberEvenCnt);
					$('#numberUnderCnt').text(data.numberUnderCnt);
					$('#numberOverCnt').text(data.numberOverCnt);
					$('#numberBigCnt').text(data.numberBigCnt);
					$('#numberMiddleCnt').text(data.numberMiddleCnt);
					$('#numberSmallCnt').text(data.numberSmallCnt);

					$('#powerballOddPer').text(data.powerballOddPer);
					$('#powerballEvenPer').text(data.powerballEvenPer);
					$('#powerballUnderPer').text(data.powerballUnderPer);
					$('#powerballOverPer').text(data.powerballOverPer);
					$('#numberOddPer').text(data.numberOddPer);
					$('#numberEvenPer').text(data.numberEvenPer);
					$('#numberUnderPer').text(data.numberUnderPer);
					$('#numberOverPer').text(data.numberOverPer);
					$('#numberBigPer').text(data.numberBigPer);
					$('#numberMiddlePer').text(data.numberMiddlePer);
					$('#numberSmallPer').text(data.numberSmallPer);

					$('#powerballOddRow').text(data.powerballOddRow);
					$('#powerballEvenRow').text(data.powerballEvenRow);
					$('#powerballUnderRow').text(data.powerballUnderRow);
					$('#powerballOverRow').text(data.powerballOverRow);
					$('#numberOddRow').text(data.numberOddRow);
					$('#numberEvenRow').text(data.numberEvenRow);
					$('#numberUnderRow').text(data.numberUnderRow);
					$('#numberOverRow').text(data.numberOverRow);
					$('#numberBigRow').text(data.numberBigRow);
					$('#numberMiddleRow').text(data.numberMiddleRow);
					$('#numberSmallRow').text(data.numberSmallRow);
				}
				else
				{
					dayLogDbgLog('refreshAnalyse non-success', { state: data && data.state });
				}
			},
			error:function (xhr,textStatus,errorThrown){
				_dayLogSyncStats.analyseErrors++;
				dayLogDbgLog('refreshAnalyse ERROR', { status: xhr && xhr.status, textStatus: textStatus, errorThrown: String(errorThrown || ''), url: _anUrl });
			}
		});
	}

	function miniViewControl(type)
	{
		var h = (type == 'open') ? '400px' : '117px';
		var el = document.getElementById('miniViewFrame');
		if(el) el.style.height = h;
		$('#powerballMiniViewDiv #miniViewFrame').css('height', h);
	}
	window.miniViewControl = miniViewControl;

	function txTooltip()
	{
		if($('#txTooltipDiv').is(':visible'))
		{
			$('#txTooltipDiv').hide();
		}
		else
		{
			$('#txTooltipDiv').show();
		}
	}

	function toggleShare()
	{
		if($('#shareDiv').is(':visible'))
		{
			$('#shareDiv').hide();
		}
		else
		{
			$('#shareDiv').show();
		}
	}

	function copyShare(type)
	{
		var url = document.getElementById('share_'+type);
		url.select();
		document.execCommand('copy');

		alert('코드가 복사되었습니다. 원하시는 곳에 Ctrl + v 로 붙여넣기 하세요.');
	}

	// ladderTimer (서버에서 다음 추첨까지 남은 초·다음 회차로 초기화)
	var remainTime = <?= (int)($remain_seconds ?? 300) ?>;
	/** ajaxChatTimer 응답 기준 직전 remain — 0초 진입 시 회차별 분석 refresh (허브 누락 보강) */
	var _prevAjaxChatRemainDayLog = null;

	/** 서버 시계와 동기화 — 채팅(ajaxChatTimer)과 동일 소스로 맞춤 (다중 타이머·iframe 간 편차 방지) */
	function syncDayLogDrawTimerFromServer() {
		try {
			if (typeof curDate !== 'undefined' && typeof today !== 'undefined' && curDate != today) return;
		} catch (e) {}
		$.ajax({
			type: 'POST',
			url: actionBaseUrl,
			dataType: 'json',
			data: { view: 'action', action: 'ajaxChatTimer' }
		}).done(function (resp) {
			if (!resp || resp.state !== 'success') {
				_dayLogSyncStats.lastChatTimer = { t: Date.now(), bad: true, resp: resp };
				dayLogDbgLog('ajaxChatTimer bad response', resp);
				return;
			}
			var sec = parseInt(resp.remain_seconds, 10);
			if (isNaN(sec)) sec = 0;
			remainTime = sec;
			_dayLogSyncStats.lastChatTimer = { t: Date.now(), remain_seconds: sec, time_round: resp.time_round };
			var ri = Math.floor(remainTime / 60);
			var rs = remainTime % 60;
			$('#dayLogTimer .minute').text(ri);
			$('#dayLogTimer .second').text(rs < 10 ? '0' + rs : '' + rs);
			if (typeof resp.time_round !== 'undefined') {
				$('#timeRound').text(resp.time_round);
			}
			try {
				if (typeof curDate !== 'undefined' && typeof today !== 'undefined' && curDate == today) {
					if (_prevAjaxChatRemainDayLog !== null && _prevAjaxChatRemainDayLog > 0 && sec === 0) {
						dayLogDbgLog('ajaxChatTimer →0 → dataRefresh');
						try { dataRefresh(); } catch (e3) {}
					}
					_prevAjaxChatRemainDayLog = sec;
				}
			} catch (e4) {}
		}).fail(function (xhr, textStatus, errorThrown) {
			_dayLogSyncStats.lastChatTimer = { t: Date.now(), error: true, status: xhr && xhr.status, textStatus: textStatus, errorThrown: String(errorThrown || '') };
			dayLogDbgLog('ajaxChatTimer HTTP ERROR', _dayLogSyncStats.lastChatTimer);
		});
	}

	var _prevHubRemainDayLog = null;
	if (dayLogUsesParentHub) {
		window.addEventListener('message', function(ev) {
			var d = ev.data;
			if (!d || d.type !== 'drawTimerHub') return;
			try {
				if (ev.source !== window.parent) return;
			} catch (e) { return; }
			var sec = Math.max(0, parseInt(d.remainSeconds, 10) || 0);
			var tr = d.timeRound;
			try {
				if (typeof curDate !== 'undefined' && typeof today !== 'undefined' && curDate == today) {
					remainTime = sec;
					if (typeof tr !== 'undefined') $('#timeRound').text(tr);
					var ri = Math.floor(sec / 60);
					var rs = sec % 60;
					$('#dayLogTimer .minute').text(ri);
					$('#dayLogTimer .second').text(rs < 10 ? '0' + rs : '' + rs);
					if (_prevHubRemainDayLog !== null && _prevHubRemainDayLog > 0 && sec === 0) {
						dayLogDbgLog('drawTimerHub →0 → dataRefresh');
						try { dataRefresh(); } catch (e3) {}
					}
					_prevHubRemainDayLog = sec;
				}
			} catch (e2) {}
			try {
				var _mvHub = document.getElementById('miniViewFrame');
				if (_mvHub && _mvHub.contentWindow) {
					_mvHub.contentWindow.postMessage(d, '*');
				}
			} catch (e4) {}
		});
	}

	$(window).load(function(){
		if(getCookie('MINIVIEWLAYER') == 'Y')
		{
			miniViewControl('open');
		}
		// 픽 영역: 기본은 닫힘. 쿠키가 Y(이전에 연 상태)일 때만 복원
		if(getCookie('POINTBETLAYER') == 'Y')
		{
			var _mvf = document.getElementById('miniViewFrame');
			if (_mvf && _mvf.contentWindow && typeof _mvf.contentWindow.toggleBetting === 'function') {
				_mvf.contentWindow.toggleBetting();
			}
		}
		// ajaxPattern('oddEven','2026-03-10','powerball');
	});

	$(document).ready(function(){

		var sixPatternCnt = 6;
		var sixPatternType = 'oddEven';
		var sixDivision = 'powerball';

		$('#sixBox .patternCnt .btn a').click(function(){
			var rel = $(this).attr('rel');
			$('#sixBox .patternCnt .btn a').removeClass('on1');
			$(this).addClass('on1');

			$('#sixBox .patternType .btn a').removeClass('on2');
			$('#sixBox .patternType .btn').find('[sixType='+sixDivision+'_'+sixPatternType+']').addClass('on2');

			sixPatternCnt = rel;
			ajaxSixPattern(sixPatternCnt,sixPatternType,curDate,sixDivision);

		});

		$('#sixBox .patternType .btn a').click(function(){

			$('#sixBox .patternType .btn a').removeClass('on2');
			$(this).addClass('on2');

			$('#sixBox .patternCnt .btn a').removeClass('on1');
			$('#sixBox .patternCnt .btn').find('[rel='+sixPatternCnt+']').addClass('on1');

			sixPatternType = $(this).attr('rel');
			sixDivision = $(this).attr('division');
			ajaxSixPattern(sixPatternCnt,sixPatternType,curDate,sixDivision);

		});

		// 기본값: 육매 분석 데이터는 로드하지 않음 (탭 클릭 시에만 표시)
	});

	$(document).ready(function(){

		try{
			top.initAd();
		}
		catch(e){}

		setTimeout(function(){
			heightResize();
		},500);

		// tooltip
		$('[title!=""]').qtip({
			position:{
				my:'top center',
				at:'botom center'
			},
			style:{
				classes:'tooltip_dark'
			}
		});
	});

	$(window).resize(function(){
		heightResize();
	});

				//]]>
			</script>
			
	<!-- Google Analytics (analytics.js, 선배님과 동일) -->
	<script async src="https://www.google-analytics.com/analytics.js"></script>
	<script>
		window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments);};ga.l=+new Date;
		ga('create', 'UA-149467684-1', 'auto');
		ga('send', 'pageview');
	</script>

		</head>
		<body onload="">

			
	<style>
		div.timeBox {position:absolute;top:17px;right:125px;width:402px;height:30px;line-height:30px;border:1px solid #D5D5D5;text-align:center;background-color:#F1F1F1;font-size:11px;font-family:tahoma,dotum;}

		div.shareBox {position:absolute;top:17px;right:15px;width:100px;height:30px;line-height:30px;border:1px solid #D5D5D5;text-align:center;background-color:#C11A20;font-size:11px;font-family:tahoma,dotum;color:#fff;border:1px solid #921417;}
		div.shareBox a {display:block;color:#fff;}

		#shareDiv {position:absolute;background-color:#333;width:790px;padding:20px;display:none;z-index:99;}
		#shareDiv .tit {color:#fff;padding:10px 0;}
		#shareDiv .text {padding-bottom:10px;}
		#shareDiv .text textarea {background-color:#000;border:1px solid #000;color:#fff;width:600px;padding:10px;resize:none;}
		#shareDiv .text .btn {position:absolute;background-color:#C11A20;color:#fff;width:50px;height:50px;text-align:center;line-height:50px;margin-left:5px;border:1px solid #921417;}

		.txTooltip {background-color:#C11A20;border:1px solid #921417;color:#fff;width:16px;height:16px;text-align:center;display:inline-block;line-height:16px;}
		.txIcon {width:20px;height:20px;}

		#txTooltipDiv {position:absolute;margin-top:5px;background-color:#333;width:200px;height:30px;color:#fff;padding:20px;border-radius:2px;text-align:left;display:none;}

		.powerballBox a {color:#5F6164;}
	</style>

	<table width="100%" border="0" class="defaultTable">
		<colgroup>
			<col width="25%"/>
			<col width="25%"/>
			<col width="25%"/>
			<col width="25%"/>
		</colgroup>
		<tr>
			
		<th class="menu on" style="position:relative;"><a href="/?view=dayLog" class="tab1 on">일자별 분석<div style="position:absolute;top:5px;left:20px;"><img src="/images/realtime_bt.gif" width="37" height="19"></div></a></th>
		<th class="menu"><a href="/?view=latestLog" class="tab2">최근 분석<div style="position:absolute;top:5px;left:20px;"><img src="/images/realtime_bt.gif" width="37" height="19"></div></a></th>
		<th class="menu"><a href="/?view=periodLog" class="tab3">기간별 분석</a></th>
		<th class="menu"><a href="/?view=patternAnalyze" class="tab5">패턴별 분석</a></th>

		</tr>
	</table>

	<?php if (!empty($flash_message)): ?>
	<div style="padding:10px 15px;margin:10px 0;background:#fff3cd;border:1px solid #ffc107;color:#856404;font-size:12px;"><?= esc($flash_message) ?></div>
	<?php endif; ?>

	<?php
		$logDate = isset($date) ? $date : date('Y-m-d');
		$dateDisplay = date('Y.m.d', strtotime($logDate));
		$datePrev = date('Y-m-d', strtotime($logDate . ' -1 day'));
		$dateNext = date('Y-m-d', strtotime($logDate . ' +1 day'));
		$dateToday = date('Y-m-d');
	?>
	<div class="dateInfo">
		<div class="date-box">
			<span class="date"><?= $dateDisplay ?></span>
			<a href="/?view=dayLog&date=<?= $datePrev ?>" class="sp-date_prev prev rollover"></a>
			<a href="/?view=dayLog&date=<?= $dateNext ?>" class="sp-date_next next rollover"></a>
			<a href="/?view=dayLog&date=<?= $dateToday ?>" class="sp-date_today today rollover"></a>
			<input type="text" class="sp-date_cal calendar rollover" id="datepicker" readonly/>
		</div>

		<div class="timeBox">
			<div class="left">
				<?php $rsec = (int)($remain_seconds ?? 300); $rmin = (int)floor($rsec / 60); $rs = (int)($rsec % 60); ?>
				<span class="time" id="dayLogTimer"><strong><span class="minute"><?= $rmin ?></span>분 <span class="second"><?= $rs < 10 ? '0'.$rs : $rs ?></span>초</strong> 후 <span class="round"><strong id="timeRound"><?= (int)($next_round ?? 1) ?></strong> 회차</span> 데이터가 갱신됩니다.</span>
			</div>
			<div class="refresh"><a href="/?view=dayLog">데이터 새로고침</a></div>
		</div>
		<div class="shareBox"><a href="#" onclick="toggleShare();return false;">퍼가기</a></div>
	</div>

	<div id="shareDiv">
		<div class="tit">파워볼게임(PBG) 중계화면 퍼가기</div>
		<div class="text"><textarea id="share_view"><iframe src="https://www.powerballgame.co.kr/?view=powerballMiniView" width="1282" height="373" scrolling="no" frameborder="0"></iframe></textarea><a href="#" onclick="copyShare('view');return false;" class="btn">복사</a></div>
		<div class="tit">파워볼게임(PBG) 추첨결과 퍼가기</div>
		<div class="text"><textarea id="share_result">https://www.powerballgame.co.kr/json/powerball.json</textarea><a href="#" onclick="copyShare('result');return false;" class="btn">복사</a></div>
		<div class="tit">파워볼게임(PBG) 추첨결과(회차별) 퍼가기</div>
		<div class="text"><textarea id="share_recent">https://www.powerballgame.co.kr/json/powerball_recent.json</textarea><a href="#" onclick="copyShare('recent');return false;" class="btn">복사</a></div>
	</div>

	<div id="powerballMiniViewDiv" style="position:absolute;z-index:12;"><iframe name="miniViewFrame" id="miniViewFrame" src="/?view=powerballMiniView" frameborder="0" scrolling="no" style="width:830px;height:117px;" allowtransparency="true"></iframe></div>

	
	<table width="100%" border="1" class="powerballBox">
		<colgroup>
			<col width="9.5%"/>
			<col width="9.5%"/>
			<col width="9.5%"/>
			<col width="9.5%"/>
			<col width="9.5%"/>
			<col width="9.5%"/>
			<col width="9.5%"/>
			<col width="9.5%"/>
			<col width="8%"/>
			<col width="8%"/>
			<col width="8%"/>
		</colgroup>
		<tr>
			<th height="30" colspan="11" class="title" style="position:relative;">전체 분석 데이터<span style="position:absolute;top:6px;right:10px;color:#969696;" class="siteLink">copyright <a href="/?referer=dayLogBtn" target="_blank">powerballgame.co.kr</a></span></th>
		</tr>
		<tr class="subTitle">
			<th height="20" colspan="4">파워볼 기준</th>
			<th colspan="4">숫자합 기준</th>
			<th colspan="3">대중소 기준</th>
		</tr>
		<tr class="thirdTitle">
			<th height="20">홀</th>
			<th>짝</th>
			<th>언더</th>
			<th>오버</th>
			<th>홀</th>
			<th>짝</th>
			<th>언더</th>
			<th>오버</th>
			<th>대</th>
			<th>중</th>
			<th>소</th>
		</tr>
		<tr>
			<td height="60" align="center"><div class="sp-data_odd"><span class="num" id="powerballOddCnt">13</span></div><span class="oddColor">(<span id="powerballOddPer">61.9</span>%)</span></td>
			<td align="center"><div class="sp-data_even"><span class="text2" id="powerballEvenCnt">8</span></div><span class="evenColor">(<span id="powerballEvenPer">38.1</span>%)</span></td>
			<td align="center"><div class="sp-data_under" id="powerballUnderCnt">10</div><span class="oddColor">(<span id="powerballUnderPer">47.62</span>%)</span></td>
			<td align="center"><div class="sp-data_over" id="powerballOverCnt">11</div><span class="evenColor">(<span id="powerballOverPer">52.38</span>%)</span></td>
			<td align="center"><div class="sp-data_odd" id="numberOddCnt">8</div><span class="oddColor">(<span id="numberOddPer">38.1</span>%)</span></td>
			<td align="center"><div class="sp-data_even" id="numberEvenCnt">13</div><span class="evenColor">(<span id="numberEvenPer">61.9</span>%)</span></td>
			<td align="center"><div class="sp-data_under" id="numberUnderCnt">11</div><span class="oddColor">(<span id="numberUnderPer">52.38</span>%)</span></td>
			<td align="center"><div class="sp-data_over" id="numberOverCnt">10</div><span class="evenColor">(<span id="numberOverPer">47.62</span>%)</span></td>
			<td align="center"><div class="bigBox" id="numberBigCnt">8</div><span class="evenColor">(<span id="numberBigPer">38.1</span>%)</span></td>
			<td align="center"><div class="middleBox" id="numberMiddleCnt">8</div><span class="middleColor">(<span id="numberMiddlePer">38.1</span>%)</span></td>
			<td align="center"><div class="smallBox" id="numberSmallCnt">5</div><span class="oddColor">(<span id="numberSmallPer">23.81</span>%)</span></td>
		</tr>
		<tr>
			<td height="30" align="center"><span class="oddText" id="powerballOddRow">6</span>연속</td>
			<td align="center"><span class="evenText" id="powerballEvenRow">2</span>연속</td>
			<td align="center"><span class="oddText" id="powerballUnderRow">2</span>연속</td>
			<td align="center"><span class="evenText" id="powerballOverRow">2</span>연속</td>
			<td align="center"><span class="oddText" id="numberOddRow">2</span>연속</td>
			<td align="center"><span class="evenText" id="numberEvenRow">6</span>연속</td>
			<td align="center"><span class="oddText" id="numberUnderRow">3</span>연속</td>
			<td align="center"><span class="evenText" id="numberOverRow">3</span>연속</td>
			<td align="center"><span class="evenText" id="numberBigRow">2</span>연속</td>
			<td align="center"><span class="middleText" id="numberMiddleRow">2</span>연속</td>
			<td align="center"><span class="oddText" id="numberSmallRow">1</span>연속</td>
		</tr>
	</table>


	<table width="100%" border="0" id="patternBox" style="margin-top:10px;">
		<colgroup>
			<col width="20%"/>
			<col width="20%"/>
			<col width="20%"/>
			<col width="20%"/>
			<col width="20%"/>
		</colgroup>
		<tr>
			<th height="30" colspan="5" class="title" style="position:relative;">패턴별 분석 데이터<span style="position:absolute;top:6px;right:10px;color:#969696;" class="siteLink">copyright <a href="/?referer=dayLogBtn" target="_blank" class="titleCopy">powerballgame.co.kr</a></span></th>
		</tr>
		<tr class="subTitle">
			<th height="30" colspan="2">파워볼 기준</th>
			<th colspan="3">숫자합 기준</th>
		</tr>
		<tr>
			<th height="30" class="btn"><a href="#" onclick="ajaxPattern('oddEven',curDate,'powerball');return false;" class="tab1" type="powerball_oddEven">홀짝 패턴</a></th>
			<th class="btn"><a href="#" onclick="ajaxPattern('underOver',curDate,'powerball');return false;" class="tab2" type="powerball_underOver">언더오버 패턴</a></th>
			<th class="btn"><a href="#" onclick="ajaxPattern('oddEven',curDate,'number');return false;" class="tab1" type="number_oddEven">홀짝 패턴</a></th>
			<th class="btn"><a href="#" onclick="ajaxPattern('underOver',curDate,'number');return false;" class="tab2" type="number_underOver">언더오버 패턴</a></th>
			<th class="btn"><a href="#" onclick="ajaxPattern('period',curDate,'number');return false;" class="tab3" type="number_period">대중소 패턴</a></th>
		</tr>
		<tr>
			<td colspan="5" style="border-top:1px solid #d5d5d5;"><div class="content"></div></td>
		</tr>
	</table>

	<table width="100%" border="0" id="sixBox" style="margin-top:10px;">
		<colgroup>
			<col width="20%"/>
			<col width="20%"/>
			<col width="20%"/>
			<col width="20%"/>
			<col width="20%"/>
		</colgroup>
		<tr>
			<th height="30" colspan="5" class="title" style="position:relative;">육매 분석 데이터<span style="position:absolute;top:5px;left:50%;margin-left:-90px;z-index:1;" class="sp-icon_open"></span><span style="position:absolute;top:6px;right:10px;color:#969696;" class="siteLink">copyright <a href="/?referer=dayLogBtn" target="_blank" class="titleCopy">powerballgame.co.kr</a></span></th>
		</tr>
		<tr class="subTitle patternCnt">
			<th colspan="5">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="sixPatternCntBox">
					<tr>
						<td height="30" class="subTitle btn"><a href="#" onclick="return false;" rel="6">6매</a></td>
						<td class="subTitle btn"><a href="#" onclick="return false;" rel="5">5매</a></td>
						<td class="subTitle btn"><a href="#" onclick="return false;" rel="4">4매</a></td>
						<td class="subTitle btn"><a href="#" onclick="return false;" rel="3">3매</a></td>
						<td class="subTitle btn"><a href="#" onclick="return false;" rel="2">2매</a></td>
						<td class="subTitle btn none"><a href="#" onclick="return false;" rel="1">1매</a></td>
					</tr>
				</table>
			</th>
		</tr>
		<tr class="patternType">
			<th height="30" class="btn"><a href="#" onclick="return false;" rel="oddEven" division="powerball" sixType="powerball_oddEven">파워볼 홀짝 패턴</a></th>
			<th class="btn"><a href="#" onclick="return false;" rel="underOver" division="powerball" sixType="powerball_underOver">파워볼 언더오버 패턴</a></th>
			<th class="btn"><a href="#" onclick="return false;" rel="oddEven" division="number" sixType="number_oddEven">숫자합 홀짝 패턴</a></th>
			<th class="btn"><a href="#" onclick="return false;" rel="underOver" division="number" sixType="number_underOver">숫자합 언더오버 패턴</a></th>
			<th class="btn"><a href="#" onclick="return false;" rel="period" division="number" sixType="number_period">숫자합 대중소 패턴</a></th>
		</tr>
		<tr>
			<td colspan="5" style="border-top:1px solid #d5d5d5;"><div class="content"></div></td>
		</tr>
	</table>

	<div id="resultBox">
		<div class="bar"></div>
	</div>

	<table width="100%" border="1" id="powerballLogBox" class="powerballBox" style="margin-top:10px;margin-bottom:10px;">
		<colgroup>
			<col width="16%"/>
			<col width="6%"/>
			<col width="8%"/>
			<col width="9%"/>
			<col width="7%"/>
			<col width="7%"/>
			<col width="12%"/>
			<col width="5%"/>
			<col width="9%"/>
			<col width="9%"/>
			<col width="7%"/>
			<col width="7%"/>
		</colgroup>
		<tr>
			<th height="30" colspan="12" class="title" style="position:relative;">회차별 분석 데이터<span style="position:absolute;top:6px;right:10px;color:#969696;" class="siteLink">copyright <a href="/?referer=dayLogBtn" target="_blank" class="titleCopy">powerballgame.co.kr</a></span></th>
		</tr>
		<tr class="subTitle">
			<th height="60" rowspan="2">회차</th>
			<th rowspan="2">시간</th>
			<th height="30" colspan="4">파워볼</th>
			<th colspan="6">숫자</th>
		</tr>
		<tr class="thirdTitle">
			<th height="30">결과</th>
			<th>구간</th>
			<th>홀짝</th>
			<th title="기준:4.5">언더/오버</th>
			<th>결과</th>
			<th>합</th>
			<th>구간</th>
			<th>대/중/소</th>
			<th>홀짝</th>
			<th title="기준:72.5">언더/오버</th>
		</tr>
		<tbody class="content">
		</tbody>
	</table>

	<div class="displayNone center" id="pageDiv" pageVal="0" round="<?= max(0, (int)($next_round ?? 1) - 1) ?>"><img src="/images/loading2.gif" width="50" height="50" alt=""></div>
	<div class="moreBox"><a href="#" onclick="moreClick();return false;">더보기</a></div>

	<!-- tmpl -->
	<script id="tmpl_dayLog" type="text/x-jquery-tmpl">
		{{each(i,item) content}}
		{{if item.reasonType}}
		<tr class="${trClass}">
			<td height="40" align="center"><span class="numberText">${round}회</span> ( <span class="numberText">${todayRound}회</span> )</td>
			<td align="center" class="numberText">${time}</td>
			<td align="center" class="numberText" colspan="10">${reasonMessage}</td>
		</tr>
		{{else}}
		<tr class="${trClass}">
			<td height="40" align="center"><span class="numberText">${round}회</span> ( <span class="numberText">${todayRound}회</span> )<br/><a href="https://www.kaiascan.io/ko/block/${blockNumber}?tabId=blockTransactions" target="_blank" class="numberText">${blockNumber}</a> / <a href="#" onclick="windowOpen('/?view=pbgCheck&query=${blockNumber}/${blockHashKey}','memo',640,620,'no');return false;" target="_blank" class="numberText">${blockHashKey}</a></td>
			<td align="center" class="numberText">${time}</td>
			<td align="center" class="numberText"><div class="sp-ball_bg">${powerball}</div></td>
			<td align="center" class="numberText">${powerballPeriod}</td>
			<td align="center"><div class="sp-${powerballOddEven}"></div></td>
			<td align="center"><div class="sp-${powerballUnderOver}"></div></td>
			<td align="center" class="numberText">${number}</td>
			<td align="center" class="numberText">${numberSum}</td>
			<td align="center" class="numberText">${numberSumPeriod}</td>
			<td align="center" class="numberText">${numberPeriod}</td>
			<td align="center"><div class="sp-${numberOddEven}"></div></td>
			<td align="center"><div class="sp-${numberUnderOver}"></div></td>
		</tr>
		{{/if}}
		{{/each}}
	</script>
	<!-- //tmpl -->


		</body>
	</html>
