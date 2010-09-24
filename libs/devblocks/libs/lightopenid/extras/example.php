<?php
require 'openid.php';
try {
    if(!isset($_GET['openid_mode'])) {
        if(isset($_POST['openid_identifier'])) {
            $openid = new LightOpenID;
            $openid->identity = $_POST['openid_identifier'];
            header('Location: ' . $openid->authUrl());
        }
?>
<form action="" method="post">
    OpenID: <input type="text" name="openid_identifier" /> <button>Submit</button>
</form>
<?php
    } elseif($_GET['openid_mode'] == 'cancel') {
        echo 'User has canceled authentication!';
    } else {
        $openid = new LightOpenID;
        echo 'User ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
    }
} catch(ErrorException $e) {
    echo $e->getMessage();
}
