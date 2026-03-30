<?php
/**
 * mainFrame 전용 — 선배님 고객센터(bo_v + bo_list) 구조
 * 스타일: public/css/default.css, style.css, bbs.css (+ 필요 시 본 문서 인라인)
 */
$post = $post ?? null;
$currentId = $post ? (int) ($post->notice_fid ?? 0) : 0;
$is_notice_admin = $is_notice_admin ?? false;
$listColspan = $is_notice_admin ? 6 : 5;
$buildCcUrl = static function (array $extra) use ($currentId, $page, $sfl, $stx) {
    $q = array_merge([
        'id' => $currentId > 0 ? $currentId : null,
        'page' => $page,
        'sfl' => $sfl,
        'stx' => $stx,
    ], $extra);
    $q = array_filter($q, static function ($v) {
        return $v !== null && $v !== '';
    });
    return site_furl('frame/customerCenter') . (count($q) ? '?' . http_build_query($q) : '');
};
?>
<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= esc($site_title ?? '고객센터') ?></title>
	<link rel="stylesheet" href="<?= esc($local) ?>/css/default.css?v=<?= esc((string) $cssVer) ?>" type="text/css" />
	<link rel="stylesheet" href="<?= esc($local) ?>/css/style.css?v=<?= esc((string) $cssVer) ?>" type="text/css" />
	<link rel="stylesheet" href="<?= esc($local) ?>/css/bbs.css?v=<?= esc((string) $cssVer) ?>" type="text/css" />
	<style>
		/* common.css의 #box-ct 범위 밖에서도 선배님 레이아웃에 가깝게 */
		#bo_v .viewinfo { position: relative; }
		a.btn_b01 {
			display: inline-block;
			padding: 6px 12px;
			font-size: 11px;
			border-radius: 2px;
			margin-right: 4px;
		}
		#bo_v_top { margin: 10px 0; overflow: hidden; }
		#bo_v_top .bo_v_nb { float: left; }
		#bo_v_top .bo_v_nb li { display: inline-block; margin-right: 4px; }
		#bo_v_top .bo_v_com { float: right; }
		#bo_v_top .bo_v_com li { display: inline-block; margin-left: 4px; }
		.sound_only { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
		/* 공지 본문: textarea 줄바꿈 유지 (HTML 공백 규칙으로 한 줄로 붙는 문제 방지) */
		#bo_v_con .cc_notice_body { white-space: pre-wrap; word-break: break-word; }
		#bo_list .tbl_head01 .td_mng {
			width: 92px;
			min-width: 92px;
			max-width: 92px;
			text-align: right;
			vertical-align: middle;
			white-space: nowrap;
			padding: 4px 6px !important;
			box-sizing: border-box;
		}
		#bo_list .tbl_head01 .td_mng .noticeActInner {
			display: flex;
			flex-direction: row;
			justify-content: flex-end;
			align-items: center;
			gap: 4px;
		}
		#bo_list .tbl_head01 .td_mng a.btnNoticeSm {
			display: inline-block;
			padding: 2px 6px;
			font-size: 10px;
			line-height: 14px;
			border-radius: 3px;
			font-weight: bold;
			text-decoration: none;
			box-sizing: border-box;
			min-width: 34px;
			text-align: center;
		}
		#bo_list .tbl_head01 .td_mng a.btnNoticeSm.edit { border: 1px solid #0e609c; color: #0e609c; background: #fff; }
		#bo_list .tbl_head01 .td_mng a.btnNoticeSm.del { border: 1px solid #c11a20; color: #c11a20; background: #fff; }
		#bo_list .tbl_head01 th.mng { width: 92px; text-align: center; }
	</style>
</head>
<body>

<script src="<?= esc($local) ?>/js/jquery-1.11.2.min.js"></script>
<?php if ($post): ?>
<script src="<?= esc($local) ?>/js/viewimageresize.js"></script>
<?php endif; ?>

<?php if ($post): ?>
<article id="bo_v">
	<div class="viewinfo">
		<div class="thumb"><img src="<?= esc(site_furl('images/profile.png')) ?>" alt=""></div>
		<div class="title"><h1><?= esc($post->notice_title ?? '') ?></h1></div>
		<div class="info">
			<?php
				$grade = (int) ($post->mb_grade ?? 30);
				$classGif = site_furl('images/class/M' . $grade . '.gif');
			?>
			<img src="<?= esc($classGif) ?>" alt="" onerror="this.style.display='none'">
			<a href="#" class="uname" onclick="return false;"><span class="sv_member"><?= esc($post->mb_nickname ?? '운영자') ?></span></a>
			<span class="bar">|</span>
			<?= esc($post->notice_time_create ? date('Y-m-d H:i', strtotime($post->notice_time_create)) : '') ?>
			<span class="bar">|</span>
			조회 <?= esc(number_format((int) ($post->notice_hit ?? 0))) ?>
		</div>
	</div>

	<section id="bo_v_atc">
		<div id="bo_v_con">
			<?php if (!empty($post->notice_content)): ?>
				<div class="cc_notice_body"><?= esc($post->notice_content) ?></div>
			<?php else: ?>
				<p>내용이 없습니다.</p>
			<?php endif; ?>
		</div>

		<div class="banner">
			<div style="text-align:center;"><script>document.write("<d"+"iv id='mobonDivBanner_543148'><iframe name='ifrad' id='mobonIframe_543148' src='//www.mediacategory.com/servlet/adBanner?from="+escape(document.referrer)+"&s=543148&igb=60&iwh=728_90&cntad=1&cntsr=1' frameborder='0' scrolling='no' style='height:90px; width:728px;'></iframe></div>");</script></div>
		</div>
	</section>
</article>

<div id="commentReply" style="display:none;">
	<form name="fviewcomment" method="post" autocomplete="off" onsubmit="return false;">
		<div class="comment">
			<div class="textarea">
				<textarea id="wr_content2" name="wr_content" placeholder="댓글 작성을 위해 로그인 해주세요."></textarea>
			</div>
			<button type="button" class="btn_submit" title="등록" onclick="alert('로그인 후 이용가능합니다.');">등록</button>
		</div>
	</form>
</div>

<script>
var char_min = parseInt(0, 10);
var char_max = parseInt(0, 10);
var save_before = '';
var save_html = document.getElementById('commentReply') ? document.getElementById('commentReply').innerHTML : '';

function comment_box(comment_id, work)
{
	var el_id = comment_id ? ('reply_' + comment_id) : '';
	if (save_before !== el_id) {
		save_before = el_id;
	}
}

comment_box('', 'c');
</script>

<div id="bo_v_top">
	<ul class="bo_v_nb">
		<?php if ($prevId): ?>
			<li><a href="<?= esc($buildCcUrl(['id' => $prevId])) ?>" class="btn_b01">이전글</a></li>
		<?php endif; ?>
		<?php if ($nextId): ?>
			<li><a href="<?= esc($buildCcUrl(['id' => $nextId])) ?>" class="btn_b01">다음글</a></li>
		<?php endif; ?>
	</ul>
	<ul class="bo_v_com">
		<li><a href="<?= esc($buildCcUrl(['id' => null, 'page' => $page])) ?>" class="btn_b01">목록</a></li>
	</ul>
</div>
<?php endif; ?>

<div id="bo_list" style="width:100%">
	<nav id="bo_cate">
		<h2 class="sound_only">고객센터 카테고리</h2>
		<ul id="bo_cate_ul">
			<li><a href="<?= esc($buildCcUrl(['page' => 1, 'id' => null])) ?>" id="bo_cate_on">전체</a></li>
			<li><a href="<?= esc($buildCcUrl(['page' => 1, 'id' => null, 'stx' => '', 'sfl' => ''])) ?>">공지사항</a></li>
			<li><a href="<?= esc($buildCcUrl(['page' => 1, 'id' => null])) ?>">이벤트</a></li>
			<li><a href="<?= esc(site_furl('/?view=prison')) ?>" target="_top">영창</a></li>
		</ul>
	</nav>

	<div class="tbl_head01 tbl_wrap">
		<table>
			<caption class="sound_only">고객센터 목록</caption>
			<colgroup>
				<col style="width:48px">
				<col>
				<col style="width:130px">
				<col style="width:64px">
				<col style="width:52px">
				<?php if ($is_notice_admin): ?>
				<col style="width:92px">
				<?php endif; ?>
			</colgroup>
			<thead>
			<tr>
				<th scope="col">번호</th>
				<th scope="col">제목</th>
				<th scope="col">글쓴이</th>
				<th scope="col">날짜</th>
				<th scope="col">조회</th>
				<?php if ($is_notice_admin): ?>
				<th scope="col" class="mng">관리</th>
				<?php endif; ?>
			</tr>
			</thead>
			<tbody>
			<?php if (empty($boardsPage)): ?>
				<tr><td colspan="<?= (int) $listColspan ?>" style="text-align:center;padding:20px;">등록된 글이 없습니다.</td></tr>
			<?php else: ?>
				<?php foreach ($boardsPage as $idx => $row) :
					$fid = (int) ($row->notice_fid ?? 0);
					$rowDate = !empty($row->notice_time_create) ? date('m-d', strtotime($row->notice_time_create)) : '';
					$gradeR = (int) ($row->mb_grade ?? 30);
					$classGifR = site_furl('images/class/M' . $gradeR . '.gif');
					$typeLabel = \App\Models\Notice_Model::isGuideCategory((string) ($row->notice_type ?? '')) ? '안내' : '공지';
				?>
				<tr class="bo_notice">
					<td class="td_num">
						<img src="<?= esc(site_furl('images/ico_notice.png')) ?>" style="vertical-align:top;" alt="">
					</td>
					<td class="td_subject">
						<a href="<?= esc($buildCcUrl(['id' => $fid, 'page' => 1])) ?>" class="bo_cate_link"><?= esc($typeLabel) ?></a>
						<a href="<?= esc($buildCcUrl(['id' => $fid, 'page' => 1])) ?>" title="<?= esc($row->notice_title ?? '') ?>">
							<?= esc(mb_strlen($row->notice_title ?? '') > 28 ? mb_substr($row->notice_title ?? '', 0, 28) . '…' : ($row->notice_title ?? '')) ?>
						</a>
					</td>
					<td class="td_name sv_use">
						<img src="<?= esc($classGifR) ?>" alt="" onerror="this.style.display='none'">
						<?php $nickShow = trim((string) ($row->mb_nickname ?? '')); ?>
						<span class="sv_member"><?= esc($nickShow !== '' ? $nickShow : '운영자') ?></span>
					</td>
					<td class="td_date"><?= esc($rowDate) ?></td>
					<td class="td_num"><?= (int) ($row->notice_hit ?? 0) ?></td>
					<?php if ($is_notice_admin): ?>
					<td class="td_mng">
						<div class="noticeActInner">
							<a href="#" class="btnNoticeSm edit" onclick="window.open('<?= site_furl('/?view=noticeBoardEdit&id=' . $fid) ?>','noticeBoardEdit','width=600,height=700'); return false;">수정</a>
							<a href="<?= esc(site_furl('/?view=noticeBoardDelete&id=' . $fid)) ?>" class="btnNoticeSm del" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
						</div>
					</td>
					<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php
$pgBlock = 5;
$pgStart = $totalPages > 0 ? (int) (floor(($page - 1) / $pgBlock) * $pgBlock) + 1 : 1;
$pgEnd = $totalPages > 0 ? min($pgStart + $pgBlock - 1, $totalPages) : 1;
$pgShowEnd = $totalPages > 0 && $pgEnd < $totalPages;
?>
<nav class="pg_wrap"><span class="pg">
<?php for ($p = $pgStart; $p <= $pgEnd; $p++) : ?>
	<?php if ($p === $page) : ?>
		<span class="sound_only">열린</span><strong class="pg_current"><?= $p ?></strong><span class="sound_only">페이지</span>
	<?php else : ?>
		<a href="<?= esc($buildCcUrl(['page' => $p, 'id' => null])) ?>" class="pg_page"><?= $p ?><span class="sound_only">페이지</span></a>
	<?php endif; ?>
<?php endfor; ?>
<?php if ($pgShowEnd) : ?>
	<a href="<?= esc($buildCcUrl(['page' => $totalPages, 'id' => null])) ?>" class="pg_page pg_end">&gt;&gt;</a>
<?php endif; ?>
</span></nav>

<fieldset id="bo_sch">
	<legend>게시물 검색</legend>

	<form name="fsearch" method="get" action="<?= esc(site_furl('frame/customerCenter')) ?>">
		<input type="hidden" name="bo_table" value="custom">
		<input type="hidden" name="sca" value="">
		<input type="hidden" name="sop" value="and">
		<label for="sfl" class="sound_only">검색대상</label>
		<select name="sfl" id="sfl">
			<option value="wr_subject"<?= ($sfl === '' || $sfl === 'wr_subject') ? ' selected' : '' ?>>제목</option>
			<option value="wr_content"<?= $sfl === 'wr_content' ? ' selected' : '' ?>>내용</option>
			<option value="wr_subject||wr_content"<?= $sfl === 'wr_subject||wr_content' ? ' selected' : '' ?>>제목+내용</option>
			<option value="mb_id,1"<?= $sfl === 'mb_id,1' ? ' selected' : '' ?>>회원아이디</option>
			<option value="mb_id,0"<?= $sfl === 'mb_id,0' ? ' selected' : '' ?>>회원아이디(코)</option>
			<option value="wr_name,1"<?= $sfl === 'wr_name,1' ? ' selected' : '' ?>>글쓴이</option>
			<option value="wr_name,0"<?= $sfl === 'wr_name,0' ? ' selected' : '' ?>>글쓴이(코)</option>
		</select>
		<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
		<input type="text" name="stx" value="<?= esc($stx) ?>" id="stx" class="frm_input" size="15">
		<input type="image" src="<?= esc(site_furl('images/btn_search_off.png')) ?>" value="검색" class="btn_search" alt="검색">
	</form>
</fieldset>

<?php if ($post): ?>
<script>
$(function() {
	$("#bo_v_atc").viewimageresize();
});
</script>
<?php endif; ?>

</body>
</html>
