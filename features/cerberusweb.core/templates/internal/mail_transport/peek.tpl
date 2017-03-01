<form action="javascript:;" method="post" class="peek-mail-transport" onsubmit="return false;">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek" style="margin-bottom:0px;">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;">
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'mail.transport'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<select name="extension_id">
					<option value=""></option>
					{foreach from=$extensions item=extension}
					<option value="{$extension->id}" {if $extension->id == $model->extension_id}selected="selected"{/if}>{$extension->manifest->name}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.default'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<label><input type="checkbox" name="is_default" value="1" {if $model->is_default}checked="checked"{/if}> Always use this transport when no others are specified</label>
			</td>
		</tr>
	</table>
</fieldset>

{$extension = $extensions.{$model->extension_id}}

<div class="mail-transport-params">
{if $extension}
{$extension->renderConfig($model)}
{/if}
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context='cerberusweb.contexts.mail.transport' context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this mail transport?
	</div>
	
	<button type="button" class="delete"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="popup-status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id) && !$model->is_default}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=mail_transport&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Mail Transport'|escape:'javascript' nofilter}");
		
		$popup.find('select[name=extension_id]').change(function() {
			// Load the parameters for the given transport extension
			genericAjaxPost(
				$popup.find('form.peek-mail-transport'),
				$popup.find('div.mail-transport-params'),
				'c=config&a=handleSectionAction&section=mail_smtp&action=getTransportParams'
			);
		});
		
		$popup.find('button.delete').click(function() {
			var $frm=$popup.find('form');
			$frm.find('input:hidden[name=do_delete]').val('1');
			
			genericAjaxPost(
				$popup.find('form.peek-mail-transport'),
				null,
				'c=config&a=handleSectionAction&section=mail_smtp&action=saveTransportPeek',
				function() {
					genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
					genericAjaxPopupClose('peek', 'mail_transport_save');
				}
			);
		});
		
		$popup.find('button.submit').click(function() {
			// Test and verify the settings, then allow the popup to submit
			genericAjaxPost(
				$popup.find('form.peek-mail-transport'),
				null,
				'c=config&a=handleSectionAction&section=mail_smtp&action=testTransportParams',
				function(json) {
					if(false == json || false == json.status) {
						Devblocks.showError($popup.find('div.popup-status'),json.error);
					} else {
						$popup.find('div.popup-status').hide().html('');
						
						genericAjaxPost(
							$popup.find('form.peek-mail-transport'),
							null,
							'c=config&a=handleSectionAction&section=mail_smtp&action=saveTransportPeek',
							function() {
								genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
								genericAjaxPopupClose('peek', 'mail_transport_save');
							}
						);
					}
				}
			);
		});
		
		$(this).find('input:text:first').focus();
	});
});
</script>