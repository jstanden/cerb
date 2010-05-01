{if is_null($workers)}{$workers = DAO_Worker::getAll()}{/if}
<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('common.workers')|capitalize}:</b><br>
<label><input name="worker_id[]" type="checkbox" value="0"><span style="font-weight:bold;color:rgb(0,120,0);">{$translate->_('common.nobody')}</span></label><br>
{foreach from=$workers item=worker key=worker_id}
<label><input name="worker_id[]" type="checkbox" value="{$worker_id}"><span style="color:rgb(0,120,0);">{$worker->getName()}</span></label><br>
{/foreach}

