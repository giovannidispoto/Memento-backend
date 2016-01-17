<?php
    class MongoUtilities{

      static function cursor_to_array($cursor){
        return json_decode(iterator_to_array($cursor));
      }
      
    }
?>
