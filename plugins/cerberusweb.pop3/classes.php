<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
/*
 * [JAS]: [TODO] This really belongs in CORE, though it was a good
 * plugin exercise.
 */
class Pop3Cron extends CerberusCronPageExtension {
    function run() {
        if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
        @set_time_limit(0); // Unlimited (if possible)
        @ini_set('memory_limit','64M');

        $accounts = DAO_Mail::getPop3Accounts(); /* @var $accounts CerberusPop3Account[] */

        $timeout = ini_get('max_execution_time');
        $max_downloads = ($timeout) ? 20 : 50; // [TODO] Make a job setting?
        
        // [JAS]: Make sure our output directory is writeable
	    if(!is_writable(APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR)) {
	        echo "The mail storage directory is not writeable.  Skipping POP3 download.<br>";
	        return;
	    }
        
        foreach ($accounts as $account) { /* @var $account CerberusPop3Account */
            if(!$account->enabled)
                continue;
            
            echo 'Account being parsed is ', $account->nickname, '<br>';
            echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ,' secs<br>';
            echo 'Memory Limit: ', ini_get('memory_limit') ,'<br>';
            	
            switch($account->protocol) {
                default:
                case 'pop3': // 110
                    $connect = sprintf("{%s:%d/pop3/notls}INBOX",
                    $account->host,
                    $account->port
                    );
                    break;
                     
                case 'pop3-ssl': // 995
                    $connect = sprintf("{%s:%d/pop3/ssl/novalidate-cert}INBOX",
                    $account->host,
                    $account->port
                    );
                    break;
                     
                case 'imap': // 143
                    $connect = sprintf("{%s:%d/notls}INBOX",
                    $account->host,
                    $account->port
                    );
                    break;
            }

            $runtime = microtime(true);
             
            $mailbox = imap_open($connect,
            !empty($account->username)?$account->username:"",
            !empty($account->password)?$account->password:"")
            or die("Failed with error: ".imap_last_error());
            	
            $messages = array();
            $check = imap_check($mailbox);
            	
            // [TODO] Make this an account setting?
            $total = min($max_downloads,$check->Nmsgs);
            	
            //			$info = imap_fetch_overview($mailbox,"1:$total",0);
            //			print_r($info);echo "<BR>";
            	
            echo 'Init time: ',((microtime(true)-$runtime)*1000),' ms<br>';

            echo "<BR>";
            flush();

            $runtime = microtime(true);

            for($i=1;$i<=$total;$i++) {
                //			foreach($info as $msg) {
                /*
                * [TODO] Logic for max message size (>1MB, etc.) handling.  If over a
                * threshold then use the attachment parser (imap_fetchstructure) to toss
                * non-plaintext until the message fits.
                */
                 
                //	            $msgno = $msg->msgno;
                $msgno = $i;
                echo "<b>Downloading message ",$msgno,"</b> ";
                flush();
                 
                $time = microtime(true);
                 
                //			    $struct = imap_fetchstructure($mailbox, $i);
                //			    echo "Length: ",$msg->size,"<BR>";
                 
                $headers = imap_fetchheader($mailbox, $msgno);
                //				print_r($headers);

                $body = imap_body($mailbox, $msgno);
                //                $runtime_bytes += strlen($body);
                //				echo "Length: ",strlen($body),"<BR>";

                // [TODO] [JAS]: We should really download into the temp directory and
	            // move it once we're successful.  A parser running in another thread 
	            // could see our partial file and move it.
                
                do {
                    $filename = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR . CerberusMail::generateMessageFilename();
                } while(file_exists($filename));
                $fp = fopen($filename,'w');

                if($fp) {
                    fwrite($fp,$headers,strlen($headers));
                    fwrite($fp,"\r\n\r\n");
                    fwrite($fp,$body,strlen($body));
                    @fclose($fp);
                }

                unset($headers);
                unset($body);

                $time = microtime(true) - $time;
                echo "(",sprintf("%d",($time*1000))," ms)<br>";
                flush();
                imap_delete($mailbox, $msgno);
                continue;
            }
            	
            imap_expunge($mailbox);
            imap_close($mailbox);
            imap_errors();
            	
            echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>";
        }
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$pop3_accounts = DAO_Mail::getPop3Accounts();
		$tpl->assign('pop3_accounts', $pop3_accounts);
		
		$tpl->display($tpl_path . 'cron/pop3/config.tpl.php');
    }
    
    function saveConfigurationAction() {
		@$ar_ids = DevblocksPlatform::importGPC($_POST['account_id'],'array');
		@$ar_enabled = DevblocksPlatform::importGPC($_POST['pop3_enabled'],'array');
		@$ar_nickname = DevblocksPlatform::importGPC($_POST['nickname'],'array');
		@$ar_protocol = DevblocksPlatform::importGPC($_POST['protocol'],'array');
		@$ar_host = DevblocksPlatform::importGPC($_POST['host'],'array');
		@$ar_username = DevblocksPlatform::importGPC($_POST['username'],'array');
		@$ar_password = DevblocksPlatform::importGPC($_POST['password'],'array');
		@$ar_port = DevblocksPlatform::importGPC($_POST['port'],'array');
		@$ar_delete = DevblocksPlatform::importGPC($_POST['delete'],'array');
		
		if(!is_array($ar_ids))
		    return;
		    
		foreach($ar_ids as $idx => $id) {
		    $nickname = $ar_nickname[$idx];
		    $protocol = $ar_protocol[$idx];
		    $host = $ar_host[$idx];
		    $username = $ar_username[$idx];
		    $password = $ar_password[$idx];
		    $port = $ar_port[$idx];
		    
			if(empty($nickname)) $nickname = "No Nickname";
			
			// Defaults
			if(empty($port)) {
			    switch($protocol) {
			        case 'pop3':
			            $port = 110; 
			            break;
			        case 'pop3-ssl':
			            $port = 995;
			            break;
			        case 'imap':
			            $port = 143;
			            break;
			    }
			}
			
			if(!empty($id) && is_numeric(array_search($id, $ar_delete))) {
				DAO_Mail::deletePop3Account($id);
				
			} elseif(!empty($id)) {
			    $enabled = (is_array($ar_enabled) && is_numeric(array_search($id, $ar_enabled))) ? 1 : 0;
			    
			    // [JAS]: [TODO] convert to field constants
				$fields = array(
				    'enabled' => $enabled,
					'nickname' => $nickname,
					'protocol' => $protocol,
					'host' => $host,
					'username' => $username,
					'password' => $password,
					'port' => $port
				);
				DAO_Mail::updatePop3Account($id, $fields);
				
			} else {
	            if(!empty($host) && !empty($username)) {
				    // [JAS]: [TODO] convert to field constants
	                $fields = array(
					    'enabled' => 1,
						'nickname' => $nickname,
						'protocol' => $protocol,
						'host' => $host,
						'username' => $username,
						'password' => $password,
						'port' => $port
					);
				    $id = DAO_Mail::createPop3Account($fields);
	            }
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
    }
};
?>