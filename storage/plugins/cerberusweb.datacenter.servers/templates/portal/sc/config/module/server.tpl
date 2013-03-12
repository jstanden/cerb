<b>{$translate->_('portal.sc.cfg.datacenter.status_url')}:</b><br>
<input type="text" size="65" value="{$dc_status_url}" name="dc_status_url">
<br>
<br>
<b>{$translate->_('portal.sc.cfg.datacenter.show')}:</b><br>
<label><input type="radio" name="dc_show_mode" value="0" {if !$dc_show_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.datacenter.just_public')}</label>
<label><input type="radio" name="dc_show_mode" value="1" {if 1==$dc_show_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.datacenter.just_own')}</label>
<label><input type="radio" name="dc_show_mode" value="2" {if 2==$dc_show_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.datacenter.own_and_public')}</label>
<br>
<br>