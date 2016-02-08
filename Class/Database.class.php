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

        public function authUser($email, $password){ //autenticazione
          $user = array(
            'e_mail' => $email,
            'password' => hash("sha512",$password.$this->salt)
          );
          try{
                $db = $this->connect('users');
                $res = $db->users->find($user);
            }catch(MongoException $e){
              die("An error occured.<br>".$e->getMessage());
            }

            //return $res;
            $user =  ($res->count() == 1)?true:false;
            if($user){
                $response = array(
                    "response" => true,
                    "user" => MongoUtilities::cursor_to_array($res)

            );
                return $response;
            }

           return false;

        }

        public function getUsers(){//funzione per ottenere la lista degli utenti

          try{
            $db = $this->connect('users');
            $res = $db->users->find();
          }catch(MongoException $e){
            die("An error occured.<br>".$e->getMessage());
          }

          $users = MongoUtilities::cursor_to_array($res);
          return $users;
        }

        public function insertMedia($path,$description,$hash_tags,$user_id){ //funzione per inserire i media nel db
            try{
                  $db = $this->connect('media');
                $res = $db->media->insert(array(
                        "user" => $user_id,
                        "description" => $description,
                        "hashtags" => array_values($hash_tags),
                        "media" => $path ,"date" => time()
                    )
                );
            }catch(MongoException $e){
              die("An Error occured<br>".$e);
            }
            print_r($res);
        }

        public function findUser($username){//funzione per trovare l'utente
          $db = $this->connect('users');
          try{
            $res = $db->users->find(array("_id" => $username));
          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }
            return MongoUtilities::cursor_to_array($res);
        }

        public function dropUser($username){//funzione per cancellare un utente
            $db = $this->connect('users');

            try{
                $res = $db->users->drop(array("_id" => $username));
            }catch(MongoException $e){
                die("An Error occured<br>".$e);
            }

            return true;
        }

        public function getMedia($user_id){//funzione per ottenere i media di un utente
            try{
                $db = $this->connect("media");
                $res = $db->media->find(array("user"=>$user_id));
            }catch(MongoException $e){
              die("An Error Occured<br>".$e->getMessage());
            }

            return MongoUtilities::cursor_to_array($res);
        }

        public function createUser($name,$surname,$e_mail,$username,$password,$date_of_birth,$sex){
          $user = array(
            "_id" => $username,
            "name" => $name,
            "surname" => $surname,
            "e_mail" => $e_mail,
              "password" => hash("sha512",$password.$this->salt),//calcolo il digest della password + il salt
            "date_of_birth" => $date_of_birth,
            "sex" => $sex
          );
          $db = $this->connect('users');
          try{
            $user = $db->users->insert($user);

          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }

            return true;
        }

        public function insertComment($user_id,$comment,$media_id){ //funzione per inserire i commenti
            try {
                $db = $this->connect('media');
                $res = $db->media->update(array("_id" => Mongoid($media_id)),array('push' => array("user" => $user_id,"comment" => $comment)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return $res;

        }

        public function checkEmail($email){ //funzione per vedere se l'email è già in uso
                $db = $this->connect('users');
            try{
                $res = $db->users->find(array("e_mail" => $email))->count();
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return ($res == 0)? true: false;
        }

        public function getPhotoByHashtag($hashtag){
            try{
                $db = $this->connect("media");
                $res = $db->media->find(array("hashtag" => $hashtag));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            return $res;
        }

        public function checkUsername($username){//funzione per vedere se lo username è già in uso
            $db = $this->connect('users');
            try{
                $res = $db->users->find(array("username" => $username))->count();
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return ($res == 0)? true: false;
        }

        public function insertLike($user_id,$media_id){//funzione per inserire il like
            try{
                $db = $this->connect('media');
                $res = $db->media->update(array("_id" => new MongoId($media_id)), array('$push'=> array("like" => $user_id)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            print_r($res);
            return true;
        }

        public function getGallery($user){

            try{
                $db = $this->connect("media");
                $res = $db->media->find(array("username" => array('$in' => $this->getFollowers($user))))->sort(array('date' => 1) ); //order by date
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
            return $res;
        }

        public function startFollow($user, $user_to_follow){
            try{
                $db = $this->connect("users");
                $res = $db->users->update(array("_id" => $user_to_follow), array('push'=> array("followers" => $user)));
                $res_2 = $db->users->update(array("_id" => $user), array('push' => array("following" => $user_to_follow)));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
            var_dump($res);
            var_dump($res_2);
        }

        public function getFollowers($user){
            try{
                $db = $this->connect("users");
                $res = $db->users->find(array("_id"=>$user),array("followers" => 1));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            foreach($res as $follower){
                $followers[] = $follower;
            }

            return (!empty($followers))? $followers : false;
        }


        public function stopFollow($user, $user_to_stop_following){
            try{
                $db = $this->connect("users");
                $res = $db->users->update(array("_id" => $user_to_stop_following), array('pulll'=> array("followers" => $user)));
                $res_2 = $db->users->update(array("_id" => $user), array('pull' => array("following" => $user_to_stop_following)));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
            var_dump($res);
            var_dump($res_2);
        }

    }

 ?>
