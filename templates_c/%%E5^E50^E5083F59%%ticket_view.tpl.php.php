<?php /* Smarty version 2.6.14, created on 2006-11-20 16:39:42
         compiled from file:C:%5Capachefriends%5Cxampp%5Cxampp%5Chtdocs%5Ccerb4%5Cplugins%5Ccom.cerberusweb.core/templates//dashboards/ticket_view.tpl.php */ ?>
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">My Tickets</td>
		<td nowrap="nowrap" class="tableThBlue" align="right"><a href="#" onclick="" class="tableThLink">refresh</a><span style="font-size:12px"> | </span><a href="javascript:;" class="tableThLink">search</a><span style="font-size:12px"> | </span><a href="javascript:;" onclick="getCustomize(<?php echo $this->_tpl_vars['id']; ?>
);" class="tableThLink">customize</a></td>
	</tr>
</table>
<div id="customize<?php echo $this->_tpl_vars['id']; ?>
"></div>
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">
	<tr>
		<td align="center" class="tableThBg">all</td>
		<td class="tableThBg">ID</td>
		<td class="tableThBg">Status</td>
		<td class="tableThBg">Last Wrote</td>
	</tr>

	<?php $_from = $this->_tpl_vars['tickets']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['idx'] => $this->_tpl_vars['ticket']):
?>
	<tr>
		<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value=""></td>
		<td colspan="3"><a href="index.php?c=core.module.dashboard&a=viewticket&id=<?php echo $this->_tpl_vars['ticket']->id; ?>
" class="normalLink"><b><?php echo $this->_tpl_vars['ticket']->subject; ?>
</b></a></td>
	</tr>
	<tr>
		<td><?php echo $this->_tpl_vars['ticket']->mask; ?>
</td>
		<td><?php echo $this->_tpl_vars['ticket']->status; ?>
</td>
		<td><?php echo $this->_tpl_vars['ticket']->last_wrote; ?>
</td>
	</tr>
	<tr>
		<td class="tableBg" colspan="4"></td>
	</tr>
	<?php endforeach; endif; unset($_from); ?>
	
</table>
