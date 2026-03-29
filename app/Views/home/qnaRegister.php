<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<title>1:1문의사항 글쓰기</title>
	<?php $local = rtrim(site_furl(''), '/'); ?>
	<link rel="stylesheet" href="<?php echo $local; ?>/css/common_logged.css?v=<?php echo time(); ?>" type="text/css"/>
	<style>
		body { color:#000; }
		.defaultTable { width:100%; border-collapse:collapse; table-layout:fixed; }
		.defaultTable td, .defaultTable th { border:1px solid #d5d5d5; padding:6px; }
		.input { width:100%; border:1px solid #949494; padding:6px; box-sizing:border-box; }
		textarea.input { min-height:220px; resize:vertical; }
		.btn { display:inline-block; background:#127CCB; color:#fff; font-weight:bold; border:1px solid #0e609c; padding:8px 14px; cursor:pointer; }
		.freeRegisterWrap {
			background: linear-gradient(180deg, #ffffff 0%, #f6fbff 100%);
			border: 1px solid #d5d5d5;
			border-radius: 10px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.05);
			padding: 14px;
		}
		.freeTitleBar {
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
		.input::placeholder, textarea.input::placeholder { color:#888; }
		.formRow td { border: 0 none; padding: 0; }
		.formBox { border:1px solid #d5d5d5; border-radius: 8px; padding: 10px; background:#fff; }
	</style>
</head>
<body>
	<div class="freeRegisterWrap">
		<div class="freeTitleBar">
			<div>1:1문의사항 글쓰기</div>
			<div class="hint">제목과 내용을 입력 후 등록하세요.</div>
		</div>
		<form method="post" action="<?= site_furl('/?view=qnaRegister') ?>">
			<table class="defaultTable">
				<tr class="formRow">
					<td>
						<div class="formBox">
							<input type="text" name="title" class="input" maxlength="200" placeholder="제목" />
						</div>
					</td>
				</tr>
				<tr class="formRow">
					<td>
						<div class="formBox" style="margin-top:10px;">
							<textarea name="content" class="input" maxlength="50000" placeholder="내용"></textarea>
						</div>
					</td>
				</tr>
			</table>

			<div style="margin-top:12px; text-align:right;">
				<button type="submit" class="btn">등록</button>
			</div>
		</form>
	</div>
</body>
</html>
