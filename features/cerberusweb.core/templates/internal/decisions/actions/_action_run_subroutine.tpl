<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[subroutine]">
		<option value=""></option>
		{foreach from=$subroutines item=subroutine}
		<option value="{$subroutine->id}" {if $params.subroutine == $subroutine->id}selected="selected"{/if}>{$subroutine->title}</option>
		{/foreach}
	</select>
</div>