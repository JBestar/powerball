<?php

/**
 * 5분 정각(XX:00, XX:05, … XX:55) 추첨을 서버에서 상시 실행하기 위한 CLI 전용 스크립트.
 *
 * 사용 (프로젝트 루트에서):
 *   php draw_daemon.php
 *
 * 한 번만 실행(테스트·수동 트리거):
 *   php draw_daemon.php once
 *
 * 중지: Ctrl+C 또는 프로세스 종료.
 * Windows에서 백그라운드: 작업 스케줄러로 로그온 시 시작하거나, nssm 등으로 서비스 등록.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$runOnce = in_array('once', $argv ?? [], true);

// CLI에서 CodeIgniter 부트스트랩·Constants(BASEURL)·HTTP 서비스 초기화에 필요
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['HTTPS']       = $_SERVER['HTTPS'] ?? 'off';
$_SERVER['SCRIPT_NAME']  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);

$pathsPath = realpath(__DIR__ . '/app/Config/Paths.php');
if ($pathsPath === false) {
    fwrite(STDERR, "Paths.php not found.\n");
    exit(1);
}

require $pathsPath;
$paths     = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '/ ') . '/bootstrap.php';
require $bootstrap;

use App\Models\PowerballDraw_Model;

function draw_daemon_log(string $msg): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL);
}

function draw_daemon_run_slot(): void
{
    $now = time();
    $dt  = (new \DateTimeImmutable('@' . $now))->setTimezone(new \DateTimeZone('Asia/Seoul'));
    $minute = (int) $dt->format('i');
    if (($minute % 5) !== 0) {
        draw_daemon_log('Not a 5-min slot (KST); skip.');

        return;
    }

    try {
        $model = new PowerballDraw_Model();
        $draw  = $model->getOrGenerate($now);

        if ($draw && isset($draw->round)) {
            draw_daemon_log('Draw done: round=' . $draw->round . ', drawn_at=' . ($draw->drawn_at ?? ''));
        } else {
            draw_daemon_log('No new draw (slot already has result).');
        }
    } catch (\Throwable $e) {
        draw_daemon_log('ERROR: ' . $e->getMessage());
    }
}

if ($runOnce) {
    draw_daemon_run_slot();
    exit(0);
}

draw_daemon_log('Daemon started; waiting for 5-minute boundaries (Ctrl+C to stop).');

while (true) {
    $sec = time() % 300;
    if ($sec !== 0) {
        sleep(300 - $sec);
    }

    draw_daemon_run_slot();

    // 동일 분 내 재실행 방지
    if (time() % 300 === 0) {
        sleep(2);
    }
}
