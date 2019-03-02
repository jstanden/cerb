<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/
class ChSimulatorTour extends DevblocksHttpResponseListenerExtension {
	function run(DevblocksHttpResponse $response, Smarty $tpl) {
		$path = $response->path;

		switch(array_shift($path)) {
			case 'config':
				switch(array_shift($path)) {
					case 'simulator':
						$tour = array(
							'title' => 'Simulator',
							'body' => "With the Simulator you can create any number of high-quality sample tickets, which allows you to immediately experiment with how the helpdesk works. Sample tickets may be created in various \"flavors\", such as Retail or Spam.  These flavors allow you to test your FAQ, e-mail templates and anti-spam filtering.",
							'callouts' => array(
							),
						);
						break;
				}
				break;
		}
		
		if(!empty($tour))
			$tpl->assign('tour', $tour);
	}
};
