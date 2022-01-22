<?php
require_once 'vendor/autoload.php';
require_once 'LightOpenID/openid.php';

// Include required libraries
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '../.env');
$dotenv->load();

define('BASE_URL', 'http://localhost/social_login/');
define('DB_HOST', $_ENV['database.default.hostname']);
define('DB_USERNAME', $_ENV['database.default.username']);
define('DB_PASSWORD', $_ENV['database.default.password']);
define('DB_NAME', $_ENV['database.default.database']);
define('DB_USER_TBL', 'users');

// facbook crediantial
define('FACEBOOK_APP_ID', $_ENV['facbook_app_id']);
define('FACEBOOK_APP_SECRET', $_ENV['facbook_app_secret']);
define('FACEBOOK_REDIRECT_URL', 'https://localhost/social_login/index.php');

// google crediantial
define('GOOGLE_CLIENT_ID', $_ENV['google_client_id']);
define('GOOGLE_CLIENT_SECRET', $_ENV['google_client_secret']);
define('GOOGLE_REDIRECT_URL', 'https://localhost/social_login/index.php');

// yahoo crediantial
define('YAHOO_CLIENT_ID', $_ENV['yahoo_client_id']);
define('YAHOO_CLIENT_SECRET', $_ENV['yahoo_client_secret']);
define('YAHOO_REDIRECT_URL', 'https://localhost/social_login/dashboard.php');

if(!session_id()) {
    session_start();
}

// Call Facebook API
$fb = new Facebook(array(
    'app_id' => FACEBOOK_APP_ID,
    'app_secret' => FACEBOOK_APP_SECRET,
    'default_graph_version' => 'v12.0',
));

// Get redirect login helper
$helper = $fb->getRedirectLoginHelper();

if (isset($_GET['state'])) {
    $helper->getPersistentDataHandler()->set('state', $_GET['state']);
}

// Try to get access token
try {
    if(isset($_SESSION['facebook_access_token'])){
        $accessToken = $_SESSION['facebook_access_token'];
    }else{
        //$accessToken = $helper->getAccessToken();
    }
}catch(FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
}catch(FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

// Call Google API
$gClient = new Google_Client();
$gClient->setApplicationName('Social Login');
$gClient->setClientId(GOOGLE_CLIENT_ID);
$gClient->setClientSecret(GOOGLE_CLIENT_SECRET);
$gClient->setRedirectUri(GOOGLE_REDIRECT_URL);
$gClient->addScope("email");
$gClient->addScope("profile");
$google_oauth = new Google_Service_Oauth2($gClient);

//yahoo API
try{
    if(!isset($_SESSION["yahoo_login"])){
        $url = "https://api.login.yahoo.com/oauth2/request_auth";
        $param = array(
                    'client_id'     => YAHOO_CLIENT_ID,
                    'response_type' => 'code',
                    'redirect_uri'  => YAHOO_REDIRECT_URL,
                    'scope'         => 'email,mail-r',
                    'nonce'         =>  'YihsFwGKgt3KJUh6tPs2'
                );
    }
}catch(Exception $ex){
    echo $ex->getMessage();
}


