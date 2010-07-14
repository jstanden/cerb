<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFindStringsEntry">
<input type="hidden" name="c" value="translators">
<input type="hidden" name="a" value="saveFindStringsPanel">

This will find text defined in U.S. English and not yet translated to other languages.  
Leaving new text blank allows you to easily find translation work with a search.
<br>
<br>

{if count($codes) > 1}
<table cellspacing="0" cellpadding="2" border="0">
<tr>
	<td><b>Language</td>
	<td style="padding-left:10px;"><b>With new text to translate...</td>
</tr>
{foreach from=$codes key=code item=lang_name}
{if $code != 'en_US'}
	<tr>
	<td>
		{$lang_name}
		<input type="hidden" name="lang_codes[]" value="{$code}">
	</td>
	
	<td style="padding-left:10px;">
		<select name="lang_actions[]">
			<option value="">- leave blank -</option>
			<option value="en_US">Copy U.S. English</option>
		</select>
	</td>
	</tr>
{/if}
{/foreach}
</table>
<br>
{else}
<br>
<b>You have no non-English languages defined.</b><br>
<br>
{/if}

{if count($codes) > 1}<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if}

</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{$translate->_('common.synchronize')|capitalize|escape:'quotes'}");
	} );
</script>
