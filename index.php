<?php include "includes/config.php"; ?>

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
                                <div class="col-md-3">
                                    <fb:login-button class="btn btn-primary btn-block mb-2" scope="public_profile,email" onlogin="checkLoginState();">Facebook</fb:login-button>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" class="btn btn-danger btn-block mb-2">Google</a>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" class="btn btn-info btn-block mb-2">Twitter</a>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" class="btn btn-primary btn-block mb-2">Yahoo</a>
                                </div>
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

    <script>
    $(document).ready(function() {
        $.ajaxSetup({ cache: true });
        $.getScript('https://connect.facebook.net/en_US/sdk.js', function(){
        FB.init({
            appId: '231401379066515',
            version: 'v12.0' 
        });     
        $('#loginbutton,#feedbutton').removeAttr('disabled');
        FB.getLoginStatus(updateStatusCallback);
        });

        FB.login(function(response) {
            // handle the response
        }, {scope: 'public_profile,email'});

        FB.login(function(response) {
            if (response.status === 'connected') {
                // Logged into your webpage and Facebook.
            } else {
                // The person is not logged into your webpage or we are unable to tell. 
            }
        });

        FB.logout(function(response) {
            // Person is now logged out
        });
    });
    </script>
</body>
</html>