{$peek_context = Context_JiraIssue::ID}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmJiraIssuePeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="jira_issue">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $model}
{$jira_project = $model->getProject()}
{/if}

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$model->summary}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'dao.jira_issue.jira_key'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<a href="{$jira_base_url}/browse/{$model->jira_key}" target="_blank" rel="noopener noreferrer">{$model->jira_key}</a>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'dao.jira_issue.project_id'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if $jira_project}
				{$jira_project->name}
			{/if}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'dao.jira_issue.jira_versions'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$model->jira_versions|default:'(none)'}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.type'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$model->type}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.status'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$model->status}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.created'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$model->created|devblocks_date} ({$model->created|devblocks_prettytime})</abbr>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.updated'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$model->updated|devblocks_date} ({$model->updated|devblocks_prettytime})</abbr>
		</td>
	</tr>
	
	{* Watchers *}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize|capitalize}: </td>
		<td width="100%">
			{if empty($model->id)}
				<button type="button" class="chooser_watcher"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="chooser-container bubbles" style="display:block;"></ul>
			{else}
				{$object_watchers = DAO_ContextLink::getContextLinks(Context_JiraIssue::ID, array($model->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=Context_JiraIssue::ID context_id=$model->id full_label=true}
			{/if}
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{* Description *}
<fieldset class="peek">
	<legend>{'common.description'|devblocks_translate|capitalize}</legend>
	<pre class="emailbody">{$model->description|escape:'html'|devblocks_hyperlinks nofilter}</pre>
</fieldset>

{if $active_worker->hasPriv("contexts.{$peek_context}.comment")}
<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>
{/if}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this jira issue?
	</div>
	
	<button type="button" class="delete" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	{if (!$model->id && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($model->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmJiraIssuePeek','{$view_id}', false, 'jira_issue_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=jira_issue&id={$model->id}-{$model->summary|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		var $textarea = $(this).find('textarea[name=comment]');
		
		$(this).dialog('option','title',"{'Jira Issue'|escape:'javascript' nofilter}");
		
		$(this).find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$(this).find('input:text:first').focus();

		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});
		
	});
</script>
