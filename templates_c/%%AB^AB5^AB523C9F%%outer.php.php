<?php /* Smarty version 2.6.14, created on 2006-11-18 15:54:56
         compiled from outer.php */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'lower', 'outer.php', 4, false),)), $this); ?>
<h1>Modules!</h1>
<ul>
<?php $_from = $this->_tpl_vars['modules']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['module']):
?>
	<li><a href="<?php echo $this->_tpl_vars['module']->getLink(); ?>
"><?php echo ((is_array($_tmp=$this->_tpl_vars['module']->manifest->params['title'])) ? $this->_run_mod_handler('lower', true, $_tmp) : smarty_modifier_lower($_tmp)); ?>
</a> (<?php echo $this->_tpl_vars['module']->manifest->id; ?>
)</li>
<?php endforeach; endif; unset($_from); ?>
</ul>

<h1>Tickets!</h1>
<ul>
<?php $_from = $this->_tpl_vars['tickets']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['ticket']):
?>
	<li></li>
<?php endforeach; endif; unset($_from); ?>
</ul>