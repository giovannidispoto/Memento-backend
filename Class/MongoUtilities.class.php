<?php
    class MongoUtilities{
           private static $array;
      static function cursor_to_array($cursor){ //trasforma un cursore mongoDB in un array

            foreach ($cursor as $k => $v){
                     self::$array[$k] = $v;
             }
        return self::$array;
      }

    }
?>
