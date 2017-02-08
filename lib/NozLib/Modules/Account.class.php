<?php
  namespace NozLib\Modules;

  class Account {
    public $username;  // Account username.
    public $group;     // Array of account group details
    public $email;     // Account email address.
    public $lastlogin; // Array of last login detials.
    public $about;
    public $firstname;
    public $lastname;

    private $password;

    private $S;

    public function __construct(PSQL $SQL) {
      // We'll check if the required parameters are filled.
      if(!is_null($S)) {
        $this->S = $SQL;
      } else {
        return 'Something went wrong with the login.';
      }
    }

    public function login() {
      $S->prepareQuery("");
      $S->executeQuery();
      return $S->fetch();
    }

    public function register() {
      if(is_null($this->password) || is_null($this->username)) {
        return array(
          'error' => 1,
          'message' => 'Username and/or password not provided.'
        );
      } else {
        $this->S->prepareQuery("");
        $this->S->executeQuery("");
      }
    }

    public function setPassword($p1, $p2) {
      if($p1 == $p2) {
        $this->password = password_hash($p1, PASSWORD_BCRYPT);
      } else {
        return array(
          'error' => 1,
          'message' => 'Passwords don\'t match.'
        );
      }
    }

    public function updateAccount() {

    }
  }
