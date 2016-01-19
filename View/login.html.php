<?php
/**
 * Created by PhpStorm.
 * User: giovannidispoto
 * Date: 19/01/16
 * Time: 21:13
 */
?>
<html>
<head>
    <title>Login</title>
    <meta charset="utf-8">
</head>
<body>
<h1>Memento</h1>
<p>A simple app for photo and video sharing</p>
    <form action="?action=auth" method="post">
        <Label for="username">Username:
            <input type="text" id="username" name="username">
        </Label><br>
        <label for="password">Password:
            <input type="password" id="password" name="password">
        </label><br>
        <input type="submit" value="entra">
    </form>
    <br>
    <br>
    <h3>Registrati</h3>
    <form action="?action=create_user" method="post">
        <Label for="Nome">Nome
            <input type="text" id="name" name="name">
        </Label><br>
        <label for="Cognome">Cognome:
            <input type="text" id="surname" name="surname">
        </label><br>
        <label for="E-mail">E-mail:
            <input type="text" id="e_mail" name="e_mail">
        </label><br>
        <label for="Username">username:
            <input type="text" id="username" name="username">
        </label><br>
        <label for="Password">Password:
            <input type="password" id="password" name="password">
        </label><br>
        <label for="date_of_birth">Data di Nascita:
            <input type="text" id="date_of_birth" name="date_of_birth">
        </label><br>
        <label for="sex">Sesso:
            <input type="text" id="sex" name="sex">
        </label><br>
        <input type="submit" value="entra">
</form>
</body>
</html>
