<?php
/**
 * Plugin Name: Last.fm Recently Played
 * Description: Shows the most recent song a Last.fm user listened to, if it was played within a configurable window. Use the [lastfm_now_playing] shortcode.
 * Version:     1.0.0
 * Author:      Waldo Jaquith
 * License:     GPL-2.0-or-later
 *
 * Single-site plugin: edit the CONFIGURATION constants below directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/* -------------------------------------------------------------------------
 * CONFIGURATION — edit these three values.
 * ---------------------------------------------------------------------- */

// Your Last.fm API key. Create one at https://www.last.fm/api/account/create
define( 'LFM_RP_API_KEY', '28a70b97fe63e68bd90b139e395ec602' );

// The Last.fm username whose listening history you want to show.
define( 'LFM_RP_USERNAME', 'waldoj' );

// Only show a track if it was played within this many minutes.
// A "now playing" track (scrobbling in real time) is always shown.
define( 'LFM_RP_RECENCY_MINUTES', 30 );

/* -------------------------------------------------------------------------
 * You shouldn't need to edit below this line.
 * ---------------------------------------------------------------------- */

// How long (in seconds) to cache the Last.fm response. Keeps pages fast and
// avoids hammering the API on every request. Also bounds how stale the
// client-side widget can get, since the REST response is cacheable this long.
define( 'LFM_RP_CACHE_SECONDS', 60 );

// Asset version, used for cache-busting the CSS and JS. Bump on each release.
define( 'LFM_RP_VERSION', '1.0.1' );

/**
 * Cache a failed or empty lookup and return false.
 *
 * Stored as a timestamped array( 'error' => true, 'fetched_at' => ... ) so it
 * shares the freshness check in lfm_rp_get_recent_track() — a cached failure
 * can't outlive its window even if the cache backend ignores the TTL.
 */
function lfm_rp_cache_failure() {
	set_transient(
		'lfm_rp_recent_track',
		array(
			'error'      => true,
			'fetched_at' => time(),
		),
		LFM_RP_CACHE_SECONDS
	);
	return false;
}

/**
 * Fetch the most recent track for the configured user.
 *
 * Returns a normalized array on success, or false if there is nothing usable
 * (network error, empty history, misconfiguration, etc.).
 *
 * Shape on success:
 *   array(
 *     'artist'      => string,
 *     'title'       => string,
 *     'now_playing' => bool,
 *     'played_uts'  => int|null,  // Unix timestamp, null when now playing
 *     'image_url'   => string,    // may be empty
 *     'fetched_at'  => int,       // Unix timestamp of the fetch, for freshness
 *   )
 */
function lfm_rp_get_recent_track() {
	// Serve from cache only while the entry is still fresh. We validate that
	// against our own 'fetched_at' timestamp rather than trusting the
	// transient's TTL: some object caches (notably W3 Total Cache's disk
	// cache) don't reliably honor short expirations and can pin a value for
	// days. Anything stale, malformed, or from an older format falls through
	// to a fresh fetch — which also means deploying this self-heals a value
	// that's currently stuck, with no manual cache purge needed.
	$cached = get_transient( 'lfm_rp_recent_track' );
	if ( is_array( $cached ) && isset( $cached['fetched_at'] )
		&& ( time() - $cached['fetched_at'] ) < LFM_RP_CACHE_SECONDS ) {
		// A cached failure is stored as array( 'error' => true, ... ).
		return empty( $cached['error'] ) ? $cached : false;
	}

	// Bail early if the plugin hasn't been configured.
	if ( 'YOUR_API_KEY_HERE' === LFM_RP_API_KEY || '' === trim( LFM_RP_API_KEY )
		|| 'YOUR_USERNAME_HERE' === LFM_RP_USERNAME || '' === trim( LFM_RP_USERNAME ) ) {
		return false;
	}

	$url = add_query_arg(
		array(
			'method'  => 'user.getrecenttracks',
			'user'    => rawurlencode( LFM_RP_USERNAME ),
			'api_key' => rawurlencode( LFM_RP_API_KEY ),
			'format'  => 'json',
			'limit'   => 1,
		),
		'https://ws.audioscrobbler.com/2.0/'
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 2, // Keep visitor-facing renders snappy on cache misses.
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		// Cache the failure briefly so a Last.fm hiccup doesn't slow every page.
		return lfm_rp_cache_failure();
	}

	$data  = json_decode( wp_remote_retrieve_body( $response ), true );
	$track = null;

	// The API nests the newest track under recenttracks.track (an array).
	if ( isset( $data['recenttracks']['track'][0] ) ) {
		$track = $data['recenttracks']['track'][0];
	} elseif ( isset( $data['recenttracks']['track']['name'] ) ) {
		// With limit=1 Last.fm sometimes returns a single object, not a list.
		$track = $data['recenttracks']['track'];
	}

	// Note: not empty(), which would reject a track legitimately titled "0".
	if ( null === $track || ! isset( $track['name'] ) || '' === trim( $track['name'] ) ) {
		return lfm_rp_cache_failure();
	}

	$now_playing = ! empty( $track['@attr']['nowplaying'] )
		&& 'true' === $track['@attr']['nowplaying'];

	$played_uts = null;
	if ( ! $now_playing && isset( $track['date']['uts'] ) ) {
		$played_uts = (int) $track['date']['uts'];
	}

	// Last.fm offers several named image sizes (small 34, medium 64, large 174,
	// extralarge 300). The widget renders the art at 60 CSS px — up to 120
	// physical px on a 2× display — so we want "large" for a crisp result, not
	// the 34px "small". Collect what's available by name, then pick the best
	// fit, falling back through larger-then-smaller sizes so we still show
	// something if the preferred size is missing.
	$images = array();
	if ( ! empty( $track['image'] ) && is_array( $track['image'] ) ) {
		foreach ( $track['image'] as $img ) {
			if ( ! empty( $img['#text'] ) ) {
				$size            = isset( $img['size'] ) ? $img['size'] : '';
				$images[ $size ] = $img['#text'];
			}
		}
	}

	$image_url = '';
	foreach ( array( 'large', 'extralarge', 'mega', 'medium', 'small', '' ) as $size ) {
		if ( ! empty( $images[ $size ] ) ) {
			$image_url = $images[ $size ];
			break;
		}
	}

	$artist = '';
	if ( isset( $track['artist']['#text'] ) ) {
		$artist = $track['artist']['#text'];
	} elseif ( isset( $track['artist']['name'] ) ) {
		$artist = $track['artist']['name'];
	}

	$result = array(
		'artist'      => trim( $artist ),
		'title'       => trim( $track['name'] ),
		'now_playing' => $now_playing,
		'played_uts'  => $played_uts,
		'image_url'   => $image_url,
		'fetched_at'  => time(),
	);

	set_transient( 'lfm_rp_recent_track', $result, LFM_RP_CACHE_SECONDS );
	return $result;
}

/**
 * Build the widget's inner markup: label + optional album art + song.
 *
 * Returns an empty string when there is no track inside the recency window.
 * This is the single source of truth for what the widget shows, called fresh
 * on each REST request (see lfm_rp_rest_now_playing) rather than baked into the
 * page, so a full-page cache can't freeze it.
 */
function lfm_rp_render_html() {
	$track = lfm_rp_get_recent_track();
	if ( false === $track ) {
		return '';
	}

	// Decide whether the track is recent enough to show.
	if ( ! $track['now_playing'] ) {
		if ( null === $track['played_uts'] ) {
			return ''; // Not now playing and no timestamp — nothing to show.
		}
		$age_seconds = time() - $track['played_uts'];
		if ( $age_seconds > LFM_RP_RECENCY_MINUTES * MINUTE_IN_SECONDS ) {
			return ''; // Outside the window.
		}
	}

	// Build the label.
	if ( $track['now_playing'] ) {
		$label = __( 'Now playing', 'lastfm-recently-played' );
	} else {
		/* translators: %s: human-readable time difference, e.g. "5 mins". */
		$label = sprintf(
			__( 'Last played %s ago', 'lastfm-recently-played' ),
			human_time_diff( $track['played_uts'], time() )
		);
	}

	$song = $track['title'];
	if ( '' !== $track['artist'] ) {
		/* translators: 1: song title, 2: artist name. */
		$song = sprintf(
			_x( '“%1$s,” by %2$s', 'song by artist', 'lastfm-recently-played' ),
			$track['title'],
			$track['artist']
		);
	}

	$out = '<span class="lastfm-label">' . esc_html( $label ) . ':</span> ';

	if ( '' !== $track['image_url'] ) {
		$out .= '<img class="lastfm-art" src="' . esc_url( $track['image_url'] ) . '" '
			. 'alt="" width="60" height="60" loading="lazy" /> ';
	}

	$out .= '<span class="lastfm-song">' . esc_html( $song ) . '</span>';

	return $out;
}

/**
 * REST callback: return the current widget markup as { "html": "..." }.
 *
 * Because the shortcode only outputs an empty placeholder, this is what keeps
 * the widget live on a full-page-cached site (e.g. W3 Total Cache). REST
 * requests under /wp-json/ aren't page-cached by W3TC by default, so this runs
 * on every request and reads the fresh (LFM_RP_CACHE_SECONDS) transient.
 */
function lfm_rp_rest_now_playing() {
	$response = new WP_REST_Response( array( 'html' => lfm_rp_render_html() ) );

	// Let browsers reuse the response briefly, but never longer than the
	// server-side cache window, so total staleness stays bounded.
	$response->header( 'Cache-Control', 'public, max-age=' . LFM_RP_CACHE_SECONDS );

	return $response;
}

/**
 * Register the REST route the front-end script fetches.
 */
function lfm_rp_register_rest_route() {
	register_rest_route(
		'lastfm/v1',
		'/now-playing',
		array(
			'methods'             => 'GET',
			'callback'            => 'lfm_rp_rest_now_playing',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'lfm_rp_register_rest_route' );

/**
 * Render the [lastfm_now_playing] shortcode.
 *
 * Outputs only an empty container plus the assets that fill it. The track
 * itself is fetched client-side from the REST endpoint on each page load, so it
 * stays current even when the surrounding page HTML is served from a full-page
 * cache. An empty container renders as zero-width, so it's invisible until the
 * script drops in a track (and stays invisible when nothing is playing).
 */
function lfm_rp_shortcode() {
	wp_enqueue_style( 'lastfm-recently-played' );
	wp_enqueue_script( 'lastfm-recently-played' );

	return '<span class="lastfm-now-playing"></span>';
}
add_shortcode( 'lastfm_now_playing', 'lfm_rp_shortcode' );

/**
 * Register the plugin's front-end assets (style.css and now-playing.js).
 *
 * Both are registered here but only enqueued when the shortcode actually
 * renders (see lfm_rp_shortcode), so pages without the widget carry neither.
 *
 * style.css is deliberately minimal and structural only — just enough to keep
 * the label, album art, and song on one line and vertically aligned. Font,
 * size, colour, and weight are all left to inherit from the surrounding theme,
 * so the widget looks like the text around it out of the box. Layout choices
 * that are yours to make (centring, margins, spacing from neighbours) live in
 * your own stylesheet via the .lastfm-now-playing / .lastfm-label /
 * .lastfm-art / .lastfm-song classes.
 */
function lfm_rp_register_assets() {
	wp_register_style(
		'lastfm-recently-played',
		plugins_url( 'style.css', __FILE__ ),
		array(),
		LFM_RP_VERSION
	);

	wp_register_script(
		'lastfm-recently-played',
		plugins_url( 'now-playing.js', __FILE__ ),
		array(),
		LFM_RP_VERSION,
		true // Load in the footer.
	);

	// Tell the script where to fetch the current track.
	wp_localize_script(
		'lastfm-recently-played',
		'lfmRP',
		array( 'endpoint' => esc_url_raw( rest_url( 'lastfm/v1/now-playing' ) ) )
	);
}
add_action( 'wp_enqueue_scripts', 'lfm_rp_register_assets' );
