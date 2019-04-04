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
 * @author Ryan Schmidt <skizzerz@gmail.com>
 * @copyright Copyright © 2010 Devunt (Bae June Hyeon) and Ryan Schmidt.
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Ensure that the script cannot be executed outside of MediaWiki.
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension and not a valid entry point.' );
}

// Display extension properties on MediaWiki.
$wgExtensionCredits['parserhook'][] = [
	'path' => __FILE__,
	'name' => 'LogoFunctions',
	'author' => [
		'JuneHyeon Bae (devunt)',
		'Ryan Schmidt',
		'...'
	],
	'url' => 'https://www.mediawiki.org/wiki/Extension:LogoFunctions',
	'descriptionmsg' => 'logofunctions-desc',
	'license-name' => 'GPL-2.0-or-later',
	'version' => '1.1.0'
];

// config
// map of namespace name => logo URL
$wgNamespaceLogos = [];

// Register extension messages and other localisation.
$wgMessagesDirs['LogoFunctions'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['LogoFunctionsMagic'] = __DIR__ . '/LogoFunctions.i18n.magic.php';

$wgAutoloadClasses['LogoFunctions'] = __DIR__ . '/LogoFunctions.class.php';

// Register extension hooks.
$wgHooks['ParserFirstCallInit'][] = 'LogoFunctions::onParserFirstCallInit';
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'LogoFunctions::onSkinTemplateOutputPageBeforeExec';
