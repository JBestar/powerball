/**
 * 탭/창 포커스·가시성 관측용 로그 (기본 OFF)
 * 켜기: URL ?focusdbg=1 (또는 ?a=1&focusdbg=1) | localStorage/sessionStorage PAGE_FOCUS_DEBUG=1 | window.PAGE_FOCUS_DEBUG=true
 * 콘솔 필터: page-focus
 * 두 번째 이상 파라미터는 & 로: ?focusdbg=1&hubdbg=1 (O) / ?focusdbg=1?hubdbg=1 (X, hub 쪽과 혼동 방지)
 */
(function () {
	'use strict';

	var recent = [];
	var MAX = 48;

	/** & 또는 ? 로만 구분된 키=값 (오타 ? 두 개도 focusdbg=1 은 인정) */
	function queryHasPair(search, key, val) {
		var s = search || '';
		if (s.charAt(0) === '?') {
			s = s.slice(1);
		}
		var parts = s.split(/[&?]/);
		for (var i = 0; i < parts.length; i++) {
			var p = parts[i];
			var eq = p.indexOf('=');
			if (eq < 0) {
				continue;
			}
			try {
				var k = decodeURIComponent(p.slice(0, eq));
				var v = decodeURIComponent(p.slice(eq + 1) || '');
				if (k === key && v === val) {
					return true;
				}
			} catch (e) {}
		}
		return false;
	}

	function enabled() {
		try {
			if (window.PAGE_FOCUS_DEBUG === true) {
				return true;
			}
			if (typeof localStorage !== 'undefined' && localStorage.getItem('PAGE_FOCUS_DEBUG') === '1') {
				return true;
			}
			if (typeof sessionStorage !== 'undefined' && sessionStorage.getItem('PAGE_FOCUS_DEBUG') === '1') {
				return true;
			}
			var q = window.location && window.location.search ? window.location.search : '';
			if (queryHasPair(q, 'focusdbg', '1')) {
				return true;
			}
		} catch (e) {}
		return false;
	}

	function snapshot() {
		var s = {
			ms: typeof performance !== 'undefined' && performance.now ? Math.round(performance.now()) : null,
			hidden: typeof document !== 'undefined' ? document.hidden : null,
			visibilityState: typeof document !== 'undefined' && document.visibilityState ? document.visibilityState : null,
			hasFocus: typeof document !== 'undefined' && typeof document.hasFocus === 'function' ? document.hasFocus() : null
		};
		try {
			var f = document.getElementById('miniViewFrame');
			if (f && f.contentWindow && f.contentWindow.document) {
				var d = f.contentWindow.document;
				s.miniViewFrame = {
					hidden: d.hidden,
					visibilityState: d.visibilityState,
					canSyncBurst: typeof f.contentWindow.scheduleMiniViewSyncBurst === 'function'
				};
			}
		} catch (e) {
			s.miniViewFrame = { err: 'no access (cross-origin or unloaded)' };
		}
		try {
			var mf = document.getElementById('mainFrame');
			if (mf && mf.contentWindow && mf.contentWindow.document) {
				var md = mf.contentWindow.document;
				s.mainFrame = { hidden: md.hidden, visibilityState: md.visibilityState };
			}
		} catch (e2) {
			s.mainFrame = { err: String(e2.message || e2) };
		}
		return s;
	}

	function pushRecent(entry) {
		recent.push(entry);
		if (recent.length > MAX) {
			recent.shift();
		}
	}

	function logEvent(evName, extra) {
		if (!enabled()) {
			return;
		}
		var entry = {
			ev: evName,
			at: new Date().toISOString(),
			extra: extra || null,
			snap: snapshot()
		};
		pushRecent(entry);
		if (typeof console !== 'undefined' && console.log) {
			console.log('[page-focus]', evName, entry.snap, extra || '');
		}
	}

	/** 앱 코드에서 호출: 포커스와 무관한 동기화 지점 표시 */
	window.pageFocusDebugNotify = function (tag, detail) {
		if (!enabled()) {
			return;
		}
		if (typeof console !== 'undefined' && console.log) {
			console.log('[page-focus] notify', tag, detail != null ? detail : '', snapshot());
		}
		pushRecent({ ev: 'notify:' + String(tag), at: new Date().toISOString(), extra: detail, snap: snapshot() });
	};

	window.__pageFocusDebug = {
		enabled: enabled,
		snapshot: snapshot,
		recent: function () {
			return recent.slice();
		},
		clear: function () {
			recent.length = 0;
		}
	};

	if (!enabled()) {
		return;
	}

	if (typeof console !== 'undefined' && console.info) {
		console.info('[page-focus] 활성: 콘솔 필터 "page-focus" | 끄기: URL에서 focusdbg 제거 또는 sessionStorage.removeItem("PAGE_FOCUS_DEBUG")');
	}

	document.addEventListener(
		'visibilitychange',
		function () {
			logEvent('visibilitychange', { nowHidden: document.hidden });
		},
		false
	);

	window.addEventListener(
		'focus',
		function () {
			logEvent('window.focus');
		},
		false
	);
	window.addEventListener(
		'blur',
		function () {
			logEvent('window.blur');
		},
		false
	);

	window.addEventListener(
		'pageshow',
		function (ev) {
			logEvent('pageshow', { persisted: !!(ev && ev.persisted) });
		},
		false
	);
	window.addEventListener(
		'pagehide',
		function (ev) {
			logEvent('pagehide', { persisted: !!(ev && ev.persisted) });
		},
		false
	);
})();
