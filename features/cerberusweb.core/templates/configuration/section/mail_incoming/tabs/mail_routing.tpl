<fieldset id="frmSetupMailRouting" class="peek" style="margin-bottom:20px;">
	<legend>{'common.automations'|devblocks_translate|capitalize}</legend>
	
	<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_AUTOMATION_EVENT}" data-context-id="mail.route" data-edit="true">
		<span class="glyphicons glyphicons-cogwheel"></span> {'common.configure'|devblocks_translate|capitalize}
	</button>
</fieldset>

<fieldset class="peek">
	<legend>Legacy Rules (Deprecated)</legend>

	<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:10px;">
		<button type="button" onclick="genericAjaxPopup('peek','c=config&a=invoke&module=mail_incoming&action=showMailRoutingRulePanel&id=0',null,false,'50%');"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
	</form>

	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="mail_incoming">
	<input type="hidden" name="action" value="saveRouting">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
	{if !empty($rules)}
	<table cellspacing="2" cellpadding="2" style="margin-bottom:10px;">
		<tr>
			<td align="center" style="padding-right:10px;"><b>{'common.order'|devblocks_translate|capitalize}</b></td>
			<td><b>Routing Rule</b></td>
			<td align="center"><b>{'common.remove'|devblocks_translate|capitalize}</b></td>
		</tr>
		{counter start=0 print=false name=order}
		{foreach from=$rules item=rule key=rule_id name=rules}
			<tr>
				<td valign="top" align="center">
					{if $rule->is_sticky}
						<input type="hidden" name="sticky_ids[]" value="{$rule_id}">
						<input type="text" name="sticky_order[]" value="{counter name=order}" size="2" maxlength="2">
					{else}
						<i><span style="color:var(--cerb-color-background-contrast-180);font-size:80%;">(auto)</span></i>
					{/if}
				</td>
				<td style="{if $rule->is_sticky}border:1px solid rgb(255,215,0);{else}{/if}padding:5px;">
					<a href="javascript:;" onclick="genericAjaxPopup('peek','c=config&a=invoke&module=mail_incoming&action=showMailRoutingRulePanel&id={$rule_id}',null,false,'50%');"><b>{$rule->name}</b></a>
					<br>
					
					{foreach from=$rule->criteria item=crit key=crit_key}
						{if $crit_key=='tocc'}
							To/Cc = <b>{$crit.value}</b><br>
						{elseif $crit_key=='from'}
							From = <b>{$crit.value}</b><br>
						{elseif $crit_key=='subject'}
							Subject = <b>{$crit.value}</b><br>
						{elseif 'header'==substr($crit_key,0,6)}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='body'}
							Body = <b>{$crit.value}</b><br>
						{elseif $crit_key=='dayofweek'}
							Day of Week is 
								{foreach from=$crit item=day name=timeofday}
								<b>{$day}</b>{if !$smarty.foreach.timeofday.last} or {/if}
								{/foreach}
								<br>
						{elseif $crit_key=='timeofday'}
							{$from_time = explode(':',$crit.from)}
							{$to_time = explode(':',$crit.to)}
							Time of Day 
								<i>between</i> 
								<b>{$from_time[0]|string_format:"%d"}:{$from_time[1]|string_format:"%02d"}</b> 
								<i>and</i> 
								<b>{$to_time[0]|string_format:"%d"}:{$to_time[1]|string_format:"%02d"}</b> 
								<br>
						{elseif 0==strcasecmp('cf_',substr($crit_key,0,3))}
							{include file="devblocks:cerberusweb.core::internal/custom_fields/filters/render_criteria_list.tpl"}
						{/if}
					{/foreach}
					
					<blockquote style="margin:2px;margin-left:20px;font-size:95%;color:var(--cerb-color-background-contrast-100);">
						{foreach from=$rule->actions item=action key=action_key}
							{if $action_key=="move"}
								{assign var=g_id value=$action.group_id}
								{if isset($groups.$g_id)}
									Move to 
									<b>{$groups.$g_id->name}</b>
								{/if}
								<br>
							{elseif 0==strcasecmp('cf_',substr($action_key,0,3))}
								{include file="devblocks:cerberusweb.core::internal/custom_fields/filters/render_action_list.tpl"}
							{/if}
						{/foreach}
					<span>(Matched {$rule->pos} new messages)</span><br>
					</blockquote>
				</td>
				<td valign="top" align="center">
					<label><input type="checkbox" name="deletes[]" value="{$rule_id}">
					<input type="hidden" name="ids[]" value="{$rule_id}">
				</td>
			</tr>
		{/foreach}
	</table>

	<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{/if}
	</form>
</fieldset>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupMailRouting');
	$frm.find('.cerb-peek-trigger').cerbPeekTrigger();
});
</script>