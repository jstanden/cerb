<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	{*
	<div style="padding:5px;">
	<a href="{devblocks_url}c=tickets&a=overview{/devblocks_url}">overview</a>
	*} 
	</div>
</div> 

<H1>Welcome to Cerberus Helpdesk!</H1>

<p><b>Cerberus Helpdesk 4.0</b> (Cerb4) is a group-based webmail project that has been evolving since January 2002.  We started the project because we couldn't find an existing PHP-based solution that seemed to be conscious enough about performance and usability.  We didn't have a spectacularly large volume of e-mail to deal with at the time, but that only made it more surprising that the available solutions were failing us as badly as they were.</p>
<p>It has taken us six years of immersion, and tens of thousands of conversations, to really appreciate how much energy it takes to find elegant solutions -- concepts that are simple to explain, but remain flexible enough to be used in creatively unexpected ways.</p>
<p>What you see in the project today is a distillation of these elegant solutions from our six years of experimentation.  If you're accustomed to complexity as a measure of software's maturity, things may look too simple at first glance.  Don't let the simplicity fool you.</p> 

<div style="padding:5px;color:rgb(7,63,134);font-size:14pt;">
	Elegant solutions look simple in hindsight.
</div>

<p>You only need to understand a few concepts to get started:</p>
<ul style="list-style:square;">
	<li><p>A <b>ticket</b> is a specific e-mail conversation and all the related data about a question or issue.  Each ticket has a unique identifier for future reference by anyone involved.</p></li>
	<li><p>The people on the originating end of tickets are called <b>requesters</b>.  A ticket can have multiple requesters.</p></li>
	<li><p>The people on the answering end of tickets are called <b>workers</b>.</p></li>
	<li><p>A <b>watcher</b> is a worker who receives copies of messages.  For example, a supervisor may be a watcher to monitor the quality of the messages workers are writing back to requesters.</p></li>
	<li><p>The <b>helpdesk</b> is a software hub for centrally managing and archiving tickets, and routing messages between workers and requesters.  This allows several workers to receive and share e-mail without requesters writing to any of them individually.</p></li>
	<li><p>A <b>bucket</b> is a container for storing similar tickets.  Common buckets are: Leads, Receipts, Newsletters, Refunds and Spam.</p></li>
	<li><p>A <b>group</b> is several workers who share responsibility for the same tickets and buckets.  Common groups are: Sales, Support, Development, Billing and Corporate.  These examples are departments, but groups can be related by anything.</p></li>
	<li><p>A worker in a group is called a <b>member</b>.  A member with the authority to modify the group is called a <b>manager</b>.  Groups can have any number of managers.</p></li>
	<li><p>Each group has an <b>inbox</b> where new tickets are delivered by default.  These tickets are then moved into buckets either automatically by the helpdesk or by workers.</p></li>
</ul>
