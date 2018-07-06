{$peek_context = CerberusContexts::CONTEXT_SKILL}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSkillPeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="skill">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.skillset'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<select name="skillset_id">
				{foreach from=$skillsets item=skillset key=skillset_id}
				<option value="{$skillset_id}" {if $model->skillset_id == $skillset_id}selected="selected"{/if}>{$skillset->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this skill?
	</div>
	
	<button type="button" class="delete" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	{if (!$model->id && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($model->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmSkillPeek','{$view_id}', false, 'skill_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if}
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=skill&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'common.skill'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$(this).find('input:text:first').focus();
	});
</script>
