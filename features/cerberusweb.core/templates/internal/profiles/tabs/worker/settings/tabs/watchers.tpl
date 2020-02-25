{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="watchers">

<fieldset class="peek">
<legend>If I'm watching something, send me a notification when these events happen:</legend>
Select: 
<a href="javascript:;" onclick="checkAll('{$form_id}',true);">{'common.all'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="checkAll('{$form_id}',false);">{'common.none'|devblocks_translate|lower}</a>
<br>

<ul style="padding:0;margin:10px 0px 10px 0px;margin-top:10px;list-style:none;line-height:150%;">
{foreach from=$activities item=activity key=activity_point}
{$selected = !in_array($activity_point,$dont_notify_on_activities)}
<li>
	<input type="hidden" name="activity_point[]" value="{$activity_point}">
	<label style="{if $selected}font-weight:bold;{/if}">
		<input type="checkbox" name="activity_enable[]" value="{$activity_point}" {if $selected}checked="checked"{/if}> 
		{$activity.params.label_key|devblocks_translate}
	</label>
</li>
{/foreach}
</ul>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm
		.find('input:checkbox')
		.change(
			function(e) {
				if(false != $(this).prop('checked'))
					$(this).closest('label').css('font-weight','bold');
				else
					$(this).closest('label').css('font-weight','');
			}
		)
		;
	
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>