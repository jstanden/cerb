<?php

/****************************************************************************/
/*                                                                          */
/* YOU MAY WISH TO MODIFY OR REMOVE THE FOLLOWING LINES WHICH SET DEFAULTS  */
/*                                                                          */
/****************************************************************************/

// Sets the default charset so that setCharset() is not needed elsewhere
Swift_Preferences::getInstance()->setCharset('utf-8');

// Without these lines the default caching mechanism is "array" but this uses
// a lot of memory.
// If possible, use a disk cache to enable attaching large attachments etc

// [CHD-1440] Errors while forwarding or creating messages with attachments (Randy Syring)
if (file_exists(APP_TEMP_PATH)) { 
   Swift_Preferences::getInstance() 
    -> setTempDir(APP_TEMP_PATH) 
    -> setCacheType('disk');
// Default Swiftmailer 
} elseif (function_exists('sys_get_temp_dir') && is_writable(sys_get_temp_dir())) { 
  Swift_Preferences::getInstance() 
    -> setTempDir(sys_get_temp_dir()) 
    -> setCacheType('disk'); 
} 