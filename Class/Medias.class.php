<?php

namespace Memento;
use MongoId;
use MongoException;
use MongoDBRef;

class Medias{

  private $handler;

  public function __construct($db_handler){
    $this->handler = $db_handler;
  }

  public function insertMedia($path, $description, $hash_tags = null, $user_id)
    { //funzione per inserire i media nel db
        $values = ($hash_tags != null)?array_values($hash_tags):null;
        try {
            $res = $this->handler->media->insert(array(
                    'user_id' => array($this->handler->createDBRef('users', $user_id)),
                    "description" => $description,
                    "hashtags" => $values,
                    "media" => $path,
                    "date" => date('Y-m-d  H:i:s')
                )
            );
        } catch (MongoException $e) {
            die("An Error occured<br>" . $e->getMessage());
        }
        return boolval($res['ok']);
    }


    public function getMediaByHashtag($hashtag)
      {
          try {
              $res = $this->handler->media->find(array("hashtags" => $hashtag));
              foreach ($res as $element) {
                  $photos[] = $element;
              }
          } catch (MongoException $e) {
              die("Something went wrong <br>" . $e->getMessage());
          }

          return (isset($photos)) ? $photos : null;
      }

      public function insertComment($user_id, $comment, $media_id)
        { //funzione per inserire i commenti
            try {
                $res = $this->handler->media->update(array("_id" => new  MongoId($media_id)), array('$push' => array('comments' => array("_id" => new MongoId(), "user_id" => $user_id, "comment" => $comment))));
            } catch (MongoException $e) {
                die("An Error Occured<br>" . $e->getMessage());
            }
            return boolval($res['ok']);
        }

        public function insertLike($user_id, $media_id)
          {//funzione per inserire il like
              try {
                  $res = $this->handler->media->update(array("_id" => new MongoId($media_id)), array('$push' => array("likes" => $user_id)));
              } catch (MongoException $e) {
                  die("An Error Occured<br>" . $e->getMessage());
              }
              //print_r($res);
              return boolval($res['ok']);
          }

          public function removeLike($user_id, $media_id)
          {//funzione per inserire il like
              try {
                  $res = $this->handler->media->update(array("_id" => new MongoId($media_id)), array('$pull' => array("likes" => $user_id)));
              } catch (MongoException $e) {
                  die("An Error Occured<br>" . $e->getMessage());
              }
              return boolval($res['ok']);
          }

          public function getHashtagList($hashtag)
            {
                try {
                    $res = $this->handler->media->aggregate(array(
                            array('$unwind' => '$hashtags'),
                            array('$group' => array("_id" => '$hashtags'))

                        )
                    );
                    $tmp = $res['result'];
                    foreach ($tmp as $element) {
                        $hashtags[] = $element['_id'];
                    }
                    $hashtags = preg_grep("/^$hashtag/", $hashtags);
                    // die(print_r($hashtags));
                } catch (MongoException $e) {
                    die("An Error Occured<br>" . $e->getMessage());
                }

                return $hashtags;
            }

            public function dropMedia($media_id)
            {
                try {
                    $res = $this->handler->media->remove(array("_id" => new MongoId($media_id)));
                } catch (MongoException $e) {
                    die("An Error Occured<br>" . $e->getMessage());
                }
                return boolval($res['ok']);
            }

            public function getHomeMedia($user) //foto home relativa ad un utente
              {

                  try {
                      $res_ = $this->handler->users->find(array('_id' => $user), array("following" => 1));

                      foreach ($res_ as $element) {
                          $users = $element['following'];
                      }

                      $res = $this->handler->media->find(array('user_id.$id' => array('$in' => $users)))->sort(array('date' => -1)); //order by date
                      foreach ($res as $element) {

                      }
                  } catch (MongoException $e) {
                      die("Something went wrong <br>" . $e->getMessage());
                  }


                  /*  foreach($res as $element){
                        echo $element['user_id'][0]['$id']."<br>"";
                        //$element['avatar'] = $this->getAvatar($element['user_id']['$id']); //aggiungo l'avatar dell'utente
                    }*/
                  return $res;
              }

              public function getPhotoDetails($photo_id)
                {
                    try {
                        $res = $this->handler->media->findOne(array("_id" => new MongoId($photo_id)));
                    } catch (MongoException $e) {
                        die("Something went wrong <br>" . $e->getMessage());
                    }

                    return $res;
                }

                public function getMediaFromId($media_id){
                      try{
                          $res = $this->handler->media->find(array("_id" => new MongoId($media_id)));
                      }catch(MongoException $e){
                          die("Something went wrong <br>" . $e->getMessage());
                      }
                      foreach($res as $row){
                         $media = $row['media'];
                      }
                      return $media;
                  }


}

?>
