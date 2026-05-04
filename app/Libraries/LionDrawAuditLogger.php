<?php

namespace App\Libraries;

/**
 * 라이온 조건 큐 ↔ 실제 추첨 감사 로그.
 * Config\Logger threshold와 무관하게 전용 파일에 한 줄 JSON으로 기록합니다.
 *
 * @see writable/logs/lion_draw_audit_Y-m-d.log
 */
class LionDrawAuditLogger
{
    public static function write(string $event, array $data = []): void
    {
        $row = array_merge([
            'ts'    => date('Y-m-d H:i:s'),
            'event' => $event,
        ], $data);

        $dir = WRITEPATH . 'logs';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'lion_draw_audit_' . date('Y-m-d') . '.log';
        $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
