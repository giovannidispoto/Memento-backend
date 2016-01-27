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
    <h1>Inserisci una foto</h1>
        <form method="post" action="?action=insert_media" enctype="multipart/form-data">
            <input type="hidden" name="file">
            Descrizione: <input type="text" name="description">
            Inserisci il file: <input type="file" name="file"><br>
            <input type="submit" value="Invia">
        </form>
    </body>
</html>
