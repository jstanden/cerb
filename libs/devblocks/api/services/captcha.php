<?php
class _DevblocksCaptchaService {
	private static $_instance = null;
	
	static function getInstance() {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksCaptchaService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function createImage($phrase) {
		if(false == ($im = imagecreate(150, 70)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		$background_color = imagecolorallocate($im, 240,240,240);
		
		$dark_byte = mt_rand(20,80);
		$text_color = imagecolorallocate($im, $dark_byte,$dark_byte,$dark_byte);
		
		$x = mt_rand(15,45);
		$y = mt_rand(50,55);
		$font = DEVBLOCKS_PATH . 'resources/font/Oswald-Bold.ttf';
		$angle = mt_rand(0,15) * (mt_rand(0,1) ? 1 : -1);
		imagettftext($im, 28, $angle, $x, $y, $text_color, $font, $phrase);
		
		imagesetthickness($im, 2);
		
		$y = mt_rand(20,35);
		imageline($im, 0, $y, 150, $y, $text_color);

		$y = mt_rand(45,60);
		imageline($im, 0, $y, 150, $y, $text_color);
		
		imagesetthickness($im, 1);
		
		$x = mt_rand(0,75);
		imageline($im, $x, 0, $x, 70, $text_color);
		
		$x = mt_rand(85,150);
		imageline($im, $x, 0, $x, 70, $text_color);
		
		for($n=0;$n<100;$n++) {
			imagefilledellipse($im, mt_rand(0,150), mt_rand(0,70), 2, 2, $background_color);
		}
		
		for($n=0;$n<50;$n++) {
			imagefilledellipse($im, mt_rand(0,150), mt_rand(0,70), 3, 3, $background_color);
		}
		
		ob_start();
		imagepng($im,null);
		$image_bytes = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($im);
		
		return $image_bytes;
	}
}
