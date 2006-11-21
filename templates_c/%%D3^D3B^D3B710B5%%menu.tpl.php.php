<?php /* Smarty version 2.6.14, created on 2006-11-20 15:28:57
         compiled from menu.tpl.php */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'lower', 'menu.tpl.php', 5, false),)), $this); ?>
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="headerMenu">
	<tr>
		<?php $_from = $this->_tpl_vars['modules']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['module']):
?>
			<?php if (! empty ( $this->_tpl_vars['module']->manifest->params['menutitle'] )): ?>
				<td width="0%" nowrap="nowrap" <?php if ($this->_tpl_vars['activeModule'] == $this->_tpl_vars['module']->id): ?>id="headerMenuSelected"<?php endif; ?>><img src="images/spacer.gif" width="10" height="1"><a href="<?php echo $this->_tpl_vars['module']->getLink(); ?>
"><?php echo ((is_array($_tmp=$this->_tpl_vars['module']->manifest->params['menutitle'])) ? $this->_run_mod_handler('lower', true, $_tmp) : smarty_modifier_lower($_tmp)); ?>
</a><img src="images/spacer.gif" width="10" height="1"></td>
				<td width="0%" nowrap="nowrap" valign="bottom"><img src="images/menuSep.gif"></td>
			<?php endif; ?>
		<?php endforeach; endif; unset($_from); ?>
		<td width="100%"><img src="images/spacer.gif" height="22" width="1"></td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr><td class="headerUnderline"><img src="images/spacer.gif" height="5" width="1"></td></tr>
</table>
<img src="images/spacer.gif" height="5" width="1"><br>