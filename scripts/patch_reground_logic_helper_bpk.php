<?php
/**
 * reground_LT Logic_Helper.php — bpk/bpk2 URL을 우리 서버로 전환 (한 번 실행).
 * CRLF 유지.
 */
$path = 'D:/xampp/htdocs/xservice/reground_LT/helpers/Logic_Helper.php';
if (! is_readable($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}
$c = file_get_contents($path);

$inject = <<<'PHP'
/** PBG reground(bpk/bpk2) — 우리 서버 베이스(끝 슬래시 없음). */
if (! defined('PBG_REGROUND_BASE')) {
	define('PBG_REGROUND_BASE', 'https://pbg-2.com');
}
/** powerball .env REGROUND_COMPAT_KEY 와 같으면 ?key= 로 전달. */
if (! defined('PBG_REGROUND_COMPAT_KEY')) {
	define('PBG_REGROUND_COMPAT_KEY', '');
}

PHP;

if (strpos($c, 'PBG_REGROUND_BASE') !== false) {
    echo "Already patched (PBG_REGROUND_BASE present).\n";
    exit(0);
}

$needle = "<?php\r\n\t\r\n\t  //자료기지 접속";
if (strpos($c, $needle) === false) {
    $needle = "<?php\n\t\n\t  //자료기지 접속";
}
if (strpos($c, $needle) === false) {
    fwrite(STDERR, "Opening block not found (expected <?php + tab line + //자료기지).\n");
    exit(1);
}
$c = str_replace($needle, "<?php\r\n\r\n" . $inject . "\t\r\n\t  //자료기지 접속", $c);

$c = str_replace(
    "\t\t\$url = \"https://bepick.net/live/result/pbgpowerball\";\r\n\t\t\$url.= \"?_=\".\$milliSec;\r\n\t\t\r\n\t\t\$header =  [\r\n            'Host: bepick.net',",
    "\t\t\$url = rtrim(PBG_REGROUND_BASE, '/').\"/live/result/pbgpowerball\";\r\n\t\t\$url.= \"?_=\".\$milliSec;\r\n\t\tif (PBG_REGROUND_COMPAT_KEY !== '') {\r\n\t\t\t\$url .= \"&key=\".urlencode(PBG_REGROUND_COMPAT_KEY);\r\n\t\t}\r\n\t\t\$host = parse_url(PBG_REGROUND_BASE, PHP_URL_HOST);\r\n\t\tif (! is_string(\$host) || \$host === '') {\r\n\t\t\t\$host = 'localhost';\r\n\t\t}\r\n\t\t\r\n\t\t\$header =  [\r\n            'Host: '.\$host,",
    $c
);

$c = str_replace(
    "\t\t\$url = \"https://bepick.net/api/get_pattern/pbgpowerball/daily/fd1/20/\";\r\n\t\t\r\n\t\t\$arrRoundInfo = getLastRoundInfo(\$roundMin);\r\n\t\t\r\n\t\t\$url.= str_replace(\"-\", \"\", \$arrRoundInfo['round_date']);\t \r\n\t\t\$url.= \"?_=\".\$milliSec;\r\n\t\t\r\n\t\t\$header =  [\r\n            'Host: bepick.net',",
    "\t\t\$url = rtrim(PBG_REGROUND_BASE, '/').\"/api/get_pattern/pbgpowerball/daily/fd1/20/\";\r\n\t\t\r\n\t\t\$arrRoundInfo = getLastRoundInfo(\$roundMin);\r\n\t\t\r\n\t\t\$url.= str_replace(\"-\", \"\", \$arrRoundInfo['round_date']);\t \r\n\t\t\$url.= \"?_=\".\$milliSec;\r\n\t\tif (PBG_REGROUND_COMPAT_KEY !== '') {\r\n\t\t\t\$url .= \"&key=\".urlencode(PBG_REGROUND_COMPAT_KEY);\r\n\t\t}\r\n\t\t\$host = parse_url(PBG_REGROUND_BASE, PHP_URL_HOST);\r\n\t\tif (! is_string(\$host) || \$host === '') {\r\n\t\t\t\$host = 'localhost';\r\n\t\t}\r\n\t\t\r\n\t\t\$header =  [\r\n            'Host: '.\$host,",
    $c
);

if (strpos($c, 'https://bepick.net/live/result/pbgpowerball') !== false) {
    fwrite(STDERR, "Replace failed for curlPbg_bpk block.\n");
    exit(1);
}

file_put_contents($path, $c);
echo "OK: patched {$path}\n";
