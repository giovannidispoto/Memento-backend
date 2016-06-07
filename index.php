<?php
/* function my_autoloader($class){
     require_once("Class/$class.class.php"); // funzione per importare automaticamente le classi richiamate
 }*/

//spl_autoload_register('my_autoloader');

require_once("Class/Database.class.php");
require_once("Class/Analytics.class.php");
require 'vendor/autoload.php';

use Coreproc\Gcm\GcmClient as GcmClient;
use Coreproc\Gcm\Classes\Message as Message;


define("TESTING", true); //flag testing
define("API_KEY", "AIzaSyBET8b8BadEmmjrU2-vV0dXfuT8UhUWLVo");
define("LIKE", 1);
define("COMMENT", 2);
define("MENTION", 3);
define("FOLLOW", 4);

if ((isset($_POST['token']) && isset($_POST['user_id'])) || TESTING) {//controllo che sia stato impostato il cookie

    if (isset($_GET['action']) && !empty($_GET['action'])) {
        $db = new Database();
        $user = (!TESTING) ? $_POST['user_id'] : "test";
        $token = (!TESTING) ? $_POST['token'] : "test";
        $client = new GcmClient(API_KEY);

        if (!$db->checkToken($user, $token) && !TESTING) die(json_encode(array("error" => "Incorrect token"))); //se non esiste nessuna accoppiata token-utente, restituisco l'errore
        switch ($_GET['action']) { //routes URL
            case 'insert_media':
                // if(isset($_POST['file'])){ //se è impostata la variabile file, passata tramite il form
                // die(print_r($_POST));
                $description = $_POST['description'];  //descrizione foto
                $media_name = uniqid(time()) . ".jpg";  //prendo il nome del file
                $media_type = $_FILES['file']['type'];
                $tmp_name = $_FILES['file']['tmp_name'];
                $media_size = $_FILES['file']['size'];
                if (!move_uploaded_file($_FILES['file']['tmp_name'], "uploads/$media_name")) { //sposto il file nella dir dei media
                    die('Error uploading file - check destination is writeable.');
                }
                $path = $media_name;
                $tmp = explode(" ", $description);
                //die(print_r($tmp));
                $elements = preg_grep("/^#/", $tmp);
                $hashtags = array();
                foreach ($elements as $element) {
                    $hashtags[] = substr($element, 1);
                }
                $res = $db->insertMedia($path, $description, $hashtags, $user); //inserisco il file
                echo json_encode(array("success" => true));


                //  }else{//se il file non è impostato, mostro il form di invio dei file
                //    echo json_encode(array("success" => false));
                //}
                break;

            case "check":
                $user = $_REQUEST['user'];
                $id = $_REQUEST['id'];
                $res = $db->checkPrivileges($user, $id);
                echo json_encode(array("privileges" => $res));
                break;

            case 'get_user_profile':
                //die("Get user photo");
                //$username = htmlspecialchars($_POST['user_id'], ENT_QUOTES, 'utf-8');
                if (isset($_REQUEST['username']) && !empty($_REQUEST['username'])) {
                    $username = htmlspecialchars($_REQUEST['username'], ENT_QUOTES, 'utf-8');
                    $res = $db->getUserProfile($username); //ottengo il profilo dell'utente
                    $medias = array();
                    foreach ($res as $element) {
                        $medias[] = $element; //inserisco le foto in un array
                    }
                    $response = array(
                        "avatar" => $db->getAvatar($username),
                        "following" => $db->getFollowing($username),
                        "followers" => $db->getFollowers($username),

                    );
                    if ($username != $user) $response["is_following"] = $db->isFollowing($user, $username);
                    //print_r($res);
                    if (count($medias) > 0) {
                        $response["photos"] = $medias; //inserisco le foto, nel caso ci siano
                    }
                    echo json_encode($response, JSON_PRETTY_PRINT); //stampo il JSON
                }

                break;

            case 'get_photo_by_hashtag':

                // die("Search photo by hashtag");
                if (isset($_REQUEST['hashtag']) && !empty($_REQUEST["hashtag"])) {

                    $hashtag = htmlspecialchars($_REQUEST['hashtag'], ENT_QUOTES, "utf-8");
                    $res = $db->getMediaByHashtag($hashtag); //ottengo le foto che hanno l'hashtag richiesto
                    if ($res != null) {
                        echo json_encode($res);
                    } else
                        echo json_encode(array("error" => "Nothing was found"));
                } else {
                    echo json_encode(array("error" => "Nothing was found"));
                }
                break;
            case 'get_hashtag_list':
                if (isset($_REQUEST['hashtag']) && !empty($_REQUEST["hashtag"])) {

                    $hashtag = htmlspecialchars($_REQUEST['hashtag'], ENT_QUOTES, "utf-8");
                    $res = $db->getHashtagList($hashtag); //ottengo le foto che hanno l'hashtag richiesto
                    //die(print_r($res));
                    if ($res != null) {
                        //print_r($res);
                        foreach ($res as $element) {
                            $response[] = $element;
                        }
                        echo json_encode($response);
                    } else
                        echo json_encode(array("error" => "Nothing was found"));
                } else {
                    echo json_encode(array("error" => "Nothing was found"));
                }

                break;
            case "update_profile":
                // die(print_r(getimagesize($_FILES['file']['tmp_name'])));

                $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'utf-8');
                $surname = htmlspecialchars($_POST['surname'], ENT_QUOTES, 'utf-8');
                $e_mail = htmlspecialchars($_POST['e_mail'], ENT_QUOTES, 'utf-8');
                // $password = htmlspecialchars($_POST['password'],ENT_QUOTES,'utf-8');
                $date_of_birth = htmlspecialchars($_POST['date_of_birth'], ENT_QUOTES, 'utf-8');
                // $sex = htmlspecialchars($_POST['sex'],ENT_QUOTES,'utf-8');

                if (isset($_FILES['file'])) {
                    $mime = getimagesize($_FILES['file']['tmp_name']);
                    // die(print_r($mime));
                    $media_type = explode('/', $mime['mime']);
                    $media_type = $media_type[1];
                    $media_name = uniqid(time()) . ".$media_type";  //prendo il nome del file
                    $tmp_name = $_FILES['file']['tmp_name'];
                    //$media_size = $_FILES['file']['size'];
                    if (!move_uploaded_file($_FILES['file']['tmp_name'], "avatar/$media_name")) { //sposto il file nella dir dei media
                        die('Error uploading file - check destination is writeable.');
                    }
                    $avatar = $media_name;
                } else {
                    $avatar = $db->getAvatar($user);
                }


                // if($db->checkUsername($user) or die("Error") or die("Error")) { //controllo che l'email o lo username non sia già presente nel db
                $res = $db->updateProfile($avatar, $name, $surname, $e_mail, $user, $date_of_birth);
                echo json_encode(array("success" => $res));

                //   }
                break;

            case 'get_photo': //get_photos
                // die(print_r($_POST));
                $res = $db->getHomeMedia($_POST['user_id']); //ottengo i media per la home page
                // $res = array("action" => "getting photo");
                foreach ($res as $element) {
                    // die($db->getAvatar($element['user_id'][0]['$id']));
                    $element['avatar'] = $db->getAvatar($element['user_id'][0]['$id']); //inserisco l'avatar dell'utente
                    $photos[] = $element;
                }
                //   echo count($photos);
                if (isset($photos) && !empty($photos)) echo json_encode($photos);
                else echo json_encode(array("error" => "photos not found"));

                break;
            case 'follow_act':
                if (isset($_POST['user_to_act']) && isset($_POST['action'])) {
                    $action = (intval($_POST['action']) == 1) ? true : false;
                    $user_to_act = htmlspecialchars($_POST['user_to_act']);
                    if ($action) {
                        $db->startFollow($user, $user_to_act);
                        $tokens = $db->retreiveToken($user_to_act);
                        (count($tokens) == 0)? $db->appendNotification($user, $user_to_act, 4) : sendNotification($client, $tokens, "Memento", "$user ha iniziato a seguirti");
                        $db->insertNotification($user_to_act, $user, "$user ha iniziarto a seguirti");
                    } else {
                        $db->stopFollow($_POST['user_id'], $_POST['user_to_act']);
                    }
                    // echo json_encode(array("success" => $res));
                }

                break;

            case 'insert_like':
                if (isset($_REQUEST['media_id'])) {
                    $res = $db->insertLike($user, $_REQUEST['media_id']); //inserisco like ad un media
                    echo json_encode(array("success" => $res));
                    $user_to_act = getUserFromPhoto($db->getPhotoDetails($_REQUEST['media_id']));
                    $tokens = $db->retreiveToken($user_to_act);
                    (count($tokens) > 0) ? sendNotification($client, $tokens, "Memento", "A $user piace la tua foto") : $db->appendNotification($user, $user_to_act, 1);
                    $db->insertNotification($user_to_act, $user, LIKE , $_REQUEST['media_id']);
                    $db->logUser($user, time(), $_REQUEST['media_id']);
                }

                break;

            case 'remove_like':
                if (isset($_REQUEST['media_id'])) {
                    $res = $db->removeLike($user, $_REQUEST['media_id']); //rimuovo il like da un media
                    $to = getUserFromPhoto($db->getPhotoDetails($_REQUEST['media_id']));
                    echo json_encode(array("success" => $res));
                    $db->removeNotification($to, $user , $_REQUEST['media_id'], LIKE);
                }
                break;
            case 'get_photo_details':
                $photo_id = $_REQUEST['media_id'];
                $res = $db->getPhotoDetails($photo_id); //ottengo la foto che si vuole visualizzare
                $user_id = getUserFromPhoto($res);
                $media;
                foreach ($res as $k => $v) {
                    $media[$k] = $v;
                }
                if (isset($media['comments'])) {
                    foreach ($media['comments'] as &$comment) {
                        $comment['avatar'] = $db->getAvatar($comment['user_id']);
                    }
                }
                /*   if(isset($media['likes'])){
                        foreach($media['likes'] as &$like){
                            $like['avatar'] = $db->getAvatar($like)
                        }
                    }*/

                //die(print_r($media['comments']));
                $response = array(
                    "user_id" => $user_id, //inserisco il nome utente
                    "avatar" => $db->getAvatar($user_id), //inserisco l'avatar
                    "photo" => $media //inserisco la foto
                );

                echo json_encode($response);
                break;

            case 'insert_comment':

                if (isset($_POST['comment']) && isset($_POST['media_id'])) {
                    $comment = $_POST['comment'];
                    $media_id = $_POST['media_id'];
                    $res = $db->insertComment($user, $comment, $media_id); //inserisco commento
                    $id = getUserFromPhoto($db->getPhotoDetails($media_id));
                    $tokens = $db->retreiveToken($id);
                    (count($token) == 0) ? $db->appendNotification($user, $user_to_act, 2) : sendNotification($client, $tokens, "Memento", "$user ha commentato la tua foto");

                    $db->insertNotification($id, $user, LIKE , $media_id);
                    $db->logUser($user, time(), $media_id);
                } else {
                    $res = false;
                }
                echo json_encode(array("success" => $res));


                break;

            case 'drop_media':
                if (isset($_POST['media_id']) && !empty($_POST['media_id'])) {
                    $media_id = htmlspecialchars($_POST['media_id'], ENT_QUOTES, 'utf-8');
                    $res = $db->dropMedia($media_id);
                    echo json_encode(array("success" => $res));
                }

                break;

            case 'change_pssw':
                if (isset($_POST['old_pssw']) && isset($_POST['new_pssw'])) {
                    $old_pssw = $_POST['old_pssw'];
                    $new_pssw = $_POST['new_pssw'];

                    $res = $db->authUser(user, $old_pssw);

                    if ($res) {
                        $change = $db->changePassw($user, $new_pssw);
                        echo json_encode(array("success" => $change));
                    } else {
                        echo json_encode(array("error" => "error with privileges"));
                    }
                }

                break;

            case 'get_user_list':
                if (isset($_REQUEST['user']) && !empty($_REQUEST['user'])) {
                    $username = htmlspecialchars($_REQUEST['user'], ENT_QUOTES, 'UTF-8');
                    $res = $db->getUserList($username);
                    if (!empty($res)) echo json_encode($res);
                    else echo json_encode(array("error" => "nothing was found"));
                }

                break;

            case 'get_info_profile':
                if (isset($user)) {
                    $res = $db->getInfoProfile($_REQUEST['user_id'], true);
                    echo json_encode($res);
                }
                break;
            case 'get_sessions':
                //$user = $_REQUEST['user'];
                $response = $db->getSessions($user);

                if ($response == null) echo json_encode(array("success" => "false"));
                else echo json_encode($response);
                break;
            case 'destroy_session':
                $token_gcm = htmlspecialchars($_POST['token_gcm'], ENT_QUOTES);
                if (isset($_POST['token_del'])) $session_to_del = $_POST['token_del'];
                $res = $db->closeSession($user, (!isset($session_to_del)) ? $token : $session_to_del);
                $db->unsetGCMToken($user, $token_gcm);
                echo json_encode(array("success" => $res));

                break;
            case 'register_token_notification':
                if (isset($_POST['token_register'])) {
                    $token_client = htmlspecialchars($_POST['token_register'], ENT_QUOTES);
                    $db->registerTokenNotification($user, $token, $token_client);
                    $notifications = $db->getAppendedNotifications($user);
                    sendNotifications($client, array($token_client), $notifications);

                }
                break;
            case 'get_notifications':
                        $notifications = $db->getNotifications($user);
                        if($notifications == null){
                            echo json_encode(array("error" => "notifications not found"));

                        }else {
                            //die(print_r($notifications));
                            foreach ($notifications as $notification) {
                                foreach ($notification['notifications'] as $row) {
                                    $row['avatar'] = $db->getAvatar($row['from']);
                                    switch ($row['notification']) {
                                        case LIKE:
                                            $row['notification'] = "A " . $row['from'] . " piace la tua foto";
                                            break;
                                        case COMMENT:
                                            $row['notification'] = $row['from'] . "ha commentato la tua foto";
                                            break;
                                        case FOLLOW:
                                            $row['notification'] = $row['from'] . " ha iniziato a seguirti";
                                            break;
                                        case MENTION:
                                            $row['notification'] = $row['from'] . "ti ha menzionato in un commento";
                                            break;
                                    }
                                    $row['media'] = $db->getMediaFromId($row['media_id']);
                                    $append[] = $row;
                                }
                            }
                            echo json_encode($append);
                        }
                break;

            case 'get_photo_recommended':
                                        if(!isset($_GET['page'])) $offset = 0;
                                        else $offset= intval($_GET['page']);

                                        $analytic = new Analytics($db);
                                        $photos = $analytic->getPhotosRecommended($_REQUEST['user_id'], $offset);
                                        if(!empty($photos)) echo json_encode($photos);
                                        else echo json_encode(array("error" => "photos not found"));
                                        break;
            default:
                echo "Your request: " . $_REQUEST['action'] . " for " . $_REQUEST['user_id'];
                break;
        }

    } else {
        echo "<h1>Memento Backend</h1>";

    }

} else {

    if (isset($_REQUEST['username']) && !empty($_REQUEST['username'])) {
        if (isset($_GET['action'])) {

            $db = new Database();

            switch ($_GET['action']) {
                case "auth":
                    $username = htmlspecialchars($_REQUEST['username'], ENT_QUOTES, 'utf-8'); //trasformo tutti i caratteri in caratteri html per evitare attacchi ti ogni genere
                    $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'utf-8');
                    $res = $db->authUser($username, $password);

                    if (!$res) die(json_encode(array("success" => false, "error" => "User not found")));

                    $token = sha1(uniqid($username));
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $db->registerSession($username, $token, time(), $ip);
                    $rs = array(
                        "success" => true,
                        "user_id" => $username,
                        "token" => $token
                    );

                    echo json_encode($rs);
                    break;


                case "check_username":
                    $user = htmlspecialchars($_REQUEST['username'], ENT_QUOTES, 'utf-8');
                    $res = $db->checkUsername($user);
                    echo json_encode(array("success" => $res));

                    break;


                case "create_user":
                    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'utf-8');
                    $surname = htmlspecialchars($_POST['surname'], ENT_QUOTES, 'utf-8');
                    $e_mail = htmlspecialchars($_POST['e_mail'], ENT_QUOTES, 'utf-8');
                    $username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'utf-8');
                    $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'utf-8');
                    $date_of_birth = htmlspecialchars($_POST['date_of_birth'], ENT_QUOTES, 'utf-8');
                    $sex = htmlspecialchars($_POST['sex'], ENT_QUOTES, 'utf-8');
                    if ($db->checkUsername($username) or die("Error") or die("Error")) { //controllo che l'email o lo username non sia già presente nel db
                        $res = $db->createUser("null", $name, $surname, $e_mail, $username, $password, $date_of_birth, $sex);

                        if (!$res)
                            die("An error occured while creating user account");
                        else
                            $success = true;

                    }
                    echo json_encode(function ($success, $error) {
                        if ($success)
                            return array("success" => true);

                        return array("success" => false, "error" => $error);


                    });

                    break;


            }


        }
    } else {
        include "View/login.html.php";
    }
}

function getUserFromPhoto($photo)
{

    $user = $photo['user_id'][0]['$id'];


    return $user;
}

function sendNotification($client, $reg_id, $title, $body)
{

    foreach ($reg_id as $id) {
        $message = new Message($client);
        $message->addRegistrationId($id);
        $message->setData([
            'title' => $title,
            'message' => $body
        ]);

        try {
            $message->send();
        } catch (Exception $e) {
            die("Error while sending push notification <br>" . $e->getMessage());
        }
    }
}

function sendNotifications($client, $tokens, $notifications)
{
    foreach ($notifications as $notification) {
        switch ($notification['type']) {

            case LIKE:
                sendNotification($client, $tokens, "Memento", "A ".$notification['from']." piace la tua foto");
                break;

            case COMMENT:
                sendNotification($client, $tokens, "Memento", $notification['from']." ha commentato la tua foto");
                break;

            case MENTION:
                sendNotification($client, $tokens, "Memento", $notification['from']." ti ha citato in un commento");
                break;

            case FOLLOW:
                sendNotification($client, $tokens, "Memento", $notification['from']." ha iniziato a seguirti");
                break;

        }
    }
}


?>
