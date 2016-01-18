<?php
        function my_autoloader($class){
            require_once("Class/$class.class.php");
        }
        
    spl_autoload_register('my_autoloader');

  if(isset($_COOKIE['id'])){//controllo che sia stato impostato il cookie
        if(isset($_REQUEST['user']) && !empty($_REQUEST['user'])){
            if(isset($_GET['action']) && !empty($_GET['action'])){
                switch($_GET['action']){
                  default:
                            echo "Your request: ".$_GET['action']." for ".$_GET['user'];
                            break;
                }
            }
        }
    }else{
      if(isset($_REQUEST['user']) && !empty($_REQUEST['user'])){
              if(isset($_GET['action']) && $_GET['action'] == 'auth'){

                $db = new Database();

                switch($_GET['action']){
                  case "auth":
                                    $username = htmlspecialchars($_REQUEST['user'],ENT_QUOTES,'utf-8');
                                    $password = htmlspecialchars($_POST['password'],ENT_QUOTES,'utf-8');
                                    $res = $db->authUser($username,$password);
                                    print_r($res);

                                    break;
                  case "create_user":
                                    $name = htmlspecialchars($_POST['name'],ENT_QUOTES,'utf-8');
                                    $surname = htmlspecialchars($_POST['surname'],ENT_QUOTES,'utf-8');
                                    $e_mail = htmlspecialchars($_POST['e_mail'],ENT_QUOTES,'utf-8');
                                    $username = htmlspecialchars($_POST['username'],ENT_QUOTES,'utf-8');
                                    $password = htmlspecialchars($_POST['password'],ENT_QUOTES,'utf-8');
                                    $date_of_birth = htmlspecialchars($_POST['date_of_bird'],ENT_QUOTES,'utf-8');
                                    $sex = htmlspecialchars($_POST['sex'],ENT_QUOTES,'utf-8');
                                    $res = $db->createUser($name,$surname,$e_mail,$username,$password,$date_of_birth,$sex);
                                    if(!$res) die("An error occured while creating user account");
                                    break;

                }


              }
        }
      }
      echo "<h1>Memento</h1><p>A simple photo and video sharing app";
?>
