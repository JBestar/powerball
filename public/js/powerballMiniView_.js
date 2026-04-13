function ladderResultTimer(divId)
{
	if(remainTime == 0)
	{
		remainTime = 300;
		var roundNum = parseInt($('#timeRound').text(), 10) + 1;
		$('#timeRound').text(roundNum);
		$('.nextRound').text(roundNum);
		// 추첨 결과 조회 후 공 애니메이션
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

	// 추첨 10초 전부터 .play 표시 (레일 재생) — 10 이하일 때마다 적용해 로드 시점이 9초 등이어도 표시
	if(remainTime <= 10 && remainTime >= 0){
		$('#lotteryBox .play').css('display', 'block').show();
		$('#ladderReady').hide();
	}

	var remain_i = Math.floor(remainTime / 60);
	var remain_s = Math.floor(remainTime % 60);

	$('#'+divId).find('.minute').text(remain_i);
	$('#'+divId).find('.second').text(remain_s);
}

function miniViewMoveCurrentResultToBefore() {
	var $kids = $('#lotteryResult').children().clone();
	if ($kids.length === 0) {
		return;
	}
	$kids.removeAttr('id');
	$kids.find('[id]').removeAttr('id');
	$('#beforeResult').empty().append($kids);
}

// update result
function updateResult(data)
{
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

	/* 당첨 파워볼번호(시안), 숫자합(흰색) - 선배님처럼 class="b" + 인라인 스타일 */
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

		var ballId = 'lotteryBallSlot_' + index;
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

// 추첨 결과 번호(1~28)에 따라 공 색상 매핑. 결과에 따라 동적으로 바뀜.
function ballColorSel(num)
{
	var ballColor = 'blue';
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
			ballColor = 'blue';
			break;
	}
	return ballColor;
}

// PHP에서 그린 공들은 줄바꿈/들여쓰기로 인해 inline-block 사이 공백이 생김. 공백 없이 한 번에 다시 그려 6개 한 행 유지.
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

$(document).ready(function(){
	rebuildBallsNoWhitespace('lotteryResult', true);
	rebuildBallsNoWhitespace('beforeResult', false);

	// 로드 시점에 이미 10초 이하면 .play 즉시 표시
	if(remainTime <= 10 && remainTime >= 0){
		$('#lotteryBox .play').css('display', 'block').show();
		$('#ladderReady').hide();
	}
	setInterval(function(){
		ladderResultTimer('ladderTimer');
	},1000);
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
			url: window.POWERBALL_AJAX_URL || '/',
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
						$('#captchaImg').html('<img src="' + (window.POWERBALL_BASE_URL || '') + '/captcha.php?type=pointBet&time='+new Date().getTime()+'">');
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
			url: window.POWERBALL_AJAX_URL || '/',
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

						$('#captchaImg img').attr('src', (window.POWERBALL_BASE_URL || '') + '/captcha.php?type=pointBet&time='+new Date().getTime());
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
