/** 부모가 같은 출처일 때만 dayLog 허브 연동(타이머 postMessage). 타 도메인(라이온 iframe 등)이면 false → 서버 동기화 타이머 사용 */
var miniViewUsesParentHub = false;
try {
	if (window.parent && window.parent !== window) {
		void window.parent.location.href;
		miniViewUsesParentHub = true;
	}
} catch (e) {
	miniViewUsesParentHub = false;
}

function ladderResultTimer(divId)
{
	// 백그라운드 탭에서는 타이머가 분 단위로만 돌아가 remainTime이 크게 밀림 → 감춤일 땐 감소 생략, 복귀 시 서버 동기화로 맞춤
	if (typeof document !== "undefined" && document.hidden) {
		return;
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

// update result
function updateResult(data)
{
	$('#lotteryBox .play').show();
	$('#ladderReady').hide();

	$('#beforeResult').html($('#lotteryResult').html());
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
		var totalBalls = 6;
		for(var idx = 0; idx < totalBalls; idx++){
			showNumber(ballArr[idx], idx);
		}
		setTimeout(function(){
			$('#lotteryBox .play').hide();
			$('#ladderReady').show();
		}, 2000 * totalBalls + 2000);
	}, 2000);
}

function showNumber(num, index)
{
	index = index == null ? 0 : index;
	var delay = 2000 * index;
	setTimeout(function(){
		var ballColor = ballColorSel(num);
		var $ball = $('#lotteryBall');
		$ball.show();
		$ball.html('<span class="ball_'+ballColor+'">'+num+'</span>');

		var ballId = 'ballNumber_'+num;
		TweenMax.to(document.getElementById('lotteryBall'), 1, {
			bezier: { type: 'cubic', values: [{x:175,y:-5},{x:-50,y:5},{x:-20,y:300},{x:345,y:210}], autoRotate: false },
			ease: Power1.easeInOut,
			onStart: function(){
				$('#lotteryResult').append('<span id="'+ballId+'" class="ball_'+ballColor+'"><span class="ballNumber">'+num+'</span></span>');
				$('#'+ballId).hide();
			},
			onComplete: function(){
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
	if (!base || base === '/') return;
	$.post(base, { view: 'action', action: 'ajaxChatTimer' }, function(resp) {
		if (!resp || resp.state !== 'success') return;
		var sec = parseInt(resp.remain_seconds, 10);
		if (isNaN(sec)) sec = 0;
		remainTime = sec;
		if (typeof resp.time_round !== 'undefined') {
			$('#timeRound').text(resp.time_round);
			$('.nextRound').text(resp.time_round);
		}
		var remain_i = Math.floor(remainTime / 60);
		var remain_s = remainTime % 60;
		$('#ladderTimer').find('.minute').text(remain_i);
		$('#ladderTimer').find('.second').text(remain_s < 10 ? '0' + remain_s : '' + remain_s);
	}, 'json');
}

/** 탭/창 복귀 직후 네트워크 지연·스로틀 보정용 연속 동기화 (부모에서도 호출 가능) */
function scheduleMiniViewSyncBurst() {
	if (miniViewUsesParentHub) {
		try {
			window.parent.postMessage({ type: 'drawTimerHubRequestSync' }, '*');
		} catch (e) {}
		return;
	}
	try { syncMiniViewDrawTimerFromServer(); } catch (e) {}
	setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 0);
	setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 150);
	setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 500);
}
window.syncMiniViewDrawTimerFromServer = syncMiniViewDrawTimerFromServer;
window.scheduleMiniViewSyncBurst = scheduleMiniViewSyncBurst;

var _prevHubRemainMini = null;

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
	_prevHubRemainMini = sec;
}

if (miniViewUsesParentHub) {
	window.addEventListener('message', function(ev) {
		var d = ev.data;
		if (!d || d.type !== 'drawTimerHub') return;
		try {
			if (ev.source !== window.parent) return;
		} catch (e) { return; }
		if (document.hidden) {
			if (window.CI_APP_DEBUG && console && console.log) {
				console.log('[drawTimerHub:miniView] document.hidden → UI 갱신 생략');
			}
			return;
		}
		miniViewApplyDrawTimerFromHub(d.remainSeconds, d.timeRound);
	});
}

$(document).ready(function(){
	rebuildBallsNoWhitespace('lotteryResult', true);
	rebuildBallsNoWhitespace('beforeResult', false);

	if(remainTime <= 10 && remainTime >= 0){
		$('#lotteryBox .play').css('display', 'block').show();
		$('#ladderReady').hide();
	}
	if (!miniViewUsesParentHub) {
		setTimeout(function() { try { syncMiniViewDrawTimerFromServer(); } catch (e) {} }, 300);
		setInterval(function() {
			try {
				if (!document.hidden) syncMiniViewDrawTimerFromServer();
			} catch (e) {}
		}, 5000);
	}
	$(document).on('visibilitychange.miniviewtimer', function() {
		if (!document.hidden) {
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