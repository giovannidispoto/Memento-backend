<?php
        //require_once("PHPUnit/Autoload.php");
        require_once("MongoUtilities.class.php");

         function my_autoload($class){
                require_once("$class.class.php");
       }

         spl_autoload_register('my_autoload');

    class Database{

        private $host = 'localhost'; //parametri accesso db
        private $port = 27010;
        private $username = '';
        private $password = '';
        private $dbname="memento";
        private $conn;
        private $salt = "mementoauderesemper"; //salt da aggiungere alle password, prima di calcolare il digest
        private $handler;


      public function connect(){ //funzione per connessione al database
              try{
                $this->conn = new MongoClient();
                $this->handler = $this->conn->selectDB($this->dbname);
              }catch(MongoConnectionException $e){
                die('An Error occured<br>'.$e);
              }
        }

        public function __construct(){
                $handler = $this->connect(); //crea una istanza di connessione al DB
          }

        public function authUser($user_id, $password){ //autenticazione
          $user = array(
            '_id' => $user_id,
            'password' => hash("sha512",$password.$this->salt) 
          );//calcolo il digest della password
          try{

                $res = $this->handler->users->find($user);
            }catch(MongoException $e){
              die("An error occured.<br>".$e->getMessage());
            }

             return ($res->count() == 1)? true: false; //se l'utente esiste, ritorno true

        }

        public function getUsers(){//funzione per ottenere la lista degli utenti

          try{

            $res = $this->handler->users->find();
          }catch(MongoException $e){
            die("An error occured.<br>".$e->getMessage());
          }

          //$users = MongoUtilities::cursor_to_array($res);
          return $res;
        }

        public function insertMedia($path,$description,$hash_tags,$user_id){ //funzione per inserire i media nel db
            try{
                $res = $this->handler->media->insert(array(
                        "user_id" => array( $this->handler->createDBRef('users',$user_id)),
                        "description" => $description,
                        "hashtags" => array_values($hash_tags),
                        "media" => $path ,
                        "date" => time()
                    )
                );
            }catch(MongoException $e){
              die("An Error occured<br>".$e);
            }
            return true;
        }

        public function findUser($user_id){//funzione per trovare l'utente
          try{
            $res = $this->handler->users->find(array("_id" => $user_id));
          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }
            return MongoUtilities::cursor_to_array($res);
        }

        public function dropUser($user_id){//funzione per cancellare un utente

            try{
                $res = $this->handler->users->drop(array("_id" => $user_id));
            }catch(MongoException $e){
                die("An Error occured<br>".$e);
            }

            return true;
        }

        public function getUserProfile($user_id){//funzione per ottenere i media di un utente
            try{
                $res = $this->handler->media->find(array('user_id.$id'=>$user_id))->sort(array("date" => 1));
            }catch(MongoException $e){
              die("An Error Occured<br>".$e->getMessage());
            }

           return $res;
        }

        public function createUser($avatar,$name,$surname,$e_mail,$user_id,$password,$date_of_birth,$sex){ //funzione per creare un utente
          $user = array(
            "_id" => $user_id,
              "avatar" => $avatar,
            "name" => $name,
            "surname" => $surname,
            "e_mail" => $e_mail,
              "password" => hash("sha512",$password.$this->salt),//calcolo il digest della password + il salt
            "date_of_birth" => $date_of_birth,
              "avatar" => "mucca.jpg",
            "sex" => $sex
          );

          try{
            $user = $this->handler->users->insert($user); //inserisco l'utente
              $this->startFollow($user_id,$user_id);

          }catch(MongoException $e){
              die("An Error occured<br>".$e);
          }

            return true;
        }

        public function insertComment($user_id,$comment,$media_id){ //funzione per inserire i commenti
            try {
                $res = $this->handler->media->update(array("_id" => new  MongoId($media_id)),array('$push' => array('comments' => array("user_id" => $user_id,"comment" => $comment))));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return true;
        }

        public function checkEmail($email){ //funzione per vedere se l'email è già in uso
            try{
                $res = $this->handler->users->find(array("e_mail" => $email))->count();
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return ($res == 0)? true: false;
        }

        public function getMediaByHashtag($hashtag){
            try{
                $res = $this->handler->media->find(array("hashtag" => $hashtag));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            return $res;
        }

        public function checkUsername($user_id){//funzione per vedere se lo username è già in uso
            try{
                $res = $this->handler->users->find(array("user_id" => $user_id))->count();
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return ($res == 0)? true: false;
        }

        public function insertLike($user_id,$media_id){//funzione per inserire il like
            try{
                $res = $this->handler->media->update(array("_id" => new MongoId($media_id)), array('$push'=> array("like" => $user_id)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            //print_r($res);
            return true;
        }

        public function removeLike($user_id,$media_id){//funzione per inserire il like
            try{
                $res = $this->handler->media->update(array("_id" => new MongoId($media_id)), array('$pop'=> array("like" => $user_id)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            //print_r($res);
            return true;
        }

        public function checkToken($user, $token){
            try{
                $res = $this->handler->users->find(array("_id" => $user, "sessions" => array('$elemMatch' => array("token" => $token))))->count();
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }

            return ($res == 1)? true : false;
        }

        public function getFollowing($user){
            try{
                $res = $this->handler->users->find(array("_id" => $user), array("following" => 1));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            foreach($res as $element){
                $person[] = $element['following'][0];
            }
            return $person;
        }

        public function getHomeMedia($user){

            try{
                $res_ = $this->handler->users->find(array('_id' => $user),array("following" => 1));

                //die(print_r($res_['following']));
                foreach($res_ as $element){
                    $users = $element['following'];
                }

                $res = $this->handler->media->find(array('user_id.$id' => array('$in' => $users)))->sort(array('date' => 1) ); //order by date
                foreach($res as $element){

            }
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }


          /*  foreach($res as $element){
                echo $element['user_id'][0]['$id']."<br>"";
                //$element['avatar'] = $this->getAvatar($element['user_id']['$id']); //aggiungo l'avatar dell'utente
            }*/
            return $res;
        }


        public function getAvatar($user_id){
            try{
                $res = $this->handler->users->find(array("_id" => $user_id, array("avatar" => 1));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            foreach ($res as $element){
                return $element['avatar'];
            }
        }

        public function startFollow($user, $user_to_follow){
            try{
                $res = $this->handler->users->update(array("_id" => $user_to_follow), array('$push'=> array("followers" => $user)));
                $res_2 = $this->handler->users->update(array("_id" => $user), array('$push' => array("following" => $user_to_follow)));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
           return true;
        }

        public function getFollowers($user){
            try{
                $res = $this->handler->users->find(array("_id"=>$user),array("followers" => 1));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            foreach($res as $follower){
                $followers[] = $follower;
            }

            return (!empty($followers))? $followers : false;
        }

        public function getPhotoDetails($photo_id){
            try{
                $res = $this->handler->media->findOne(array("_id" => new MongoId($photo_id)));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            return $res;
        }

        public function stopFollow($user, $user_to_stop_following){
            try{
                 $this->handler->users->update(array("_id" => $user_to_stop_following), array('$pull'=> array("followers" => $user)));
                 $this->handler->users->update(array("_id" => $user), array('$pull' => array("following" => $user_to_stop_following)));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
            return true;
        }

        public function registerSession($user_id,$token,$time,$ip){
            $time = $time + 31536000000;
            try{
                $res = $this->handler->users->update(array("_id" => $user_id), array('$push' => array("sessions" => array("token" => $token, "ip"=>$ip, "expire" => $time))));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            //print_r($res);
        }

    }

 ?>
