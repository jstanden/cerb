<?php
/**
 * This file will convert the binary datafiles from the Text::Unidecode
 * project into a format used by Devblocks.
 *
 * When complete, move the new x*.php files to the appropriate location.
 *
 * @author Jeff Standen @ WebGroup Media <jeff@webgroupmedia.com>
 */

$bin_files = glob("*.bin");
$blocks = array();

foreach($bin_files as $bin_file) {
	$unicode_block = file_get_contents($bin_file);
	
	//var_dump($bin_file);
	
	if(!preg_match("#x(.*)\.bin#", $bin_file, $matches))
		continue;
	$highbyte = $matches[1];
	$glyph_len = ord($unicode_block[0]);
	$unicode_block = substr($unicode_block, 1);

	if(!empty($glyph_len)) {
		$glyphs = str_split($unicode_block, $glyph_len);
		
	} else {
		$glyphs = array_fill(0, 256, '');
	}
	
	foreach($glyphs as $glyph) {
		// var_dump(trim($glyph));
		$glyph = trim($glyph,"\0");
		if($glyph == '[?]')
			$glyph = '';
		$blocks[$highbyte][] = $glyph;
	}
}

foreach($blocks as $highbyte => $glyphs) {
	$fp = fopen(sprintf("x%s.php", $highbyte), "wb");
		
	fwrite($fp,
		utf8_encode(sprintf(
			"<?php\n\$glyphs = array(\n"
		))
	);
		
	foreach($glyphs as $idx => $glyph) {
		$glyph = str_replace(
			array("\\"),
			array("\\\\"),
			$glyph
		);

		$glyph = str_replace(
			array('"'),
			array('\"'),
			$glyph
		);
		
		if('00' == $highbyte) {
			if($idx <= 31 || $idx == 34 || $idx == 92 || $idx == 127 || $idx == 168) {
				if($idx == 169) {
					$idx = 22;
				}
				$hex = dechex($idx);
				if(1 == strlen($hex))
					$hex = '0'.$hex;
				$glyph = sprintf("\x%s",$hex);
			}
		}
		
		$unicode_chr = $highbyte . str_pad(dechex($idx),2,'0',STR_PAD_LEFT);
		$chr = mb_convert_encoding('&#x' . $unicode_chr . ';', 'UTF-8', 'HTML-ENTITIES');
		if('00' == $highbyte && $idx <= 31)
			$chr = '';
		
		fwrite($fp,
			sprintf(
				"\"%s\"%s // 0x%s  %s\n",
				$glyph,
				(($idx < 255) ? ',':''),
				$unicode_chr,
				$chr
			)
		);
	}
			
	fwrite($fp,
		sprintf(
			");\n"
		)
	);
	
	fclose($fp);
}
