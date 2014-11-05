<?php
/**
 * MiddMedia - A custom tag extension for displaying MiddMedia video files.
 *
 * To activate this extension, add the following into your LocalSettings.php file:
 * require_once('$IP/extensions/MiddMedia.php');
 *
 * Usage:
 *      <middmedia height="400px" width="600px" dir="afranco" file="MIDDLEBURY KICKOFF.mp4"/>
 *
 * @ingroup Extensions
 * @author Adam Franco <afranco@middlebury.edu>
 * @version 1.0
 * @link https://www.assembla.com/wiki/show/MiddMedia/MediaWiki_Plugin Documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
 
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}
 
// Extension credits that will show up on Special:Version    
$wgExtensionCredits['parserhook'][] = array(
	'name'         => 'MiddMedia',
	'version'      => '1.0',
	'author'       => 'Adam Franco (Middlebury College)', 
	'url'          => 'https://www.assembla.com/wiki/show/MiddMedia/MediaWiki_Plugin',
	'description'  => 'A custom tag extension for displaying MiddMedia video files. <br/>Example usage: <br/><code><nowiki><middmedia height="160px" width="300px" dir="afranco" file="MIDDLEBURY KICKOFF.mp4"/></nowiki></code><br/><middmedia height="160px" width="300px" dir="afranco" file="MIDDLEBURY KICKOFF.mp4"/>'
);


// Avoid unstubbing $wgParser on setHook() too early on modern (1.12+) MW versions, as per r35980
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'middmediaInit';
} else { // Otherwise do things the old fashioned way
	$wgExtensionFunctions[] = 'middmediaInit';
}


function middmediaInit () {
	global $wgParser;
	$wgParser->setHook( 'middmedia', 'middmediaRender' );
	return true;
}

function middmediaRender ( $input, $args, $parser ) {
	
	if (preg_match('/^[0-9]+(px|%)?$/i', $args['width']))
		$width = $args['width'];
	else
		$width = '600px';
	
	if (preg_match('/^[0-9]+(px|%)?$/i', $args['height']))
		$height = $args['height'];
	else
		$height = '400px';
	
	$parts = pathinfo($args['file']);
	// PHP < 5.2.0 doesn't have 'filename'
	if (!isset($parts['filename'])) {
		$parts['filename'] = basename($parts['basename'], '.'.$parts['extension']); 
	}
	
	$dir = rawurlencode($args['dir']);
	$thumbFile = rawurlencode($parts['filename'].'.jpg');
	$file = rawurlencode($parts['basename']);
	$extension = strtolower($parts['extension']);
	
	if ($extension == 'm4a') {
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE &&
			strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === FALSE &&
			strpos($_SERVER['HTTP_USER_AGENT'], 'Version/5') !== FALSE) 
		{
		  return '<video width="{$width}" height="{$height}"controls="controls" src="http://middmedia.middlebury.edu/media/{$dir}/m4a/'. $file .'">';
		} else {
		  return '<a href="http://middmedia.middlebury.edu/media/{$dir}/m4a/'. $file .'">'. htmlentities($parts['basename']) .'</a>';
		}
	} 
	else if ($extension == 'mp3') {
		$html5 = strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE || strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE;
		
		ob_start();
		if ($html5) {
			print '<audio src="http://middmedia.middlebury.edu/media/'. $dir .'/mp3/'. $file .'" controls="true" preload="none">';
		}
		
		$id = md5($dir.'/'.$file);
		print '
<script type="text/javascript" src="http://middmedia.middlebury.edu/AudioPlayer/audio-player.js"></script><object width="290" height="24" id="'.$id.'" data="http://middmedia.middlebury.edu/AudioPlayer/player.swf" type="application/x-shockwave-flash"><param value="http://middmedia.middlebury.edu/AudioPlayer/player.swf" name="movie" /><param value="high" name="quality" /><param value="false" name="menu" /><param value="transparent" name="wmode" /><param value="soundFile=http://middmedia.middlebury.edu/media/'.$dir.'/'.$file.'" name="FlashVars" /></object>
';
		if ($html5) {
			print "</audio>";
		}
		return ob_get_clean();
	}
	// Video
	else {		
		$splashImage = 'http://middmedia.middlebury.edu/media/'. $dir . '/splash/' . $thumbFile;
		ob_start();
		if ($extension != 'flv') {
			print '<video width="'. $width .'" height="'. $height .'" controls="true" poster="'. $splashImage .'">';
			print '<source src="http://middmedia.middlebury.edu/media/'. $dir .'/mp4/'. $parts['filename'] .'.mp4" type=\'video/mp4; codecs="avc1.42E01E, mp4a.40.2"\' />';
			print '<source src="http://middmedia.middlebury.edu/media/'. $dir .'/webm/'. $parts['filename'] .'.webm" type=\'video/webm; codecs="vp8, vorbis"\' />'; 
		}
		
		
		print '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0" width="' . $width . '" height="' . $height . '">';
		print '<param name="movie" value="http://middmedia.middlebury.edu/strobe_mp/StrobeMediaPlayback.swf"></param>';
		print '<param name="FlashVars" value="src=http://middmedia.middlebury.edu/media/' . $dir .'/mp4/'. $parts['filename'] .'.mp4&poster=' . urlencode($splashImage) . '"></param>';
		print '<param name="allowFullScreen" value="true"></param>';
		print '<param name="allowscriptaccess" value="always"></param>';
		print '<embed src="http://middmedia.middlebury.edu/strobe_mp/StrobeMediaPlayback.swf" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $width . '" height="' . $height . '" FlashVars="src=http://middmedia.middlebury.edu/media/' . $dir .'/mp4/'. $parts['filename'] .'.mp4&poster=' . urlencode($splashImage) . '">';
		print '</embed>';
		print '</object>';
		
		
		if ($extension != 'flv') {
			print "</video>";
		}
		
		return ob_get_clean();
	}
}

