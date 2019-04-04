<?php

class LogoFunctions {

	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'setlogo', [ __CLASS__, 'renderSetLogo' ] );
		$parser->setFunctionHook( 'getlogo', [ __CLASS__, 'renderGetLogo' ] );
	}

	public static function renderSetLogo( $parser, $logo = '' ) {
		global $wgLogo;

		$imageobj = wfFindFile( $logo );
		if ( $imageobj == null ) {
			return Html::element( 'strong', [ 'class' => 'error' ],
				wfMessage( 'logofunctions-filenotexist', $logo )->inContentLanguage()->text()
			);
		}

		$thumb_arr = [
			'width' => 135,
			'height' => 135
		];
		$thumb = $imageobj->transform( $thumb_arr );
		$wgLogo = $thumb->getUrl();
	}

	public static function renderGetLogo( $parser, $prefix = false ) {
		global $wgLogo;
		return ( $prefix ? $prefix . ':' : '' ) . basename( $wgLogo );
	}

}
