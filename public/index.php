<?php

/**
 * ---------------------------------------------------------------
 * 파워볼 프로젝트 진입점 (Entry Point)
 * ---------------------------------------------------------------
 * 보안을 위해 이 파일은 /public 폴더 안에 위치하며,
 * 실제 핵심 로직은 상위 폴더인 /app 및 /system에 존재합니다.
 */

// 1. PHP 최소 버전 체크 (선배님 스타일의 안정성 검사)
$minPHPVersion = '7.4';
if (phpversion() < $minPHPVersion) {
    die("PHP 버전을 {$minPHPVersion} 이상으로 업그레이드해주세요. 현재 버전: " . phpversion());
}
unset($minPHPVersion);

// 2. 프론트 컨트롤러 경로 상수 정의
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// 3. 설정 파일(Paths.php) 위치 지정 
// 현재 위치가 /public 이므로 한 단계 위(..)의 app/Config를 바라봅니다.
$pathsPath = realpath(FCPATH . '../app/Config/Paths.php');

// 4. 환경 설정 (.env) 로드 (선배님의 보안 핵심)
// 프로젝트 루트에 있는 .env 파일을 읽어 환경변수로 등록합니다.
$basePath = realpath(FCPATH . '../') . DIRECTORY_SEPARATOR;
if (file_exists($basePath . '.env')) {
    $env = parse_ini_file($basePath . '.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// 5. 프레임워크 부트스트랩 가동
chdir(__DIR__); // 현재 실행 디렉토리를 public으로 고정

if (! file_exists($pathsPath)) {
    die("시스템 설정 파일을 찾을 수 없습니다. 경로를 확인하세요: {$pathsPath}");
}

require $pathsPath;
$paths = new Config\Paths();

// 6. 어플리케이션 엔진 구동
$app = require rtrim($paths->systemDirectory, '/ ') . '/bootstrap.php';

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 */
$app->run();
