<?php
/**
 * @package addGEORSS
 * @version 1.2
 */
/*
Plugin Name: addGEORSS
Plugin URI: http://www.funsite.eu/addGEORSS
Description: Adds a GEORSS point to the RSS feed using the GEO information in the featured image
Author: Gerhard Hoogterp
Version: 1.2
Author URI: http://www.funsite.eu/
*/

class addgeorss_class {

	const FS_TEXTDOMAIN = 'addgeorss';
	const FS_PLUGINNAME = 'addgeorss';
	
    public function __construct() {
		add_action('init', array($this,'myTextDomain'));
		add_filter('plugin_row_meta', array($this,'addgeorss_PluginLinks'),10,2);
		add_action( "rss2_ns", array($this,"feed_addNameSpace") );
		add_action( "rss_item", array($this,"feed_addMeta"), 5, 1 );
		add_action( "rss2_item", array($this,"feed_addMeta"), 5, 1 );
    }

	function gps($coordinate, $hemisphere) {
		for ($i = 0; $i < 3; $i++) {
		$part = explode('/', $coordinate[$i]);
		if (count($part) == 1) {
			$coordinate[$i] = $part[0];
		} else if (count($part) == 2) {
			$coordinate[$i] = floatval($part[0])/floatval($part[1]);
		} else {
			$coordinate[$i] = 0;
		}
		}
		list($degrees, $minutes, $seconds) = $coordinate;
		$sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
		return $sign * ($degrees + $minutes/60 + $seconds/3600);
	}

	function getLocationFromDBorExif($post_thumbnail_id) {
		// Check if the location is already stored in the database
		// if not, try to get it from the EXIF information and store it.
		$location = get_post_meta($post_thumbnail_id,'EXIF_location',true);
		if (empty($location)) {

			$thumbnail=get_attached_file( $post_thumbnail_id, true );
			$exif = exif_read_data($thumbnail);
			if (is_array($exif["GPSLatitude"]) && is_array($exif["GPSLongitude"])) {
				$location['latitude'] = $this->gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
				$location['longitude'] = $this->gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
				$location['hasLocation'] = true;

				} else {
				$location['hasLocation'] = false;
			}

			add_post_meta($post_thumbnail_id,'EXIF_location',$location) || update_post_meta($post_thumbnail_id,'EXIF_location',$location);
		}
	return $location;
	}

	function feed_addNameSpace() {
		echo 'xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"'."\n";
	}

	function feed_addMeta($for_comments) {
		$post_thumbnail_id = get_post_thumbnail_id( $GLOBALS['post']->ID );
		$location = $this->getLocationFromDBorExif($post_thumbnail_id);
		if (!$for_comments && $location['hasLocation']) {
			echo "<geo:lat>".$location['latitude']."</geo:lat>\n";
			echo "<geo:long>".$location['longitude']."</geo:long>\n";
		}
	}

	function addgeorss_PluginLinks($links, $file) {
		$base = plugin_basename(__FILE__);
		if ($file == $base) {
			$links[] = '<a href="https://wordpress.org/support/view/plugin-reviews/'.self::FS_PLUGINNAME.'#postform">' . __('Please rate me.',self::FS_TEXTDOMAIN) . '</a>';
		}
		return $links;
	}

	function myTextDomain() {
		load_plugin_textdomain(
			self::FS_TEXTDOMAIN,
			false,
			dirname(plugin_basename(__FILE__)).'/languages/'
		);
	}
	
}

$addgeorss = new addgeorss_class();
?>