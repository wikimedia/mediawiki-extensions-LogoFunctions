<?php

class LogoFunctions {

	public static $prev = false;
	public static $chain = '';
	public static $logo = false;

	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'setlogo', [ __CLASS__, 'renderSetLogo' ] );
		$parser->setFunctionHook( 'getlogo', [ __CLASS__, 'renderGetLogo' ] );
		$parser->setFunctionHook( 'stamplogo', [ __CLASS__, 'renderStampLogo' ] );
	}

	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$tpl ) {
		global $wgNamespaceLogos;

		// self::$logo is only set if we parsed a page (so preview or whatnot)
		// if set, override value from page_props so that page previews are correct
		if ( self::$logo !== false ) {
			$tpl->set( 'logopath', self::$logo );
			return true;
		}

		$dbr = wfGetDB( DB_REPLICA );
		$logopath = $dbr->selectField(
			'page_props',
			'pp_value',
			[
				'pp_page' => $skin->getTitle()->getArticleID(),
				'pp_propname' => 'logopath'
			],
			__METHOD__
		);
		if ( $logopath !== false ) {
			$tpl->set( 'logopath', $logopath );
			return true;
		}

		// grab namespace logo (if set)
		$ns = $skin->getTitle()->getNamespace();
		$logopath = false;
		if ( isset( $wgNamespaceLogos[$ns] ) ) {
			if ( is_array( $wgNamespaceLogos[$ns] ) ) {
				$tpl->set( 'logopath', $wgNamespaceLogos[$ns]['url'] );
			} else {
				$tpl->set( 'logopath', $wgNamespaceLogos[$ns] );
			}
		}

		return true;
	}

	public static function renderSetLogo( $parser, $logo = '', $width = 154, $height = 155 ) {
		global $wgLogo, $wgUploadPath, $wgUploadDirectory;

		$imageobj = wfFindFile( $logo );
		if ( $imageobj == null ) {
			return Html::element( 'strong', [ 'class' => 'error' ],
				wfMessage( 'logofunctions-filenotexist', $logo )->inContentLanguage()->text()
			);
		}

		$thumb_arr = [
			'width' => ( is_numeric( $width ) && $width > 0 ) ? $width : 154,
			'height' => ( is_numeric( $height ) && $height > 0 ) ? $height : 155
		];
		$thumb = $imageobj->transform( $thumb_arr, File::RENDER_NOW );

		$parser->getOutput()->setProperty( 'logopath', $thumb->getUrl() );
		$wgLogo = $thumb->getUrl();
		self::$logo = $wgLogo;

		self::$prev = $wgUploadDirectory . substr( $thumb->getUrl(), strlen( $wgUploadPath ) );
		self::$chain = self::$prev . '#' . $width . '#' . $height;
	}

	public static function renderGetLogo( $parser, $prefix = false ) {
		global $wgLogo;
		return ( $prefix ? $prefix . ':' : '' ) . basename( $wgLogo );
	}

	public static function renderStampLogo( $parser, $logo = '', $offX = 0, $offY = 0, $canvX = 0, $canvY = 0 ) {
		global $wgLogo, $wgUploadPath, $wgUploadDirectory, $wgNamespaceLogos;
		global $wgStylePath, $wgStyleDirectory;

		$imageobj = wfFindFile( $logo );
		if ( $imageobj == null ) {
			return Html::element( 'strong', [ 'class' => 'error' ],
				wfMessage( 'logofunctions-filenotexist', $logo )->inContentLanguage()->text()
			);
		}

		if ( self::$prev === false ) {
			// grab current logo
			// 2 checks: first we check namespace logo, then we check $wgLogo
			// namely if $wgLogo is in the default $wgStylePath dir instead of $wgUploadPath and act
			// accordingly for filename
			$ns = $parser->getTitle()->getNamespace();
			$logoData = $logopath = $logoURL = false;
			if ( isset( $wgNamespaceLogos[$ns] ) ) {
				$logoData = $wgNamespaceLogos[$ns];
			} else {
				$logoData = $wgLogo;
			}
			if ( is_array( $logoData ) ) {
				$logopath = $logoData['path'];
				$logoURL = $logoData['url'];
			} else {
				$logoURL = $logoData;
				if ( strpos( $logoURL, $wgUploadPath ) === 0 ) {
					$logopath = $wgUploadDirectory . substr( $logoURL, strlen( $wgUploadPath ) );
				} elseif ( strpos( $logoURL, $wgStylePath ) === 0 ) {
					$logopath = $wgStyleDirectory . substr( $logoURL, strlen( $wgStylePath ) );
				} else {
					$logopath = $logoURL;
				}
			}
			self::$prev = $logopath;
			self::$chain = self::$prev;
		}

		// time to have fun :D
		wfMkdirParents( $wgUploadDirectory . '/logos' );
		$old = false;
		$ext = strtolower( substr( self::$prev, -4 ) );
		Wikimedia\suppressWarnings();
		if ( $ext == '.png' ) {
			$old = imagecreatefrompng( self::$prev );
		} elseif ( $ext == '.jpg' || $ext == 'jpeg' ) {
			$old = imagecreatefromjpeg( self::$prev );
		} elseif ( $ext == '.gif' ) {
			$old = imagecreatefromgif( self::$prev );
		}
		Wikimedia\restoreWarnings();
		if ( !$old ) {
			return Html::element( 'strong', [ 'class' => 'error' ],
				wfMessage( 'logofunctions-badstamptype', self::$prev )->inContentLanguage()->text()
			);
		}

		// hackery follows, ensure that each image (old and new) are on a 154x155 transparent canvas
		$canvX = ( is_numeric( $canvX ) && $canvX > 0 ) ? $canvX : 0;
		$canvY = ( is_numeric( $canvY ) && $canvY > 0 ) ? $canvY : 0;
		$canvas_x = max( imagesx( $old ), $canvX, 154 );
		$canvas_y = max( imagesy( $old ), $canvY, 155 );
		$old_canvas = imagecreatetruecolor( $canvas_x, $canvas_y );
		$t1 = imagecolorallocatealpha( $old_canvas, 0, 0, 0, 127 );
		imagefill( $old_canvas, 0, 0, $t1 );
		imagealphablending( $old_canvas, true );
		imagesavealpha( $old_canvas, true );
		imagecopy( $old_canvas, $old, 0, 0, 0, 0, imagesx( $old ), imagesy( $old ) );
		// resize to canvas size (yay hackiness)
		$thumb_arr = [
			'width' => $canvas_x,
			'height' => $canvas_y
		];
		if ( !is_numeric( $offX ) ) {
			$offX = 0;
		}
		if ( !is_numeric( $offY ) ) {
			$offY = 0;
		}
		$thumb = $imageobj->transform( $thumb_arr, File::RENDER_NOW );
		$new = false;
		$loc = $wgUploadDirectory . substr( $thumb->getUrl(), strlen( $wgUploadPath ) );
		$ext = strtolower( substr( $loc, -4 ) );
		Wikimedia\suppressWarnings();
		if ( $ext == '.png' ) {
			$new = imagecreatefrompng( $loc );
		} elseif ( $ext == '.jpg' || $ext == 'jpeg' ) {
			$new = imagecreatefromjpeg( $loc );
		} elseif ( $ext == '.gif' ) {
			$new = imagecreatefromgif( $loc );
		}
		Wikimedia\restoreWarnings();
		if ( !$new ) {
			imagedestroy( $old );
			imagedestroy( $old_canvas );
			return Html::element( 'strong', [ 'class' => 'error' ],
				wfMessage( 'logofunctions-badstamptype', $loc )->inContentLanguage()->text()
			);
		}

		$new_canvas = imagecreatetruecolor( $canvas_x, $canvas_y );
		$t2 = imagecolorallocatealpha( $new_canvas, 0, 0, 0, 127 );
		imagefill( $new_canvas, 0, 0, $t2 );
		imagealphablending( $new_canvas, true );
		imagesavealpha( $new_canvas, true );
		imagecopy( $new_canvas, $new, $offX, $offY, 0, 0, imagesx( $new ), imagesy( $new ) );

		imagecopy( $old_canvas, $new_canvas, 0, 0, 0, 0, $canvas_x, $canvas_y );
		self::$chain .= '##' . $logo . '#' . $offX . '#' . $offY . '#' . $canvX . '#' . $canvY;
		self::$prev = $wgUploadDirectory . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR . md5( self::$chain ) . '.png';
		imagepng( $old_canvas, self::$prev );

		// Save the new logo
		$parser->getOutput()->setProperty( 'logopath', $wgUploadPath . '/logos/' . md5( self::$chain ) . '.png' );
		$wgLogo = $wgUploadPath . '/logos/' . md5( self::$chain ) . '.png';
		self::$logo = $wgLogo;

		imagedestroy( $old );
		imagedestroy( $old_canvas );
		imagedestroy( $new );
		imagedestroy( $new_canvas );
	}

}
