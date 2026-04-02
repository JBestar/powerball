<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?= esc($site_title ?? '패턴별 분석') ?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
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
	<script type="text/javascript" src="<?php echo $local; ?>/js/patternAnalyze.js?v=<?php echo time(); ?>"></script>

	<script type="text/javascript">
		//<![CDATA[
		var actionBaseUrl = '<?= rtrim(esc(site_furl("")), "/") ?>/';
		window.ACTION_BASE_URL = actionBaseUrl;

		// iframe(mainFrame)에서 하단 테두리 잘림 방지용 높이 보정
		var PATTERNLOG_MIN_HEIGHT = 500;
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

			var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
			var totalHeight = Math.max(
				PATTERNLOG_MIN_HEIGHT,
				document.body.scrollHeight || document.documentElement.scrollHeight || 0
			);

			var box = document.getElementById('patternLogBox');
			if (box && box.getBoundingClientRect) {
				var rect = box.getBoundingClientRect();
				if (rect && typeof rect.bottom === 'number') totalHeight = Math.max(totalHeight, rect.bottom + scrollTop);
			}

			// 테두리/렌더링 반올림 오차 보정
			totalHeight = Math.ceil(totalHeight) + 6;
			setFrameHeight(totalHeight);
		}

		//]]>
	</script>
</head>
<body onload="">

	<table width="100%" border="0" class="defaultTable">
		<colgroup>
			<col width="25%"/>
			<col width="25%"/>
			<col width="25%"/>
			<col width="25%"/>
		</colgroup>
		<tbody><tr>
			<th class="menu" style="position:relative;"><a href="/?view=dayLog" class="tab1">일자별 분석<div style="position:absolute;top:5px;left:20px;"><img src="/images/realtime_bt.gif" width="37" height="19"></div></a></th>
			<th class="menu"><a href="/?view=latestLog" class="tab2">최근 분석<div style="position:absolute;top:5px;left:20px;"><img src="/images/realtime_bt.gif" width="37" height="19"></div></a></th>
			<th class="menu"><a href="/?view=periodLog" class="tab3">기간별 분석</a></th>
			<th class="menu on"><a href="/?view=patternAnalyze" class="tab5 on">패턴별 분석</a></th>
		</tr></tbody>
	</table>

	<div class="patternSearchBox">
		<ul class="tabMenu">
			<li class="on" rel="powerballOddEven">파워볼 홀/짝</li>
			<li rel="powerballUnderOver">파워볼 언더/오버</li>
			<li rel="numberOddEven">숫자합 홀/짝</li>
			<li rel="numberUnderOver">숫자합 언더/오버</li>
			<li rel="numberPeriod">숫자합 대/중/소</li>
		</ul>

		<div style="width:75px;position:absolute;top:1px;right:50px;line-height:36px;">
			<input type="text" name="patternCnt" value="10" id="patternCnt" class="roundCntSelect"
				   style="width:40px;height:34px;font-size:16px;font-weight:bold;text-align:center;" />
		</div>

		<a href="#" onclick="patternSearch();return false;" style="float:right;" class="sp-search rollover"></a>
	</div>

	<div style="height:30px;line-height:30px;text-align:center;background-color:#F1F1F1;color:#000;border:1px solid #D5D5D5;">
		검색할 패턴 선택 (<span class="red">클릭시 이미지 토글됩니다.</span>)
	</div>

	<ul id="patternSet"></ul>

	<table width="100%" id="patternLogBox" class="patternBox" style="margin-top:5px;">
		<colgroup>
			<col width="89">
			<col>
			<col width="91">
		</colgroup>
		<tbody class="content">
		</tbody>
	</table>

	<div class="displayNone center" id="pageDiv" pageval="0" offset="0" searchpattern="">
		<img src="/images/loading2.gif" width="50" height="50" alt="">
	</div>
	<div class="displayNone moreBox"><a href="#" onclick="moreClick();return false;">더보기</a></div>

	<!-- tmpl -->
	<script id="tmpl_patternLog" type="text/x-jquery-tmpl">
		{{if titleYN == 'Y'}}
		<tr>
			<th height="30" colspan="3" class="title">패턴별 분석 데이터</th>
		</tr>
		<tr class="subTitle">
			<th height="20">날짜</th>
			<th>패턴</th>
			<th>다음회차 결과</th>
		</tr>
		{{/if}}
		{{if content}}
		<tr>
			<td colspan="3">
				<table class="innerTable">
					{{each(i,item) content}}
					<tr class="${trClass} line">
						<td class="date">${item.date}</td>
						<td>
							<table>
								<tr>
									{{each(i,subItem) subList}}
									<td width="40">
										<table class="bd">
											<tr>
												<td class="patternRound">${subItem.round}</td>
											</tr>
											<tr>
												<td class="patternImg"><div class="${subItem.img}"></div></td>
											</tr>
										</table>
									</td>
									{{if i == 12}}
									</tr><tr>
									{{/if}}
									{{/each}}
								</tr>
							</table>
						</td>
						<td class="nextResult">
							<table width="100%" class="bd">
								<tr>
									<td class="patternRound">${nextResult_round}</td>
								</tr>
								<tr>
									<td class="patternImg"><div class="${nextResult_img}"></div></td>
								</tr>
							</table>
						</td>
					</tr>
					{{/each}}
				</table>
			</td>
		</tr>
		{{/if}}
	</script>
	<!-- //tmpl -->

	<script type="text/javascript">
		// 로딩 즉시 높이 계산 + 패턴셋/검색 초기화는 patternAnalyze.js에서 수행
		setTimeout(function(){ if (typeof heightResize === 'function') heightResize(); }, 50);
	</script>
</body>
</html>

