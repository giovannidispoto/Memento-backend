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


            public function testCreateUser(){ //test creazione utente
                $db = new Database();
                $res = $db->createUser("Giuseppe","Dispoto","prova@prova","giuseppedispoto","mariolino","01/07/1971","M");
                $this->assertTrue($res);
                $res = $db->createUser("Mario","Rossi","prova@prova.it","mariorossi","miao","10/07/1971","M");
                $this->assertTrue($res);


            }

            public function testAuth(){ //test autenticazione
                $db = new Database();
                $res = $db->authUser("giuseppedispoto","mariolino");
                $this->assertEquals($res,1);
                $res = $db->authUser("utente32","admin123");
                $this->assertEquals($res,0);
            }

            public function testDropUsers(){// test cancellamento utenti
                $db = new Database();
                $res = $db->dropUser("mariorossi");
                $this->assertTrue($res);
                $res = $db->dropUser("giuseppdispoto");
                $this->assertTrue($res);
            }
}
