# VivOAuthIMAP
####Version: 1.0

##Overview
VivOAuthIMAP is a library to support OAuth for IMAP.  
PHP's default library imap_open doesn't have support for OAuth.   
So a quiick solution will be this library.  

###Features

  * Connect to IMAP using OAuth
  * Count Total Mails
  * Fetch Mails Headers
  * Fetch Mails Body
  * List folders of mailbox
  * Swtich to folders easily

###Usage

    $imap = new VivOAuthIMAP();

    $imap->host = 'ssl://imap.gmail.com';
    $imap->port = 993;

    //Using Username And Password
    $imap->username = 'changeMe@gmail.com';
    $imap->password = 'changeMe';
    //Or you can use access token
    //$imap->accessToken = 'tokenreceivedbyoauthlibrary'

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
        $mails = $imap->getMessages(1, 20);

        foreach ($mails as $mail) {

            //Using mime_parser you can parse the rfc822 format mail or can write own parser
            //Here in example used a mime_parser_class 

            $mime = new mime_parser_class();
            $parameters = array('Data' => $mail);
            $mime->Decode($parameters, $decoded);

            /*
              //See how much variables you can access
              echo "<pre>";
              print_r($decoded);
              echo "</pre>";
             */

            echo "<b>From :</b> " . $decoded[0]['ExtractedAddresses']['from:'][0]['address'] . "   ";
            echo "<b>Subject :</b> " . $decoded[0]['Headers']['subject:'] . "<br>";
            echo "======================================================== <br>";
        }

    }

##Requirement
  * PHP Version > PHP 5.3+
  * PHP Extensions needs to enable 
    * php_openssl
    * php_curl
    * php_sockets

##License
----
The MIT License (MIT)

Copyright (c) 2013 Vivek Muthal

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

Author : Vivek Muthal  
Email : vmuthal.18@gmail.com  
Website : [www.vivsoftware.in](http://www.vivsoftware.in)