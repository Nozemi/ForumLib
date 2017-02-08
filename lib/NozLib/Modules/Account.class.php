<?php
  namespace NozLib\Modules;

  class Account {
    public $username;  // Account username.
    public $group;     // Array of account group details
    public $email;     // Account email address.
    public $lastlogin; // Array of last login detials.
    public $about;

    public function __construct(PSQL $S, $uname, $pword) {
      // Do the login with $uname and $pword.
    }

    private function login($uname, $pword) {

    }

    public function updateAccount() {

    }
  }
