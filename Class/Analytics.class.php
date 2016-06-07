<?php
  /*
    Class used for perform analysis over data
  */
  class Analytics{

      private $handler;
      const ELEMENTS = 30;

      public function __construct(Database &$db){
          $this->handler = $db->getConnection();

      }

      private function getFrequencyLikes($user_id){
            try{
                $res_log = $this->handler->log->distinct("media_id");
                foreach($res_log as $media){

                    $rs = $this->handler->media->distinct("hashtags", array("_id" => new MongoId($media)));
                    foreach($rs as $element){
                        $hashtags[] = $element;
                    }

                }
            }catch(MongoException $e){
                die("Something went wrong <br>" . $e->getMessage());
            }

            return array_count_values($hashtags);
        }

        public function getPhotosRecommended($user_id,$offset){
          $frequency = $this->getFrequencyLikes($user_id);
            foreach($frequency as $k => $v){

                try{
                    $photos = $this->handler->media->find(array('hashtags' => $k))->skip($offset * self::ELEMENTS);
                }catch(MongoException $e){
                    die("Something went wrong <br>" . $e->getMessage());
                }

                foreach($photos as $photo){
                    $feed[] = $photo;
                }
                if(isset($feed))
                         shuffle($feed);

                //array_slice($feed, 0, 30);

            }
            return (isset($feed))? $feed : null;
        }

  }

?>
