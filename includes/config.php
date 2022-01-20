<?php
require_once 'vendor/autoload.php';
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

if(!session_id()) {
    session_start();
}

// Include required libraries
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

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
        $accessToken = $helper->getAccessToken();
    }
}catch(FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
}catch(FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}