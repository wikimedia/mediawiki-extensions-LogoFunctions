<?php
/**
 * LogoFunctions
 *
 * Adds parser functions relating to the wiki's logo
 *
 * @link https://www.mediawiki.org/wiki/Extension:LogoFunctions
 *
 * @author Devunt <devunt@devunt.kr>
 * @authorlink https://www.mediawiki.org/wiki/User:Devunt
 * @copyright Copyright © 2010 Devunt (Bae June Hyeon).
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Ensure that the script cannot be executed outside of MediaWiki.
if ( !defined( 'MEDIAWIKI' ) )
	die( 'This file is a MediaWiki extension and not a valid entry point.' );

// Display extension properties on MediaWiki.
$wgExtensionCredits[ 'parserhook' ][] = array(
	'path' => __FILE__,
	'name' => 'LogoFunctions',
	'author' => array(
		'JuneHyeon Bae (devunt)',
		'...'
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:LogoFunctions',
	'descriptionmsg' => 'logofunctions-desc',
	'license-name' => 'GPL-2.0-or-later',
	'version' => '1.0.0'
);

// Register extension messages and other localisation.
$wgMessagesDirs['LogoFunctions'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['LogoFunctionsMagic'] = __DIR__ . '/LogoFunctions.i18n.magic.php';

// Register extension hooks.
$wgHooks['ParserFirstCallInit'][] = 'efLogoFunctions_Setup';

// Do the extension's actions.
function efLogoFunctions_Setup( &$parser ) {
	$parser->setFunctionHook( 'setlogo', 'efSetLogo_Render' );
	$parser->setFunctionHook( 'getlogo', 'efGetLogo_Render' );
	return true;
}

function efSetLogo_Render( $parser, $logo = '' ) {
	global $wgLogo;
	$imageobj = wfFindFile( $logo );
	if ( $imageobj == null ) {
		return Html::element( 'strong', array( 'class' => 'error' ),
			wfMessage( 'logofunctions-filenotexist', $logo )->inContentLanguage()->text()
		);
	}
	$thumb_arr = array(
		'width' => 135,
		'height' => 135
	);
	$thumb = $imageobj->transform( $thumb_arr );
	$wgLogo = $thumb->getUrl();
}

function efGetLogo_Render( $parser, $prefix = false ) {
	global $wgLogo;
	return ($prefix?$prefix.':':'').basename($wgLogo);
}
