<?php
/**
 * Plugin Name: Manacost Web Vitals
 * Description: Adds sampled, privacy-safe Core Web Vitals events to the existing Plausible tracker.
 * Version: 1.0.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Coordinates production-only Web Vitals measurement. */
final class Manacost_Web_Vitals {
	/** Registers the tracker guard and the RUM script. */
	public static function boot(): void {
		add_action( 'wp_loaded', array( __CLASS__, 'disable_staging_tracker' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'render' ), 21 );
	}

	/** Keeps the existing Plausible tracker inert outside production. */
	public static function disable_staging_tracker(): void {
		if ( self::tracking_allowed() ) {
			return;
		}

		remove_action( 'wp_head', array( 'Manacost_Plausible_Analytics', 'render_tracker' ), 20 );
	}

	/** Renders sampled aggregate metric observers without persistent identifiers. */
	public static function render(): void {
		if (
			is_admin()
			|| ! self::tracking_allowed()
			|| ( defined( 'MANACOST_WEB_VITALS_ENABLED' ) && ! MANACOST_WEB_VITALS_ENABLED )
		) {
			return;
		}
		?>
<script id="manacost-web-vitals">
(function(){
if(!('PerformanceObserver' in window)||Math.random()>0.05)return;
var values={LCP:null,CLS:0,INP:null},sent=false;
function observe(options,read){try{new PerformanceObserver(function(list){list.getEntries().forEach(read);}).observe(options);}catch(error){}}
observe({type:'largest-contentful-paint',buffered:true},function(entry){values.LCP=Math.round(entry.startTime);});
observe({type:'layout-shift',buffered:true},function(entry){if(!entry.hadRecentInput)values.CLS+=entry.value;});
observe({type:'event',buffered:true,durationThreshold:40},function(entry){if(entry.interactionId&&(!values.INP||entry.duration>values.INP))values.INP=Math.round(entry.duration);});
function rating(name,value){if(name==='LCP')return value<=2500?'good':value<=4000?'needs-improvement':'poor';if(name==='INP')return value<=200?'good':value<=500?'needs-improvement':'poor';return value<=0.1?'good':value<=0.25?'needs-improvement':'poor';}
function bucket(name,value){if(name==='CLS')return value<=0.05?'0-0.05':value<=0.1?'0.05-0.1':value<=0.25?'0.1-0.25':'0.25+';if(name==='INP')return value<=100?'0-100ms':value<=200?'100-200ms':value<=500?'200-500ms':'500ms+';return value<=1000?'0-1s':value<=2500?'1-2.5s':value<=4000?'2.5-4s':value<=8000?'4-8s':'8s+';}
function send(){if(sent)return;sent=true;var nav=performance.getEntriesByType('navigation')[0],navigationType=nav&&nav.type?String(nav.type):'unknown';window.plausible=window.plausible||function(){(window.plausible.q=window.plausible.q||[]).push(arguments);};Object.keys(values).forEach(function(name){var value=values[name];if(value===null)return;window.plausible('Web Vital',{props:{metric:name,rating:rating(name,value),value_bucket:bucket(name,value),navigation_type:navigationType}});});}
window.addEventListener('pagehide',send,{once:true});
document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden')send();});
})();
</script>
		<?php
	}

	/** Returns whether the request belongs to an approved production host. */
	private static function tracking_allowed(): bool {
		if ( function_exists( 'wp_get_environment_type' ) && 'production' !== wp_get_environment_type() ) {
			return false;
		}

		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		return in_array( $host, array( 'hs-manacost.ru', 'www.hs-manacost.ru', 'hs-manacost.com', 'www.hs-manacost.com' ), true );
	}
}

Manacost_Web_Vitals::boot();
