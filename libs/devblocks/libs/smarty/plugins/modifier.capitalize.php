<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */


/**
 * Smarty capitalize modifier plugin
 *
 * Type:     modifier<br>
 * Name:     capitalize<br>
 * Purpose:  capitalize words in the string
 * @link http://smarty.php.net/manual/en/language.modifiers.php#LANGUAGE.MODIFIER.CAPITALIZE
 *      capitalize (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @return string
 */
function smarty_modifier_capitalize($string)
{
	// Jeff@WGM
	if(extension_loaded('mbstring')) {
		return mb_convert_case($string, MB_CASE_TITLE);
	} else {
		return $string;
	}
}
?>
