<?php
/**
 * mainFrame 전용 — 선배님 출석체크 (?view=attendance)
 */
$local = $local ?? rtrim(site_furl(''), '/');
$cssVer = $cssVer ?? '1';
$simg = $simg ?? '';
$postUrl = $postUrl ?? site_furl('frame/attendance');
$curMonth = $curMonth ?? date('Y-m');
$calendarYear = (int) ($calendarYear ?? (int) date('Y'));
$calendarMonth = (int) ($calendarMonth ?? (int) date('n'));
$page = (int) ($page ?? 1);
$totalPages = max(1, (int) ($totalPages ?? 1));
$list = is_array($list ?? null) ? $list : [];
$isLogin = $isLogin ?? false;
$alreadyToday = $alreadyToday ?? false;
$attendedYmd = is_array($attendedYmd ?? null) ? $attendedYmd : [];
$commentNo = (int) ($commentNo ?? 1);
$commentPreset = (string) ($commentPreset ?? '');

$buildAttUrl = static function (array $extra) use ($curMonth, $page) {
    $q = array_merge([
        'curMonth' => $curMonth,
        'page'     => $page,
    ], $extra);
    $q = array_filter($q, static function ($v) {
        return $v !== null && $v !== '';
    });

    return site_furl('frame/attendance') . (count($q) ? '?' . http_build_query($q) : '');
};

$dtMonth = new \DateTimeImmutable(sprintf('%04d-%02d-01', $calendarYear, $calendarMonth));
$prevMonth = $dtMonth->modify('-1 month')->format('Y-m');
$nextMonth = $dtMonth->modify('+1 month')->format('Y-m');
$firstDow = (int) $dtMonth->format('w');
$daysInMonth = (int) $dtMonth->format('t');

$cells = [];
for ($i = 0; $i < $firstDow; $i++) {
    $cells[] = null;
}
for ($d = 1; $d <= $daysInMonth; $d++) {
    $cells[] = $d;
}
while (count($cells) % 7 !== 0) {
    $cells[] = null;
}
$weeks = array_chunk($cells, 7);

$pgBlock = 10;
$pgStart = $totalPages > 0 ? (int) (floor(($page - 1) / $pgBlock) * $pgBlock) + 1 : 1;
$pgEnd = $totalPages > 0 ? min($pgStart + $pgBlock - 1, $totalPages) : 1;
$pgShowNext = $totalPages > 0 && $pgEnd < $totalPages;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= esc($site_title ?? '출석체크') ?></title>
	<link rel="stylesheet" href="<?= esc($local) ?>/css/common.css?v=<?= esc((string) $cssVer) ?>" type="text/css" />
	<link rel="stylesheet" href="<?= esc($local) ?>/css/jquery.qtip.min.css" type="text/css" />
	<link rel="stylesheet" href="<?= esc($local) ?>/css/jquery-ui.css?v=<?= esc((string) $cssVer) ?>" type="text/css" />
	<script type="text/javascript" src="<?= esc($local) ?>/js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="<?= esc($local) ?>/js/jquery-ui.js"></script>
	<script type="text/javascript" src="<?= esc($local) ?>/js/jquery.qtip.min.js"></script>
	<script type="text/javascript" src="<?= esc($local) ?>/js/default.js?v=<?= esc((string) $cssVer) ?>"></script>
	<script type="text/javascript">
	//<![CDATA[
	$(document).ready(function(){
		$('body').on('click','.choice',function(){
			$('.choice').css('background-color','#fff');
			$('.choice').css('border-color','#BEE0FA');
			$(this).css('background-color','#BEE0FA');
			$(this).css('border-color','#127CCB');
			$('#selectNumber').val($(this).text());
		});

		$('body').on('mouseover','.submit',function(){
			$(this).css('background-color','#127CCB');
			$(this).css('border-color','#0D568C');
			$(this).css('color','#fff');
		});

		$('body').on('mouseleave','.submit',function(){
			$(this).css('background-color','#BEE0FA');
			$(this).css('border-color','#BEE0FA');
			$(this).css('color','#000');
		});

		$('body').on('click','.submit',function(){
			var fn = document.forms.attendanceForm;
			if(!fn || !fn.selectNumber.value)
			{
				alert('사다리 숫자를 선택해주세요.');
				return false;
			}
			else if(!fn.comment.value)
			{
				alert('출석 코멘트를 입력해주세요.');
				fn.comment.focus();
				return false;
			}
			else
			{
				var params = $('#attendanceForm').serialize();
				$.ajax({
					url: <?= json_encode($postUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
					type:'POST',
					data:params,
					dataType:'json',
					success:function(data)
					{
						if(data.state == 'success')
						{
							$('.contentArea').hide();
							$('#ladderResult').html('<img src="<?= esc($simg) ?>/images/ladder'+data.selectNumber+'-'+data.number+'.gif" alt="">');
							setTimeout(function(){
								if(data.ladderResult == 'win')
								{
									alert('축하합니다. 당첨되어 [랜덤아이템상자]가 지급되었습니다. 아이템 메뉴에서 확인하세요!');
								}
								else
								{
									alert('꽝입니다. 다음에 이용해주세요~');
								}
								location.reload();
							},3000);
						}
						else
						{
							alert(data.msg);
						}
					}
				});
			}
		});
	});

	$(document).ready(function(){
		try{ top.initAd(); } catch(e){}
		setTimeout(function(){ heightResize(); },500);
		$('[title!=""]').qtip({
			position:{ my:'top center', at:'bottom center' },
			style:{ classes:'tooltip_dark' }
		});
	});

	$(window).resize(function(){ heightResize(); });
	//]]>
	</script>
</head>
<body>

<div class="attendanceBox">
	<div class="titleBox">
		출석체크 <span>- 매일 매일 출석체크하고 랜덤아이템상자 받자!</span>
	</div>

	<div class="topBox">
		<div class="calendarBox">
			<div class="control">
				<a href="<?= esc($buildAttUrl(['curMonth' => $prevMonth, 'page' => 1])) ?>"><img src="<?= esc($local) ?>/images/btn_month-prev.gif" width="18" height="18" alt="이전달"></a>
				<span class="day"><?= (int) $calendarYear ?> . <?= sprintf('%02d', $calendarMonth) ?></span>
				<a href="<?= esc($buildAttUrl(['curMonth' => $nextMonth, 'page' => 1])) ?>"><img src="<?= esc($local) ?>/images/btn_month-next.gif" width="18" height="18" alt="다음달"></a>
			</div>
			<div class="body">
				<table class="calendar">
					<colgroup>
						<col width="50"><col width="50"><col width="50"><col width="50"><col width="50"><col width="50"><col width="50">
					</colgroup>
					<thead>
						<tr>
							<th class="sun">일</th><th>월</th><th>화</th><th>수</th><th>목</th><th>금</th><th class="sat">토</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($weeks as $week) : ?>
						<tr>
						<?php foreach ($week as $col => $day) : ?>
							<?php if ($day === null) : ?>
								<td style="background-color:#fcfcfc;"></td>
							<?php else :
								$ymd = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day);
								$isToday = ($ymd === date('Y-m-d'));
								$tdClass = $isToday ? 'today' : '';
								$spanClass = 'day';
								if ($col === 0) {
									$spanClass .= ' sun';
								}
								if ($col === 6) {
									$spanClass .= ' sat';
								}
								?>
								<td class="<?= esc($tdClass) ?>">
									<span class="<?= esc($spanClass) ?>"><?= (int) $day ?></span>
									<?php if (isset($attendedYmd[$ymd])) : ?>
										<img src="<?= esc($local) ?>/images/realtime_bt.gif" width="14" height="14" alt="" style="vertical-align:middle;margin-left:4px;">
									<?php endif; ?>
								</td>
							<?php endif; ?>
						<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="ladderBox">
			<div class="choiceBox">
				<div class="choiceNumber" style="left:40px;">1</div>
				<div class="choiceNumber" style="left:210px;">2</div>
				<div class="choiceNumber" style="left:380px;">3</div>
			</div>
			<div class="ladderContent">
				<div id="ladderResult"></div>
			</div>

			<?php if ($isLogin && ! $alreadyToday) : ?>
			<div class="contentArea">
				<form name="attendanceForm" id="attendanceForm" method="post" action="<?= esc($postUrl) ?>">
					<input type="hidden" name="view" value="action">
					<input type="hidden" name="action" value="attendance">
					<input type="hidden" name="actionType" value="insert">
					<input type="hidden" name="selectNumber" id="selectNumber" value="">
					<input type="hidden" name="commentNo" value="<?= (int) $commentNo ?>">

					<ul>
						<li class="choice">1</li>
						<li class="choice">2</li>
						<li class="choice">3</li>
					</ul>
					<div class="text">출석 코멘트</div>
					<div class="inputBox"><input type="text" name="comment" class="input" value="<?= esc($commentPreset) ?>" readonly></div>
					<div class="submit">출석체크</div>
				</form>
			</div>
			<?php elseif ($isLogin && $alreadyToday) : ?>
			<div class="contentArea">
				<div class="text" style="padding:24px 8px;text-align:center;line-height:1.6;">오늘 출석을 완료했습니다.<br>내일 다시 도전해 주세요.</div>
			</div>
			<?php else : ?>
			<div class="contentArea">
				<div class="text" style="padding:24px 8px;text-align:center;line-height:1.6;">로그인 후 출석체크를 이용할 수 있습니다.</div>
			</div>
			<?php endif; ?>

			<div class="choiceBox">
				<div class="choiceNumber win" style="left:40px;">당첨</div>
				<div class="choiceNumber" style="left:210px;">꽝</div>
				<div class="choiceNumber" style="left:380px;">꽝</div>
			</div>
		</div>
	</div>

	<div class="listBox" style="clear:both;">
		<table class="table">
			<colgroup>
				<col width="50"><col width="50"><col width="80"><col width="150"><col><col width="100">
			</colgroup>
			<tbody>
			<tr>
				<th>번호</th>
				<th>결과</th>
				<th>개근</th>
				<th>닉네임</th>
				<th>코멘트</th>
				<th>출석시간</th>
			</tr>
			<?php if ($list === []) : ?>
			<tr><td colspan="6" class="txt" style="text-align:center;">출석 기록이 없습니다.</td></tr>
			<?php else : ?>
				<?php foreach ($list as $row) :
					$fid = (int) ($row->att_id ?? 0);
					$isWin = (int) ($row->is_win ?? 0) === 1;
					$trClass = $isWin ? 'bgYellow' : '';
					$nick = (string) ($row->mb_nickname ?? '');
					$classGifId = member_class_gif_id_for_display((string) ($row->mb_color ?? ''), (int) ($row->mb_fid ?? 0));
					$fullComment = (string) ($row->comment ?? '');
					$shortComment = $fullComment;
					if (mb_strlen($shortComment) > 40) {
						$shortComment = mb_substr($shortComment, 0, 40) . '...';
					}
					$ts = strtotime((string) ($row->created_at ?? '')) ?: 0;
					$timeShow = $ts ? date('y-m-d H:i', $ts) : '';
					$streak = (int) ($row->streak_days ?? 0);
					?>
				<tr class="<?= esc($trClass) ?>">
					<td class="number"><?= $fid ?></td>
					<td class="result"><?= $isWin ? '당첨' : '꽝' ?></td>
					<td class="number"><?= $streak ?>일</td>
					<td class="nick"><img src="<?= esc($local) ?>/images/class/<?= esc($classGifId) ?>.gif" width="23" height="23" alt=""> <?= esc($nick) ?></td>
					<td class="txt" title="<?= esc($fullComment) ?>"><?= esc($shortComment) ?></td>
					<td class="number"><?= esc($timeShow) ?></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<div class="pagingBox">
			<div style="height:25px;">
			<?php for ($p = $pgStart; $p <= $pgEnd; $p++) : ?>
				<?php if ($p === $page) : ?>
					<a href="<?= esc($buildAttUrl(['page' => $p])) ?>" class="on pageLink"><?= $p ?></a>
				<?php else : ?>
					<a href="<?= esc($buildAttUrl(['page' => $p])) ?>" class="pageLink" rel="<?= $p ?>"><?= $p ?></a>
				<?php endif; ?>
			<?php endfor; ?>
			<?php if ($pgShowNext) : ?>
				<a href="<?= esc($buildAttUrl(['page' => $pgEnd + 1])) ?>" class="pageLink" rel="<?= $pgEnd + 1 ?>">&gt;</a>
				<a href="<?= esc($buildAttUrl(['page' => $totalPages])) ?>" class="pageLink" rel="<?= $totalPages ?>">&gt;&gt;</a>
			<?php endif; ?>
			</div>
		</div>
	</div>
</div>

</body>
</html>
