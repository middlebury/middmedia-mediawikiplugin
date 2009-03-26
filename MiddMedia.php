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
 * @link http://www.mediawiki.org/wiki/Extension:MyExtension Documentation
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
//	'url'          => 'http://www.mediawiki.org/wiki/Extension:MyExtension',
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
	
	switch (strtolower($parts['extension'])) {
		case 'mp3':
			$file = rawurlencode($parts['basename']);
			$id = md5($dir.'/'.$file);
			return '
<script type="text/javascript" src="http://middmedia.middlebury.edu/AudioPlayer/audio-player.js"></script><object width="290" height="24" id="'.$id.'" data="http://middmedia.middlebury.edu/AudioPlayer/player.swf" type="application/x-shockwave-flash"><param value="http://middmedia.middlebury.edu/AudioPlayer/player.swf" name="movie" /><param value="high" name="quality" /><param value="false" name="menu" /><param value="transparent" name="wmode" /><param value="soundFile=http://middmedia.middlebury.edu/media/'.$dir.'/'.$file.'" name="FlashVars" /></object>
';	
		case 'flv':
			$prefix = '';
			$file = rawurlencode($parts['filename']);
			break;
		default:
			$prefix = rawurlencode(strtolower($parts['extension']).':');
			$file = rawurlencode($parts['basename']);
	}
	
	return <<< END

<embed src="http://middmedia.middlebury.edu/flowplayer/FlowPlayerLight.swf?config=%7Bembedded%3Atrue%2CstreamingServerURL%3A%27rtmp%3A%2F%2Fmiddmedia.middlebury.edu%2Fvod%27%2CautoPlay%3Afalse%2Cloop%3Afalse%2CinitialScale%3A%27fit%27%2CvideoFile%3A%27{$prefix}{$dir}%2F{$file}%27%2CsplashImageFile%3A%27http://middmedia.middlebury.edu/media/{$dir}/splash/{$thumbFile}%27%7D" width="{$width}" height="{$height}" scale="fit" bgcolor="#111111" type="application/x-shockwave-flash" allowFullScreen="true" allowNetworking="all" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed>

END;
}

