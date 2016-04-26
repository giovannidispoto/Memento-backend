<?php
        //require_once("PHPUnit/Autoload.php");
        require_once("MongoUtilities.class.php");

         function my_autoload($class){
                require_once("$class.class.php");
       }
        use MongoDB\BSON\Regex as Regex;

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
             $this->connect(); //crea una istanza di connessione al DB
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
                        'user_id' => array( $this->handler->createDBRef('users',$user_id)),
                        "description" => $description,
                        "hashtags" => array_values($hash_tags),
                        "media" => $path ,
                        "date" => date('Y-m-d  H:i:s')
                    )
                );
            }catch(MongoException $e){
              die("An Error occured<br>".$e->getMessage());
            }
            return true;
        }

    /*   public function replaceUser($old_user, $new_user){
            try{
                $res = $this->handler->medias->update(array("_id" => $old_user), array('$set' => $new_user));
            }catch(MongoException $e){
                die("An Error occured<br>".$e->getMessage());
            }
        }

        public function updateProfile($avatar,$name,$surname,$e_mail,$user_id,$password,$date_of_birth,$sex){
             $user_data = array(
                "avatar" => $avatar,
                 "name" => $name,
                "surname" => $surname,
                "e_mail" => $e_mail,
                "password" => hash("sha512",$password.$this->salt),//calcolo il digest della password + il salt
                "date_of_birth" => $date_of_birth,
                "sex" => $sex
          );
            try{
                $res = $this->handler->update(array("_id" => $user_id ), $user_data);
              }catch(MongoException $e){
                die("An Error occured<br>".$e->getMessage());
            }

               return $res;
        }*/

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
                $res = $this->handler->users->remove(array("_id" => $user_id));
                $res2 = $this->handler->media->remove(array("user_id" => array(new MongoDBRef("users",$user_id))));
            }catch(MongoException $e){
                die("An Error occured<br>".$e);
            }

            return true;
        }

        public function getUserProfile($user_id){//funzione per ottenere i media di un utente
            try{
                $res = $this->handler->media->find(array('user_id.$id'=>$user_id))->sort(array("date" => -1));
            }catch(MongoException $e){
              die("An Error Occured<br>".$e->getMessage());
            }

           return $res;
        }

        public function createUser($avatar,$name,$surname,$e_mail,$user_id,$password,$date_of_birth,$sex){ //funzione per creare un utente
          $user = array(
                "_id" => $user_id,
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
                $res = $this->handler->media->find(array("hashtags" => $hashtag));
               foreach($res as $element){
                   $photos[] = $element;
               }
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            return (isset($photos))? $photos : null;
        }

        public function checkUsername($user_id){//funzione per vedere se lo username è già in uso
            try{
                $res = $this->handler->users->find(array("_id" => $user_id))->count();
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            return ($res == 0)? true: false;
        }

        public function insertLike($user_id,$media_id){//funzione per inserire il like
            try{
                $res = $this->handler->media->update(array("_id" => new MongoId($media_id)), array('$push'=> array("like" => $user_id)), array("upsert" => true));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            //print_r($res);
            return true;
        }

        public function removeLike($user_id,$media_id){//funzione per inserire il like
            try{
                $res = $this->handler->media->update(array("_id" => new MongoId($media_id)), array('$pull'=> array("like" => $user_id)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
            //print_r($res);
            return true;
        }

        public function getHashtagList($hashtag){
            try{
                    $res = $this->handler->media->aggregate( array(
                          //array('$match' => array('hashtags' => '/ci/')),
                           array('$unwind' => '$hashtags'),
                          //array('$match' =>  array('hashtags' => '/ci/')),
                            //array('$match' => array('hashtags' => array('$regex' => '//'))),
                           array('$group' => array("_id" => '$hashtags'))

                        )
                    );
                $tmp = $res['result'];
                foreach($tmp as $element){
                    $hashtags[] = $element['_id'];
                }
                $hashtags = preg_grep("/^$hashtag/",$hashtags);
              // die(print_r($hashtags));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }

            return $hashtags;
        }

        public function dropMedia($media_id){
            try{
                    $res = $this->handler->media->remove(array("_id" => new MongoId($media_id)));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }
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


        public function checkPrivileges($user,$photo_id){
            try{
                $res = $this->handler->media->find( array("_id" => $photo_id, "user_id" => $user ));
                foreach($res as $element){
                    $results[] = $element;
                }
                die(print_r($results));
            }catch(MongoException $e){
                die("An Error Occured<br>".$e->getMessage());
            }

        }

        public function getUserList($username){
            try{
                $res = $this->handler->users->find(array("_id" => new MongoRegex("/^$username/")),array("_id" => 1,"avatar" => 1));
                $users = array();
                foreach($res as $element){
                    $users[] = $element;
                }
               // die(print_r($users));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
            return (isset($users))?$users:null;
        }


        public function getHomeMedia($user){

            try{
                $res_ = $this->handler->users->find(array('_id' => $user),array("following" => 1));

                //die(print_r($res_['following']));
                foreach($res_ as $element){
                    $users = $element['following'];
                }

                $res = $this->handler->media->find(array('user_id.$id' => array('$in' => $users)))->sort(array('date' => -1) ); //order by date
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
                $res = $this->handler->users->find(array("_id" => $user_id), array("avatar" => 1));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }

            foreach ($res as $element){
                return $element['avatar'];
            }
        }

        public function startFollow($user, $user_to_follow){
            try{
                $res = $this->handler->users->update(array("_id" => $user) , array('$push'=> array("following" => $user_to_follow)), array("upsert" => true));
               // $res_2 = $this->handler->users->update(array("_id" => $user), array('$push' => array("following" => $user_to_follow)));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
           return true;
        }

        public function isFollowing($user,$user_to_check){

            try{
                $res = $this->handler->users->find(array("_id" => $user, "following" => $user_to_check ))->count();
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
            return ($res > 0)? true: false;
        }
        public function getFollowing($user)
        {
            try {
                $res = $this->handler->users->find(array("_id" => $user), array("following" => 1));
            } catch (MongoException $e) {
                die("Something went wrong <br>" . $e->getMessage());
            }

            foreach ($res as $element) {
                if ($element['following'][0] == $user) continue;
                $following[] = $element['following'][0];
            }

            return (isset($following)) ? $following : null;
        }

        public function getFollowers($user){
            try{
                $res = $this->handler->users->find(array("following" => $user),array("_id" => 1));
            }catch(MongoException $e){
                die("Something went wrong <br>".$e->getMessage());
            }
            foreach($res as $follower){
                if($follower['_id'] == $user) continue;
                $followers[] = $follower['_id'];
            }

            return (isset($followers))? $followers : null;
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
                 $this->handler->users->update(array("_id" => $user), array('$pull'=> array("following" => $user_to_stop_following)));
                 //$this->handler->users->update(array("_id" => $user), array('$pull' => array("following" => $user_to_stop_following)));
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
