<?php
/* function my_autoloader($class){
     require_once("Class/$class.class.php"); // funzione per importare automaticamente le classi richiamate
 }*/

//spl_autoload_register('my_autoloader');

require_once("Class/Database.class.php");
require_once("Class/Analytics.class.php");
require_once("Class/User.class.php");
require_once("Class/Medias.class.php");
require 'vendor/autoload.php';

use Coreproc\Gcm\GcmClient;
use Coreproc\Gcm\Classes\Message;
use Memento\Database;
use Memento\User;
use Memento\Medias;
use Memento\Analytics;


define("TESTING", false); //flag testing
define("API_KEY", "AIzaSyBET8b8BadEmmjrU2-vV0dXfuT8UhUWLVo");
define("LIKE", 1);
define("COMMENT", 2);
define("MENTION", 3);
define("FOLLOW", 4);
$db = new Database();

if ((isset($_POST['token']) && isset($_POST['user_id'])) || TESTING) {//controllo che sia stato impostato il cookie

    if (isset($_GET['action']) && !empty($_GET['action'])) {


        $user = new User($db->getConnection());
        $medias = new Medias($db->getConnection());

        $user_id = (!TESTING) ? $_POST['user_id'] : "test";
        $token = (!TESTING) ? $_POST['token'] : "test";
        $client = new GcmClient(API_KEY);

        if (!$user->checkToken($user_id, $token) && !TESTING) die(json_encode(array("error" => "Incorrect token"))); //se non esiste nessuna accoppiata token-utente, restituisco l'errore
        switch ($_GET['action']) { //routes URL
            case 'insert_media':
                $description = $_POST['description'];  //descrizione foto
                $media_name = uniqid(time()) . ".jpg";  //prendo il nome del file
                $media_type = $_FILES['file']['type'];
                $tmp_name = $_FILES['file']['tmp_name'];
                $media_size = $_FILES['file']['size'];
                if (!move_uploaded_file($_FILES['file']['tmp_name'], "uploads/$media_name")) { //sposto il file nella dir dei media
                    die('Error uploading file - check destination is writeable.');
                }
                $hashtags_mentions = (isset($_POST['hashtags_mentions']))?$_POST['hashtags_mentions']:null;
                $path = $media_name;
                //$tmp = explode(" ", $description); //isolo le parole
                //die(print_r($tmp));
                if(!empty($hashtags_mentions)){
                    $hashtags_to_strip = preg_grep("/^#/", $hashtags_mentions); //estraggo tutti gli hashtag
                    $metions = preg_grep("/^@/", $hashtags_mentions);
                    foreach ($hashtags_to_strip as $hashtag) {
                        $hashtags[] = substr($hashtag, 1);
                    }
                }
                if(!isset($hashtags)) $hashtags = array();
                $res = $medias->insertMedia($path, $description, $hashtags, $user_id); //inserisco il file
                echo json_encode(array("success" => true));

                break;

            case "check":
                $user = $_REQUEST['user'];
                $id = $_REQUEST['id'];
                $res = $user->checkPrivileges($user_id, $id);
                echo json_encode(array("privileges" => $res));
                break;

            case 'get_user_profile':
                //die("Get user photo");
                //$username = htmlspecialchars($_POST['user_id'], ENT_QUOTES, 'utf-8');
                if (isset($_REQUEST['username']) && !empty($_REQUEST['username'])) {
                    $username = htmlspecialchars($_REQUEST['username'], ENT_QUOTES, 'utf-8');
                    $res = $user->getUserProfile($username); //ottengo il profilo dell'utente
                    $medias = array();
                    foreach ($res as $element) {
                        $medias[] = $element; //inserisco le foto in un array
                    }
                    $response = array(
                        "avatar" => $user->getAvatar($username),
                        "following" => $user->getFollowing($username),
                        "followers" => $user->getFollowers($username),

                    );
                    if ($username != $user) $response["is_following"] = $user->isFollowing($user_id, $username);
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
                    $res = $medias->getMediaByHashtag($hashtag); //ottengo le foto che hanno l'hashtag richiesto
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
                    $res = $medias->getHashtagList($hashtag); //ottengo le foto che hanno l'hashtag richiesto
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
                    $avatar = $user->getAvatar($user_id);
                }


                // if($db->checkUsername($user) or die("Error") or die("Error")) { //controllo che l'email o lo username non sia già presente nel db
                $res = $user->updateProfile($avatar, $name, $surname, $e_mail, $user_id, $date_of_birth);
                echo json_encode(array("success" => $res));

                //   }
                break;

            case 'get_photo': //get_photos
                // die(print_r($_POST));
                $res = $medias->getHomeMedia($_REQUEST['user_id']); //ottengo i media per la home page
                // $res = array("action" => "getting photo");
                foreach ($res as $element) {
                    // die($db->getAvatar($element['user_id'][0]['$id']));
                    $element['avatar'] = $user->getAvatar($element['user_id'][0]['$id']); //inserisco l'avatar dell'utente
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
                        $user->startFollow($user, $user_to_act);
                        $tokens = $user->retreiveToken($user_to_act);
                        (count($tokens) == 0)? $user->appendNotification($user, $user_to_act, 4) : sendNotification($client, $tokens, "Memento", "$user_id ha iniziato a seguirti");
                        $user->insertNotification($user_to_act, $user_id, "$user ha iniziarto a seguirti");
                    } else {
                        $user->stopFollow($_POST['user_id'], $_POST['user_to_act']);
                    }
                    // echo json_encode(array("success" => $res));
                }

                break;

            case 'insert_like':
                if (isset($_REQUEST['media_id'])) {
                    $res = $medias->insertLike($user_id, $_REQUEST['media_id']); //inserisco like ad un media
                    echo json_encode(array("success" => $res));
                    $user_to_act = getUserFromPhoto($medias->getPhotoDetails($_REQUEST['media_id']));
                    $tokens = $db->retreiveToken($user_to_act);
                    (count($tokens) > 0) ? sendNotification($client, $tokens, "Memento", "A $user piace la tua foto") : $user->appendNotification($user_id, $user_to_act, 1);
                    $user->insertNotification($user_to_act, $user_id, LIKE , $_REQUEST['media_id']);
                    $user->logUser($user, time(), $_REQUEST['media_id']);
                }

                break;

            case 'remove_like':
                if (isset($_REQUEST['media_id'])) {
                    $res = $medias->removeLike($user_id, $_REQUEST['media_id']); //rimuovo il like da un media
                    $to = getUserFromPhoto($medias->getPhotoDetails($_REQUEST['media_id']));
                    echo json_encode(array("success" => $res));
                    $user->removeNotification($to, $user_id , $_REQUEST['media_id'], LIKE);
                }
                break;
            case 'get_photo_details':
                $photo_id = $_REQUEST['media_id'];
                $res = $medias->getPhotoDetails($photo_id); //ottengo la foto che si vuole visualizzare
                $user_id = getUserFromPhoto($res);
                $media;
                foreach ($res as $k => $v) {
                    $media[$k] = $v;
                }
                if (isset($media['comments'])) {
                    foreach ($media['comments'] as &$comment) {
                        $comment['avatar'] = $user->getAvatar($comment['user_id']);
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
                    "avatar" => $user->getAvatar($user_id), //inserisco l'avatar
                    "photo" => $media //inserisco la foto
                );

                echo json_encode($response);
                break;

            case 'insert_comment':

                if (isset($_POST['comment']) && isset($_POST['media_id'])) {
                    $comment = $_POST['comment'];
                    $media_id = $_POST['media_id'];
                    $res = $medias->insertComment($user_id, $comment, $media_id); //inserisco commento
                    $id = getUserFromPhoto($medias->getPhotoDetails($media_id));
                    $tokens = $user->retreiveToken($id);
                    (count($token) == 0) ? $user->appendNotification($user, $user_to_act, 2) : sendNotification($client, $tokens, "Memento", "$user_id ha commentato la tua foto");

                    $user->insertNotification($id, $user_id, LIKE , $media_id);
                    $user->logUser($user_id, time(), $media_id);
                } else {
                    $res = false;
                }
                echo json_encode(array("success" => $res));


                break;

            case 'drop_media':
                if (isset($_POST['media_id']) && !empty($_POST['media_id'])) {
                    $media_id = htmlspecialchars($_POST['media_id'], ENT_QUOTES, 'utf-8');
                    $res = $medias->dropMedia($media_id);
                    echo json_encode(array("success" => $res));
                }

                break;

            case 'change_pssw':
                if (isset($_POST['old_pssw']) && isset($_POST['new_pssw'])) {
                    $old_pssw = $_POST['old_pssw'];
                    $new_pssw = $_POST['new_pssw'];

                    $res = $user->authUser($user_id, $old_pssw);

                    if ($res) {
                        $change = $user->changePassw($user_id, $new_pssw);
                        echo json_encode(array("success" => $change));
                    } else {
                        echo json_encode(array("error" => "error with privileges"));
                    }
                }

                break;

            case 'get_user_list':
                if (isset($_REQUEST['user']) && !empty($_REQUEST['user'])) {
                    $username = htmlspecialchars($_REQUEST['user'], ENT_QUOTES, 'UTF-8');
                    $res = $user->getUserList($username);
                    if (!empty($res)) echo json_encode($res);
                    else echo json_encode(array("error" => "nothing was found"));
                }

                break;

            case 'get_info_profile':
                if (isset($user)) {
                    $res = $user->getInfoProfile($_REQUEST['user_id'], true);
                    echo json_encode($res);
                }
                break;
            case 'get_sessions':
                //$user = $_REQUEST['user'];
                $response = $user->getSessions($user_id);

                if ($response == null) echo json_encode(array("success" => "false"));
                else echo json_encode($response);
                break;
            case 'destroy_session':
                $token_gcm = htmlspecialchars($_POST['token_gcm'], ENT_QUOTES);
                if (isset($_POST['token_del'])) $session_to_del = $_POST['token_del'];
                $res = $user->closeSession($user_id, (!isset($session_to_del)) ? $token : $session_to_del);
                $user->unsetGCMToken($user_id, $token_gcm);
                echo json_encode(array("success" => $res));

                break;
            case 'register_token_notification':
                if (isset($_POST['token_register'])) {
                    $token_client = htmlspecialchars($_POST['token_register'], ENT_QUOTES);
                    $user->registerTokenNotification($user_id, $token, $token_client);
                    $notifications = $user->getAppendedNotifications($user_id);
                    sendNotifications($client, array($token_client), $notifications);

                }
                break;
            case 'get_notifications':
                        $notifications = $user->getNotifications($user_id);
                        if($notifications == null){
                            echo json_encode(array("error" => "notifications not found"));

                        }else {
                            //die(print_r($notifications));
                            foreach ($notifications as $notification) {
                                foreach ($notification['notifications'] as $row) {
                                    $row['avatar'] = $user->getAvatar($row['from']);
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
                                    $row['media'] = $medias->getMediaFromId($row['media_id']);
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

            $user = new User($db->getConnection());


            switch ($_GET['action']) {
                case "auth":
                    $username = htmlspecialchars($_REQUEST['username'], ENT_QUOTES, 'utf-8'); //trasformo tutti i caratteri in caratteri html per evitare attacchi ti ogni genere
                    $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'utf-8');
                    $res = $user->authUser($username, $password);

                    if (!$res) die(json_encode(array("success" => false, "error" => "User not found")));

                    $token = sha1(uniqid($username));
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $user->registerSession($username, $token, time(), $ip);
                    $rs = array(
                        "success" => true,
                        "user_id" => $username,
                        "token" => $token,
                        "avatar" => $user->getAvatar($username),
                    );

                    echo json_encode($rs);
                    break;


                case "check_username":
                    $user_id = htmlspecialchars($_REQUEST['username'], ENT_QUOTES, 'utf-8');
                    $res = $user->checkUsername($user_id);
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
                    if ($user->checkUsername($username) or die("Error") or die("Error")) { //controllo che l'email o lo username non sia già presente nel db
                        $res = $user->createUser("null", $name, $surname, $e_mail, $username, $password, $date_of_birth, $sex);

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

    $user_id = $photo['user_id'][0]['$id'];


    return $user_id;
}

function sendNotification($client, $reg_id, $title, $body)
{

    foreach ($reg_id as $id) { //per ogni id presente nel DB
        $message = new Message($client); //creo il nuono messaggio
        $message->addRegistrationId($id); //aggiungo l'id del dispositivo
        $message->setData([ //aggiungo i dati
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
