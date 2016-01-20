<?php
        require_once("PHPUnit/Autoload.php");
        require_once("MongoUtilities.class.php");

//          function my_autoload($class){
//              require_once("$class.class.php");
//          }
//
//          spl_autoload_register('my_autoload');

    class Database{

        private $host = 'localhost';
        private $port = 27010;
        private $username = '';
        private $password = '';
        private $conn;
        private $salt = "mementoauderesemper"; //salt da aggiungere alle password, prima di calcolare il digest


      public function connect($dbname){ //funzione per connessione al database
              try{
                $this->conn = new MongoClient();
                $db = $this->conn->selectDB($dbname);
              }catch(MongoConnectionException $e){
                die('An Error occured<br>'.$e);
              }
              return $db;
        }

        public function authUser($username, $password){ //autenticazione
          $user = array(
            '_id' => $username,
            'password' => hash("sha512",$password.$this->salt)
          );
          try{
                $db = $this->connect('login');
                $res = $db->login->find($user)->count();
            }catch(MongoException $e){
              die("An error occured.<br>".$e->getMessage());
            }
            if($res == 0) return false;

            $user = $this->findUser($username);

            return $res;
            //return $user;
        }

        public function getUsers(){

          try{
            $db = $this->connect('users');
            $res = $db->users->find();
          }catch(MongoException $e){
            die("An error occured.<br>".$e->getMessage());
          }

          $users = MongoUtilities::cursor_to_array($res);
          return $users;
        }

        public function insertMedia($media, $user_id){ //funzione per inserire i media nel db
            try{
                  $db = $this->connect('media');
                  $gridFs = $db->media->getGridFs(); //utilizzo il gridFs per salvare i dati binari
                  $path = "/tmp/"; //percorso dei media
                  $storedFile = $gridFs->storeFile(
                  $path.$media,
                  array("metadata" => array("user" => $user_id, "date" => time())),
                  array("filename" => $media)
                );
            }catch(MongoException $e){
              die("An Error occured<br>".$e);
            }
            $res = MongoUtilities::cursor_to_array($storedFile);
            if($res['ok'] == 1) return true;
            else return false;
        }

        public function findUser($username){
          $db = $this->connect('users');
          try{
            $res = $db->users->find(array("_id" => $username));
          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }
            return true;
        }

        public function dropUser($username){
            $db = $this->connect('users');
            $db_login = $this->connect('login');
            try{
                $res = $db->users->drop(array("_id" => $username));
                $res = $db_login->login->drop(array("_id" => $username));
            }catch(MongoException $e){
                die("An Error occured<br>".$e);
            }

            return true;
        }

        public function getMedia($user_id){
            try{
                $db = $this->connect("media");
                $res = $db->media->find(array("user" => $user_id));
            }catch(MongoException $e){
              die("An Error Occured<br>".$e->getMessage());
            }
            $media = MongoUtilities::cursor_to_array($res);
            return $media;
        }

        public function createUser($name,$surname,$e_mail,$username,$password,$date_of_birth,$sex){
          $user = array(
            "_id" => $username,
            "name" => $name,
            "surname" => $surname,
            "e_mail" => $e_mail,
            "date_of_birth" => $date_of_birth,
            "sex" => $sex
          );
          $db = $this->connect('users');
          try{
            $res = $db->users->insert($user);

          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }

          $user_credential = array(
            "_id" => $username,
            "password" => hash("sha512",$password.$this->salt)
          );
          $db = $this->connect('login');
          try{
              $res = $db->login->insert($user_credential);
          }catch(MongoException $e){
              die("An Error Occured<br>".$e->getMessage());
          }
          return true;
        }

        public function insertComment($user_id,$comment,$media_id){
            try {
                $db = $this->connect('media');
                $res = $db->media->update(array("_id" => $media_id),array('push' => array("user" => $user_id,"comment" => $comment)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return $res;

        }

        public function insertLike($user_id,$media_id){
            try{
                $db = $this->connect('media');
                $res = $db->media->update(array("_id" => $media_id),array('$push'=>array("like" => $user_id)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }

            return $res;
        }

    }

 ?>
