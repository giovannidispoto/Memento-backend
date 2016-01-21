<?php
/**
 * Created by PhpStorm.
 * User: giovannidispoto
 * Date: 21/01/16
 * Time: 09:16
 */
?>
<html>
    <head>
        <title>Inserisci File</title>
        <meta charset="utf-8">
    </head>

    <body>
    <h1>Includi una foto</h1>
        <form method="post" action="?user=<?php echo $user_id;?>&action=insert_media">
            <input type="hidden" value="<?php echo $user_id;?>" name="user_id">
            Inserisci il file: <input type="file"><br>
            <input type="submit" value="Invia">
        </form>
    </body>
</html>
