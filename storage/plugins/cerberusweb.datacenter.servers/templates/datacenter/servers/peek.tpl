<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmServer">
<input type="hidden" name="c" value="datacenter">
<input type="hidden" name="a" value="saveServerPeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;">
			</td>
		</tr>
		
		{* Owner *}
		
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.contact_person'|devblocks_translate}:</b></td>
			<td width="99%">
				<button type="button" class="chooser_contact"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="chooser-container bubbles" style="display:block;">
					{if !empty($model)}
						{$owners = $model->getOwners()}
						{if !empty($owners)}
							{foreach from=$owners item=v}
								{$contact = DAO_ContactPerson::get($v->id)}
								<li>
									{$contact->getPrimaryAddress()->getName()}
									<input type="hidden" name="owner_id[]" value="{$v->id}" />
									<a href="javascript:;" onclick="$(this).parent().remove();">
										<span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span>
									</a>
								</li>
							{/foreach}
						{/if}
					{/if}
				</ul>
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks('cerberusweb.contexts.datacenter.server', array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='cerberusweb.contexts.datacenter.server' context_id=$model->id full=true}
				{/if}
			</td>
		</tr>
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
	</div>
</fieldset>
		
{* Journal *}
{if !empty($last_journal)}
	{include file="devblocks:cerberusweb.core::internal/journal/entry.tpl" readonly=true entry=$last_journal}
{/if}

<fieldset class="peek">
	<legend>{'common.journal'|devblocks_translate|capitalize}</legend>
	<textarea name="journal" rows="5" cols="45" style="width:98%"></textarea>
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
	</div>
	<div class="additional_information" style="display:none;">
		<b>{'common.show_settings'|devblocks_translate}:</b>
		<table cellpadding="1" cellspacing="0" border="0">
			<tr>
				<td>
					{'common.show_settings.internal_and_owner'|devblocks_translate}<br />
					{'common.show_settings.public'|devblocks_translate}<br />
					{'common.show_settings.internal'|devblocks_translate}
				</td>
				<td>
					<input type="radio" checked="checked" onclick="$(this).parent().children().not(this).prop('checked', false);" /><br />
					<input type="radio" name="ispublic" value="1" onclick="$(this).parent().children().not(this).prop('checked', false);" /><br />
					<input type="radio" name="isinternal" value="1" onclick="$(this).parent().children().not(this).prop('checked', false);" />
				</td>
			</tr>
		</table><br/>
		<b>{'common.status'|devblocks_translate}:</b>
		<table cellpadding="1" cellspacing="0" border="0">
			<tr>
				<td>
					<input class="journal-state" type="radio" name="state" value="2" /><br />
					<input class="journal-state" type="radio" name="state" value="1" /><br />
					<input class="journal-state" type="radio" name="state" value="0" checked="checked" />
				</td>
				<td>
					<img id="journal-tl-0" class="journal-tl" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/traffic_light_0.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="green" />
					<img id="journal-tl-1" class="journal-tl" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/traffic_light_1.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="yellow" style="display:none;" />
					<img id="journal-tl-2" class="journal-tl" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/traffic_light_2.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="red" style="display:none;" />
				</td>
			</tr>
		</table>
	</div>
</fieldset>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmServer','{$view_id}', false, 'datacenter_server_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id && $active_worker->is_superuser}<button type="button" onclick="if(confirm('Permanently delete this server?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'frmServer','{$view_id}'); } "><span class="cerb-sprite2 sprite-minus-circle"></span> {$translate->_('common.delete')|capitalize}</button>{/if}

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=server&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'cerberusweb.datacenter.common.server'|devblocks_translate|capitalize}");
		
		$(this).find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$(this).find('button.chooser_contact').each(function() {
			ajax.chooser(this, 'cerberusweb.contexts.contact_person', 'owner_id', { autocomplete:true });
		});
		
		$(this).find('textarea[name=comment], textarea[name=journal]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
				$(this).parent().children('DIV.additional_information').show();
			} else {
				$(this).next('DIV.notify').hide();
				$(this).parent().children('DIV.additional_information').show();
			}
		});
		
		$('#frmServer button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$(this).find('input:text:first').focus();
		
		$(this).find('.journal-state').click(function() {
			var val = $(this).val();
			$('.journal-tl').hide();
			$('#journal-tl-'+val).show();
		});
	} );
</script>
