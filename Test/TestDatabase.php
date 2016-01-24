<?php

/**
 * Created by PhpStorm.
 * User: giovannidispoto
 * Date: 20/01/16
 * Time: 15:08
 */
require_once("../Class/Database.class.php");

class TestDatabase extends PHPUnit_Framework_TestCase
{
               private  $user = array(
                "name" => "Giuseppe",
                "surname" => "Dispoto",
                "e_mail" => "prova@prova.it",
                "username" => "giuseppedispoto",
                "password" => "mariolino",
                "date_of_birth" => "01/07/1971",
                "sex" => "M"
                );

               private  $user2 = array(

                "name" => "Giovanni",
                "surname" => "Dispoto",
                "e_mail" => "prova@prova.it",
                "username" => "giovannidispoto",
                "password" => "admin123",
                "date_of_birth" => "01/07/1971",
                "sex" => "M"

                );

                private $photo = "56a54717e3bcdc8a428b4567";
                private $photo2 = "56a54717e3bcdc8a428b4568";



            public function testCreateUser(){ //test creazione utente

                 $result2 = array(
                    "_id" => "giovannidispoto",
                    "name" => "Giovanni",
                    "surname" => "Dispoto",
                    "e_mail" => "prova@prova.it",
                    "date_of_birth" => "01/07/1971",
                    "sex" => "M"
                );

                $result = array(
                    "_id" => "giuseppedispoto",
                    "name" => "Giuseppe",
                    "surname" => "Dispoto",
                    "e_mail" => "prova@prova.it",
                    "date_of_birth" => "01/07/1971",
                    "sex" => "M"
                );


                $db = new Database();
                $res = $db->createUser($this->user['name'],$this->user['surname'],$this->user['e_mail'],$this->user['username'],$this->user['password'],$this->user['date_of_birth'],$this->user['sex']);
                $res = $db->findUser($this->user['username']);
                $this->assertEquals($result,$res[$this->user['username']]);
                $res = $db->createUser($this->user2['name'],$this->user2['surname'],$this->user2['e_mail'],$this->user2['username'],$this->user2['password'],$this->user2['date_of_birth'],$this->user2['sex']);
                $res = $db->findUser($this->user2['username']);
                $this->assertEquals($result2,$res[$this->user2['username']]);

            }

            public function testAuth(){ //test autenticazione
                $db = new Database();
                $res = $db->authUser($this->user['username'],$this->user['password']);
                $this->assertEquals($res,1);
                $res = $db->authUser($this->user2['username'],"abbominevole");
                $this->assertEquals($res,0);
            }

            public function testInsertPhoto(){ //test inserimento foto
              $db = new Database();
                $res1 = $db->insertMedia("uploads/media1.jpg","descrizione1",array("ciaone","ueue"),"ciaoneee");
                $res2 = $db->insertMedia("uploads/media2.jpg","descrizione2",array("ciaone","ueue"),"utente2");
                $this->assertTrue($res1);
                $this->assertTrue($res2);
             }

            public function testRetrievePhoto(){ //test percorso file
                $db = new Database();
                $res = $db->getMedia("ciaoneee");
                $res1 = $db->getMedia("utente2");

                foreach($res as $v){
                    $path = $v['media'];
                }
                foreach($res1 as $v){
                    $path1 = $v['media'];
                }


                $this->assertEquals($path,"uploads/media1.jpg");
                $this->assertEquals($path1,"uploads/media2.jpg");
            }

            public function testLike(){ //test inserimento like
                $db = new Database();
                $res = $db->insertLike("utente0",$this->photo);
                $this->assertTrue($res);
                $res = $db->insertLike("utente3",$this->photo2);
                $this->assertTrue($res);
            }

            public function testComment(){}

            public function testDropUsers(){// test cancellamento utenti
                $db = new Database();
                $res = $db->dropUser($this->user['username']);
                $this->assertTrue($res);
                $res = $db->dropUser($this->user2['username']);
                $this->assertTrue($res);
            }
}
