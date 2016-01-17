<?php
    spl_autoload_register(function _autoload($class){
        require_once '$class.class.php';
    });
    class Database{

        private $host = 'localhost';
        private $port = 27010;
        private $username = '';
        private $password = '';
        private $conn;


        public connect($dbname){

              try{
                $this->conn = new MongoClient();
                $db = $this->conn->selectDB('$db');
              }catch(MongoConnectionException $e){
                die('An Error occured<br>'.$e);
              }
              return $db;
        }

        public insertMedia($media, $user_id){
          //  $db = $this->conn->selectDB('media');
            $db = connect('media');
            $gridFs = $db->getGridFs();
            $path = "/tmp/";
            try{
                $storedFile = $gridFs->storeFile(
                  $path.$fileName,
                  array("metadata" => array("user" => $user_id, "date" => time())),
                  array("filename" => $filename)
                );
            }catch(MongoException $e){
              die("An Error occured<br>".$e);
            }
            $res = iterator_to_array($res);
            return ($res['ok'] == 1) true : false;
        }

        public findUser($username){
          $db = connect('users');
          try{
            $res = $db->find(array("username" => $username));
          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }
          $res = MongoUtilities::cursor_to_array($res);
            return $res;
        }

        public createUser($name,$surname,$e_mail,$username,$password,$date_of_bird,$sex){
          $user = array(
            "name" => $name,
            "surname" => $surname,
            "e-mail" => $e_mail,
            "username" => $username,
            "date_of_bird" => $date_of_bird,
            "sex" => $sex
          );
          $db = connect('users');
          try{
            $res = $db->save($user);

          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }
          $res = MongoUtilities::cursor_to_array($res);
          $user_id = $res['_id'];

          $user_credential = array(
            'user_id' => $user_id,
            'password' => hash("sha512",$password)
          );
          $db = connect('login');
          try{
              $res = $db->save($user);
          }catch(MongoException $e){
              die("An Error Occured<br>".$e->getMessage());
          }
        }


    }
 ?>
