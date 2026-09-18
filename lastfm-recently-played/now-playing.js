/**
 * Last.fm Recently Played — fills the widget from the REST endpoint.
 *
 * The shortcode outputs an empty <span class="lastfm-now-playing">, which a
 * full-page cache (e.g. W3 Total Cache) is free to cache. This script runs on
 * every page load, asks the REST endpoint for the current markup, and drops it
 * into that span — so the song stays live even when the page HTML does not.
 */
( function () {
	if ( typeof lfmRP === 'undefined' || ! lfmRP.endpoint ) {
		return;
	}

	var containers = document.querySelectorAll( '.lastfm-now-playing' );
	if ( ! containers.length ) {
		return;
	}

	fetch( lfmRP.endpoint, { headers: { Accept: 'application/json' } } )
		.then( function ( response ) {
			return response.ok ? response.json() : null;
		} )
		.then( function ( data ) {
			if ( ! data || typeof data.html !== 'string' ) {
				return;
			}
			// The markup is escaped server-side (esc_html / esc_url), so it is
			// safe to inject; an empty string simply leaves the widget hidden.
			containers.forEach( function ( el ) {
				el.innerHTML = data.html;
			} );
		} )
		.catch( function () {
			/* Leave the widget empty on any network or parse failure. */
		} );
}() );
