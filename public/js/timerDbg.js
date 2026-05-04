/**
 * #timeRound / drawTimer 허브 원인 분석용 — 기본 OFF, 콘솔 필터 [timerdbg]
 *
 * 활성화: URL ?timerdbg=1 또는 &timerdbg=1
 * 유지: sessionStorage TIMER_DBG=1 자동 설정(같은 탭)
 * 또는 localStorage/sessionStorage 에 TIMER_DBG=1 | window.TIMER_DBG = true
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
})(typeof window !== 'undefined' ? window : this);
