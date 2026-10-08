/**
 * Çark Çevirici – core wheel engine. Vanilla JS, no dependencies besides
 * confetti.js. One CarkWheel instance per ".cark-wheel" element on the page.
 *
 * Fairness: the winner is chosen with crypto.getRandomValues() BEFORE the
 * spin animation starts; the animation then eases to land on that
 * pre-chosen segment. The animation never decides the outcome, it only
 * displays it. See /nasil-calisir/ for the full explanation shown to users.
 */
( function ( window, document ) {
	'use strict';

	var I18N = window.CARK_I18N || {};
	var T = function ( key, fallback ) {
		return I18N[ key ] || fallback;
	};

	var THEMES = {
		klasik: [ '#2563eb', '#f59e0b', '#16a34a', '#db2777', '#7c3aed', '#ef4444', '#0ea5e9', '#84cc16' ],
		okyanus: [ '#0ea5e9', '#0369a1', '#14b8a6', '#075985', '#22d3ee', '#0c4a6e', '#38bdf8', '#155e75' ],
		orman: [ '#166534', '#4d7c0f', '#15803d', '#65a30d', '#22c55e', '#3f6212', '#16a34a', '#365314' ],
		gunbatimi: [ '#f97316', '#dc2626', '#f59e0b', '#be123c', '#fb923c', '#9a3412', '#facc15', '#7c2d12' ],
		parti: [ '#db2777', '#7c3aed', '#2563eb', '#f59e0b', '#ef4444', '#16a34a', '#0ea5e9', '#f472b6' ],
		pastel: [ '#a5b4fc', '#fca5a5', '#fde68a', '#86efac', '#fbcfe8', '#99f6e4', '#d8b4fe', '#fdba74' ],
	};

	var MUTE_KEY = 'cark_muted';
	var SAVED_WHEELS_KEY = 'cark_saved_wheels';

	/* ---------------------------------------------------------- *
	 *  Random helpers – crypto based, used for every "who wins".
	 * ---------------------------------------------------------- */

	function cryptoRandom() {
		if ( window.crypto && window.crypto.getRandomValues ) {
			var buf = new Uint32Array( 1 );
			window.crypto.getRandomValues( buf );
			return buf[ 0 ] / 4294967296;
		}
		return Math.random();
	}

	function cryptoInt( maxExclusive ) {
		return Math.floor( cryptoRandom() * maxExclusive );
	}

	function shuffleArray( arr ) {
		var a = arr.slice();
		for ( var i = a.length - 1; i > 0; i-- ) {
			var j = cryptoInt( i + 1 );
			var tmp = a[ i ];
			a[ i ] = a[ j ];
			a[ j ] = tmp;
		}
		return a;
	}

	function easeOutQuint( t ) {
		return 1 - Math.pow( 1 - t, 5 );
	}

	/* ---------------------------------------------------------- *
	 *  Tiny WebAudio synth for tick / win sounds (no audio files).
	 * ---------------------------------------------------------- */

	var AudioCtx = window.AudioContext || window.webkitAudioContext;
	var audioCtx = null;

	function getAudioCtx() {
		if ( ! AudioCtx ) {
			return null;
		}
		if ( ! audioCtx ) {
			audioCtx = new AudioCtx();
		}
		return audioCtx;
	}

	function isMuted() {
		try {
			return localStorage.getItem( MUTE_KEY ) === '1';
		} catch ( e ) {
			return false;
		}
	}

	function setMuted( muted ) {
		try {
			localStorage.setItem( MUTE_KEY, muted ? '1' : '0' );
		} catch ( e ) {}
	}

	function playTone( freq, duration, type, gainValue ) {
		if ( isMuted() ) {
			return;
		}
		var ctx = getAudioCtx();
		if ( ! ctx ) {
			return;
		}
		var osc = ctx.createOscillator();
		var gain = ctx.createGain();
		osc.type = type || 'sine';
		osc.frequency.value = freq;
		gain.gain.value = gainValue || 0.05;
		gain.gain.exponentialRampToValueAtTime( 0.0001, ctx.currentTime + duration );
		osc.connect( gain );
		gain.connect( ctx.destination );
		osc.start();
		osc.stop( ctx.currentTime + duration );
	}

	function playTick() {
		playTone( 900, 0.04, 'square', 0.04 );
	}

	function playWin() {
		playTone( 660, 0.18, 'triangle', 0.08 );
		window.setTimeout( function () {
			playTone( 880, 0.25, 'triangle', 0.08 );
		}, 120 );
	}

	/* ---------------------------------------------------------- *
	 *  localStorage helpers
	 * ---------------------------------------------------------- */

	function storageGet( key, fallback ) {
		try {
			var raw = localStorage.getItem( key );
			return raw ? JSON.parse( raw ) : fallback;
		} catch ( e ) {
			return fallback;
		}
	}

	function storageSet( key, value ) {
		try {
			localStorage.setItem( key, JSON.stringify( value ) );
		} catch ( e ) {}
	}

	/* ---------------------------------------------------------- *
	 *  Base64url encode/decode for the share-URL fragment.
	 * ---------------------------------------------------------- */

	function encodeState( obj ) {
		var json = JSON.stringify( obj );
		var b64 = window.btoa( unescape( encodeURIComponent( json ) ) );
		return b64.replace( /\+/g, '-' ).replace( /\//g, '_' ).replace( /=+$/, '' );
	}

	function decodeState( str ) {
		try {
			var b64 = str.replace( /-/g, '+' ).replace( /_/g, '/' );
			while ( b64.length % 4 ) {
				b64 += '=';
			}
			var json = decodeURIComponent( escape( window.atob( b64 ) ) );
			return JSON.parse( json );
		} catch ( e ) {
			return null;
		}
	}

	/* ---------------------------------------------------------- *
	 *  Entry text <-> textarea parsing (supports "Metin*3" weights)
	 * ---------------------------------------------------------- */

	function parseEntriesText( text ) {
		return text
			.split( /\r\n|\r|\n/ )
			.map( function ( line ) {
				return line.trim();
			} )
			.filter( function ( line ) {
				return line.length > 0;
			} )
			.map( function ( line ) {
				var m = line.match( /^(.*)\*(\d+)$/ );
				if ( m && m[ 1 ].trim() ) {
					return { text: m[ 1 ].trim(), weight: Math.max( 1, parseInt( m[ 2 ], 10 ) ) };
				}
				return { text: line, weight: 1 };
			} );
	}

	function entriesToText( entries, useWeights ) {
		return entries
			.map( function ( e ) {
				return useWeights && e.weight > 1 ? e.text + '*' + e.weight : e.text;
			} )
			.join( '\n' );
	}

	/* ============================================================ *
	 *  CarkWheel
	 * ============================================================ */

	function CarkWheel( root ) {
		this.root = root;
		this.id = root.id;
		var config = {};
		try {
			config = JSON.parse( root.getAttribute( 'data-cark' ) || '{}' );
		} catch ( e ) {}

		this.title = config.title || T( 'spin', 'Çark Çevir' );
		this.type = config.type || 'list';
		this.min = config.min || 1;
		this.max = config.max || 100;
		this.entries = ( config.entries || [] ).map( function ( e ) {
			return { text: e.text, weight: e.weight || 1 };
		} );
		if ( 'range' === this.type && ! this.entries.length ) {
			this.entries = this.buildRangeEntries( this.min, this.max );
		}

		this.settings = {
			theme: config.theme || 'klasik',
			removeWinner: !! config.removeWinner,
			winnersPerSpin: config.winnersPerSpin || 1,
			useWeights: this.entries.some( function ( e ) {
				return e.weight > 1;
			} ),
			duration: config.duration || 6,
			dark: false,
			sound: config.sound !== false,
		};

		this.history = [];
		this.spinning = false;
		this.currentAngle = 0;

		this.loadAutosave();
		this.loadFromShareUrl();

		this.cacheDom();
		this.bindEvents();
		this.syncTextareaFromEntries();
		this.applySettingsToUi();
		this.resizeCanvas();
		this.draw();

		window.addEventListener( 'resize', this.debounce( this.onResize.bind( this ), 150 ) );
	}

	CarkWheel.prototype.buildRangeEntries = function ( min, max ) {
		var out = [];
		for ( var n = min; n <= max; n++ ) {
			out.push( { text: String( n ), weight: 1 } );
		}
		return out;
	};

	CarkWheel.prototype.debounce = function ( fn, wait ) {
		var t;
		return function () {
			var args = arguments;
			var ctx = this;
			window.clearTimeout( t );
			t = window.setTimeout( function () {
				fn.apply( ctx, args );
			}, wait );
		};
	};

	/* ---------------- DOM ---------------- */

	CarkWheel.prototype.cacheDom = function () {
		var q = this.root.querySelector.bind( this.root );
		this.canvas = q( '.cark-canvas' );
		this.ctx = this.canvas.getContext( '2d' );
		this.spinBtn = q( '.cark-spin-btn' );
		this.live = q( '.cark-live' );
		this.textarea = q( '.cark-entries-input' );
		this.entryCount = q( '.cark-entry-count' );
		this.settingsPanel = q( '.cark-settings-panel' );
		this.historyPanel = q( '.cark-history' );
		this.historyList = q( '.cark-history-list' );
		this.importFile = q( '.cark-import-file' );

		this.setDuration = q( '.cark-set-duration' );
		this.setRemoveWinner = q( '.cark-set-remove-winner' );
		this.setWeights = q( '.cark-set-weights' );
		this.setWinners = q( '.cark-set-winners' );
		this.setTheme = q( '.cark-set-theme' );
		this.setDark = q( '.cark-set-dark' );
	};

	CarkWheel.prototype.bindEvents = function () {
		var self = this;

		this.spinBtn.addEventListener( 'click', function () {
			self.spin();
		} );

		this.canvas.addEventListener( 'click', function () {
			self.spin();
		} );
		this.canvas.setAttribute( 'tabindex', '0' );
		this.canvas.setAttribute( 'role', 'button' );
		this.canvas.setAttribute( 'aria-label', T( 'spin', 'ÇEVİR' ) );
		this.canvas.addEventListener( 'keydown', function ( ev ) {
			if ( 'Enter' === ev.key || ' ' === ev.key || 'Spacebar' === ev.key ) {
				ev.preventDefault();
				self.spin();
			}
		} );

		this.root.addEventListener( 'click', function ( ev ) {
			var btn = ev.target.closest( '[data-action]' );
			if ( ! btn || ! self.root.contains( btn ) ) {
				return;
			}
			self.handleAction( btn.getAttribute( 'data-action' ) );
		} );

		if ( this.textarea ) {
			this.textarea.addEventListener( 'input', this.debounce( function () {
				self.entries = parseEntriesText( self.textarea.value );
				self.updateEntryCount();
				self.saveAutosave();
				self.draw();
			}, 200 ) );
		}

		if ( this.importFile ) {
			this.importFile.addEventListener( 'change', function ( ev ) {
				var file = ev.target.files && ev.target.files[ 0 ];
				if ( ! file ) {
					return;
				}
				var reader = new FileReader();
				reader.onload = function () {
					self.textarea.value = String( reader.result );
					self.entries = parseEntriesText( self.textarea.value );
					self.updateEntryCount();
					self.saveAutosave();
					self.draw();
				};
				reader.readAsText( file );
			} );
		}

		[ [ this.setDuration, 'input', function () {
			self.settings.duration = parseFloat( self.setDuration.value );
			self.saveAutosave();
		} ],
		[ this.setRemoveWinner, 'change', function () {
			self.settings.removeWinner = self.setRemoveWinner.checked;
			self.saveAutosave();
		} ],
		[ this.setWeights, 'change', function () {
			self.settings.useWeights = self.setWeights.checked;
			self.syncTextareaFromEntries();
			self.saveAutosave();
		} ],
		[ this.setWinners, 'change', function () {
			self.settings.winnersPerSpin = Math.max( 1, parseInt( self.setWinners.value, 10 ) || 1 );
			self.saveAutosave();
		} ],
		[ this.setTheme, 'change', function () {
			self.settings.theme = self.setTheme.value;
			self.saveAutosave();
			self.draw();
		} ],
		[ this.setDark, 'change', function () {
			self.settings.dark = self.setDark.checked;
			self.root.classList.toggle( 'is-dark', self.settings.dark );
			self.saveAutosave();
			self.draw();
		} ] ].forEach( function ( pair ) {
			if ( pair[ 0 ] ) {
				pair[ 0 ].addEventListener( pair[ 1 ], pair[ 2 ] );
			}
		} );
	};

	CarkWheel.prototype.handleAction = function ( action ) {
		switch ( action ) {
			case 'shuffle':
				this.entries = shuffleArray( this.entries );
				this.syncTextareaFromEntries();
				this.saveAutosave();
				this.draw();
				break;
			case 'sort':
				this.entries = this.entries.slice().sort( function ( a, b ) {
					return a.text.localeCompare( b.text, 'tr' );
				} );
				this.syncTextareaFromEntries();
				this.saveAutosave();
				this.draw();
				break;
			case 'clear':
				this.entries = [];
				this.syncTextareaFromEntries();
				this.saveAutosave();
				this.draw();
				break;
			case 'sample':
				this.entries = [
					{ text: 'Ahmet', weight: 1 },
					{ text: 'Ayşe', weight: 1 },
					{ text: 'Mehmet', weight: 1 },
					{ text: 'Zeynep', weight: 1 },
					{ text: 'Can', weight: 1 },
					{ text: 'Elif', weight: 1 },
				];
				this.syncTextareaFromEntries();
				this.saveAutosave();
				this.draw();
				break;
			case 'fullscreen':
				this.toggleFullscreen();
				break;
			case 'mute':
				setMuted( ! isMuted() );
				this.updateMuteLabel();
				break;
			case 'share':
				this.share();
				break;
			case 'embed':
				this.copyEmbed();
				break;
			case 'my-wheels':
				this.openMyWheels();
				break;
			case 'settings':
				this.toggleSettings();
				break;
			case 'copy-history':
				this.copyHistory();
				break;
			case 'download-history':
				this.downloadHistory();
				break;
			case 'modal-close':
				this.hideModal();
				break;
			case 'modal-spin-again':
				this.hideModal();
				this.spin();
				break;
			case 'modal-remove-winner':
				this.removeLastWinners();
				this.hideModal();
				break;
		}
	};

	CarkWheel.prototype.updateEntryCount = function () {
		if ( this.entryCount ) {
			this.entryCount.textContent = this.entries.length + ' ' + ( 1 === this.entries.length ? 'seçenek' : 'seçenek' );
		}
	};

	CarkWheel.prototype.syncTextareaFromEntries = function () {
		if ( this.textarea ) {
			this.textarea.value = entriesToText( this.entries, this.settings.useWeights );
		}
		this.updateEntryCount();
	};

	CarkWheel.prototype.applySettingsToUi = function () {
		if ( this.setDuration ) this.setDuration.value = this.settings.duration;
		if ( this.setRemoveWinner ) this.setRemoveWinner.checked = this.settings.removeWinner;
		if ( this.setWeights ) this.setWeights.checked = this.settings.useWeights;
		if ( this.setWinners ) this.setWinners.value = this.settings.winnersPerSpin;
		if ( this.setTheme ) this.setTheme.value = this.settings.theme;
		if ( this.setDark ) this.setDark.checked = this.settings.dark;
		this.root.classList.toggle( 'is-dark', this.settings.dark );
		this.updateMuteLabel();
	};

	CarkWheel.prototype.updateMuteLabel = function () {
		var btn = this.root.querySelector( '[data-action="mute"]' );
		if ( btn ) {
			btn.textContent = isMuted() ? 'Sesi Aç' : 'Sesi Kapat';
		}
	};

	CarkWheel.prototype.toggleSettings = function () {
		if ( ! this.settingsPanel ) {
			return;
		}
		var hidden = this.settingsPanel.hasAttribute( 'hidden' );
		if ( hidden ) {
			this.settingsPanel.removeAttribute( 'hidden' );
		} else {
			this.settingsPanel.setAttribute( 'hidden', '' );
		}
		var btn = this.root.querySelector( '[data-action="settings"]' );
		if ( btn ) {
			btn.setAttribute( 'aria-expanded', hidden ? 'true' : 'false' );
		}
	};

	CarkWheel.prototype.toggleFullscreen = function () {
		var self = this;
		var goingFullscreen = ! this.root.classList.contains( 'is-fullscreen' );
		this.root.classList.toggle( 'is-fullscreen', goingFullscreen );

		if ( goingFullscreen && this.root.requestFullscreen ) {
			this.root.requestFullscreen().catch( function () {} );
		} else if ( ! goingFullscreen && document.fullscreenElement && document.exitFullscreen ) {
			document.exitFullscreen().catch( function () {} );
		}
		window.setTimeout( function () {
			self.resizeCanvas();
			self.draw();
		}, 50 );
	};

	/* ---------------- Canvas drawing ---------------- */

	CarkWheel.prototype.resizeCanvas = function () {
		var dpr = Math.min( window.devicePixelRatio || 1, 2 );
		var rect = this.canvas.getBoundingClientRect();
		var size = Math.max( rect.width, 1 );
		this.canvas.width = size * dpr;
		this.canvas.height = size * dpr;
		this.ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		this.size = size;
	};

	CarkWheel.prototype.onResize = function () {
		this.resizeCanvas();
		this.draw();
	};

	CarkWheel.prototype.getColors = function () {
		return THEMES[ this.settings.theme ] || THEMES.klasik;
	};

	CarkWheel.prototype.fitFontSize = function ( ctx, text, maxWidth, baseSize ) {
		var size = baseSize;
		ctx.font = '700 ' + size + 'px -apple-system, Segoe UI, Roboto, sans-serif';
		while ( size > 10 && ctx.measureText( text ).width > maxWidth ) {
			size -= 1;
			ctx.font = '700 ' + size + 'px -apple-system, Segoe UI, Roboto, sans-serif';
		}
		if ( ctx.measureText( text ).width > maxWidth ) {
			while ( text.length > 1 && ctx.measureText( text + '…' ).width > maxWidth ) {
				text = text.slice( 0, -1 );
			}
			text = text + '…';
		}
		return { size: size, text: text };
	};

	CarkWheel.prototype.draw = function () {
		var ctx = this.ctx;
		var size = this.size || this.canvas.getBoundingClientRect().width;
		var cx = size / 2;
		var cy = size / 2;
		var radius = size / 2 - 4;
		var entries = this.entries;

		ctx.clearRect( 0, 0, size, size );

		if ( ! entries.length ) {
			ctx.save();
			ctx.fillStyle = '#e2e4e9';
			ctx.beginPath();
			ctx.arc( cx, cy, radius, 0, Math.PI * 2 );
			ctx.fill();
			ctx.fillStyle = '#5b616e';
			ctx.font = '600 16px sans-serif';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.fillText( T( 'addEntries', 'Çarkı çevirmek için en az iki seçenek ekle.' ), cx, cy, radius * 1.6 );
			ctx.restore();
			return;
		}

		var colors = this.getColors();
		var totalWeight = entries.reduce( function ( sum, e ) {
			return sum + ( this.settings.useWeights ? e.weight : 1 );
		}.bind( this ), 0 );

		var angle = this.currentAngle;
		ctx.save();
		ctx.translate( cx, cy );
		ctx.rotate( angle );

		var start = 0;
		entries.forEach( function ( entry, i ) {
			var weight = this.settings.useWeights ? entry.weight : 1;
			var slice = ( weight / totalWeight ) * Math.PI * 2;
			var end = start + slice;

			ctx.beginPath();
			ctx.moveTo( 0, 0 );
			ctx.arc( 0, 0, radius, start, end );
			ctx.closePath();
			ctx.fillStyle = entry.color || colors[ i % colors.length ];
			ctx.fill();
			ctx.strokeStyle = 'rgba(255,255,255,.55)';
			ctx.lineWidth = 1.5;
			ctx.stroke();

			var mid = start + slice / 2;
			ctx.save();
			ctx.rotate( mid );
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			ctx.fillStyle = this.readableTextColor( entry.color || colors[ i % colors.length ] );
			var maxWidth = Math.max( 24, radius * 0.78 );
			var fitted = this.fitFontSize( ctx, entry.text, maxWidth, Math.max( 12, Math.min( 22, radius / 9 ) ) );
			ctx.font = '700 ' + fitted.size + 'px -apple-system, Segoe UI, Roboto, sans-serif';
			ctx.fillText( fitted.text, radius - 14, 0 );
			ctx.restore();

			start = end;
		}.bind( this ) );

		ctx.restore();
	};

	CarkWheel.prototype.readableTextColor = function ( hex ) {
		if ( ! hex ) {
			return '#ffffff';
		}
		var c = hex.replace( '#', '' );
		var r = parseInt( c.substring( 0, 2 ), 16 );
		var g = parseInt( c.substring( 2, 4 ), 16 );
		var b = parseInt( c.substring( 4, 6 ), 16 );
		var yiq = ( r * 299 + g * 587 + b * 114 ) / 1000;
		return yiq >= 150 ? '#1b1d22' : '#ffffff';
	};

	/* ---------------- Spin logic ---------------- */

	CarkWheel.prototype.pickWinnerIndex = function () {
		var useWeights = this.settings.useWeights;
		var weights = this.entries.map( function ( e ) {
			return useWeights ? e.weight : 1;
		} );
		var total = weights.reduce( function ( a, b ) {
			return a + b;
		}, 0 );
		var r = cryptoRandom() * total;
		var acc = 0;
		for ( var i = 0; i < weights.length; i++ ) {
			acc += weights[ i ];
			if ( r < acc ) {
				return i;
			}
		}
		return weights.length - 1;
	};

	CarkWheel.prototype.spin = function () {
		if ( this.spinning ) {
			return;
		}
		if ( this.entries.length < 2 ) {
			this.live.textContent = T( 'addEntries', 'Çarkı çevirmek için en az iki seçenek ekle.' );
			return;
		}

		var winnersCount = Math.min( this.settings.winnersPerSpin, this.entries.length );
		var winnerIndex = this.pickWinnerIndex();
		var additionalWinners = [];

		if ( winnersCount > 1 ) {
			var pool = this.entries.map( function ( e, i ) {
				return i;
			} ).filter( function ( i ) {
				return i !== winnerIndex;
			} );
			additionalWinners = shuffleArray( pool ).slice( 0, winnersCount - 1 );
		}

		this.spinning = true;
		this.spinBtn.disabled = true;
		this.live.textContent = T( 'spinning', 'Çevriliyor...' );

		var useWeights = this.settings.useWeights;
		var weights = this.entries.map( function ( e ) {
			return useWeights ? e.weight : 1;
		} );
		var total = weights.reduce( function ( a, b ) {
			return a + b;
		}, 0 );
		var start = 0;
		for ( var i = 0; i < winnerIndex; i++ ) {
			start += ( weights[ i ] / total ) * Math.PI * 2;
		}
		var slice = ( weights[ winnerIndex ] / total ) * Math.PI * 2;
		var targetMid = start + slice / 2;

		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var duration = ( reduceMotion ? Math.min( 1.2, this.settings.duration ) : this.settings.duration ) * 1000;

		var extraTurns = 4 + cryptoInt( 3 );
		var fromAngle = this.currentAngle % ( Math.PI * 2 );
		var pointerAngle = -Math.PI / 2;
		var toAngle = fromAngle + extraTurns * Math.PI * 2 + ( pointerAngle - targetMid - fromAngle );

		var self = this;
		var startTime = null;
		var lastTickSlice = -1;
		var sliceCount = this.entries.length;

		function frame( ts ) {
			if ( ! startTime ) {
				startTime = ts;
			}
			var elapsed = ts - startTime;
			var t = Math.min( 1, elapsed / duration );
			var eased = easeOutQuint( t );
			self.currentAngle = fromAngle + ( toAngle - fromAngle ) * eased;

			var normalized = ( ( self.currentAngle % ( Math.PI * 2 ) ) + Math.PI * 2 ) % ( Math.PI * 2 );
			var sliceIndex = Math.floor( ( normalized / ( Math.PI * 2 ) ) * sliceCount );
			if ( sliceIndex !== lastTickSlice ) {
				lastTickSlice = sliceIndex;
				if ( self.settings.sound ) {
					playTick();
				}
			}

			self.draw();

			if ( t < 1 ) {
				requestAnimationFrame( frame );
			} else {
				self.spinning = false;
				self.spinBtn.disabled = false;
				self.onSpinComplete( winnerIndex, additionalWinners );
			}
		}

		requestAnimationFrame( frame );
	};

	CarkWheel.prototype.onSpinComplete = function ( winnerIndex, additionalWinnerIndexes ) {
		var winners = [ this.entries[ winnerIndex ] ].concat(
			additionalWinnerIndexes.map( function ( i ) {
				return this.entries[ i ];
			}.bind( this ) )
		);
		var names = winners.map( function ( w ) {
			return w.text;
		} );

		this.live.textContent = T( 'winner', 'Kazanan' ) + ': ' + names.join( ', ' );

		if ( this.settings.sound ) {
			playWin();
		}

		this.lastWinnerIndexes = [ winnerIndex ].concat( additionalWinnerIndexes );

		var self = this;
		names.forEach( function ( name ) {
			self.history.unshift( { text: name, at: new Date() } );
		} );
		this.renderHistory();

		this.showModal( names );

		if ( this.settings.removeWinner ) {
			this.saveAutosave();
		}
	};

	CarkWheel.prototype.removeLastWinners = function () {
		if ( ! this.lastWinnerIndexes || ! this.lastWinnerIndexes.length ) {
			return;
		}
		var toRemove = this.lastWinnerIndexes.slice().sort( function ( a, b ) {
			return b - a;
		} );
		var self = this;
		toRemove.forEach( function ( idx ) {
			self.entries.splice( idx, 1 );
		} );
		this.lastWinnerIndexes = [];
		this.syncTextareaFromEntries();
		this.saveAutosave();
		this.draw();
	};

	/* ---------------- Winner modal ---------------- */

	CarkWheel.prototype.showModal = function ( names ) {
		this.hideModal();

		var backdrop = document.createElement( 'div' );
		backdrop.className = 'cark-modal-backdrop';

		var modal = document.createElement( 'div' );
		modal.className = 'cark-modal';
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );
		modal.setAttribute( 'aria-label', T( 'winner', 'Kazanan' ) );

		var confettiCanvas = document.createElement( 'canvas' );
		confettiCanvas.className = 'cark-modal-confetti';

		var label = document.createElement( 'p' );
		label.className = 'cark-modal-label';
		label.textContent = T( 'winner', 'Kazanan' );

		var winnerEl = document.createElement( 'p' );
		winnerEl.className = 'cark-modal-winner';
		winnerEl.textContent = names.join( ', ' );

		var actions = document.createElement( 'div' );
		actions.className = 'cark-modal-actions';

		var again = document.createElement( 'button' );
		again.type = 'button';
		again.className = 'cark-btn cark-btn-primary';
		again.setAttribute( 'data-action', 'modal-spin-again' );
		again.textContent = T( 'spinAgain', 'Tekrar Çevir' );

		var remove = document.createElement( 'button' );
		remove.type = 'button';
		remove.className = 'cark-btn';
		remove.setAttribute( 'data-action', 'modal-remove-winner' );
		remove.textContent = T( 'removeWinner', 'Kazananı Kaldır' );

		var close = document.createElement( 'button' );
		close.type = 'button';
		close.className = 'cark-btn';
		close.setAttribute( 'data-action', 'modal-close' );
		close.textContent = T( 'close', 'Kapat' );

		actions.appendChild( again );
		actions.appendChild( remove );
		actions.appendChild( close );

		modal.appendChild( confettiCanvas );
		modal.appendChild( label );
		modal.appendChild( winnerEl );
		modal.appendChild( actions );
		backdrop.appendChild( modal );
		document.body.appendChild( backdrop );

		this.modalEl = backdrop;

		backdrop.addEventListener( 'click', function ( ev ) {
			if ( ev.target === backdrop ) {
				this.hideModal();
			}
		}.bind( this ) );

		document.addEventListener( 'keydown', this.onModalKeydown = function ( ev ) {
			if ( 'Escape' === ev.key ) {
				this.hideModal();
			}
		}.bind( this ) );

		close.focus();

		if ( window.CarkConfetti ) {
			window.CarkConfetti.burst( confettiCanvas );
		}
	};

	CarkWheel.prototype.hideModal = function () {
		if ( this.modalEl ) {
			this.modalEl.remove();
			this.modalEl = null;
		}
		if ( this.onModalKeydown ) {
			document.removeEventListener( 'keydown', this.onModalKeydown );
			this.onModalKeydown = null;
		}
	};

	/* ---------------- History ---------------- */

	CarkWheel.prototype.renderHistory = function () {
		if ( ! this.historyList ) {
			return;
		}
		this.historyPanel.removeAttribute( 'hidden' );
		this.historyList.innerHTML = '';
		this.history.slice( 0, 50 ).forEach( function ( item ) {
			var li = document.createElement( 'li' );
			li.textContent = item.text;
			this.historyList.appendChild( li );
		}.bind( this ) );
	};

	CarkWheel.prototype.copyHistory = function () {
		var text = this.history.map( function ( h ) {
			return h.text;
		} ).join( '\n' );
		this.copyToClipboard( text, T( 'copied', 'Kopyalandı' ) );
	};

	CarkWheel.prototype.downloadHistory = function () {
		var text = this.history.map( function ( h ) {
			return h.text;
		} ).join( '\n' );
		var blob = new Blob( [ text ], { type: 'text/plain;charset=utf-8' } );
		var url = URL.createObjectURL( blob );
		var a = document.createElement( 'a' );
		a.href = url;
		a.download = 'cark-cevirici-kazananlar.txt';
		document.body.appendChild( a );
		a.click();
		a.remove();
		URL.revokeObjectURL( url );
	};

	/* ---------------- Share / embed / autosave / Çarklarım ---------------- */

	CarkWheel.prototype.getShareableState = function () {
		return {
			title: this.title,
			entries: this.entries,
			settings: this.settings,
		};
	};

	CarkWheel.prototype.copyToClipboard = function ( text, message ) {
		var self = this;
		var done = function () {
			if ( self.live ) {
				self.live.textContent = message;
			}
		};
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done, function () {
				window.prompt( message, text );
			} );
		} else {
			window.prompt( message, text );
		}
	};

	CarkWheel.prototype.share = function () {
		var encoded = encodeState( this.getShareableState() );
		var url = window.location.origin + window.location.pathname + '#w=' + encoded;
		this.copyToClipboard( url, T( 'shareCopied', 'Paylaşım bağlantısı kopyalandı' ) );
	};

	CarkWheel.prototype.copyEmbed = function () {
		var params = new URLSearchParams();
		var entriesParam = this.entries.map( function ( e ) {
			return e.text;
		} ).join( '|' );
		if ( entriesParam ) params.set( 'entries', entriesParam );
		if ( this.title ) params.set( 'title', this.title );
		if ( this.settings.theme ) params.set( 'theme', this.settings.theme );

		var url = window.location.origin + '/embed/?' + params.toString();
		var snippet = '<iframe src="' + url + '" width="500" height="600" style="border:0;max-width:100%;" loading="lazy" title="Çark Çevirici"></iframe>';
		this.copyToClipboard( snippet, T( 'embedCopied', 'Ekleme kodu kopyalandı' ) );
	};

	CarkWheel.prototype.loadFromShareUrl = function () {
		var hash = window.location.hash;
		if ( hash.indexOf( '#w=' ) !== 0 ) {
			return;
		}
		var state = decodeState( hash.slice( 3 ) );
		if ( state && Array.isArray( state.entries ) ) {
			this.title = state.title || this.title;
			this.entries = state.entries;
			this.settings = Object.assign( this.settings, state.settings || {} );
		}
	};

	CarkWheel.prototype.autosaveKey = function () {
		return 'cark_autosave_' + this.id;
	};

	CarkWheel.prototype.saveAutosave = function () {
		storageSet( this.autosaveKey(), this.getShareableState() );
	};

	CarkWheel.prototype.loadAutosave = function () {
		var saved = storageGet( this.autosaveKey(), null );
		if ( saved && Array.isArray( saved.entries ) && saved.entries.length ) {
			this.entries = saved.entries;
			this.settings = Object.assign( this.settings, saved.settings || {} );
		}
	};

	CarkWheel.prototype.openMyWheels = function () {
		var saved = storageGet( SAVED_WHEELS_KEY, [] );
		var listText = saved.length
			? saved.map( function ( w, i ) {
				return ( i + 1 ) + '. ' + w.name;
			} ).join( '\n' )
			: 'Henüz kayıtlı çarkın yok.';

		var choice = window.prompt(
			listText + '\n\nKaydetmek için bir isim yaz, yüklemek için numara yaz, silmek için "sil 1" gibi yaz:',
			''
		);

		if ( ! choice ) {
			return;
		}
		choice = choice.trim();

		if ( /^sil\s+\d+$/i.test( choice ) ) {
			var delIndex = parseInt( choice.replace( /\D/g, '' ), 10 ) - 1;
			if ( saved[ delIndex ] ) {
				saved.splice( delIndex, 1 );
				storageSet( SAVED_WHEELS_KEY, saved );
			}
			return;
		}

		if ( /^\d+$/.test( choice ) ) {
			var loadIndex = parseInt( choice, 10 ) - 1;
			if ( saved[ loadIndex ] ) {
				var wheelState = saved[ loadIndex ].state;
				this.title = wheelState.title;
				this.entries = wheelState.entries;
				this.settings = Object.assign( this.settings, wheelState.settings || {} );
				this.syncTextareaFromEntries();
				this.applySettingsToUi();
				this.saveAutosave();
				this.draw();
			}
			return;
		}

		saved.unshift( { name: choice, state: this.getShareableState(), savedAt: Date.now() } );
		storageSet( SAVED_WHEELS_KEY, saved.slice( 0, 20 ) );
	};

	/* ============================================================ *
	 *  Boot: hydrate every .cark-wheel on the page.
	 * ============================================================ */

	function init() {
		var nodes = document.querySelectorAll( '.cark-wheel' );
		nodes.forEach( function ( node ) {
			if ( ! node.cark ) {
				node.cark = new CarkWheel( node );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )( window, document );
