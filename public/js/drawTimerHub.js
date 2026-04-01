/**
 * 메인(부모) 창 단일 모듈: ajaxChatTimer를 5초마다 1회 호출하고,
 * 그 사이 1초 간격으로 remainSeconds만 로컬 감소시킨 뒤
 * chatFrame / mainFrame 에 postMessage로 브로드캐스트한다.
 * 디버그 로그: window.CI_APP_DEBUG === true 일 때만 (CI_ENVIRONMENT=development).
 */
(function () {
    'use strict';

    function dbg() {
        if (!window.CI_APP_DEBUG) {
            return;
        }
        if (typeof console !== 'undefined' && console.log) {
            var a = ['[drawTimerHub]'];
            for (var i = 0; i < arguments.length; i++) {
                a.push(arguments[i]);
            }
            console.log.apply(console, a);
        }
    }

    function dbgWarn() {
        if (!window.CI_APP_DEBUG) {
            return;
        }
        if (typeof console !== 'undefined' && console.warn) {
            var a = ['[drawTimerHub]'];
            for (var j = 0; j < arguments.length; j++) {
                a.push(arguments[j]);
            }
            console.warn.apply(console, a);
        }
    }

    function normalizeHubBase(s) {
        if (typeof s !== 'string') {
            s = '';
        }
        s = s.replace(/\/?$/, '/');
        if (s !== '' && s !== '/' && s !== '//') {
            return s;
        }
        var path = window.location.pathname || '/';
        if (path.length > 1 && path.charAt(path.length - 1) === '/') {
            return (window.location.origin || '') + path;
        }
        var dir = path.replace(/\/[^/]+$/, '/');
        if (dir === '//') {
            dir = '/';
        }
        if (dir.charAt(0) !== '/') {
            dir = '/' + dir;
        }
        return (window.location.origin || '') + dir;
    }

    /** PHP/프록시가 http URL 을 넘겨도, 페이지가 HTTPS 면 AJAX 는 https 로 (Mixed Content 방지). */
    function upgradeBaseToMatchPageHttps(baseUrl) {
        if (!baseUrl || typeof baseUrl !== 'string') {
            return baseUrl;
        }
        try {
            if (window.location.protocol === 'https:' && /^http:\/\//i.test(baseUrl)) {
                return baseUrl.replace(/^http:\/\//i, 'https://');
            }
        } catch (e) {}
        return baseUrl;
    }

    var base = normalizeHubBase(typeof window.DRAW_TIMER_HUB_BASE === 'string' ? window.DRAW_TIMER_HUB_BASE : '');
    base = upgradeBaseToMatchPageHttps(base);
    if (!base || base === '/') {
        dbgWarn('중단: POST URL 을 결정할 수 없음 (DRAW_TIMER_HUB_BASE=', window.DRAW_TIMER_HUB_BASE, ')');
        return;
    }

    dbg('시작', 'base=', base, 'origin=', window.location.origin);

    var remainSeconds = 0;
    var timeRound = 1;
    var connectUserCnt = 0;
    var hubReady = false;
    var tickSkipNotReady = 0;
    var tickHiddenLog = 0;
    var broadcastCount = 0;
    var fetchCount = 0;

    function buildPayload() {
        return {
            type: 'drawTimerHub',
            remainSeconds: Math.max(0, remainSeconds | 0),
            timeRound: timeRound | 0,
            connectUserCnt: connectUserCnt | 0
        };
    }

    function broadcast() {
        var payload = buildPayload();
        // targetOrigin 은 *수신 창*의 origin 이어야 함. 부모 origin 을 넣으면 www/비-www·리다이렉트 시
        // 자식과 불일치해 브라우저가 메시지를 버림 → '*' + 수신 측에서 source 검증.
        var ids = ['chatFrame', 'mainFrame'];
        for (var i = 0; i < ids.length; i++) {
            var id = ids[i];
            var el = document.getElementById(id);
            if (!el) {
                if (broadcastCount % 30 === 0) {
                    dbgWarn('iframe 없음: #' + id);
                }
                continue;
            }
            if (!el.contentWindow) {
                dbgWarn('contentWindow 없음: #' + id);
                continue;
            }
            try {
                el.contentWindow.postMessage(payload, '*');
            } catch (e) {
                dbgWarn('postMessage 실패 #' + id, e && e.message ? e.message : e);
            }
        }
        broadcastCount++;
        if (broadcastCount <= 3 || broadcastCount % 60 === 0) {
            dbg('broadcast #' + broadcastCount, 'sec=', payload.remainSeconds, 'round=', payload.timeRound);
        }
    }

    function fetchFromServer() {
        fetchCount++;
        if (typeof jQuery === 'undefined') {
            dbgWarn('jQuery 없음 (fetch #' + fetchCount + ')');
            return;
        }
        dbg('ajaxChatTimer 요청 (fetch #' + fetchCount + ')');
        var jqXHR = jQuery.post(
            base,
            { view: 'action', action: 'ajaxChatTimer' },
            function (resp) {
                if (resp == null) {
                    dbgWarn('응답 null');
                    return;
                }
                if (typeof resp === 'string') {
                    dbgWarn('JSON 아님(문자열)', resp.slice ? resp.slice(0, 200) : resp);
                    return;
                }
                if (resp.state !== 'success') {
                    dbgWarn('state !== success', resp);
                    return;
                }
                var sec = parseInt(resp.remain_seconds, 10);
                remainSeconds = isNaN(sec) ? 0 : sec;
                var tr = parseInt(resp.time_round, 10);
                timeRound = isNaN(tr) ? 1 : tr;
                var cu = parseInt(resp.connectUserCnt, 10);
                connectUserCnt = isNaN(cu) ? 0 : cu;
                hubReady = true;
                tickSkipNotReady = 0;
                dbg('서버 동기화 OK', 'remainSeconds=', remainSeconds, 'timeRound=', timeRound);
                broadcast();
            },
            'json'
        );
        if (jqXHR && typeof jqXHR.fail === 'function') {
            jqXHR.fail(function (xhr, status, err) {
                dbgWarn('ajax 실패', {
                    status: xhr && xhr.status,
                    statusText: status,
                    err: err,
                    responseHead: xhr && xhr.responseText ? String(xhr.responseText).slice(0, 300) : ''
                });
            });
        }
    }

    function tick() {
        if (!hubReady) {
            tickSkipNotReady++;
            if (tickSkipNotReady === 1 || tickSkipNotReady % 10 === 0) {
                dbgWarn('tick 대기 hubReady=false n=', tickSkipNotReady);
            }
            return;
        }
        if (document.hidden) {
            tickHiddenLog++;
            if (tickHiddenLog === 1 || tickHiddenLog % 30 === 0) {
                dbg('tick 생략 document.hidden n=', tickHiddenLog);
            }
            return;
        }
        if (remainSeconds > 0) {
            remainSeconds--;
        }
        broadcast();
    }

    function start() {
        fetchFromServer();
        setInterval(fetchFromServer, 5000);
        setInterval(tick, 1000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                dbg('visibilitychange → 재동기화');
                fetchFromServer();
            }
        });
        window.addEventListener('focus', function () {
            dbg('focus → 재동기화');
            fetchFromServer();
        });
    }

    window.addEventListener(
        'message',
        function (ev) {
            var d = ev.data;
            if (!d || d.type !== 'drawTimerHubRequestSync') {
                return;
            }
            var ch = document.getElementById('chatFrame');
            var mf = document.getElementById('mainFrame');
            var ok = false;
            try {
                if (ch && ev.source === ch.contentWindow) {
                    ok = true;
                }
                if (mf && ev.source === mf.contentWindow) {
                    ok = true;
                }
            } catch (e) {}
            if (!ok) {
                dbgWarn('drawTimerHubRequestSync 출처 불일치');
                return;
            }
            dbg('자식 재동기화 요청');
            fetchFromServer();
        },
        false
    );

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
