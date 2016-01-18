<?php
    class MongoUtilities{

      static function cursor_to_array($cursor){ //trasforma un cursore mongoDB in un array
        return json_decode(iterator_to_array($cursor));
      }

    }
?>
