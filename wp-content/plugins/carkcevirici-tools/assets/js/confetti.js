/**
 * Tiny dependency-free canvas confetti burst, used by wheel.js's winner
 * modal. No external library: a few dozen rectangles with gravity + drag.
 */
( function ( window ) {
	'use strict';

	var COLORS = [ '#2563eb', '#f59e0b', '#16a34a', '#db2777', '#7c3aed', '#ef4444' ];

	function burst( canvas, options ) {
		if ( ! canvas || ! canvas.getContext ) {
			return;
		}
		options = options || {};
		var count = options.count || 90;
		var duration = options.duration || 2600;
		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		if ( reduceMotion ) {
			return;
		}

		var dpr = Math.min( window.devicePixelRatio || 1, 2 );
		var rect = canvas.getBoundingClientRect();
		canvas.width = rect.width * dpr;
		canvas.height = rect.height * dpr;
		var ctx = canvas.getContext( '2d' );
		ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );

		var pieces = [];
		for ( var i = 0; i < count; i++ ) {
			pieces.push( {
				x: rect.width / 2,
				y: rect.height * 0.25,
				vx: ( Math.random() - 0.5 ) * 8,
				vy: Math.random() * -6 - 2,
				size: 5 + Math.random() * 5,
				color: COLORS[ i % COLORS.length ],
				rot: Math.random() * Math.PI,
				vr: ( Math.random() - 0.5 ) * 0.4,
			} );
		}

		var start = null;

		function frame( ts ) {
			if ( ! start ) {
				start = ts;
			}
			var elapsed = ts - start;
			ctx.clearRect( 0, 0, rect.width, rect.height );

			pieces.forEach( function ( p ) {
				p.vy += 0.18;
				p.x += p.vx;
				p.y += p.vy;
				p.rot += p.vr;
				ctx.save();
				ctx.translate( p.x, p.y );
				ctx.rotate( p.rot );
				ctx.fillStyle = p.color;
				ctx.globalAlpha = Math.max( 0, 1 - elapsed / duration );
				ctx.fillRect( -p.size / 2, -p.size / 2, p.size, p.size * 0.6 );
				ctx.restore();
			} );

			if ( elapsed < duration ) {
				requestAnimationFrame( frame );
			} else {
				ctx.clearRect( 0, 0, rect.width, rect.height );
			}
		}

		requestAnimationFrame( frame );
	}

	window.CarkConfetti = { burst: burst };
} )( window );
