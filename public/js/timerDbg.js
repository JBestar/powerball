/**
 * #timeRound / drawTimer 허브 원인 분석용 — 기본 OFF, 콘솔 필터 [timerdbg]
 *
 * 활성화: URL ?timerdbg=1 또는 &timerdbg=1
 * 유지: sessionStorage + 같은 origin iframe 과 공유되는 localStorage 에 TIMER_DBG=1
 * (브라우저는 iframe 마다 sessionStorage 를 분리하므로 timerdbg=1 만으로는 자식 프레임에서 꺼진 것처럼 보일 수 있음)
 * 또는 수동으로 localStorage/sessionStorage TIMER_DBG=1 | window.TIMER_DBG = true
 */
(function (global) {
    'use strict';

    function urlHasTimerDbg() {
        var q = global.location && global.location.search ? global.location.search : '';
        return /(?:^\?|&)timerdbg=1(?:&|$)/.test(q);
    }

    function timerDbgEnabled() {
        try {
            if (global.TIMER_DBG === true) {
                return true;
            }
            if (typeof global.localStorage !== 'undefined' && global.localStorage.getItem('TIMER_DBG') === '1') {
                return true;
            }
            if (typeof global.sessionStorage !== 'undefined' && global.sessionStorage.getItem('TIMER_DBG') === '1') {
                return true;
            }
            if (urlHasTimerDbg()) {
                try {
                    global.sessionStorage.setItem('TIMER_DBG', '1');
                    /* iframe 은 별도 sessionStorage — dayLog/chat 등 자식에서 로그 켜기 위해 localStorage 동기화 */
                    global.localStorage.setItem('TIMER_DBG', '1');
                } catch (e) {}
                return true;
            }
        } catch (e2) {}
        return false;
    }

    function timerDbgLog() {
        if (!timerDbgEnabled()) {
            return;
        }
        if (typeof global.console !== 'undefined' && global.console.log) {
            var a = ['[timerdbg]'];
            for (var i = 0; i < arguments.length; i++) {
                a.push(arguments[i]);
            }
            global.console.log.apply(global.console, a);
        }
    }

    function timerDbgWarn() {
        if (!timerDbgEnabled()) {
            return;
        }
        if (typeof global.console !== 'undefined' && global.console.warn) {
            var b = ['[timerdbg]'];
            for (var j = 0; j < arguments.length; j++) {
                b.push(arguments[j]);
            }
            global.console.warn.apply(global.console, b);
        }
    }

    global.timerDbgEnabled = timerDbgEnabled;
    global.timerDbgLog = timerDbgLog;
    global.timerDbgWarn = timerDbgWarn;

    /* DevTools 필터 `timerdbg` 로 잡히는 1회 확인용(이벤트 전 없이도 보임) */
    try {
        if (timerDbgEnabled() && typeof global.console !== 'undefined' && global.console.info) {
            var ctx = 'top';
            try {
                ctx = global.self !== global.top ? 'iframe' : 'top';
            } catch (eTop) {
                ctx = 'iframe?';
            }
            global.console.info('[timerdbg] probe active', ctx, String(global.location.pathname || '') + String(global.location.search || ''));
        }
    } catch (eProbe) {}
})(typeof window !== 'undefined' ? window : this);
