<?php
          function my_autoload($class){
              require_once("Class/$class.class.php");
          }

          spl_autoload_register('my_autoload');

    class Database{

        private $host = 'localhost';
        private $port = 27010;
        private $username = '';
        private $password = '';
        private $conn;
        private $salt= "mementoauderesemper"; //salt da aggiungere alle password, prima di calcolare il digest


      public function connect($dbname){ //funzione per connessione al database
              try{
                $this->conn = new MongoClient();
                $db = $this->conn->selectDB('$db');
              }catch(MongoConnectionException $e){
                die('An Error occured<br>'.$e);
              }
              return $db;
        }

        public function authUser($username, $password){ //autenticazione
          $user = array(
            '_id' => $username,
            'password' => hash("sha512",$password.$salt)
          );
          try{
                $db = connect('login');
                $res = $db->find($user)->count();
            }catch(MongoException $e){
              die("An error occured.<br>".$e->getMessage());
            }
            $res = MongoUtilities::cursor_to_array($res); //trasformo il cursore MongoDB ottenuto in un array associativo
            return $res;
        }

        public function getUsers(){

          try{
            $db = connect('users');
            $res = $db->find();
          }catch(MongoException $e){
            die("An error occured.<br>".$e->getMessage());
          }

          $users = MongoUtilities::cursor_to_array($res);
          return $users;
        }

        public function insertMedia($media, $user_id){ //funzione per inserire i media nel db
            try{
                  $db = connect('media');
                  $gridFs = $db->getGridFs(); //utilizzo il gridFs per salvare i dati binari
                  $path = "/tmp/"; //percorso dei media
                  $storedFile = $gridFs->storeFile(
                  $path.$fileName,
                  array("metadata" => array("user" => $user_id, "date" => time())),
                  array("filename" => $filename)
                );
            }catch(MongoException $e){
              die("An Error occured<br>".$e);
            }
            $res = MongoUtilities::cursor_to_array($res);
            if($res['ok'] == 1) return true;
            else return false;
        }

        public function findUser($username){
          $db = connect('users');
          try{
            $res = $db->find(array("username" => $username));
          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }
          $res = MongoUtilities::cursor_to_array($res);
            return $res;
        }

        public function getMedia($user_id){
            try{
                $db = connect("media");
                $res = $db->find(array("user" => $user_id));
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
          $db = connect('users');
          try{
            $res = $db->save($user);

          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }

          $user_credential = array(
            "_id" => $username,
            "password" => hash("sha512",$password.$salt)
          );
          $db = connect('login');
          try{
              $res = $db->save($user);
          }catch(MongoException $e){
              die("An Error Occured<br>".$e->getMessage());
          }
          return true;
        }
    }
 ?>
