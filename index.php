<?php
        function my_autoloader($class){
            require_once("Class/$class.class.php");
        }
        
    spl_autoload_register('my_autoloader');

  if(isset($_COOKIE['id'])){//controllo che sia stato impostato il cookie
        if(isset($_REQUEST['user']) && !empty($_REQUEST['user'])){
            if(isset($_GET['action']) && !empty($_GET['action'])){
                switch($_GET['action']){
                    case 'insert_media':
                                        if(isset($_POST['media'])){

                                        }else{
                                            $user_id = $_COOKIE['id'];
                                            include App::view("send_file");
                                        }
                                        break;
                  default:
                            echo "Your request: ".$_GET['action']." for ".$_GET['user'];
                            break;
                }
            }
        }else{
            echo "Bevenuto ".$_COOKIE['name']." ".$_COOKIE['surname'];
        }

    }else{

      if(isset($_REQUEST['username']) && !empty($_REQUEST['username'])){
              if(isset($_GET['action'])){

                $db = new Database();

                switch($_GET['action']){
                  case "auth":
                                    $username = htmlspecialchars($_REQUEST['username'],ENT_QUOTES,'utf-8');
                                    $password = htmlspecialchars($_POST['password'],ENT_QUOTES,'utf-8');
                                    $res = $db->authUser($username,$password);
                                    if(!$res) die("Utente non trovato!");
                                      setcookie("id",$res[$username]['_id'],time()+1000);
                                      setcookie("name",$res[$username]['name'],time()+1000);
                                      setcookie("surname",$res[$username]['surname'],time()+1000);
                                      header("Location: .");
                                    break;

                    case "create_user":
                                    $name = htmlspecialchars($_POST['name'],ENT_QUOTES,'utf-8');
                                    $surname = htmlspecialchars($_POST['surname'],ENT_QUOTES,'utf-8');
                                    $e_mail = htmlspecialchars($_POST['e_mail'],ENT_QUOTES,'utf-8');
                                    $username = htmlspecialchars($_POST['username'],ENT_QUOTES,'utf-8');
                                    $password = htmlspecialchars($_POST['password'],ENT_QUOTES,'utf-8');
                                    $date_of_birth = htmlspecialchars($_POST['date_of_birth'],ENT_QUOTES,'utf-8');
                                    $sex = htmlspecialchars($_POST['sex'],ENT_QUOTES,'utf-8');
                                    $res = $db->createUser($name,$surname,$e_mail,$username,$password,$date_of_birth,$sex);
                                    if(!$res) die("An error occured while creating user account");
                                    else{
                                        echo "Grazie per esserti registrato $name $surname";
                                        setcookie("id",$username,time()+1000);
                                        setcookie("name",$name,time()+1000);
                                        setcookie("surname",$surname,time()+100);
                                        header("Location: .");

                                    }
                                    break;

                }


              }
         }else{
          include "View/login.html.php";
        }
      }
?>
