<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmWatcherPrefs">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveWatcherTab">

<fieldset>
<legend>If I'm watching something, send me a notification when these events happen:</legend>
Select: 
<a href="javascript:;" onclick="checkAll('frmWatcherPrefs',true);">{'common.all'|devblocks_translate|lower}</a>
 | <a href="javascript:;" onclick="checkAll('frmWatcherPrefs',false);">{'common.none'|devblocks_translate|lower}</a>
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
	
<button type="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	
</fieldset>
</form>

<script type="text/javascript">
$('#frmWatcherPrefs')
	.find('input:checkbox')
	.change(
		function(e) {
			if(false != $(this).attr('checked'))
				$(this).closest('label').css('font-weight','bold');
			else
				$(this).closest('label').css('font-weight','');
		}
	)
	;
</script>