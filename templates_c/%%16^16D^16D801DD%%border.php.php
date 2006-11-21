<?php /* Smarty version 2.6.14, created on 2006-11-19 14:02:27
         compiled from border.php */ ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.tpl.php", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<table cellspacing="0" cellpadding="2" border="0" width="100%">
	<tr>
		<td align="left" valign="bottom"><img src="images/logo.jpg"></td>
		<td align="right" valign="bottom">
		<?php if (empty ( $this->_tpl_vars['visit'] )): ?>
		Not signed in [<a href="?c=core.module.signin&a=show">sign in</a>]
		<?php else: ?>
		Signed in as <b><?php echo $this->_tpl_vars['visit']->login; ?>
</b> [<a href="?c=core.module.signin&a=signout">sign off</a>]
		<?php endif; ?>
		</td>
	</tr>
</table>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "menu.tpl.php", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<?php if (! empty ( $this->_tpl_vars['module'] )): ?>
	<?php echo $this->_tpl_vars['module']->render(); ?>

<?php else: ?>
	No module selected.
<?php endif; ?>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.tpl.php", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>