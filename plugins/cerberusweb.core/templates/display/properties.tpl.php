<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="updateProperties">
<input type="hidden" name="id" value="{$ticket->id}">
<table class="tableGreen" border="0" cellpadding="2" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td class="tableThGreen" nowrap="nowrap"> <img src="{devblocks_url}images/folder_gear.gif{/devblocks_url}"> Properties</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Status:</b><br>
      	<select name="status">
      		<option value="O" {if $ticket->status=='O'}selected{/if}>{$translate->_('status.open')|lower}
      		<option value="W" {if $ticket->status=='W'}selected{/if}>{$translate->_('status.waiting')|lower}
      		<option value="C" {if $ticket->status=='C'}selected{/if}>{$translate->_('status.closed')|lower}
      		<option value="D" {if $ticket->status=='D'}selected{/if}>{$translate->_('status.deleted')|lower}
      	</select>
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Priority:</b><br>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td align="center"><label for="priority0"><img src="{devblocks_url}images/star_alpha.gif{/devblocks_url}" width="16" height="16" border="0" title="None" alt="No Priority"></label></td>
					<td align="center"><label for="priority3"><img src="{devblocks_url}images/star_green.gif{/devblocks_url}" width="16" height="16" border="0" title="{$translate->_('priority.low')}" alt="{$translate->_('priority.low')}"></label></td>
					<td align="center"><label for="priority4"><img src="{devblocks_url}images/star_yellow.gif{/devblocks_url}" width="16" height="16" border="0" title="{$translate->_('priority.moderate')}" alt="{$translate->_('priority.moderate')}"></label></td>
					<td align="center"><label for="priority5"><img src="{devblocks_url}images/star_red.gif{/devblocks_url}" width="16" height="16" border="0" title="{$translate->_('priority.high')}" alt="{$translate->_('priority.high')}"></label></td>
				</tr>
				<tr>
					<td align="center"><input id="priority0" type="radio" name="priority" value="0" {if $ticket->priority==0}checked{/if}></td>
					<td align="center"><input id="priority3" type="radio" name="priority" value="25" {if $ticket->priority==25}checked{/if}></td>
					<td align="center"><input id="priority4" type="radio" name="priority" value="50" {if $ticket->priority==50}checked{/if}></td>
					<td align="center"><input id="priority5" type="radio" name="priority" value="75" {if $ticket->priority==75}checked{/if}></td>
				</tr>
			</table>      	
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Owner:</b> (Team/Category)<br>
      	<select name="category_id">
      		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
      		<optgroup label="Team (No Category)">
      		{foreach from=$teams item=team}
      			<option value="t{$team->id}" {if $t_or_c=='t' && $ticket->team_id==$team->id}selected{/if}>{$team->name}</option>
      		{/foreach}
      		</optgroup>
      		{foreach from=$team_categories item=categories key=teamId}
      			{assign var=team value=$teams.$teamId}
      			<optgroup label="{$team->name}">
      			{foreach from=$categories item=category}
    				<option value="c{$category->id}" {if $t_or_c=='c' && $ticket->category_id==$category->id}selected{/if}>{$category->name}</option>
    			{/foreach}
    			</optgroup>
     		{/foreach}
      	</select>
     	</td>
    </tr>
    <tr>
      <td>
      	<b class="green">Spam Probability:</b><br>
			<table cellpadding="1" cellspacing="0">
				<tr>
					<td style="{if $ticket->spam_score < .90}background-color:rgb(0,200,0){else}background-color:rgb(200,0,0){/if};padding:3px;"><b style="color:rgb(255,255,255);">{math equation="x*100" format="%0.2f" x=$ticket->spam_score}%</b></td>
					<td>
						{if !empty($ticket->spam_training)}
							&nbsp;{if $ticket->spam_training=='N'}Marked as Not Spam{else}Marked as Spam{/if}
						{else}
							<select name="training">
								<option value="N">This is Not Spam
								<option value="S" {if $ticket->spam_score >= 0.90}selected{/if}>This is Spam
							</select>
						{/if}
					</td>
				</tr>
			</table>
     	</td>
    </tr>
    <!---
    <tr>
      <td>
      	<b class="green">Due:</b><br>
      	<input name="due" type="text">
			<a href="javascript:;"><img src="{devblocks_url}images/icon_calendar.gif{/devblocks_url}" width="16" height="16" border="0" align="absmiddle"></a>
     	</td>
    </tr>
    --->
    <!-- 
    <tr>
      <td nowrap="nowrap">
      	<b class="green">Add Tags:</b><br>
      	<input type="text" name="tags" value="" size="22">
      	<input type="button" value="..." onclick="genericAjaxPanel('c=tickets&a=showTagChooserPanel',this,true,'500px');">
     	</td>
    </tr>
    <tr>
      <td nowrap="nowrap">
      	<b class="green">Add Workers:</b> (by e-mail)<br>
      	<input type="text" name="workers" value="" size="22">
      	<input type="button" value="..." onclick="genericAjaxPanel('c=tickets&a=showWorkerChooserPanel',this,true,'500px');">
     	</td>
    </tr>
     -->
    <tr>
      <td>
      	<b class="green">Subject:</b><br>
      	<input type="text" name="subject" value="{$ticket->subject|escape:"htmlall"}" size="25">
     	</td>
    </tr>
    <tr>
    	<td align="right">
    		<input type="submit" value="Update Properties">
    	</td>
    </tr>
  </tbody>
</table>