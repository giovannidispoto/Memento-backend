<?php


namespace Memento;
use MongoException;
use MongoDBRef;


class User
{
    private $salt = "mementoauderesemper"; //salt da aggiungere alle password, prima di calcolare il digest
    private $handler;
    const LIMIT = 30;

    public function __construct($db_handler)
    {
        $this->handler = $db_handler;
    }

    public function authUser($user_id, $password)
    { //autenticazione
        $user = array(
            '_id' => $user_id,
            'password' => hash("sha512", $password . $this->salt)
        );//calcolo il digest della password
        try {

            $res = $this->handler->users->find($user);
        } catch (MongoException $e) {
            die("An error occured.<br>" . $e->getMessage());
        }

        return ($res->count() == 1) ? true : false; //se l'utente esiste, ritorno true

    }

    public function getUsers()
    {//funzione per ottenere la lista degli utenti

        try {

            $res = $this->handler->users->find();
        } catch (MongoException $e) {
            die("An error occured.<br>" . $e->getMessage());
        }

        //$users = MongoUtilities::cursor_to_array($res);
        return $res;
    }

    public function changePassw($user_id, $new_passw)
    {
        try {
            $user = $this->getInfoProfile($user_id);
            $user['password'] = hash("sha512", $new_passw . $this->salt);
            $res = $this->handler->users->update(array("_id" => $user_id), $user);
        } catch (MongoException $e) {
            die("An error occured.<br>" . $e->getMessage());
        }

        return boolval($res['ok']);
    }

    public function updateProfile($avatar, $name, $surname, $e_mail, $user_id, $date_of_birth)
    {

        $user_data = $this->getInfoProfile($user_id);

        $user_data["avatar"] = $avatar;
        $user_data["name"] = $name;
        $user_data["surname"] = $surname;
        $user_data["e_mail"] = $e_mail;
        $user_data["date_of_birth"] = $date_of_birth;


        try {
            $res = $this->handler->users->update(array("_id" => $user_id), $user_data);
        } catch (MongoException $e) {
            die("An Error occured<br>" . $e->getMessage());
        }

        return boolval($res['ok']);
    }

    public function findUser($user_id)
    {//funzione per trovare l'utente
        try {
            $res = $this->handler->users->find(array("_id" => $user_id));
        } catch (MongoException $e) {
            die("An Error occured<br>" . $e);
        }
        return boolval($res['ok']);
    }

    public function dropUser($user_id)
    {//funzione per cancellare un utente

        try {
            $res = $this->handler->users->remove(array("_id" => $user_id));
            $res2 = $this->handler->media->remove(array("user_id" => array(new MongoDBRef("users", $user_id))));
        } catch (MongoException $e) {
            die("An Error occured<br>" . $e);
        }

        return boolval($res['ok']);
    }

    public function getUserProfile($user_id)
    {//funzione per ottenere i media di un utente
        try {
            $res = $this->handler->media->find(array('user_id.$id' => $user_id))->sort(array("date" => -1));
        } catch (MongoException $e) {
            die("An Error Occured<br>" . $e->getMessage());
        }

        return $res;
    }

    public function createUser($avatar, $name, $surname, $e_mail, $user_id, $password, $date_of_birth, $sex)
    { //funzione per creare un utente
        $user = array(
            "_id" => $user_id,
            "name" => $name,
            "surname" => $surname,
            "e_mail" => $e_mail,
            "password" => hash("sha512", $password . $this->salt),//calcolo il digest della password + il salt
            "date_of_birth" => $date_of_birth,
            "avatar" => "mucca.jpg",
            "sex" => $sex
        );

        try {
            $res = $this->handler->users->insert($user); //inserisco l'utente
            $this->startFollow($user_id, $user_id);

        } catch (MongoException $e) {
            die("An Error occured<br>" . $e);
        }

        return boolval($res['ok']);
    }

    public function checkToken($user, $token)
    {
        try {
            $res = $this->handler->users->find(array("_id" => $user, "sessions" => array('$elemMatch' => array("token" => $token))))->count();
        } catch (MongoException $e) {
            die("An Error Occured<br>" . $e->getMessage());
        }

        return ($res == 1) ? true : false;
    }


    public function checkPrivileges($user, $photo_id)
    {
        try {
            $res = $this->handler->media->find(array("_id" => $photo_id, "user_id" => $user));
            foreach ($res as $element) {
                $results[] = $element;
            }
            die(print_r($results));
        } catch (MongoException $e) {
            die("An Error Occured<br>" . $e->getMessage());
        }

    }

    public function getUserList($username)
    {
        try {
            $res = $this->handler->users->find(array("_id" => new MongoRegex("/^$username/")), array("_id" => 1, "avatar" => 1));
            $users = array();
            foreach ($res as $element) {
                $users[] = $element;
            }
            // die(print_r($users));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        return (isset($users)) ? $users : null;
    }

    public function getAvatar($user_id)
    {
        try {
            $res = $this->handler->users->find(array("_id" => $user_id), array("avatar" => 1));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        foreach ($res as $element) {
            return $element['avatar'];
        }
    }

    public function startFollow($user, $user_to_follow)
    {
        try {
            $res = $this->handler->users->update(array("_id" => $user), array('$push' => array("following" => $user_to_follow)), array("upsert" => true));
            // $res_2 = $this->handler->users->update(array("_id" => $user), array('$push' => array("following" => $user_to_follow)));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        return boolval($res['ok']);
    }

    public function isFollowing($user, $user_to_check)
    {

        try {
            $res = $this->handler->users->find(array("_id" => $user, "following" => $user_to_check))->count();
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        return ($res > 0) ? true : false;
    }

    public function registerTokenNotification($user_id, $token_session, $token)
    {

        $user = $this->getInfoProfile($user_id);

        foreach ($user['sessions'] as &$session) {
            if ($session['token'] == $token_session) $session['token_notification'] = array($token);
        }
        try {
            $res = $this->handler->users->update(array("_id" => $user_id), $user);
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        return boolval($res['ok']);
    }

    public function retreiveToken($user_id)
    {
        try {
            $res = $this->handler->users->findOne(array("_id" => $user_id), array("sessions" => true), array("multi" => true));
            $token_to_send = null;
            foreach ($res['sessions'] as $session) {
                if (isset($session['token_notification'])) $token_to_send[] = $session['token_notification'];
            }

        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        //die($token_to_send);
        return $token_to_send;
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

    public function getFollowers($user)
    {
        try {
            $res = $this->handler->users->find(array("following" => $user), array("_id" => 1));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        foreach ($res as $follower) {
            if ($follower['_id'] == $user) continue;
            $followers[] = $follower['_id'];
        }

        return (isset($followers)) ? $followers : null;
    }

    public function stopFollow($user, $user_to_stop_following)
    {
        try {
            $res = $this->handler->users->update(array("_id" => $user), array('$pull' => array("following" => $user_to_stop_following)));
            //$this->handler->users->update(array("_id" => $user), array('$pull' => array("following" => $user_to_stop_following)));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        return boolval($res['ok']);
    }

    public function registerSession($user_id, $token, $time, $ip)
    {
        $time = $time + 31536000000;
        try {
            $res = $this->handler->users->update(array("_id" => $user_id), array('$push' => array("sessions" => array("token" => $token, "ip" => $ip, "expire" => $time))));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        //print_r($res);
    }

    public function closeSession($user_id, $token)
    {
        try {
            $res = $this->handler->users->update(array("_id" => $user_id), array('$pull' => array("sessions" => array('token' => $token))), array("multi" => true));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        //die(print_r($res));
        return boolval($res['ok']);
    }

    public function getSessions($user_id)
    {
        try {
            $res = $this->handler->users->find(array("_id" => $user_id), array("sessions" => 1));
            foreach ($res as $rs) {
                $sessions = $rs['sessions'];
            }

        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        return (isset($sessions)) ? $sessions : null;
    }

    public function getInfoProfile($user_id, $filter = false)
    {
        try {
            $res = ($filter) ? $this->handler->users->find(array("_id" => $user_id), array("name" => 1, "surname" => 1, "e_mail" => 1, "date_of_birth" => 1, "sex" => 1, "avatar" => 1)) : $this->handler->users->find(array("_id" => $user_id));
            $response = null;
            foreach ($res as $element) {
                $response = $element;
            }
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        //$response['avatar'] = "mucca.jpg";
        return $response;

    }

    public function insertNotification($user_id, $from, $notification, $media_id = null)
    {
        try {
            $res = $this->handler->usersNotifications->update(array("_id" => $user_id), array('$push' => array("notifications" => array("media_id" => $media_id, "notification" => $notification, "from" => $from))), array('upsert' => true));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        return boolval($res['ok']);
    }

    public function removeNotification($user_id, $from, $media_id = null, $type)
    {
        try {
            $res = $this->handler->usersNotifications->update(array("_id" => $user_id), array('$pull' => array("notifications" => array("media_id" => $media_id, "notification" => $type, "from" => $from))), array("multi" => true));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }
        return boolval($res['ok']);
    }

    public function logUser($user_id, $time, $media_id)
    {

        $log = array(
            "user" => $user_id,
            "time" => $time,
            "media_id" => $media_id
        );

        try {
            $res = $this->handler->log->insert($log);
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        return boolval($res['ok']);

    }


    public function getNotifications($user_id)
    {

        try {
            $res = $this->handler->usersNotifications->find(array("_id" => $user_id))->limit(self::LIMIT);
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        foreach ($res as $row) {
            $notifications[] = $row;
        }

        //die(print_r($notifications));


        return (isset($notifications)) ? $notifications : null;
    }

    public function checkEmail($email)
    { //funzione per vedere se l'email è già in uso
        try {
            $res = $this->handler->users->find(array("e_mail" => $email))->count();
        } catch (MongoException $e) {
            die("An Error Occured<br>" . $e->getMessage());
        }
        return ($res == 0) ? true : false;
    }


    public function checkUsername($user_id)
    {//funzione per vedere se lo username è già in uso
        try {
            $res = $this->handler->users->find(array("_id" => $user_id))->count();
        } catch (MongoException $e) {
            die("An Error Occured<br>" . $e->getMessage());
        }
        return ($res == 0) ? true : false;

    }


    public function appendNotification($from, $to, $type)
    {

        /*
         * type values:
         *      1. Like
         *      2. Comment
         *      3. Mention
         *      4. Follow
         *
         */

        $notification = array(
            "from" => $from,
            "to" => $to,
            "type" => $type
        );

        //die(print_r($notification));

        try {
            $res = $this->handler->notifications->insert($notification);
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        return boolval($res['ok']);
    }

    public function getAppendedNotifications($user_id)
    {
        try {
            $res = $this->handler->notifications->find(array("to" => $user_id));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        foreach ($res as $notifications) {
            $app[] = $notifications;

        }
        return $app;

    }


    public function unsetGCMToken($user_id, $token)
    {
        try {
            $sessions = $this->handler->users->findOne(array("_id" => $user_id), array("sessions" => 1));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        foreach ($sessions['sessions'] as &$ession) {
            if (isset($sessions['token_notification']) && $sessions['token_notification'] == $token) unset($sessions['notification']);
        }

        try {
            $res = $this->handler->users->update(array("_id" => $user_id), array('$set' => array("sessions" => $sessions['sessions'])));
        } catch (MongoException $e) {
            die("Something went wrong <br>" . $e->getMessage());
        }

        return boolval($res['ok']);
    }

}

?>
