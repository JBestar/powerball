<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<title>공지 등록 완료</title>
	<?php $local = rtrim(site_furl(''), '/'); ?>
	<link rel="stylesheet" href="<?php echo $local; ?>/css/common_logged.css?v=<?php echo time(); ?>" type="text/css"/>
	<style>
		body { color:#000; }
		.wrap { padding:14px; }
		.msg { border:1px solid #d5d5d5; background:#fff; padding:12px; border-radius:10px; }
		.title { font-size:16px; font-weight:bold; margin-bottom:8px; }
		.small { color:#666; font-size:12px; line-height:1.5; }
		.btn { display:inline-block; margin-top:12px; background:#127CCB; color:#fff; font-weight:bold; border:1px solid #0e609c; padding:8px 14px; cursor:pointer; border-radius:4px; text-decoration:none; }
	</style>
</head>
<body>
	<div class="wrap">
		<div class="msg">
			<div class="title">등록이 완료되었습니다.</div>
			<div class="small">메인 화면을 갱신해 상단 공지에 반영합니다.</div>
			<div style="text-align:right;">
				<a class="btn" href="#" onclick="window.close(); return false;">닫기</a>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		try {
			if (window.opener && !window.opener.closed) {
				window.opener.location.reload();
			}
		} catch (e) {}
		setTimeout(function () {
			try { window.close(); } catch (e2) {}
		}, 400);
	</script>
</body>
</html>
