<?php
include '../src/VivOAuthIMAP.php';
include 'lib/mime_parser.php';
include 'lib/rfc822_addresses.php';
require_once 'GoogleClient/Google_Client.php';
require_once 'GoogleClient/contrib/Google_Oauth2Service.php';

session_start();

//Setting up Application Information for Google Client
$client = new Google_Client();
$client->setApplicationName('VivOAuthIMAP');
// Visit https://code.google.com/apis/console?api=plus to generate your
// client id, client secret, and to register your redirect uri.
$client->setClientId('');
$client->setClientSecret('');
$client->setRedirectUri('http://localhost/VivOAuthIMAP/example');
$client->setDeveloperKey('');
$client->setScopes("https://www.googleapis.com/auth/userinfo.email https://mail.google.com/");
$oauth2 = new Google_Oauth2Service($client);

if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $_SESSION['token'] = $client->getAccessToken();
    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
    return;
}

if (isset($_SESSION['token'])) {
    $client->setAccessToken($_SESSION['token']);
}
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['token']);
    $client->revokeToken();
    if (isset($imap))
        $imap->logout();
}

if ($client->getAccessToken()) {
    $user = $oauth2->userinfo->get();

    // These fields are currently filtered through the PHP sanitize filters.
    // See http://www.php.net/manual/en/filter.filters.sanitize.php
    $email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);

    // The access token may have been updated lazily.
    $_SESSION['token'] = $client->getAccessToken();
    $_SESSION['email'] = $email;
} else {
    $authUrl = $client->createAuthUrl();
}
if (isset($personMarkup)) {
    print $personMarkup;
}

//VivOAuthIMAP Code Starts Here

$parameters = NULL;
if ($_POST) {
    $parameters['email'] = $_POST['email'];
    $parameters['password'] = $_POST['password'];
}
if (isset($_SESSION['email']) && isset($_SESSION['token'])) {
    $parameters['email'] = $_SESSION['email'];
    $token = json_decode($_SESSION['token']);
    $parameters['access_token'] = $token->access_token;
}
if ($parameters) {

    $imap = new VivOAuthIMAP();
    $imap->host = 'ssl://imap.gmail.com';
    $imap->port = 993;

    $imap->username = $parameters['email'];
    if (isset($parameters['password'])) {
        $imap->password = $parameters['password'];
    }
    if (isset($parameters['access_token'])) {
        $imap->accessToken = $parameters['access_token'];
    }

    if ($imap->login()) {

        /*
          $header = $imap->getHeader(1); //Returns mail header array

          $mails = $imap->getMessage(1); //Returns mails array

          $headers = $imap->getHeaders(1,10); //Returns mail headers array

          $mails = $imap->getMessage(2); //Returns mails array

          $total = $imap->totalMails(); //By default inbox is selected

          $total = $imap->totalMails('Folder Name'); //Any folder which exist can be passed as folder name

          $folders = $imap->listFolders(); //Lists all folders of mailbox

          $imap->selectFolder('Folder Name'); // Default is INBOX autoselected after login
         */

        /*
         * Using mime_parser you can parse the rfc822 format mail or can write own parser
         * Here in example used a mime_parser_class 
         */


        $mails = $imap->getMessages(1,50);
        echo "First 50 mails fetched follwing are Email And Subject. <br>";
        foreach ($mails as $mail) {

            $mime = new mime_parser_class();
            $parameters = array('Data' => $mail);
            $mime->Decode($parameters, $decoded);

            /*
              //See how much variables you can access
              echo "<pre>";
              print_r($decoded);
              echo "</pre>";
             */
            echo "==================== <br>";
            echo "<b>From :</b> " . $decoded[0]['ExtractedAddresses']['from:'][0]['address'] . "<br>";
            echo "<b>Subject :</b> " . $decoded[0]['Headers']['subject:'] . "<br>";
            echo "====== <br>";
        }
    } else {
        echo "Login Failed";
    }
}

?>


<html>
    <div>
        <?php if (isset($authUrl)) : ?>
            Normal Method Using Email and Password
            <form method="POST">
                Email <input type="text" name="email"/></br>
                Password <input type="password" name="password"/></br>
                <input type="submit" value="Login"/>
            </form>
        <?php endif; ?>
        <?php
        if (isset($authUrl)) {
            echo "<br><br>Or use Oauth method to connect to google <br>";
            print "<a class='login' href='$authUrl'>Login (Connect using google)!</a>";
        } else {
            print "<a class='logout' href='?logout'>Logout</a>";
        }
        ?>
    </div>
</html>