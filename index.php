<?php 
include "includes/config.php"; 
include "includes/User.class.php";

if(isset($accessToken)){
    if(isset($_SESSION['facebook_access_token'])){
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }else{
        // Put short-lived access token in session
        $_SESSION['facebook_access_token'] = (string) $accessToken;
        
          // OAuth 2.0 client handler helps to manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();
        
        // Exchanges a short-lived access token for a long-lived one
        $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);
        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;
        
        // Set default access token to be used in script
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }
    
    // Redirect the user back to the same page if url has "code" parameter in query string
    if(isset($_GET['code'])){
        header('Location: ./');
    }
    
    // Getting user's profile info from Facebook
    try {
        $graphResponse = $fb->get('/me?fields=name,first_name,last_name,email,link,gender,picture');
        $fbUser = $graphResponse->getGraphUser();
    } catch(FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        session_destroy();
        // Redirect user back to app login page
        header("Location: ./");
        exit;
    } catch(FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    
    // Initialize User class
    $user = new User();
    
    // Getting user's profile data
    $fbUserData = array();
    $fbUserData['oauth_uid']  = !empty($fbUser['id'])?$fbUser['id']:'';
    $fbUserData['first_name'] = !empty($fbUser['first_name'])?$fbUser['first_name']:'';
    $fbUserData['last_name']  = !empty($fbUser['last_name'])?$fbUser['last_name']:'';
    $fbUserData['email']      = !empty($fbUser['email'])?$fbUser['email']:'';
    $fbUserData['gender']     = !empty($fbUser['gender'])?$fbUser['gender']:'';
    $fbUserData['picture']    = !empty($fbUser['picture']['url'])?$fbUser['picture']['url']:'';
    $fbUserData['link']       = !empty($fbUser['link'])?$fbUser['link']:'';
    
    // Insert or update user data to the database
    $fbUserData['oauth_provider'] = 'facebook';
    $userData = $user->checkUser($fbUserData);
    
    // Storing user data in the session
    $_SESSION['userData'] = $userData;
    
    // Get logout url
    $logoutURL = $helper->getLogoutUrl($accessToken, BASE_URL.'logout.php');
    
    // Render Facebook profile data
    if(!empty($userData)){
        $output  = '<div class="col-md-12">';
        $output .= '<h2>Facebook Profile Details</h2>';
        $output .= '<div class="ac-data">';
        $output .= '<img src="'.$userData['picture'].'"/>';
        $output .= '<p><b>Facebook ID:</b> '.$userData['oauth_uid'].'</p>';
        $output .= '<p><b>Name:</b> '.$userData['first_name'].' '.$userData['last_name'].'</p>';
        $output .= '<p><b>Email:</b> '.$userData['email'].'</p>';
        $output .= '<p><b>Gender:</b> '.$userData['gender'].'</p>';
        $output .= '<p><b>Logged in with:</b> Facebook</p>';
        $output .= '<p><b>Profile Link:</b> <a href="'.$userData['link'].'" target="_blank">Click to visit Facebook page</a></p>';
        $output .= '<p><b>Logout from <a href="'.$logoutURL.'">Facebook</a></p>';
        $output .= '</div>';
        $output .= '</div>';
    }else{
        $output = '<h3 style="color:red">Some problem occurred, please try again.</h3>';
    }
}else if(isset($_GET['code'])){
    $token = $gClient->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['google_token'] = $token;

    if (isset($_SESSION['google_token'])) {
        $gClient->setAccessToken($_SESSION['google_token']);
    }
    
     
    if(isset($_SESSION['google_token'])){ 
        $gClient->setAccessToken($_SESSION['google_token']); 
    } 
     
    if ($gClient->getAccessToken()) { 
        // Get user profile data from google 
        $gpUserProfile = $google_oauth->userinfo->get();
         
        // Initialize User class 
        $user = new User(); 
         
        // Getting user profile info 
        $gpUserData = array(); 
        $gpUserData['oauth_uid']  = !empty($gpUserProfile['id'])?$gpUserProfile['id']:''; 
        $gpUserData['first_name'] = !empty($gpUserProfile['given_name'])?$gpUserProfile['given_name']:''; 
        $gpUserData['last_name']  = !empty($gpUserProfile['family_name'])?$gpUserProfile['family_name']:''; 
        $gpUserData['email']      = !empty($gpUserProfile['email'])?$gpUserProfile['email']:''; 
        $gpUserData['gender']     = !empty($gpUserProfile['gender'])?$gpUserProfile['gender']:''; 
        $gpUserData['link']     = !empty($gpUserProfile['locale'])?$gpUserProfile['locale']:''; 
        $gpUserData['picture']    = !empty($gpUserProfile['picture'])?$gpUserProfile['picture']:''; 
         
        // Insert or update user data to the database 
        $gpUserData['oauth_provider'] = 'google'; 
        $userData = $user->checkUser($gpUserData); 
         
        // Storing user data in the session 
        $_SESSION['userData'] = $userData; 
         
        // Render user profile data 
        if(!empty($userData)){ 
            $output     = '<h2>Google Account Details</h2>'; 
            $output .= '<div class="ac-data">'; 
            $output .= '<img src="'.$userData['picture'].'">'; 
            $output .= '<p><b>Google ID:</b> '.$userData['oauth_uid'].'</p>'; 
            $output .= '<p><b>Name:</b> '.$userData['first_name'].' '.$userData['last_name'].'</p>'; 
            $output .= '<p><b>Email:</b> '.$userData['email'].'</p>'; 
            $output .= '<p><b>Gender:</b> '.$userData['gender'].'</p>'; 
            $output .= '<p><b>Locale:</b> '.$userData['link'].'</p>'; 
            $output .= '<p><b>Logged in with:</b> Google Account</p>'; 
            $output .= '<p>Logout from <a href="logout.php">Google</a></p>'; 
            $output .= '</div>'; 
        }else{ 
            $output = '<h3 style="color:red">Some problem occurred, please try again.</h3>'; 
        } 
    }
}else if(($yahooid->mode != "cancel") && $yahooid->mode){
    if($yahooid->validate()){
        $data = $yahooid->getAttributes();
        $Identity = explode("=",$yahooid->identity);
        $userid = $Identity[1];
        $loginwith = 'Yahoo';

        // Initialize User class
        $user = new User();

        $yahooUserData = array();
        $yahooUserData['oauth_uid']  = !empty($userid)?$userid:'';
        $yahooUserData['first_name'] = !empty($data['namePerson/first'])?$data['namePerson/first']:'';
        $yahooUserData['last_name']  = !empty($data['namePerson/last'])?$data['namePerson/last']:'';
        $yahooUserData['email']      = !empty($data['contact/email'])?$data['contact/email']:'';
        $yahooUserData['gender']     = !empty($data['person/gender	'])?$data['person/gender']:'';
        $yahooUserData['link']       = !empty($data['pref/language'])?$data['pref/language']:'';
        
        // Insert or update user data to the database
        $yahooUserData['oauth_provider'] = 'yahoo';
        $userData = $user->checkUser($yahooUserData);
        $logoutURL = BASE_URL."logout";
        if(!empty($userData)){
            $output  = '<div class="col-md-12">';
            $output .= '<h2>Yahoo Profile Details</h2>';
            $output .= '<div class="ac-data">';
            $output .= '<p><b>Yahoo ID:</b> '.$userData['oauth_uid'].'</p>';
            $output .= '<p><b>Name:</b> '.$userData['first_name'].' '.$userData['last_name'].'</p>';
            $output .= '<p><b>Email:</b> '.$userData['email'].'</p>';
            $output .= '<p><b>Gender:</b> '.$userData['gender'].'</p>';
            $output .= '<p><b>Logged in with:</b> Yahoo</p>';
            $output .= '<p><b>Logout from <a href="'.$logoutURL.'">Yahoo</a></p>';
            $output .= '</div>';
            $output .= '</div>';
        }else{
            $output = '<h3 style="color:red">Some problem occurred, please try again.</h3>';
        }
    }else{
        $output = '<h3 style="color:red">Some problem occurred, please try again.</h3>';
    }
}else{
    // Get login url
    $permissions = ['email']; // Optional permissions
    $loginURL = $helper->getLoginUrl(FACEBOOK_REDIRECT_URL, $permissions);
    $authUrl = $gClient->createAuthUrl();
    $google_url = filter_var($authUrl, FILTER_SANITIZE_URL);
    $yahoo_url = BASE_URL."index.php?login=1";
    $output = "<div class=\"col-md-3\"><a href=\"$loginURL\" class=\"btn btn-primary btn-block mb-2\">Login with Facebook</a></div>";
    $output .= "<div class=\"col-md-3\"><a href=\"$google_url\" class=\"btn btn-danger btn-block mb-2\">Login with Google</a></div>";
    $output .= "<div class=\"col-md-3\"><a href=\"#\" class=\"btn btn-info btn-block mb-2\">Login with Twitter</a></div>";
    $output .= "<div class=\"col-md-3\"><a href=\"$yahoo_url\" class=\"btn btn-primary btn-block mb-2\">Login with Yahoo</a></div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Login</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <section class="section bg-grey">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12 col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Social Login</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?= $output ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="js/style.js"></script>
</body>
</html>