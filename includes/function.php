<?php
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class Functions{

    public function fblogin(string ...$path){
        $facebook = new Facebook([
            'app_id'      => FACEBOOK_APP_ID,
            'app_secret'     => FACEBOOK_APP_SECRET,
            'default_graph_version'  => 'v2.10'
        ]);

        $facebook_helper = $facebook->getRedirectLoginHelper();

        try {
            if(isset($_SESSION['facebook_access_token'])){
                $access_token = $_SESSION['facebook_access_token'];
            }else{
                $access_token = $facebook_helper->getAccessToken();
            }

            if(isset($accessToken)){
                $graph_response = $facebook->get("/me?fields=email,name,picture", $access_token);
                $facebook_user_info = $graph_response->getGraphUser();
                //id field
                $userDetails = [];
                $userDetails['email'] = $facebook_user_info['email'];
                $userDetails['first_name'] = $facebook_user_info['name'];
                $userDetails['auth_id'] = $facebook_user_info['id'];
                $userDetails['auth_medium'] = "facebook";
                if (isset($facebook_user_info['picture']))
                    $userDetails['picture'] = $facebook_user_info['picture']->getUrl();
                if (empty($facebook_user_info['email']) or empty($facebook_user_info['id'])) {
                    session_destroy();
                    $_SESSION['authEmailError'] = 1;
                    header('Location: ' . BASE_URL);
                    exit();
                }
                
                $this->loginuserinportal($userDetails);
            } else{
                $facebook_permissions = ['email'];
                $facebook_login_url = $facebook_helper->getLoginUrl(FACEBOOK_REDIRECT_URL, $facebook_permissions);
                return $facebook_login_url;
            }
        }catch(FacebookResponseException $e) {
            session_destroy();
            $_SESSION['authEmailError'] = 1;
            header('Location: ' . BASE_URL);
            exit();
        } catch(FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
    }

    public function linelogin(string ...$path)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $CLIENT_ID = LINE_CLIENT_ID;
        $CLIENT_SECRET = LINE_CLIENT_SECRET;
        $REDIRECT_URL = BASE_URL . '/linelogin';
        $AUTH_URL = 'https://access.line.me/oauth2/v2.1/authorize';
        $TOKEN_URL = 'https://api.line.me/oauth2/v2.1/token';
        $VERIFYTOKEN_URL = 'https://api.line.me/oauth2/v2.1/verify';

        if (!empty($_GET['code'])) {

            //Fetch id token
            $http = new Client();
            $response = $http->post($TOKEN_URL, [
                "grant_type" => "authorization_code",
                'code' => $_GET['code'],
                'redirect_uri' => $REDIRECT_URL,
                'client_id' => $CLIENT_ID,
                'client_secret' => $CLIENT_SECRET
            ], ['headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);
            $response = $response->getJson();
            //sub
            //Fetch profile
            $http = new Client();
            $profile = $http->post($VERIFYTOKEN_URL, [
                "id_token" => $response['id_token'],
                'client_id' => $CLIENT_ID,
            ], ['headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);
            $profile = $profile->getJson();

            //if user email not available rediect user to homepage with error toast
            if (!isset($profile['email']) or !isset($profile['sub'])) {
                session_destroy();
                $_SESSION['authEmailError'] = 1;
                header('Location: ' . BASE_URL);
                exit();
            }

            $userDetails = ['nickname' => $profile['name'], 'first_name' => $profile['name'], 'email' => $profile['email'],'auth_id'=>$profile['sub'],'auth_medium' => "line"];
            if (isset($profile['picture']))
                $userDetails['picture'] = $profile['picture'];
            $this->loginuserinportal($userDetails);
        } else {
            $_SESSION['state'] = hash('sha256', microtime(TRUE) . rand() . $_SERVER['REMOTE_ADDR']);
            $link = $AUTH_URL . '?response_type=code&client_id=' . $CLIENT_ID . '&redirect_uri=' . $REDIRECT_URL . '&scope=profile%20openid%20email&state=' . $_SESSION['state'];
            header("Location: " . $link);
            exit();
        }
    }

    public function googlelogin()
    {
        session_start();
        unset($_SESSION['token']);

        $CLIENT_ID = GOOGLE_CLIENT_ID;
        $CLIENT_SECRET = GOOGLE_CLIENT_SECRET;
        $REDIRECT_URI = BASE_URL . '/googlelogin';

        $client  = new Google\Client();
        $client->setClientId($CLIENT_ID);
        $client->setClientSecret($CLIENT_SECRET);
        $client->setRedirectUri($REDIRECT_URI);
        $client->addScope('email');
        $client->addScope('profile');

        $google_oauth = new Google_Service_Oauth2($client);
        if (isset($_GET['code'])) {
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $_SESSION['token'] = $token;
        }

        if (isset($_SESSION['token'])) {
            $client->setAccessToken($_SESSION['token']);
        }

        if ($client->getAccessToken()) {

            $gpUserProfile = $google_oauth->userinfo->get();
            //id
            $userDetails = array();
            $userDetails['auth_id']  = !empty($gpUserProfile['id']) ? $gpUserProfile['id'] : '';
            $userDetails['first_name'] = !empty($gpUserProfile['given_name']) ? $gpUserProfile['given_name'] : '';
            $userDetails['last_name']  = !empty($gpUserProfile['family_name']) ? $gpUserProfile['family_name'] : '';
            $userDetails['nickname']  = !empty($gpUserProfile['given_name']) ? $gpUserProfile['given_name'] : '';
            $userDetails['email']       = !empty($gpUserProfile['email']) ? $gpUserProfile['email'] : '';
            $userDetails['gender']       = !empty($gpUserProfile['gender']) ? $gpUserProfile['gender'] : '';
            $userDetails['picture']       = !empty($gpUserProfile['picture']) ? $gpUserProfile['picture'] : '';
            $userDetails['auth_medium'] = "google";
            

            //if user email not available rediect user to homepage with error toast
            if (empty($gpUserProfile['email']) or empty($gpUserProfile['id'])) {
                session_destroy();
                $_SESSION['authEmailError'] = 1;
                header('Location: ' . BASE_URL);
                exit();
            }

            $this->loginuserinportal($userDetails);
        } else {
            $authUrl = $client->createAuthUrl();
            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            exit();
        }
    }

    public function twitterlogin()
    {
        session_start();

        $CONSUMER_KEY = TWITTER_CONSUMER_KEY;
        $CONSUMER_SECRET = TWITTER_CONSUMER_SECRET;

        try {
            if (!isset($_GET['oauth_token']) and !isset($_GET['oauth_verifier'])) {
                $client = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET);
                $request_token = $client->oauth('oauth/request_token');
                $_SESSION['oauth_token'] = $request_token['oauth_token'];
                $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
                $url = $client->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
                header("Location:$url");
                exit();
            } else {
                if (isset($_GET['oauth_token']) && $_SESSION['oauth_token'] !== $_GET['oauth_token']) {
                    throw new ForbiddenException();
                } else {
                    $client = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
                    $access_token = $client->oauth("oauth/access_token", ["oauth_verifier" => $_GET['oauth_verifier']]);
                    $client = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
                    $user = $client->get('account/verify_credentials', ['tweet_mode' => 'extended', 'include_entities' => 'true', 'include_email' => 'true']);
                    // screen_name is username
                    //id and id_str
                    $userDetails = array();
                    $userDetails['auth_id']       = !empty($user->id) ? $user->id : '';
                    $userDetails['first_name'] = !empty($user->name) ? $user->name : '';
                    $userDetails['nickname']  = !empty($user->name) ? $user->name : '';
                    $userDetails['email']       = !empty($user->email) ? $user->email : '';
                    $userDetails['picture']       = isset($user->profile_image_url_https) ? $user->profile_image_url_https : '';
                    $userDetails['auth_medium'] = "twitter";
                    //if user email not available rediect user to homepage with error toast
                    if (empty($userDetails['email']) or empty($userDetails['auth_id'])) {
                        session_destroy();
                        $_SESSION['authEmailError'] = 1;
                        header('Location: ' . BASE_URL);
                        exit();
                    }

                    $this->loginuserinportal($userDetails);
                }
            }
        } catch (ForbiddenException $exception) {
            echo 'Fordbidden request' . $exception;
        }
    }

    public function loginuserinportal($userDetails)
    {
        $email = $userDetails['email'];
        $auth_id = $userDetails['auth_id'];
        $auth_medium = $userDetails['auth_medium'];
        $results = $conn->query("SELECT * FROM user where (email = '$email' or auth_id='$auth_id')")->fetch_assoc();

        if (!empty($results)) {
            $results = current($results);
            

            if($results['auth_medium'] !== $auth_medium){
                $this->Flash->error(__('Unable to login. As your account linked using '.$results['auth_medium'].' SNS service'));
                unset($_SESSION['loginRedirect']);
                header('Location:' . BASE_URL);
                exit();
            }

            //check for active user
            if ($results['status'] === 'inactive') {
                $this->Flash->error(__('Unable to login. As your account has been suspended'));
                unset($_SESSION['loginRedirect']);
                header('Location:' . BASE_URL);
                exit();
            }

            $_SESSION['user_id'] = $results['id'];
            $_SESSION['username'] = $results['name'];
            $_SESSION['email_id'] = $results['email'];
            if (isset($results['profile']) and !empty($results['profile']))
                $_SESSION['profile'] = $results['profile'];
           
            $redirectUrl = $_SESSION['loginRedirect'];
            if (isset($redirectUrl)) {
                unset($_SESSION['loginRedirect']);
                header('Location:' . $redirectUrl);
                exit();
            } else {
                header('Location:' . BASE_URL.'fblogin.php');
                exit();
            }
        } else {
            $this->registeruserindB($userDetails);
        }
    }

    public function registeruserindB($userDetails){
        $registerData = [
            'auth_medium'=> !empty($userDetails['auth_medium']) ? $userDetails['auth_medium'] : "",
            'auth_id'=> !empty($userDetails['auth_id']) ? $userDetails['auth_id'] : "",
            'email ' => $userDetails['email'],
            'password' => md5($userDetails['email']),
            'name' => !empty($userDetails['first_name']) ? $userDetails['first_name'] : "",
            'last_name' => !empty($userDetails['last_name']) ? $userDetails['last_name'] : "",
            'phone' => '',
            'status' => 'active',
            'profile' => !empty($userDetails['picture']) ? $userDetails['picture'] : NULL
        ];
        $columns = implode(", ",array_keys($registerData));
        $escaped_values = array_map('mysql_real_escape_string', array_values($registerData));
        $values  = implode(", ", $escaped_values);
        $conn->query("INSERT INTO user (".$columns.") VALUES (".$values.") ");
        $this->loginuserinportal(['email' => $userDetails['email'],'auth_id'=>$userDetails['auth_id'],'auth_medium'=>$userDetails['auth_medium']]);
    }

}