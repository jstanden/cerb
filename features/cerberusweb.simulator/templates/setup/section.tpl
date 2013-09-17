<h2>{'simulator.common'|devblocks_translate|capitalize}</h2>

<fieldset>
	<legend>{'simulator.ui.cfg.sample_tickets'|devblocks_translate}</legend>

	<form method="post" action="{devblocks_url}{/devblocks_url}" id="simulatorMailForm">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="simulator">
	<input type="hidden" name="action" value="generateTicketsJson">
	
	<table cellpadding="5" cellspacing="0" border="0">
		<tr>
			<td>
				<b>{'simulator.ui.cfg.to_address'|devblocks_translate}</b><br>
				<input type="text" name="address" value="{$address}" size="45">
			</td>
			<td>
				<b>{'simulator.ui.cfg.flavor'|devblocks_translate}</b><br>
				<select name="dataset">
					{foreach from=$flavors item=flavor key=flavor_id}
						<option value="{$flavor_id}">{$flavor}
					{/foreach}
				</select>
			</td>
			<td>
				<b>{'simulator.ui.cfg.how_many'|devblocks_translate}</b><br>
				<input type="text" name="how_many" size="4" maxlength="3" value="10">
			</td>
		</tr>
	</table>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-gear"></span> {'simulator.ui.cfg.generate'|devblocks_translate}</button>
	</form>
</fieldset>

<script type="text/javascript">
	$('#simulatorMailForm BUTTON.submit')
	.click(function(e) {
		Devblocks.showSuccess('#simulatorMailForm div.status', "{'simulator.ui.cfg.generate_wait'|devblocks_translate}", false, false);
		
		genericAjaxPost('simulatorMailForm','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#simulatorMailForm div.status',$o.error);
			} else {
				Devblocks.showSuccess('#simulatorMailForm div.status',$o.message);
			}
		});
	})
	;	
</script>

<fieldset>
	<legend>Create Sample Tasks</legend>

	<form method="post" action="{devblocks_url}{/devblocks_url}" id="simulatorTaskForm">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="simulator">
	<input type="hidden" name="action" value="generateTasksJson">
	
	<table cellpadding="5" cellspacing="0" border="0">
		<tr>
			{*
			<td>
				<b>{'simulator.ui.cfg.to_address'|devblocks_translate}</b><br>
				<input type="text" name="address" value="{$address}" size="45">
			</td>
			<td>
				<b>{'simulator.ui.cfg.flavor'|devblocks_translate}</b><br>
				<select name="dataset">
					{foreach from=$flavors item=flavor key=flavor_id}
						<option value="{$flavor_id}">{$flavor}
					{/foreach}
				</select>
			</td>
			*}
			<td>
				<b>{'simulator.ui.cfg.how_many'|devblocks_translate}</b><br>
				<input type="text" name="how_many" size="4" maxlength="3" value="10">
			</td>
		</tr>
	</table>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-gear"></span> {'simulator.ui.cfg.generate'|devblocks_translate}</button>
	</form>
</fieldset>

<script type="text/javascript">
	$('#simulatorTaskForm BUTTON.submit')
	.click(function(e) {
		Devblocks.showSuccess('#simulatorTaskForm div.status', "{'simulator.ui.cfg.generate_wait'|devblocks_translate}", false, false);
		
		genericAjaxPost('simulatorTaskForm','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#simulatorTaskForm div.status',$o.error);
			} else {
				Devblocks.showSuccess('#simulatorTaskForm div.status',$o.message);
			}
		});
	})
	;	
</script>

<fieldset>
	<legend>Create Sample Organizations</legend>

	<form method="post" action="{devblocks_url}{/devblocks_url}" id="simulatorOrgForm">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="simulator">
	<input type="hidden" name="action" value="generateOrgsJson">
	
	<table cellpadding="5" cellspacing="0" border="0">
		<tr>
			{*
			<td>
				<b>{'simulator.ui.cfg.to_address'|devblocks_translate}</b><br>
				<input type="text" name="address" value="{$address}" size="45">
			</td>
			<td>
				<b>{'simulator.ui.cfg.flavor'|devblocks_translate}</b><br>
				<select name="dataset">
					{foreach from=$flavors item=flavor key=flavor_id}
						<option value="{$flavor_id}">{$flavor}
					{/foreach}
				</select>
			</td>
			*}
			<td>
				<b>{'simulator.ui.cfg.how_many'|devblocks_translate}</b><br>
				<input type="text" name="how_many" size="4" maxlength="3" value="10">
			</td>
		</tr>
	</table>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-gear"></span> {'simulator.ui.cfg.generate'|devblocks_translate}</button>
	</form>
</fieldset>

<script type="text/javascript">
	$('#simulatorOrgForm BUTTON.submit')
	.click(function(e) {
		Devblocks.showSuccess('#simulatorOrgForm div.status', "{'simulator.ui.cfg.generate_wait'|devblocks_translate}", false, false);
		
		genericAjaxPost('simulatorOrgForm','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#simulatorOrgForm div.status',$o.error);
			} else {
				Devblocks.showSuccess('#simulatorOrgForm div.status',$o.message);
			}
		});
	})
	;	
</script>
