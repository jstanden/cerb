<div class="block">
<h2>Introducing Workspaces</h2>

<table>
	<tr>
		<td valign="top">
			While you're working in Cerb5 you'll often find yourself jumping around between various lists and searches: tickets, tasks, opportunities, etc.  You can group all those lists together as a "workspace" and see that information on the same screen.  This is incredibly powerful when combined with custom field functionality; you can create temporary workspaces based on your daily projects, quickly build and save your worklists using searches, perform your duties, and then toss the workspace at the end of the day.  You can also keep workspaces around permanently to build your own workflow.
			<br>
			<br>
			
			<h3>Step 1:</h3>
			<blockquote>
				Creating a new worklist is easy; just run a search with your desired filters as you normally would.  When the results display you'll see a 'copy' link in the blue header of the list.  Click that link to copy the list to one of your workspaces.  When copying a list you're grabbing a copy of the settings for that list, not a copy of the current results, which ensures the list will always display the latest content when used on your workspace.
				<br>
				<br>
				<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/home/workspaces/workspaces_intro3.png{/devblocks_url}" align="top" style="padding:5px;border:1px solid rgb(200,200,200);">
			</blockquote>
	
			<h3>Step 2:</h3>
			<blockquote>
				Once you click 'copy' you'll be given the option to save the list to a new or existing workspace.  You can also choose a more useful name.  Once you've made your selections, click the 'Save Changes' button.
				<br>
				<br>
				<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/home/workspaces/workspaces_intro2.png{/devblocks_url}" align="top" style="padding:5px;border:1px solid rgb(200,200,200);">
			</blockquote>
			
			<h3>Step 3:</h3>
			<blockquote>
				Now you have a new workspace in your 'home' page.  When you click the tab all your worklists will display on the same page.  If you want to change the layout or filters of any list you can click the 'customize' link in the blue header and make your adjustments.  You can change the order of the lists on your workspace by using the 'reorder lists' link in the top right of your tab.
				<br>
				<br>
				<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/home/workspaces/workspaces_intro1.png{/devblocks_url}" align="top" style="padding:5px;border:1px solid rgb(200,200,200);">
			</blockquote>
			
			<h3>Let's make your first workspace!</h3>
			<blockquote>
				The most common use for workspaces is to show a list of assigned work.  Click the button below to create a new workspace showing your assigned mail and tasks.  If you're responsible for other activities as well, use what you learned above to add more content to the workspace.
				<br>
				<br>
				<form action="{devblocks_url}{/devblocks_url}" method="POST">
				<input type="hidden" name="c" value="home">
				<input type="hidden" name="a" value="doWorkspaceInit">
				<button type="submit"><span class="cerb-sprite sprite-add"></span> Create your first workspace</button>
				</form>
			</blockquote>
		</td>
	</tr>
</table>

</div>