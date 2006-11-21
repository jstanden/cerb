<?php /* Smarty version 2.6.14, created on 2006-11-19 14:30:40
         compiled from file:C:%5CProgram+Files%5Cxampp%5Chtdocs%5Ccerb4%5Cplugins%5Ccom.cerberusweb.core/templates/dashboards/index.tpl.php */ ?>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap"><h1>Dashboards</h1></td>
      <td nowrap="nowrap" width="0%"><img src="images/spacer.gif" width="10">
      	<a href="#" class="smallLink">add view</a> | 
      	<a href="#" class="smallLink">customize</a> | 
      	<a href="#" class="smallLink">remove</a> | 
      	<a href="#" class="smallLink">create new ticket</a> | 
      	<a href="#" class="smallLink">refresh</a></td>
      <td align="right" nowrap="nowrap" width="100%">
      	<b>Dashboard:</b> 
      	<select name="">
      	</select>
      	<input type="submit" value="switch"> <a href="#" class="smallLink">add dashboard</a>
      </td>
    </tr>
  </tbody>
</table>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="0%" valign="top">
      
      <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "file:".($this->_tpl_vars['path'])."/dashboards/team_loads.tpl.php", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
      <br>
      <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "file:".($this->_tpl_vars['path'])."/dashboards/mailbox_loads.tpl.php", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
      	
      </td>
      <td nowrap="nowrap" width="0%"><img src="images/spacer.gif" width="5" height="1"></td>
      <td nowrap="nowrap" width="100%" valign="top">
      
      <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "file:".($this->_tpl_vars['path'])."/dashboards/ticket_view.tpl.php", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
      <br>
      <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "file:".($this->_tpl_vars['path'])."/dashboards/whos_online.tpl.php", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
      	
      </td>
    </tr>
  </tbody>
</table>
