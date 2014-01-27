<?php

/**
 * Description of VIVOAuthIMAP
 * This is a Library to Support OAuth in IMAP
 * This is designed to work with Gmail
 * @author Vivek
 */
class VivOAuthIMAP {

    /**
     * @var string $host
     */
    public $host;

    /**
     * @var integer $port
     */
    public $port;

    /**
     * @var string $username
     */
    public $username;

    /**
     * @var string $password
     */
    public $password;

    /**
     * @var string $accessToken
     */
    public $accessToken;

    /**
     * @var FilePointer $sock
     */
    private $fp;

    /**
     * Command Counter
     * @var string
     */
    private $codeCounter = 1;

    /**
     * If successfull login then set to true else false
     * @var boolean
     */
    private $isLoggedIn = false;

    /**
     * Connects to Host if successful returns true else false
     * @return boolean
     */
    private function connect() {
        $this->fp = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if ($this->fp)
            return true;
        return false;
    }

    /**
     * Closes the file pointer
     */
    private function disconnect() {
        fclose($this->fp);
    }

    /**
     * Login with username password / access_token returns true if successful else false
     * @return boolean
     */
    public function login() {

        if ($this->connect()) {
            $command = NULL;
            if (isset($this->username) && isset($this->password)) {
                $command = "LOGIN $this->username $this->password";
            } else if (isset($this->username) && isset($this->accessToken)) {
                $token = base64_encode("user=$this->username\1auth=Bearer $this->accessToken\1\1");
                $command = "AUTHENTICATE XOAUTH2 $token";
            }

            if ($command != NULL) {
                $this->writeCommannd("A" . $this->codeCounter, $command);
                $response = $this->readResponse("A" . $this->codeCounter);

                if ($response[0][0] == "OK") { //Got Successful response
                    $this->isLoggedIn = true;
                    $this->selectInbox();
                    return true;
                }
            }

            return false;
        }
        return false;
    }

    /**
     * Logout then disconnects
     */
    public function logout() {
        $this->writeCommannd("A" . $this->codeCounter, "LOGOUT");
        $this->readResponse("A" . $this->codeCounter);
        $this->disconnect();
        $this->isLoggedIn = false;
    }

    /**
     * Returns true if user is authenticated else false
     * @return boolean
     */
    public function isAuthenticated() {
        return $this->isLoggedIn;
    }

    /**
     * Fetch a single mail header and return
     * @param integer $id
     * @return array
     */
    public function getHeader($id) {
        $this->writeCommannd("A" . $this->codeCounter, "FETCH $id RFC822.HEADER");
        $response = $this->readResponse("A" . $this->codeCounter);

        if ($response[0][0] == "OK") {
            $modifiedResponse = $response;
            unset($modifiedResponse[0]);
            return $modifiedResponse;
        }

        return $response;
    }

    /**
     * Returns headers array
     * @param integer $from
     * @param integer $to
     * @return Array
     */
    public function getHeaders($from, $to) {
        $this->writeCommannd("A" . $this->codeCounter, "FETCH $from:$to RFC822.HEADER");
        $response = $this->readResponse("A" . $this->codeCounter);
        return $this->modifyResponse($response);
    }

    /**
     * Fetch a single mail and return
     * @param integer $id
     * @return Array
     */
    public function getMessage($id) {
        $this->writeCommannd("A" . $this->codeCounter, "FETCH $id RFC822");
        $response = $this->readResponse("A" . $this->codeCounter);
        return $this->modifyResponse($response);
    }

    /**
     * Returns mails array
     * @param integer $from
     * @param integer $to
     * @retun Array
     */
    public function getMessages($from, $to) {
        $this->writeCommannd("A" . $this->codeCounter, "FETCH $from:$to RFC822");
        $response = $this->readResponse("A" . $this->codeCounter);
        return $this->modifyResponse($response);
    }

    /**
     * Selects inbox for further operations
     */
    private function selectInbox() {
        $this->writeCommannd("A" . $this->codeCounter, "EXAMINE INBOX");
        $this->readResponse("A" . $this->codeCounter);
    }

    /**
     * List all folders
     * @return Array
     */
    public function listFolders() {
        $this->writeCommannd("A" . $this->codeCounter, "LIST \"\" \"*\"");
        $response = $this->readResponse("A" . $this->codeCounter);
        $line = $response[0][1];
        $statusString = explode("*", $line);

        $totalStrings = count($statusString);

        $statusArray = Array();
        $finalFolders = Array();

        for ($i = 1; $i < $totalStrings; $i++) {

            $statusArray[$i] = explode("\"/\" ", $statusString[$i]);

            if (!strpos($statusArray[$i][0], "\Noselect")) {
                $folder = str_replace("\"", "", $statusArray[$i][1]);
                array_push($finalFolders, $folder);
            }
        }

        return $finalFolders;
    }

    /**
     * Examines the folder
     * @param string $folder
     * @return boolean
     */
    public function selectFolder($folder) {
        $this->writeCommannd("A" . $this->codeCounter, "EXAMINE \"$folder\"");
        $response = $this->readResponse("A" . $this->codeCounter);
        if ($response[0][0] == "OK") {
            return true;
        }
        return false;
    }

    public function totalMails($folder = "INBOX") {
        $this->writeCommannd("A" . $this->codeCounter, "STATUS \"$folder\" (MESSAGES)");
        $response = $this->readResponse("A" . $this->codeCounter);

        $line = $response[0][1];
        $splitMessage = explode("(", $line);
        $splitMessage[1] = str_replace("MESSAGES ", "", $splitMessage[1]);
        $count = str_replace(")", "", $splitMessage[1]);

        return $count;
    }

    /**
    * The APPEND command appends the literal argument as a new message
    *  to the end of the specified destination mailbox
    *
    * @param string $mailbox MANDATORY
    * @param string $message MANDATORY
    * @param string $flags OPTIONAL DEFAULT "(\Seen)"
    * @param string $from OPTIONAL
    * @param string $to OPTIONAL
    * @param string $subject OPTIONAL
    * @param string $messageId OPTIONAL DEFAULT uniqid()
    * @param string $mimeVersion OPTIONAL DEFAULT "1.0"
    * @param string $contentType OPTIONAL DEFAULT "TEXT/PLAIN;CHARSET=UTF-8"
    *
    * @return bool false if mandatory params are not set or empty or if command execution fails, otherwise true
    */
    public function appendMessage($mailbox, $message, $from = "", $to = "", $subject = "", $messageId = "", $mimeVersion = "", $contentType = "", $flags = "(\Seen)")
    {
        if (!isset($mailbox) || !strlen($mailbox)) return false;
        if (!isset($message) || !strlen($message)) return false;
        if (!strlen($flags)) return false;

        $date = date('d-M-Y H:i:s O');
        $crlf = "\r\n";

        if (strlen($from)) $from = "From: $from";
        if (strlen($to)) $to = "To: $to";
        if (strlen($subject)) $subject = "Subject: $subject";
        $messageId = (strlen($messageId)) ? "Message-Id: $messageId" : "Message-Id: " . uniqid();
        $mimeVersion = (strlen($mimeVersion)) ? "MIME-Version: $mimeVersion" : "MIME-Version: 1.0";
        $contentType = (strlen($contentType)) ? "Content-Type: $contentType" : "Content-Type: TEXT/PLAIN;CHARSET=UTF-8";

        $composedMessage = $date . $crlf;
        if (strlen($from)) $composedMessage .= $from . $crlf;
        if (strlen($subject)) $composedMessage .= $subject . $crlf;
        if (strlen($to)) $composedMessage .= $to . $crlf;
        $composedMessage .= $messageId . $crlf;
        $composedMessage .= $mimeVersion . $crlf;
        $composedMessage .= $contentType . $crlf . $crlf;
        $composedMessage .= $message . $crlf;

        $size = strlen($composedMessage);

        $command = "APPEND \"$mailbox\" $flags {" . $size . "}" . $crlf . $composedMessage;

        $this->writeCommannd("A" . $this->codeCounter, $command);
        $response = $this->readResponse("A" . $this->codeCounter);

        if ($response[0][0] == "OK") return true;

        return false;
    }

    /**
     * Write's to file pointer
     * @param string $code
     * @param string $command
     */
    private function writeCommannd($code, $command) {
        fwrite($this->fp, $code . " " . $command . "\r\n");
    }

    /**
     * Reads response from file pointer, parse it and returns response array
     * @param string $code
     * @return Array
     */
    private function readResponse($code) {
        $response = Array();

        $i = 1;
        // $i = 1, because 0 will be status of response
        // Position 0 server reply two dimentional
        // Position 1 message

        while ($line = fgets($this->fp)) {
            $checkLine = preg_split('/\s+/', $line, 0, PREG_SPLIT_NO_EMPTY);
            if (@$checkLine[0] == $code) {
                $response[0][0] = $checkLine[1];
                break;
            } else if (@$checkLine[0] != "*") {
                if (isset($response[1][$i]))
                    $response[1][$i] = $response[1][$i] . $line;
                else
                    $response[1][$i] = $line;
            }
            if (@$checkLine[0] == "*") {
                if (isset($response[0][1]))
                    $response[0][1] = $response[0][1] . $line;
                else
                    $response[0][1] = $line;
                if (isset($response[1][$i])) {
                    $i++;
                }
            }
        }
        $this->codeCounter++;
        return $response;
    }

    /**
     * If response is OK then removes server response status messages else returns the original response
     * @param Array $response
     * @return Array
     */
    private function modifyResponse($response) {
        if ($response[0][0] == "OK") {
            $modifiedResponse = $response[1];
            return $modifiedResponse;
        }
        return $response;
    }

}

?>
