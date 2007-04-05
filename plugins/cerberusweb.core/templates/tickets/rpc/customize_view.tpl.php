<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveCustomize">
<div style="background-color: #EEEEEE;padding:5px;">
<h1>{$translate->_('common.customize')|capitalize}</h1>

{if is_numeric($view->id)}
	<b>{$translate->_('common.name')|capitalize}:</b> <br>
	<input type="text" name="name" value="{$view->name}" size="45"><br>
	<br>
{else}
	<input type="hidden" name="name" value="{$view->name}" size="45">
{/if}

<b>{$translate->_('dashboard.columns')|capitalize}:</b><br>
{section start=0 step=1 loop=15 name=columns}
{assign var=index value=$smarty.section.columns.index}
{math equation="x+1" x=$index format="%02d"}: 
<select name="columns[]">
	<option value=""></option> {*-- {$translate->_('dashboard.choose_column')|lower} --*}
	{foreach from=$optColumns item=optColumn}
		<option value="{$optColumn->column}" {if $view->view_columns.$index==$optColumn->column}selected{/if}>{$optColumn->name}</option>
	{/foreach}
</select>
<br>
{/section}
<br>
<b>{$translate->_('dashboard.num_rows')|capitalize}:</b> <input type="text" name="num_rows" size="3" maxlength="3" value="{$view->renderLimit}"><br>
<br>

{if $view->type == 'D'}
<label><span style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="1"> {$translate->_('dashboard.remove_view')}</span></label><br>
<br>
{/if}

<input type="button" value="{$translate->_('common.save_changes')|capitalize}" onclick="ajax.saveCustomize('{$id}');">
<input type="button" value="{$translate->_('common.cancel')|capitalize}" onclick="ajax.getCustomize('{$id}');">
<br>
<br>
</div>