<?php

use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;

/**
 * ResourceLoader module to replace ResourceLoaderSkinModule output styles
 * for namespaces set in config
 *
 * We're not extending SkinModule because all skins we're trying to affect
 * will be already using that regardless, and that'd just give us double
 * styles.
 */
class LogoFunctionsSkinModule extends FileModule {
	/**
	 * Make the styles from the config array
	 *
	 * @param Context $context
	 * @return array
	 */
	public function getStyles( Context $context ) {
		$config = $this->getConfig();
		$logos = $config->get( 'NamespaceLogos' );
		$styles = parent::getStyles( $context );

		if ( !is_array( $logos ) || !count( $logos ) ) {
			// Okay, we got nothing here.
			return $styles;
		}

		$timeless = ExtensionRegistry::getInstance()->isLoaded( 'Timeless' );
		$css = '';
		foreach ( $logos as $ns => $logo ) {
			// We'll just assume they're the right sizes to begin with here.
			$background = LogoFunctions::getBackground( null, $logo );
			if ( !$background ) {
				// Not found
				continue;
			}

			// Just use the number because we can?
			$nsClass = '.ns-' . $ns;
			$background = "$nsClass .mw-wiki-logo {\n$background}\n";
			// wtf timeless
			if ( $timeless ) {
				$background = "$nsClass .mw-wiki-logo.timeless-logo,\n$background";
			}

			$css .= "\n$background";
		}

		// We're currently not defining a module with anything but flat css (skinstyles),
		// so this is just a string for now
		if ( isset( $styles['all'] ) && $styles['all'] ) {
			$styles['all'] .= $css;
		} else {
			$styles['all'] = $css;
		}
		return $styles;
	}

	/**
	 * Register the config var with the caching stuff so it properly updates the cache
	 *
	 * @param Context $context
	 * @return array
	 */
	public function getDefinitionSummary( Context $context ) {
		$summary = parent::getDefinitionSummary( $context );
		$summary[] = [
			'LogoFunctionsNamespaceLogos' => md5(
				serialize( $this->getConfig()->get( 'NamespaceLogos' ) )
			)
		];
		return $summary;
	}
}
