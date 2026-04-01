<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?= esc($site_title ?? '최근 분석') ?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<?php $local = rtrim(site_furl(''), '/'); ?>
	<link rel="stylesheet" href="<?= $local ?>/css/jquery.qtip.min.css" type="text/css"/>
	<link rel="stylesheet" href="<?= $local ?>/css/jquery-ui.css?v=<?= @filemtime(FCPATH.'css/jquery-ui.css') ?: time() ?>" type="text/css"/>
	<link rel="stylesheet" href="<?= $local ?>/css/common.css?v=<?= time() ?>" type="text/css"/>
	<link rel="stylesheet" href="<?= $local ?>/css/sprites.css?v=<?= time() ?>" type="text/css"/>
	<link rel="shortcut icon" href="favicon.ico"/>
	<script type="text/javascript" src="<?= $local ?>/js/jquery-1.11.2.min.js"></script>
	<script type="text/javascript" src="<?= $local ?>/js/jquery-ui.js"></script>
	<script type="text/javascript" src="<?= $local ?>/js/jquery.qtip.min.js"></script>
	<script type="text/javascript" src="<?= $local ?>/js/default.js?v=<?= time() ?>"></script>
	<script type="text/javascript" src="<?= $local ?>/js/jquery.tmpl.min.js"></script>
	<style>
		#roundCnt { display: none; } /* selectmenu 위젯 사용 */
	</style>
	<script type="text/javascript">
	//<![CDATA[
	function toggleShare(){
		if($('#shareDiv').is(':visible')) $('#shareDiv').hide();
		else $('#shareDiv').show();
	}
	function copyShare(type){
		var el = document.getElementById('share_'+type);
		if(el){ el.select(); document.execCommand('copy'); alert('코드가 복사되었습니다.'); }
	}

	$(document).ready(function(){
		$('.defaultTable .menu').click(function(){
			$('.defaultTable .menu').each(function(){ $(this).removeClass('on'); });
			$(this).addClass('on');
		});
		$('#roundCnt').selectmenu({
			width: 98
		}).on('selectmenuchange', function(){
			document.logForm.submit();
		});
	});

	var actionBaseUrl = '<?= rtrim(esc(site_furl("")), "/") ?>/';
	window.ACTION_BASE_URL = actionBaseUrl;
	var roundCnt = <?= (int)($roundCnt ?? 300) ?>;
	window.ANALYSIS_MODE = 'latestLog';
	window.LATEST_ROUND_CNT = roundCnt;
	var today = '<?= date('Y-m-d') ?>';
	var curDate = today;

	// ladderTimer (서버에서 다음 추첨까지 남은 초·다음 회차로 초기화)
	var remainTime = <?= (int)($remain_seconds ?? 300) ?>;
	function ladderTimer(divId)
	{
		if(remainTime == 0)
		{
			remainTime = 300;

			var roundNum = parseInt($('#timeRound').text())+1;
			if(roundNum == 289) roundNum = 1;
			$('#timeRound').text(roundNum);
		}

		remainTime--;

		var remain_i = Math.floor(remainTime / 60);
		var remain_s = Math.floor(remainTime % 60);
		if(remain_s < 10) remain_s = '0' + remain_s;

		$('#'+divId).find('.minute').text(remain_i);
		$('#'+divId).find('.second').text(remain_s);
	}

	var DAYLOG_MIN_HEIGHT = 500;
	function heightResize() {
		function setFrameHeight(h) {
			try {
				if (window.parent && window.parent.frameAutoResize)
					window.parent.frameAutoResize('mainFrame', h);
				else if (window.parent && window.parent.document) {
					var frame = window.parent.document.getElementById('mainFrame');
					if (frame) frame.style.height = h + 'px';
				}
			} catch (e) {}
		}
		var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
		var totalHeight = Math.max(
			DAYLOG_MIN_HEIGHT,
			document.body.scrollHeight || document.documentElement.scrollHeight || 0
		);
		// 회차별 분석 테이블(#powerballLogBox) 하단 테두리 잘림 방지용 보정
		var powerballLogBox = document.getElementById('powerballLogBox');
		if (powerballLogBox && powerballLogBox.getBoundingClientRect) {
			var boxRect = powerballLogBox.getBoundingClientRect();
			if (boxRect && typeof boxRect.bottom === 'number') totalHeight = Math.max(totalHeight, boxRect.bottom + scrollTop);
		}
		setFrameHeight(Math.ceil(totalHeight) + 6);
	}

	var loading = false;
	function moreClick() {
		var page = parseInt($('#pageDiv').attr('pageVal'), 10);
		if (isNaN(page)) page = 0;
		if (loading) return;
		loading = true;
		$('#pageDiv').show();
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: actionBaseUrl,
			data: {
				view: 'action',
				action: 'ajaxPowerballLog',
				actionType: 'latestLog',
				page: page,
				roundCnt: roundCnt
			},
			success: function(data) {
				var $tbody = $('#powerballLogBox tbody.content');
				if (page === 0 && data && data.content) {
					$tbody.empty();
					if (data.content.length)
						$tbody.append($('#tmpl_dayLog').tmpl(data));
					else
						$tbody.append('<tr class="trOdd"><td colspan="12" height="50" align="center" style="color:#888;">최근 추첨 결과가 없습니다.</td></tr>');
					var maxRows = roundCnt;
					if ($tbody.find('tr').length > maxRows)
						$tbody.find('tr').slice(maxRows).remove();
				} else if (page !== 0 && data && data.content && data.content.length) {
					$tbody.append($('#tmpl_dayLog').tmpl(data));
				}
				if (data && data.endYN == 'Y') $('.moreBox').hide();
				else if (data && data.endYN == 'N') $('.moreBox').show();
				if (data && data.round != null) $('#pageDiv').attr('round', data.round);
				$('#pageDiv').hide();
				$('#pageDiv').attr('pageVal', page + 1);
				loading = false;
				heightResize();
			},
			error: function(xhr) {
				loading = false;
				$('#pageDiv').hide();
				var pageErr = parseInt($('#pageDiv').attr('pageVal'), 10);
				if (isNaN(pageErr)) pageErr = 0;
				if (pageErr === 0) {
					var $tbody = $('#powerballLogBox tbody.content');
					if ($tbody.find('tr').length === 0) {
						if (xhr && xhr.responseJSON && xhr.responseJSON.content === 'notlogin')
							$tbody.append('<tr class="trOdd"><td colspan="12" height="50" align="center" style="color:#888;">로그인 후 이용가능합니다.</td></tr>');
						else
							$tbody.append('<tr class="trOdd"><td colspan="12" height="50" align="center" style="color:#888;">데이터를 불러올 수 없습니다.</td></tr>');
					}
					heightResize();
				}
			}
		});
	}

	function refreshAnalyse()
	{
		$.ajax({
			type:'POST',
			url: actionBaseUrl,
			data:{
				view:'action',
				action:'ajaxPowerballAnalyse',
				roundCnt: roundCnt
			},
			dataType:'json',
			timeout:2000,
			success:function(data){
				if(!data || data.state !== 'success') return;
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

				heightResize();
			}
		});
	}

	$(document).ready(function(){
		setTimeout(function(){ moreClick(); }, 50);
		refreshAnalyse();
		setInterval(function(){ ladderTimer('dayLogTimer'); },1000);

		// 육매 버튼 동작은 dayLog와 동일
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

		// tooltip
		$('[title!=""]').qtip({
			position:{ my:'top center', at:'botom center' },
			style:{ classes:'tooltip_dark' }
		});
	});
	//]]>
	</script>
</head>
<body>

	<table width="100%" border="0" class="defaultTable">
		<colgroup>
			<col width="25%"/><col width="25%"/><col width="25%"/><col width="25%"/>
		</colgroup>
		<tbody><tr>
			<th class="menu"><a href="/?view=dayLog" class="tab1">일자별 분석<div style="position:absolute;top:5px;left:20px;"><img src="/images/realtime_bt.gif" width="37" height="19"></div></a></th>
			<th class="menu on"><a href="/?view=latestLog" class="tab2 on">최근 분석<div style="position:absolute;top:5px;left:20px;"><img src="/images/realtime_bt.gif" width="37" height="19"></div></a></th>
			<th class="menu"><a href="/?view=periodLog" class="tab3">기간별 분석</a></th>
			<th class="menu"><a href="/?view=patternAnalyze" class="tab5">패턴별 분석</a></th>
		</tr></tbody>
	</table>

	<div class="periodBox" style="position:relative;margin:0;height:65px;top:0;">
		<div style="position:absolute;top:17px;left:15px;">
			<form name="logForm" method="get" action="<?= esc(site_furl('')) ?>">
				<input type="hidden" name="view" value="latestLog">
				<div style="display:inline-block;vertical-align:top;padding-top:10px;font-weight:bold;">최근</div>
				<select name="roundCnt" id="roundCnt" class="roundCntSelect" style="display: none;">
					<?php for ($n = 50; $n <= 2000; $n += 50): ?>
					<option value="<?= $n ?>"<?= (isset($roundCnt) && (int)$roundCnt === $n) ? ' selected' : '' ?>><?= $n ?></option>
					<?php endfor; ?>
				</select>
				<div style="display:inline-block;vertical-align:top;padding-top:10px;font-weight:bold;">회 데이터 분석</div>
			</form>
		</div>
		<?php $rsec = (int)($remain_seconds ?? 300); $rmin = (int)floor($rsec / 60); $rs = (int)($rsec % 60); ?>
		<div class="timeBox" style="position:absolute;top:17px;right:125px;width:402px;height:30px;line-height:30px;border:1px solid #D5D5D5;text-align:center;background-color:#F1F1F1;font-size:11px;font-family:tahoma,dotum;">
			<div class="left">
				<span class="time" id="dayLogTimer"><strong><span class="minute"><?= $rmin ?></span>분 <span class="second"><?= $rs < 10 ? '0'.$rs : $rs ?></span>초</strong> 후 <span class="round"><strong id="timeRound"><?= (int)($next_round ?? 1) ?></strong> 회차</span> 데이터가 갱신됩니다.</span>
			</div>
			<div class="refresh"><a href="<?= rtrim(site_furl(''), '/') ?>/?view=latestLog&amp;roundCnt=<?= (int)($roundCnt ?? 300) ?>">데이터 새로고침</a></div>
		</div>
		<div class="shareBox" style="position:absolute;top:17px;right:15px;width:100px;height:30px;line-height:30px;border:1px solid #921417;text-align:center;background-color:#C11A20;font-size:11px;font-family:tahoma,dotum;color:#fff;"><a href="#" onclick="toggleShare();return false;" style="display:block;color:#fff;">퍼가기</a></div>
	</div>

	<div id="shareDiv" style="position:absolute;background:#333;width:790px;padding:20px;display:none;z-index:99;">
		<div class="tit" style="color:#fff;padding:10px 0;">추첨결과 퍼가기</div>
		<div class="text" style="padding-bottom:10px;">
			<textarea id="share_recent" style="background:#000;border:1px solid #000;color:#fff;width:600px;padding:10px;resize:none;"></textarea>
			<a href="#" onclick="copyShare('recent');return false;" class="btn" style="background:#C11A20;color:#fff;padding:5px 10px;">복사</a>
		</div>
	</div>

	<table width="100%" border="1" class="powerballBox">
		<colgroup>
			<col width="9.5%"/><col width="9.5%"/><col width="9.5%"/><col width="9.5%"/>
			<col width="9.5%"/><col width="9.5%"/><col width="9.5%"/><col width="9.5%"/>
			<col width="8%"/><col width="8%"/><col width="8%"/>
		</colgroup>
		<tr>
			<th height="30" colspan="11" class="title" style="position:relative;">전체 분석 데이터 (최근 <?= (int)($roundCnt ?? 300) ?>회)<span style="position:absolute;top:6px;right:10px;color:#969696;" class="siteLink">copyright <a href="/?referer=dayLogBtn" target="_blank">powerballgame.co.kr</a></span></th>
		</tr>
		<tr class="subTitle">
			<th height="20" colspan="4">파워볼 기준</th>
			<th colspan="4">숫자합 기준</th>
			<th colspan="3">대중소 기준</th>
		</tr>
		<tr class="thirdTitle">
			<th height="20">홀</th><th>짝</th><th>언더</th><th>오버</th>
			<th>홀</th><th>짝</th><th>언더</th><th>오버</th>
			<th>대</th><th>중</th><th>소</th>
		</tr>
		<tr>
			<td height="60" align="center"><div class="sp-data_odd"><span class="num" id="powerballOddCnt">0</span></div><span class="oddColor">(<span id="powerballOddPer">0</span>%)</span></td>
			<td align="center"><div class="sp-data_even"><span class="text2" id="powerballEvenCnt">0</span></div><span class="evenColor">(<span id="powerballEvenPer">0</span>%)</span></td>
			<td align="center"><div class="sp-data_under" id="powerballUnderCnt">0</div><span class="oddColor">(<span id="powerballUnderPer">0</span>%)</span></td>
			<td align="center"><div class="sp-data_over" id="powerballOverCnt">0</div><span class="evenColor">(<span id="powerballOverPer">0</span>%)</span></td>
			<td align="center"><div class="sp-data_odd" id="numberOddCnt">0</div><span class="oddColor">(<span id="numberOddPer">0</span>%)</span></td>
			<td align="center"><div class="sp-data_even" id="numberEvenCnt">0</div><span class="evenColor">(<span id="numberEvenPer">0</span>%)</span></td>
			<td align="center"><div class="sp-data_under" id="numberUnderCnt">0</div><span class="oddColor">(<span id="numberUnderPer">0</span>%)</span></td>
			<td align="center"><div class="sp-data_over" id="numberOverCnt">0</div><span class="evenColor">(<span id="numberOverPer">0</span>%)</span></td>
			<td align="center"><div class="bigBox" id="numberBigCnt">0</div><span class="evenColor">(<span id="numberBigPer">0</span>%)</span></td>
			<td align="center"><div class="middleBox" id="numberMiddleCnt">0</div><span class="middleColor">(<span id="numberMiddlePer">0</span>%)</span></td>
			<td align="center"><div class="smallBox" id="numberSmallCnt">0</div><span class="oddColor">(<span id="numberSmallPer">0</span>%)</span></td>
		</tr>
		<tr>
			<td height="30" align="center"><span class="oddText" id="powerballOddRow">0</span>연속</td>
			<td align="center"><span class="evenText" id="powerballEvenRow">0</span>연속</td>
			<td align="center"><span class="oddText" id="powerballUnderRow">0</span>연속</td>
			<td align="center"><span class="evenText" id="powerballOverRow">0</span>연속</td>
			<td align="center"><span class="oddText" id="numberOddRow">0</span>연속</td>
			<td align="center"><span class="evenText" id="numberEvenRow">0</span>연속</td>
			<td align="center"><span class="oddText" id="numberUnderRow">0</span>연속</td>
			<td align="center"><span class="evenText" id="numberOverRow">0</span>연속</td>
			<td align="center"><span class="evenText" id="numberBigRow">0</span>연속</td>
			<td align="center"><span class="middleText" id="numberMiddleRow">0</span>연속</td>
			<td align="center"><span class="oddText" id="numberSmallRow">0</span>연속</td>
		</tr>
	</table>

	<table width="100%" border="0" id="patternBox" style="margin-top:10px;">
		<colgroup>
			<col width="20%"/><col width="20%"/><col width="20%"/><col width="20%"/><col width="20%"/>
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
			<col width="20%"/><col width="20%"/><col width="20%"/><col width="20%"/><col width="20%"/>
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
			<col width="16%"/><col width="6%"/><col width="8%"/><col width="9%"/><col width="7%"/><col width="7%"/>
			<col width="12%"/><col width="5%"/><col width="9%"/><col width="9%"/><col width="7%"/><col width="7%"/>
		</colgroup>
		<tr>
			<th height="30" colspan="12" class="title" style="position:relative;">회차별 분석 데이터 (최근)<span style="position:absolute;top:6px;right:10px;color:#969696;" class="siteLink">copyright <a href="/?referer=dayLogBtn" target="_blank" class="titleCopy">powerballgame.co.kr</a></span></th>
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
		<tbody class="content"></tbody>
	</table>

	<div class="displayNone center" id="pageDiv" pageVal="0" round="<?= max(0, (int)($next_round ?? 1) - 1) ?>"><img src="/images/loading2.gif" width="50" height="50" alt=""></div>
	<div class="moreBox"><a href="#" onclick="moreClick();return false;">더보기</a></div>

	<script id="tmpl_dayLog" type="text/x-jquery-tmpl">
		{{each(i,item) content}}
		{{if item.reasonType}}
		<tr class="${trClass}">
			<td height="40" align="center"><span class="numberText">누적 ${round}회</span> ( <span class="numberText">일 ${todayRound}회</span> )</td>
			<td align="center" class="numberText">${time}</td>
			<td align="center" class="numberText" colspan="10">${reasonMessage}</td>
		</tr>
		{{else}}
		<tr class="${trClass}">
			<td height="40" align="center"><span class="numberText">누적 ${round}회</span> ( <span class="numberText">일 ${todayRound}회</span> )<br/><a href="https://www.kaiascan.io/ko/block/${blockNumber}?tabId=blockTransactions" target="_blank" class="numberText">${blockNumber}</a> / <a href="#" onclick="windowOpen('/?view=pbgCheck&query=${blockNumber}/${blockHashKey}','memo',640,620,'no');return false;" target="_blank" class="numberText">${blockHashKey}</a></td>
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

</body>
</html>
