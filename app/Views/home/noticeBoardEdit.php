<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<title>공지 수정</title>
	<?php $local = rtrim(site_furl(''), '/'); ?>
	<link rel="stylesheet" href="<?php echo $local; ?>/css/common_logged.css?v=<?php echo time(); ?>" type="text/css"/>
	<style>
		body { color:#000; }
		.defaultTable { width:100%; border-collapse:collapse; table-layout:fixed; }
		.defaultTable td, .defaultTable th { border:1px solid #d5d5d5; padding:6px; }
		.input { width:100%; border:1px solid #949494; padding:6px; box-sizing:border-box; }
		textarea.input { min-height:220px; resize:vertical; }
		.btn { display:inline-block; background:#127CCB; color:#fff; font-weight:bold; border:1px solid #0e609c; padding:8px 14px; cursor:pointer; }
		.wrapBox {
			background: linear-gradient(180deg, #ffffff 0%, #f6fbff 100%);
			border: 1px solid #d5d5d5;
			border-radius: 10px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.05);
			padding: 14px;
		}
		.titleBar {
			display:flex;
			align-items:center;
			justify-content:space-between;
			margin-bottom: 12px;
			padding: 10px 12px;
			border-radius: 8px;
			background: #127CCB;
			border: 1px solid #0e609c;
			color:#fff;
			font-weight:bold;
			font-size:13px;
		}
		.hint { color:#d9ecff; font-weight:normal; font-size:12px; }
		.formRow td { border: 0 none; padding: 0; }
		.formBox { border:1px solid #d5d5d5; border-radius: 8px; padding: 10px; background:#fff; }
		.flashMsg {
			border:1px solid #f1b8ba;
			background:#fff3f4;
			color:#b0111a;
			padding:8px 10px;
			margin-bottom:10px;
			border-radius:6px;
			font-size:12px;
		}
	</style>
</head>
<body>
	<div class="wrapBox">
		<div class="titleBar">
			<div>공지 수정</div>
			<div class="hint">공지 형태·제목·내용 수정</div>
		</div>
		<?php $flashMsg = session('message'); ?>
		<?php if (!empty($flashMsg)): ?>
			<div class="flashMsg"><?= esc($flashMsg) ?></div>
		<?php endif; ?>
		<?php
			$curType = \App\Models\Notice_Model::normalizeBoardType($post->notice_type ?? \App\Models\Notice_Model::TYPE_NOTICE);
			if (!in_array($curType, \App\Models\Notice_Model::siteBoardTypes(), true)) {
				$curType = \App\Models\Notice_Model::TYPE_NOTICE;
			}
		?>
		<form method="post" action="<?= site_furl('/?view=noticeBoardEdit&id=' . (int) ($post->notice_fid ?? 0)) ?>">
			<table class="defaultTable">
				<tr class="formRow">
					<td>
						<div class="formBox">
							<select name="notice_type" class="input" style="max-width:200px;">
								<option value="<?= esc(\App\Models\Notice_Model::TYPE_NOTICE) ?>"<?= $curType === \App\Models\Notice_Model::TYPE_NOTICE ? ' selected' : '' ?>>공지</option>
								<option value="<?= esc(\App\Models\Notice_Model::TYPE_GUIDE) ?>"<?= $curType === \App\Models\Notice_Model::TYPE_GUIDE ? ' selected' : '' ?>>안내</option>
							</select>
						</div>
					</td>
				</tr>
				<tr class="formRow">
					<td>
						<div class="formBox" style="margin-top:10px;">
							<input type="text" name="title" class="input" maxlength="200" placeholder="제목" value="<?= esc($post->notice_title ?? '') ?>" />
						</div>
					</td>
				</tr>
				<tr class="formRow">
					<td>
						<div class="formBox" style="margin-top:10px;">
							<textarea name="content" class="input" maxlength="100000" placeholder="내용"><?= esc($post->notice_content ?? '') ?></textarea>
						</div>
					</td>
				</tr>
			</table>
			<div style="margin-top:12px; text-align:right;">
				<button type="submit" class="btn">수정</button>
			</div>
		</form>
	</div>
</body>
</html>
