<?php
        function my_autoloader($class){
            require_once("Class/$class.class.php"); // funzione per importare automaticamente le classi richiamate
        }
        
    spl_autoload_register('my_autoloader');

    define("TESTING", false); //flag testing

  if(isset($_POST['token']) && isset($_POST['user_id'])){//controllo che sia stato impostato il cookie

            if(isset($_GET['action']) && !empty($_GET['action'])){
                $db = new Database(); 
                $user = $_POST['user_id'];
                $token = $_POST['token'];

                if(!$db->checkToken($user,$token)) die(json_encode(array("error" => "Incorrect token"))); //se non esiste nessuna accoppiata token-utente, restituisco l'errore
                switch($_GET['action']){ //routes URL
                    case 'insert_media':
                                       // if(isset($_POST['file'])){ //se è impostata la variabile file, passata tramite il form
                                           // die(print_r($_POST));
                                            $description = $_POST['description'];  //descrizione foto
                                            $media_name =  uniqid(time()).".jpg";  //prendo il nome del file
                                            $media_type = $_FILES['file']['type']; 
                                            $tmp_name = $_FILES['file']['tmp_name'];
                                            $media_size = $_FILES['file']['size'];
                                            if(!move_uploaded_file($_FILES['file']['tmp_name'], "uploads/$media_name")) { //sposto il file nella dir dei media
                                                die('Error uploading file - check destination is writeable.');
                                            }
                                            $path = $media_name;
                                            $res =  $db->insertMedia($path,$description,array("test","ciaone","eimarò?"),$_POST['user_id']); //inserisco il file
                                            echo json_encode(array("success" => true));


                                      //  }else{//se il file non è impostato, mostro il form di invio dei file
                                        //    echo json_encode(array("success" => false));
                                        //}
                                        break;

                    case 'get_user_profile':
                                            //die("Get user photo");
                                                $username = htmlspecialchars($_POST['user_id'], ENT_QUOTES, 'utf-8');
                                                $res = $db->getUserProfile($username); //ottengo il profilo dell'utente
                                                foreach($res as $element) {
                                                    $medias[] = $element; //inserisco le foto in un array
                                                }
                                                    $response = array(
                                                        "avatar" => "mucca.jpg"
                                                    );
                                                //print_r($res);
                                                if(count($medias) > 0 ){
                                                    $response["photos"] = $medias; //inserisco le foto, nel caso ci siano
                                                }
                                                echo json_encode($response); //stampo il JSON

                                            break;

                    case 'get_photo_by_hashtag':

                                           // die("Search photo by hashtag");
                                           if(isset($_POST['hashtag']) && !empty($_POST["hashtag"])){ 

                                               $hashtag = htmlspecialchars($_POST['hashtag'],ENT_QUOTES,"utf-8");
                                               $res = $db->getMediaByHashtag($hashtag); //ottengo le foto che hanno l'hashtag richiesto
                                               echo json_encode($res);
                                           }else{
                                               echo json_encode(array("error" => "Nothing was found"));
                                           }
                                            break;

                    case 'get_photo': //get_photos
                                               // die(print_r($_POST));
                                            $res = $db->getHomeMedia($_POST['user_id']); //ottengo i media per la home page
                                          // $res = array("action" => "getting photo");
                                               foreach($res as $element){

                                                   $element['avatar'] =  $db->getAvatar($element['user_id'][0]['$id']); //inserisco l'avatar dell'utente
                                                   $photos[]= $element;
                                               }
                                            //   echo count($photos);
                                           echo json_encode($photos);

                                           break;
                    case 'start_following':
                                                if(isset($_POST['user_to_follow'])){
                                                    $res = $db->startFollow($_POST['user'],$_POST['user_to_follow']); //inizio a seguire una utente
                                                    echo json_encode(array("success" => $res));
                                                }

                                             break;

                    case 'stop_following':      if(isset($_POST['user_to_unfollow'])){
                                                        $res = $db->stopFollow($_POST['user'],$_POST['user_to_follow']); //smetto di seguire una persona
                                                          echo json_encode(array("success" => $res));
                                                    }

                                             break;
                    case 'insert_like':
                                                if(isset($_POST['media_id'])){
                                                    $res = $db->insertLike($_POST['user_id'],$_POST['media_id']); //inserisco like ad un media
                                                    echo json_encode(array("success" => $res));
                                                }

                                             break;

                    case 'remove_like':
                                                    if(isset($_POST['media_id'])){
                                                      $res = $db->removeLike($_POST['user_id'],$_POST['media_id']); //rimuovo il like da un media
                                                       echo json_encode(array("success" => $res));
                                                     }
                                             break;
                    case 'get_photo_details':
                                                $photo_id = $_POST['media_id'];
                                                $res = $db->getPhotoDetails($photo_id); //ottengo la foto che si vuole visualizzare
                                                $user_id = getUserFromPhoto($res);
                                                $media;
                                                foreach($res as $k => $v){
                                                    $media[$k] = $v;
                                                }
                                                $response = array(
                                                    "user_id" => $user_id, //inserisco il nome utente
                                                    "avatar" => $db->getAvatar($user_id), //inserisco l'avatar
                                                    "photo" => $media //inserisco la foto
                                                );
                                                echo json_encode($response);
                                                break;

                    case 'insert_comment':
                                                if(isset($_POST['comment']) && isset($_POST['media_id'])){ 
                                                    $comment = $_POST['comment'];
                                                    $media_id = $_POST['media_id'];
                                                    $res = $db->insertComment($user,$comment,$media_id); //inscerisco commento
                                                }else{
                                                    $res = false;
                                                }
                                                 echo json_encode(array("success" => $res));


                                                break;
                  default:
                            echo "Your request: ".$_REQUEST['action']." for ".$_REQUEST['user_id'];
                            break;
                }

        }else{
            echo "<h1>Memento Backend</h1>";

        }

    }else{

      if(isset($_REQUEST['username']) && !empty($_REQUEST['username'])){
              if(isset($_GET['action'])){

                $db = new Database();

                switch($_GET['action']){
                  case "auth":
                                    $username = htmlspecialchars($_REQUEST['username'],ENT_QUOTES,'utf-8'); //trasformo tutti i caratteri in caratteri html per evitare attacchi ti ogni genere
                                    $password = htmlspecialchars($_POST['password'],ENT_QUOTES,'utf-8');
                                    $res = $db->authUser($username,$password);
                                   // die(print_r($res));

                                    if(!$res) die(json_encode(array("success" => false, "error" => "User not found")));
                                        //imposto i cookie
                                     /*
                                      * setcookie("id",$res['user'][$username]['_id'],time()+10000);
                                        setcookie("name",$res['user'][$username]['name'],time()+10000);
                                        setcookie("surname",$res['user'][$username]['surname'],time()+10000);

                                        header("Location: .");//ricarico la pagina
                                     */
                                    $token = sha1(uniqid($username));
                                    $ip = $_SERVER['REMOTE_ADDR'];
                                    $db->registerSession($username,$token,time(),$ip);
                                    $rs = array(
                                        "success" => true,
                                        "user_id" => $username,
                                        "token" => $token
                                    );
                      echo json_encode($rs);
                                    break;

                    case "create_user":
                                    $name = htmlspecialchars($_POST['name'],ENT_QUOTES,'utf-8');
                                    $surname = htmlspecialchars($_POST['surname'],ENT_QUOTES,'utf-8');
                                    $e_mail = htmlspecialchars($_POST['e_mail'],ENT_QUOTES,'utf-8');
                                    $username = htmlspecialchars($_POST['username'],ENT_QUOTES,'utf-8');
                                    $password = htmlspecialchars($_POST['password'],ENT_QUOTES,'utf-8');
                                    $date_of_birth = htmlspecialchars($_POST['date_of_birth'],ENT_QUOTES,'utf-8');
                                    $sex = htmlspecialchars($_POST['sex'],ENT_QUOTES,'utf-8');
                                    if($db->checkUsername($username) or die("Error") && $db->checkEmail($email) or die("Error")){ //controllo che l'email o lo username non sia già presente nel db
                                        $res = $db->createUser("null",$name,$surname,$e_mail,$username,$password,$date_of_birth,$sex);
                                        if(!$res) die("An error occured while creating user account");
                                        else{
                                            //imposto i cookie
                                           /* setcookie("id",$username,time()+1000);
                                            setcookie("name",$name,time()+1000);
                                            setcookie("surname",$surname,time()+1000);
                                            header("Location: .");*/
                                           $success = true;

                                        }
                                    }
                                    echo json_encode(function($success, $error){
                                        if($success){
                                            $response = array("success" => true);
                                        }else{
                                            $response = array("success" => false, "error"=> $error);
                                        }
                                        return $response;
                                    });

                                    break;
              

                }


              }
         }else{
          include "View/login.html.php";
        }
      }

function getUserFromPhoto($photo){

            $user = $photo['user_id'][0]['$id'];


    return $user;
}
?>
