/**
 * Çark Çevirici – non-wheel tools: Takım Oluşturucu, Kura Çekme,
 * Rastgele Sayı Seçici, Yazı Tura, Zar At. Self-contained vanilla JS,
 * no dependency on wheel.js (pages can load just this file).
 */
( function ( window, document ) {
	'use strict';

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

	function shuffle( arr ) {
		var a = arr.slice();
		for ( var i = a.length - 1; i > 0; i-- ) {
			var j = cryptoInt( i + 1 );
			var tmp = a[ i ];
			a[ i ] = a[ j ];
			a[ j ] = tmp;
		}
		return a;
	}

	function namesFrom( textarea ) {
		return textarea.value
			.split( /\r\n|\r|\n/ )
			.map( function ( l ) {
				return l.trim();
			} )
			.filter( Boolean );
	}

	function copyToClipboard( text ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).catch( function () {
				window.prompt( 'Kopyala:', text );
			} );
		} else {
			window.prompt( 'Kopyala:', text );
		}
	}

	function printNode( node ) {
		var w = window.open( '', '_blank', 'width=600,height=700' );
		if ( ! w ) {
			return;
		}
		w.document.write(
			'<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Çark Çevirici – Sonuç</title>' +
			'<style>body{font:16px/1.5 system-ui,sans-serif;padding:2rem;color:#16181d;} h1,h2,h3{margin-top:0;} ' +
			'.cark-team-card{border:1px solid #e2e4e9;border-radius:10px;padding:.8rem 1rem;margin-bottom:.6rem;}</style></head><body>' +
			node.innerHTML +
			'</body></html>'
		);
		w.document.close();
		w.focus();
		w.print();
	}

	/* ---------------- Takım Oluşturucu ---------------- */

	function initTakim( root ) {
		var names = root.querySelector( '.cark-takim-names' );
		var mode = root.querySelector( '.cark-takim-mode' );
		var count = root.querySelector( '.cark-takim-count' );
		var leader = root.querySelector( '.cark-takim-leader' );
		var results = root.querySelector( '.cark-takim-results' );
		var createBtn = root.querySelector( '.cark-takim-create' );
		var printBtn = root.querySelector( '.cark-takim-print' );
		var copyBtn = root.querySelector( '.cark-takim-copy' );

		function build() {
			var list = shuffle( namesFrom( names ) );
			if ( list.length < 2 ) {
				results.innerHTML = '<p>Takım oluşturmak için en az iki isim yaz.</p>';
				return;
			}

			var n = Math.max( 2, parseInt( count.value, 10 ) || 2 );
			var teamCount = 'per-team' === mode.value ? Math.max( 1, Math.ceil( list.length / n ) ) : n;

			var teams = [];
			for ( var i = 0; i < teamCount; i++ ) {
				teams.push( [] );
			}
			list.forEach( function ( person, i ) {
				teams[ i % teamCount ].push( person );
			} );

			results.innerHTML = '';
			teams.forEach( function ( team, i ) {
				var card = document.createElement( 'div' );
				card.className = 'cark-team-card';
				var h4 = document.createElement( 'h4' );
				h4.textContent = 'Takım ' + ( i + 1 );
				card.appendChild( h4 );
				var ol = document.createElement( 'ol' );
				team.forEach( function ( person, pi ) {
					var li = document.createElement( 'li' );
					li.textContent = person + ( leader.checked && 0 === pi ? ' (Lider)' : '' );
					ol.appendChild( li );
				} );
				card.appendChild( ol );
				results.appendChild( card );
			} );
		}

		createBtn.addEventListener( 'click', build );
		printBtn.addEventListener( 'click', function () {
			printNode( results );
		} );
		copyBtn.addEventListener( 'click', function () {
			copyToClipboard( results.innerText );
		} );
	}

	/* ---------------- Kura Çekme ---------------- */

	function initKura( root ) {
		var names = root.querySelector( '.cark-kura-names' );
		var winnersInput = root.querySelector( '.cark-kura-winners' );
		var subsInput = root.querySelector( '.cark-kura-subs' );
		var results = root.querySelector( '.cark-kura-results' );
		var drawBtn = root.querySelector( '.cark-kura-draw' );
		var printBtn = root.querySelector( '.cark-kura-print' );
		var copyBtn = root.querySelector( '.cark-kura-copy' );

		function draw() {
			var list = namesFrom( names );
			var winnersCount = Math.max( 1, parseInt( winnersInput.value, 10 ) || 1 );
			var subsCount = Math.max( 0, parseInt( subsInput.value, 10 ) || 0 );

			if ( list.length < winnersCount ) {
				results.innerHTML = '<p>Kazanan sayısı, katılımcı sayısından fazla olamaz.</p>';
				return;
			}

			var shuffled = shuffle( list );
			var winners = shuffled.slice( 0, winnersCount );
			var subs = shuffled.slice( winnersCount, winnersCount + subsCount );
			var now = new Date();

			results.innerHTML = '';
			var h3 = document.createElement( 'h3' );
			h3.textContent = 'Kura Sonucu';
			results.appendChild( h3 );

			var time = document.createElement( 'p' );
			time.textContent = 'Çekiliş zamanı: ' + now.toLocaleString( 'tr-TR' );
			results.appendChild( time );

			var wTitle = document.createElement( 'p' );
			wTitle.innerHTML = '<strong>Kazananlar</strong>';
			results.appendChild( wTitle );
			var wList = document.createElement( 'ol' );
			wList.className = 'cark-kura-winner-list';
			winners.forEach( function ( w ) {
				var li = document.createElement( 'li' );
				li.textContent = w;
				wList.appendChild( li );
			} );
			results.appendChild( wList );

			if ( subs.length ) {
				var sTitle = document.createElement( 'p' );
				sTitle.innerHTML = '<strong>Yedekler</strong>';
				results.appendChild( sTitle );
				var sList = document.createElement( 'ol' );
				sList.className = 'cark-kura-sub-list';
				subs.forEach( function ( s ) {
					var li = document.createElement( 'li' );
					li.textContent = s;
					sList.appendChild( li );
				} );
				results.appendChild( sList );
			}
		}

		drawBtn.addEventListener( 'click', draw );
		printBtn.addEventListener( 'click', function () {
			printNode( results );
		} );
		copyBtn.addEventListener( 'click', function () {
			copyToClipboard( results.innerText );
		} );
	}

	/* ---------------- Sayı Seçici ---------------- */

	function initSayi( root ) {
		var min = root.querySelector( '.cark-sayi-min' );
		var max = root.querySelector( '.cark-sayi-max' );
		var count = root.querySelector( '.cark-sayi-count' );
		var unique = root.querySelector( '.cark-sayi-unique' );
		var sort = root.querySelector( '.cark-sayi-sort' );
		var result = root.querySelector( '.cark-sayi-result' );
		var pickBtn = root.querySelector( '.cark-sayi-pick' );

		pickBtn.addEventListener( 'click', function () {
			var lo = parseInt( min.value, 10 );
			var hi = parseInt( max.value, 10 );
			if ( isNaN( lo ) || isNaN( hi ) || hi < lo ) {
				result.textContent = 'Geçerli bir aralık gir.';
				return;
			}
			var howMany = Math.max( 1, parseInt( count.value, 10 ) || 1 );
			var range = hi - lo + 1;

			if ( unique.checked ) {
				howMany = Math.min( howMany, range );
			}

			var picked = [];
			if ( unique.checked ) {
				var pool = [];
				for ( var n = lo; n <= hi; n++ ) {
					pool.push( n );
				}
				picked = shuffle( pool ).slice( 0, howMany );
			} else {
				for ( var i = 0; i < howMany; i++ ) {
					picked.push( lo + cryptoInt( range ) );
				}
			}

			if ( sort.checked ) {
				picked.sort( function ( a, b ) {
					return a - b;
				} );
			}

			result.textContent = picked.join( ', ' );
		} );
	}

	/* ---------------- Yazı Tura ---------------- */

	function initTura( root ) {
		var coin = root.querySelector( '.cark-coin' );
		var flipBtn = root.querySelector( '.cark-tura-flip' );
		var live = root.querySelector( '.cark-live' );
		var stats = root.querySelector( '.cark-tura-stats' );
		var counts = { yazi: 0, tura: 0 };

		flipBtn.addEventListener( 'click', function () {
			flipBtn.disabled = true;
			var result = cryptoInt( 2 ) === 0 ? 'yazi' : 'tura';
			coin.classList.remove( 'is-flipping-yazi', 'is-flipping-tura' );
			// Force reflow so the animation restarts every click.
			void coin.offsetWidth;
			coin.classList.add( 'is-flipping-' + result );

			window.setTimeout( function () {
				counts[ result ]++;
				live.textContent = ( 'yazi' === result ? 'Yazı' : 'Tura' ) + ' geldi!';
				stats.textContent = 'Yazı: ' + counts.yazi + ' · Tura: ' + counts.tura;
				flipBtn.disabled = false;
			}, 1150 );
		} );
	}

	/* ---------------- Zar At ---------------- */

	var DIE_PIP_LAYOUT = {
		1: [ 4 ],
		2: [ 0, 8 ],
		3: [ 0, 4, 8 ],
		4: [ 0, 2, 6, 8 ],
		5: [ 0, 2, 4, 6, 8 ],
		6: [ 0, 2, 3, 5, 6, 8 ],
	};

	function buildDie( value ) {
		var die = document.createElement( 'div' );
		die.className = 'cark-die';
		for ( var i = 0; i < 9; i++ ) {
			var pip = document.createElement( 'i' );
			if ( DIE_PIP_LAYOUT[ value ].indexOf( i ) !== -1 ) {
				pip.className = 'on';
			}
			die.appendChild( pip );
		}
		return die;
	}

	function initZar( root ) {
		var countInput = root.querySelector( '.cark-zar-count' );
		var diceWrap = root.querySelector( '.cark-zar-dice' );
		var rollBtn = root.querySelector( '.cark-zar-roll' );
		var live = root.querySelector( '.cark-live' );

		function render( values ) {
			diceWrap.innerHTML = '';
			values.forEach( function ( v ) {
				var die = buildDie( v );
				die.classList.add( 'is-rolling' );
				diceWrap.appendChild( die );
			} );
		}

		render( [ 1 ] );

		rollBtn.addEventListener( 'click', function () {
			var n = Math.max( 1, Math.min( 6, parseInt( countInput.value, 10 ) || 1 ) );
			var values = [];
			for ( var i = 0; i < n; i++ ) {
				values.push( 1 + cryptoInt( 6 ) );
			}
			render( values );
			var total = values.reduce( function ( a, b ) {
				return a + b;
			}, 0 );
			live.textContent = 'Sonuç: ' + values.join( ' - ' ) + ( values.length > 1 ? ' (Toplam: ' + total + ')' : '' );
		} );
	}

	function init() {
		document.querySelectorAll( '.cark-takim' ).forEach( initTakim );
		document.querySelectorAll( '.cark-kura' ).forEach( initKura );
		document.querySelectorAll( '.cark-sayi' ).forEach( initSayi );
		document.querySelectorAll( '.cark-tura' ).forEach( initTura );
		document.querySelectorAll( '.cark-zar' ).forEach( initZar );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )( window, document );
