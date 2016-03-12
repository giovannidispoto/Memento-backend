<?php
        function my_autoloader($class){
            require_once("Class/$class.class.php");
        }
        
    spl_autoload_register('my_autoloader');

    define("TESTING", false);


  if((isset($_POST['token']) && isset($_POST['user_id'])) || TESTING){//controllo che sia stato impostato il cookie
            if(isset($_GET['action']) && !empty($_GET['action'])){
                $db = new Database();
                $user = $_POST['user_id'];
                $token = $_POST['token'];
                if(!$db->checkToken($user,$token)) die(json_encode(array("error" => "Incorrect token")));
                switch($_GET['action']){
                    case 'insert_media':
                                        if(isset($_POST['file'])){ //se è impostata la variabile file, passata tramite il form
                                            $description = $_POST['description'];
                                            $media_name = $_FILES['file']['name']; //prendo il nome del file
                                            $media_type = $_FILES['file']['type'];//tipo
                                            $tmp_name = $_FILES['file']['tmp_name'];
                                            $media_size = $_FILES['file']['size'];
                                            if(!move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name'])) { //sposto il file nella dir dei media
                                                die('Error uploading file - check destination is writeable.');
                                            }
                                            $path = "uploads/".$media_name;
                                           $res =  $db->insertMedia($path,$description,array("test","ciaone","eimarò?"),$_COOKIE['id']); //inserisco il file


                                        }else{//se il file non è impostato, mostro il form di invio dei file
                                            $user_id = $_COOKIE['id'];
                                            include App::view("send_file");
                                        }
                                        break;

                    case 'get_user_photo':
                                            //die("Get user photo");
                                            if(isset($_GET['user']) && !empty($_GET['user'])){
                                                $username = htmlspecialchars($_GET['user'],ENT_QUOTES,'utf-8');
                                                $res = $db->getMedia($username);
                                                print_r($res);
                                            }else{
                                                die("Insert username");
                                            }

                                            break;

                    case 'get_photo_by_hashtag':

                                           // die("Search photo by hashtag");
                                           if(isset($_GET['hashtag']) && !empty($_GET["hashtag"])){

                                               $hashtag = htmlspecialchars($_GET['hashtag'],ENT_QUOTES,"utf-8");
                                               $res = $db->getPhotoByHashtag($hashtag);
                                               print_r($res);
                                           }else{
                                               echo "Error - Hashtag not found";
                                           }
                                            break;

                    case 'get_photo':
                                            $res = $db->getHomePhoto(function($testing){
                                                if($testing) return $_POST['user_id'];
                                                return $_COOKIE['id'];
                                            });
                                           $res = array("action" => "getting photo");
                                            echo json_encode($res);

                                           break;

                  default:
                            echo "Your request: ".$_GET['action']." for ".$_GET['user'];
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
                    case "cookie":
                        echo json_encode($_COOKIE);
                        //echo "ok";
                                        break;

                }


              }
         }else{
          include "View/login.html.php";
        }
      }
?>
