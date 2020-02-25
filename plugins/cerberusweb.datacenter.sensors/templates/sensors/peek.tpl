{$peek_context = CerberusContexts::CONTEXT_SENSOR}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSensor" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="sensor">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top" align="right">{'common.name'|devblocks_translate|capitalize}:</td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top" align="right">{'common.tag'|devblocks_translate|capitalize}:</td>
		<td width="99%">
			<input type="text" name="tag" value="{$model->tag}" style="width:98%;">
		</td>
	</tr>
	
	{if !empty($sensor_manifests)}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top" align="right">{'dao.datacenter_sensor.extension_id'|devblocks_translate|capitalize}:</td>
		<td width="99%">
			<select name="extension_id">
				{foreach from=$sensor_manifests item=sensor_manifest key=k}
					<option value="{$k}" {if $k==$model->extension_id}selected="selected"{/if}>{$sensor_manifest->name}</option>
				{/foreach}
			</select>
			
			<div class="params" style="margin:0px 5px;padding:5px;background-color:rgb(245,245,245);">
				{if isset($sensor_manifests.{$model->extension_id})}
					{$sensor_ext_id = $model->extension_id}
				{else}
					{$sensor_ext_id = 'cerberusweb.datacenter.sensor.external'}
				{/if}
				
				{$sensor_ext = $sensor_manifests.{$sensor_ext_id}->createInstance()}
				{if method_exists($sensor_ext,'renderConfig')}
					{$sensor_ext->renderConfig($model->params)}
				{/if}
			</div>
		</td>
	</tr>
	{/if}
	
	{* Watchers *}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			{if empty($model->id)}
				<button type="button" class="chooser_watcher"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="chooser-container bubbles" style="display:block;"></ul>
			{else}
				{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_SENSOR, array($model->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_SENSOR context_id=$model->id full_label=true}
			{/if}
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{* Comment *}
{if !empty($last_comment)}
<div id="comment{$last_comment->id}">
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
</div>
{/if}

{if (!$model->id && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($model->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmSensor','{$view_id}', false, 'datacenter_sensor_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>{/if}
{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="if(confirm('Permanently delete this sensor?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'frmSensor','{$view_id}'); } "><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'datacenter.sensors.common.sensor'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$popup.find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$popup.find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
		
		$('#frmSensor button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$popup.find('select[name=extension_id]').change(function() {
			genericAjaxGet($(this).next('DIV.params'), 'c=profiles&a=invoke&module=sensor&action=renderConfigExtension&extension_id=' + $(this).val() + "&sensor_id={$model->id}");
		});
		
		$popup.find('input:text:first').focus();
	});
});
</script>
