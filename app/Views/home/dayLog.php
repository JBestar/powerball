
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
			<?php $cdn = 'https://static.powerballgame.co.kr'; $local = rtrim(site_furl(''), '/'); ?>
			<link rel="stylesheet" href="<?php echo $cdn; ?>/css/jquery.qtip.min.css" type="text/css" onerror="this.onerror=null;this.href='<?php echo $local; ?>/css/jquery.qtip.min.css'"/>
			<link rel="stylesheet" href="<?php echo $cdn; ?>/css/common.css?v=201905194" type="text/css" onerror="this.onerror=null;this.href='<?php echo $local; ?>/css/common.css?v=201905194'"/>
			<link rel="stylesheet" href="<?php echo $cdn; ?>/css/sprites.css?201905194" type="text/css" onerror="this.onerror=null;this.href='<?php echo $local; ?>/css/sprites.css?v=201905194'"/>
			<link rel="stylesheet" href="<?php echo $cdn; ?>/css/jquery-ui.css" type="text/css" onerror="this.onerror=null;this.href='<?php echo $local; ?>/css/jquery-ui.css'"/>
			<link rel="shortcut icon" href="favicon.ico"/>
			<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" onerror="this.onerror=null;var s=document.createElement('script');s.src='<?php echo $local; ?>/js/jquery-1.11.2.min.js';document.body.appendChild(s);"></script>
			<script type="text/javascript" src="<?php echo $cdn; ?>/js/jquery-ui.js" onerror="this.onerror=null;var s=document.createElement('script');s.src='<?php echo $local; ?>/js/jquery-ui.js';document.body.appendChild(s);"></script>
			<script type="text/javascript" src="<?php echo $cdn; ?>/js/jquery.qtip.min.js" onerror="this.onerror=null;var s=document.createElement('script');s.src='<?php echo $local; ?>/js/jquery.qtip.min.js';document.body.appendChild(s);"></script>
			<script type="text/javascript" src="<?php echo $local; ?>/js/default.js?v=<?php echo time(); ?>"></script>
			<script type="text/javascript" src="<?php echo $cdn; ?>/js/jquery.number.min.js" onerror="this.onerror=null;var s=document.createElement('script');s.src='<?php echo $local; ?>/js/jquery.number.min.js';document.body.appendChild(s);"></script>
			<script type="text/javascript" src="<?php echo $cdn; ?>/js/jquery.tmpl.min.js" onerror="this.onerror=null;var s=document.createElement('script');s.src='<?php echo $local; ?>/js/jquery.tmpl.min.js';document.body.appendChild(s);"></script>
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

	// mainFrame 높이: 실제 문서 높이로 조절 (육매/패턴 영역·더보기 포함해 잘리지 않도록)
	var DAYLOG_MIN_HEIGHT = 500;
	function heightResize() {
		function setFrameHeight(h) {
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
			var totalHeight = DAYLOG_MIN_HEIGHT;
			var moreBox = document.querySelector('.moreBox');
			if (moreBox) {
				var rect = moreBox.getBoundingClientRect();
				var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
				totalHeight = rect.bottom + scrollTop;
				try {
					var mb = parseFloat(window.getComputedStyle(moreBox).marginBottom) || 0;
					totalHeight += mb;
				} catch (e) {}
			} else {
				totalHeight = document.body.scrollHeight || document.documentElement.scrollHeight || document.body.offsetHeight || DAYLOG_MIN_HEIGHT;
			}
			totalHeight = Math.max(DAYLOG_MIN_HEIGHT, Math.ceil(totalHeight));
			setFrameHeight(totalHeight);
		}
		if (window.requestAnimationFrame) {
			requestAnimationFrame(function() { requestAnimationFrame(measureAndSet); });
		} else {
			setTimeout(measureAndSet, 80);
		}
	}

	$(document).ready(function(){

		moreClick();
		// 전체 분석 데이터 (해당 날짜 집계) 초기 로드
		refreshAnalyse();

		setInterval(function(){
			ladderTimer('dayLogTimer');
		},1000);

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
		if(loading == false)
		{
			loading = true;

			$('#pageDiv').show();
			var page = parseInt($('#pageDiv').attr('pageVal'), 10);
			if(isNaN(page)) page = 0;

			$.ajax({
				type:'POST',
				dataType:'json',
				url:'/',
				data:{
					view:'action',
					action:'ajaxPowerballLog',
					actionType:'dayLog',
					date:curDate,
					page:page
				},
				success:function(data,textStatus){
					if(data && data.content && data.content.length)
					{
						$('#powerballLogBox tbody.content').append($('#tmpl_dayLog').tmpl(data));
					}
					else if(data && data.content)
					{
						$('#powerballLogBox tbody.content').append($('#tmpl_dayLog').tmpl(data));
					}
					// 첫 페이지(0)일 때 30개 초과 시 30개만 유지
					if(page === 0){
						var $content = $('#powerballLogBox tbody.content');
						if($content.find('tr').length > 30){
							$content.find('tr').slice(30).remove();
						}
					}
					if(data && data.endYN == 'Y')
					{
						$('.moreBox').hide();
					}
					else if(data && data.endYN == 'N')
					{
						$('.moreBox').show();
					}
					if(data && data.round != null)
					{
						$('#pageDiv').attr('round', data.round);
					}

					$('#pageDiv').hide();
					$('#pageDiv').attr('pageVal', page + 1);

					loading = false;

					heightResize();
				},
				error:function (xhr,textStatus,errorThrown){
					loading = false;
					$('#pageDiv').hide();
				}
			});
		}
	}

	// data refresh
	var dataRefresh_process = false;

	function dataRefresh()
	{
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
			$.ajax({
				type:'POST',
				dataType:'json',
				url:'/',
				data:{
					view:'action',
					action:'ajaxPowerballLog',
					actionType:'refreshLog',
					date:curDate,
					round:round
				},
				success:function(data,textStatus){

					dataRefresh_process = false;

					if(data.state == 'success')
					{
						// 전체 분석 데이터 갱신
						refreshAnalyse();

						if(data && $('#pageDiv').attr('round') != data.round)
						{
							$('#pageDiv').attr('round',data.round);

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
					}
					else
					{
						//dataRefresh();
					}
				},
				error:function (xhr,textStatus,errorThrown){
					//alert('error'+(errorThrown?errorThrown:xhr.status));
				}
			});
		}
	}

	function refreshAnalyse()
	{
		var dateStr = curDate.replace(/-/g, '');
		$.ajax({
			type:'GET',
			cache:false,
			url:'/json/powerballAnalyse/'+dateStr+'.json?_='+(Date.now()),
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
			},
			error:function (xhr,textStatus,errorThrown){
				//alert('error'+(errorThrown?errorThrown:xhr.status));
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

	$(window).load(function(){
		if(getCookie('MINIVIEWLAYER') == 'Y')
		{
			miniViewControl('open');
		}
		if(getCookie('POINTBETLAYER') != 'Y')
		{
			document.getElementById('miniViewFrame').contentWindow.toggleBetting();
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
		<th class="menu"><a href="#" onclick="alert('로그인 후 이용가능합니다.');return false;" class="tab2">최근 분석<div style="position:absolute;top:5px;left:20px;"><img src="/images/realtime_bt.gif" width="37" height="19"></div></a></th>
		<th class="menu"><a href="#" onclick="alert('로그인 후 이용가능합니다.');return false;" class="tab3">기간별 분석</a></th>
		<th class="menu"><a href="#" onclick="alert('로그인 후 이용가능합니다.');return false;" class="tab5">패턴별 분석</a></th>

		</tr>
	</table>

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

	<div class="displayNone center" id="pageDiv" pageVal="0" round="<?= max(0, (int)($next_round ?? 1) - 1) ?>"><img src="https://simg.powerballgame.co.kr/images/loading2.gif" width="50" height="50"></div>
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
