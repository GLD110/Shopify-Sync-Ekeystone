<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mail extends MY_Controller {

  public $conn;

  // inbox storage and inbox message count
  private $inbox;
  private $msg_cnt;

  // email login credentials
  private $server = '{mail.watchersalerts.com:993/imap/ssl/novalidate-cert}INBOX';
  private $user   = 'alerts@watchersalerts.com';
  private $pass   = 'cryptosignal8918';
  private $port   = 993; // adjust according to server settings

  // connect to the server and get the inbox emails
  public function __construct() {
    parent::__construct();
    $this->connect();
    $this->inbox();
  }

  public function index(){
      //$this->is_logged_in();
      //print_r($this->get(14)['header']);
      //print_r($this->get(0)['body']);
      //print_r($this->get(14)['structure']);
      //print_r($this->get(0));
      $this->manage();
  }

  public function manage(){
    /* connect to gmail */
    $hostname = '{mail.watchersalerts.com:993/imap/ssl/novalidate-cert}INBOX';
    $username = 'alerts@watchersalerts.com';
    $password = 'cryptosignal8918';

    /* try to connect */
    $inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());

    /* grab emails */
    $emails = imap_search($inbox,'SUBJECT "TradingView Alert" SEEN');
    //$emails = imap_search($inbox,'SEEN');

    /* if emails are returned, cycle through each... */
    if($emails) {

    	/* put the newest emails on top */
    	rsort($emails);

    	/* for every email... */
    	foreach($emails as $email_number) {
    		/* get information specific to this email */
    		//$overview = imap_fetch_overview($inbox,$email_number,0);
        $output = '';
    		$message = imap_fetchbody($inbox,$email_number,1);

    		/* output the email body */
    		$output.= '<div class="messagebody">'.$message.'</div>';

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $body = "<html> <head> <title>HTML email</title>";
        $body.= '<style type="text/css"> .messagebody table tbody tr td table tbody tr:nth-child(1) { display: none; } </style>';
        $body.= '<style type="text/css"> .messagebody table tbody tr td table tbody tr:nth-child(3) { display: none; } </style>';
        $body.= '<style type="text/css"> .messagebody table tbody tr td table tbody tr:nth-child(2) td table tbody tr td table tbody tr:nth-child(3) { display: none; } </style>';
        $body.= '<style type="text/css"> .messagebody table tbody tr td table tbody tr:nth-child(2) td table tbody tr td table tbody tr:nth-child(1) { display: block; } </style>';
        $body.= '<style type="text/css"> .messagebody table tbody tr td table tbody tr:nth-child(2) td table tbody tr td table tbody tr:nth-child(2) { display: block; } </style>';
        $body.= '<style type="text/css"> .messagebody table tbody tr td table tbody tr:nth-child(2) td table tbody tr { display: block; } </style>';
        $body.= "</head>";
        $body.="<body>" . $output . "</body> </html>";

        echo $body;
        //mail('z@watchersbrief.com,diana4545@outlook.com', '', $body, $headers);
        //mail('diana4545@outlook.com', '', $body, $headers);
    	}
    }

    /* close the connection */
    imap_close($inbox);
  }

  function email_pull() {

  	// load the meals_model to store meal information
  	//$this->load->model('meals_model');

  	// this method is run on a cronjob and should process all emails in the inbox
  	while (1) {
  		// get an email
  		$email = $this->get();

  		// if there are no emails, jump out
  		if (count($email) <= 0) {
  			break;
  		}

  		$attachments = array();
  		// check for attachments
  		if (isset($email['structure']->parts) && count($email['structure']->parts)) {
  			// loop through all attachments
  			for ($i = 0; $i < count($email['structure']->parts); $i++) {
  				// set up an empty attachment
  				$attachments[$i] = array(
  					'is_attachment' => FALSE,
  					'filename'      => '',
  					'name'          => '',
  					'attachment'    => ''
  				);

  				// if this attachment has idfparameters, then proceed
  				if ($email['structure']->parts[$i]->ifdparameters) {
  					foreach ($email['structure']->parts[$i]->dparameters as $object) {
  						// if this attachment is a file, mark the attachment and filename
  						if (strtolower($object->attribute) == 'filename') {
  							$attachments[$i]['is_attachment'] = TRUE;
  							$attachments[$i]['filename']      = $object->value;
  						}
  					}
  				}

  				// if this attachment has ifparameters, then proceed as above
  				if ($email['structure']->parts[$i]->ifparameters) {
  					foreach ($email['structure']->parts[$i]->parameters as $object) {
  						if (strtolower($object->attribute) == 'name') {
  							$attachments[$i]['is_attachment'] = TRUE;
  							$attachments[$i]['name']          = $object->value;
  						}
  					}
  				}

  				// if we found a valid attachment for this 'part' of the email, process the attachment
  				if ($attachments[$i]['is_attachment']) {
  					// get the content of the attachment
  					$attachments[$i]['attachment'] = imap_fetchbody($this->conn, $email['index'], $i+1);

  					// check if this is base64 encoding
  					if ($email['structure']->parts[$i]->encoding == 3) { // 3 = BASE64
  						$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
  					}
  					// otherwise, check if this is "quoted-printable" format
  					elseif ($email['structure']->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
  						$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
  					}
  				}
  			}
  		}

  		// for My Slow Low, check if I found an image attachment
  		$found_img = FALSE;
  		foreach ($attachments as $a) {
  			if ($a['is_attachment'] == 1) {
  				// get information on the file
  				$finfo = pathinfo($a['filename']);

  				// check if the file is a jpg, png, or gif
  				if (preg_match('/(jpg|gif|png)/i', $finfo['extension'], $n)) {
  					$found_img = TRUE;
  					// process the image (save, resize, crop, etc.)
  					$fname = $this->_process_img($a['attachment'], $n[1]);

  					break;
  				}
  			}
  		}

  		// if there was no image, move the email to the Rejected folder on the server
  		if ( ! $found_img) {
  			$this->move($email['index'], 'INBOX.Rejected');
  			continue;
  		}

  		// get content from the email that I want to store
  		$addr   = $email['header']->from[0]->mailbox."@".$email['header']->from[0]->host;
  		$sender = $email['header']->from[0]->mailbox;
  		$text   = ( ! empty($email['header']->subject) ? $email['header']->subject : '');

  		// move the email to Processed folder on the server
  		$this->move($email['index'], 'INBOX.Processed');

  		// add the data to the database
  		var_dump(array(
  			'username'    => $sender,
  			'email'       => $addr,
  			'photo'       => $fname,
  			'description' => ($text == '' ? NULL : $text)
  		));exit;

  		// don't slam the server
  		sleep(1);
  	}

  	// close the connection to the IMAP server
  	$this->close();
  }

    // close the server connection
    function close() {
      $this->inbox = array();
      $this->msg_cnt = 0;

      imap_close($this->conn);
    }

    // open the server connection
    // the imap_open function parameters will need to be changed for the particular server
    // these are laid out to connect to a Dreamhost IMAP server
    function connect() {
      $this->conn = imap_open($this->server, $this->user, $this->pass);
    }

    // move the message to a new folder
    function move($msg_index, $folder='INBOX.Processed') {
      // move on server
      imap_mail_move($this->conn, $msg_index, $folder);
      imap_expunge($this->conn);

      // re-read the inbox
      $this->inbox();
    }

    // get a specific message (1 = first email, 2 = second email, etc.)
    function get($msg_index=NULL) {
      if (count($this->inbox) <= 0) {
        return array();
      }
      elseif ( ! is_null($msg_index) && isset($this->inbox[$msg_index])) {
        return $this->inbox[$msg_index];
      }

      return $this->inbox[0];
    }

    // read the inbox
    function inbox() {
      $this->msg_cnt = imap_num_msg($this->conn);

      $in = array();
      for($i = 1; $i <= $this->msg_cnt; $i++) {
        $in[] = array(
          'index'     => $i,
          'header'    => imap_headerinfo($this->conn, $i),
          'body'      => imap_body($this->conn, $i),
          'structure' => imap_fetchstructure($this->conn, $i)
        );
      }
      $this->inbox = $in;
    }
}
