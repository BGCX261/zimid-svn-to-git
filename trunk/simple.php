<?php
// this code simply port from php openid lib's simple.php.

$LIB_DIR = "../../php_lib";
require_once( $LIB_DIR . '/openid/consumer.php' );

define('HOST', $_SERVER['SERVER_NAME']);
define('PORT', $_SERVER['SERVER_PORT']);

function my_redirect($url) {
    header( 'Location: ' . $url );
    exit; // okay, so *I* never use exit within a script, but am porting from Python.
}

function formArgstoDict() {
    // Returns a dict of the GET and POST arguments
    return array_merge( $_GET, $_POST );
}

function drawAlert($msg) {
    if( $msg ) {
        return sprintf( '<div id="alert">%s</div>', $msg );
    }
    return '';
}

$_message='';
function setAlert($m) {
    global $_message;
    $_message .= $m;
}

// Our OpenIDConsumer subclass.  See openid.consumer.OpenIDConsumer
// for more more documentation.
class SimpleConsumer extends OpenIDConsumer {
    
    function verify_return_to( $return_to ) {
    
        $parts = parse_url( $return_to );

        if (! isset($parts["port"])) $parts["port"] = ($parts["scheme"] == 'https' ? 443 : 80);

        // you should verify return_to host:port string match host and 
        // port of this server
        if( $parts['host'] != HOST || $parts['port'] != PORT ) {
            return false;
        }

        return true;
    }

};

// A handler with application specific callback logic.
class SimpleActionHandler extends ActionHandler {

    function SimpleActionHandler($query, $consumer) {
        $this->query = $query;
        $this->consumer = $consumer;
    }

    // callbacks
    function doValidLogin($login) {
        // here is where you would do what is necessary to log an openid "user"
        // user into your system.  We just print a message confirming the
        // valid login.
        setAlert( sprintf( '<b>Identity verified!</b> Thanks, ' .
                           '<a href="%s">' .
                           '%s</a>', $this->query['open_id'], $this->query['open_id'] ) );
    }

    function doInvalidLogin() {
        setAlert('Identity NOT verified!');
    }

    function doUserCancelled() {
        setAlert('Cancelled by user.');
    }

    function doCheckAuthRequired($server_url, $return_to, $post_data) {
        // do openid.mode=check_authentication call, and then change state
        $response = $this->consumer->check_auth($server_url, $return_to, $post_data,
                                                $this->getOpenID());
        $response->doAction($this);
    }

    function doErrorFromServer($message) {
        setAlert('Error from server: ' . $message);
    }

    // helpers
    function createReturnTo($base_url, $identity_url, $args=null) {
        if( !is_array( $args ) ) {
            $args = array();
        }
        $args['open_id'] = $identity_url;
        return oidUtil::append_args($base_url, $args);
    }

    function getOpenID() {
        // return the openid from the original form
        return $this->query['open_id'];
    }
};    


function dispatch() {
    // generate a dictionary of arguments
    $query = formArgstoDict();
    
    // create consumer and handler objects
    $consumer = new SimpleConsumer();
    $handler = new SimpleActionHandler($query, $consumer);

    // extract identity url from arguments.  Will be null if absent from query.
    $identity_url = isset( $query['identity_url'] ) ? $query['identity_url'] : null;

    if( $identity_url ) {
        $ret = $consumer->find_identity_info($identity_url);
        if( !$ret ) {
            setAlert(sprintf('Unable to find openid server for identity url %s', $identity_url) );
        }
        else {
            // found identity server info
            list( $identity_url, $server_id, $server_url ) = $ret;

            // build trust root - this examines the script env and builds
            // based on your running location.  In practice this may be static.
            // You will likely want it to be your entire website, not just
            // this script.
            $trust_root = isset($_SERVER['SCRIPT_URI']) ? $_SERVER['SCRIPT_URI'] : 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

            // build url to application for use in creating return_to
            $app_url = isset($_SERVER['SCRIPT_URI']) ? $_SERVER['SCRIPT_URI'] : 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

            // create return_to url from app_url
            $return_to = $handler->createReturnTo($app_url, $identity_url);

            // handle the request
            $redirect_url = $consumer->handle_request(
                $server_id, $server_url, $return_to, $trust_root);

            // redirect the user-agent to the server
            my_redirect($redirect_url);
        }
    }
    // php's url parsing converts '.' to '_'. So we check for both cases.
    else if( isset( $query['openid.mode'] ) || isset( $query['openid_mode'] ) ) {
        // got a request from the server.  build a Request object and pass
        // it off to the consumer object.  OpendIDActionHandler handles
        // the various end cases (see above).
        $openid = $handler->getOpenID();
        $req = new ConsumerRequest($openid, $query, 'GET');
        $response = $consumer->handle_response($req);

        // let our SimpleActionHandler do the work
        $response->doAction($handler);
    }
};    

// dispatch the event based on url args.
dispatch();

// Our helpful display page.
$buf = <<< END
<html>
<head>  
  <title>Simple PHP OpenID Demo</title>
  <style type="text/css">
  * {font-family:verdana,sans-serif;}
  body {width:50em; margin:1em;}
  div {padding:.5em; }
  table {margin:null;padding:null;}
  #alert {border:1px solid #e7dc2b; background: #fff888;}
  #login {border:1px solid #777; background: #ddd; margin-top:1em;padding-bottom:0em;}
  </style>
</head>
<body>
  <h2>PHP OpenID Identity Verification Demo</h3>
  <p>This is a simple demo of the PHP OpenID library and also a demo for OpenID identity varification process.</p>
  <p>No idea for OpenID? Please visit <a href="http://www.openid.net">this site</a> to see the detail. To get your own OpenID, please see these sites:
  <ul>
		<li><a href="http://www.myopenid.com">MyOpenID.com</a></li>
		<li><a href="https://getopenid.com">GetOpenID.com</a></li>
		<li><a href="http://openid.cn">OpenID.cn</a></li>
		<li>... plz use google: 'openid server' ... </li>
  </ul>
  </p>
  <p>If you surely have a OpenID, you can test it here, just input your identity into the textbox below and click verity button. That will take you to your OpenID verification page. Just click the 'allow once' button. (Please do not deny me, I will never do evil to you.)</p>
  <p><strong>SECURITY NOTE</strong>: This is only a simple test and demo. So this page cannot solve the 'replay attack'. BUT, this page do nothing but show the verification result, there is nothing worthy for your great hack capabilities unless you hate me or my hosting company. :)</p>
  %s
  <div id="login">
  Verify an Identity URL
  <hr/>
    <form action="%s" method="get">     
    OpenID: <input type="text" name="identity_url" class="openid_identity" />
    <input type="submit" value="Verify" />
    </form>
  </div>

</body>
</html>
END;

echo sprintf( $buf, drawAlert($_message), $_SERVER['SCRIPT_NAME'] );

?>
