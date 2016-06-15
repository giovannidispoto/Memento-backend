<?php


namespace Memento;
use MongoClient;
use MongoConnectionException;

class Database
{

    private $host = 'localhost'; //parametri accesso db
    private $port = 27010;
    private $username = '';
    private $password = '';
    private $dbname = "memento";
    private $conn;
    private $handler;



    private function connect()
    { //funzione per connessione al database
        try {
            $this->conn = new MongoClient();
            $this->handler = $this->conn->selectDB($this->dbname);
        } catch (MongoConnectionException $e) {
            die('An Error occured<br>' . $e);
        }
    }

    public function __construct()
    {
        $this->connect();
    }

    public function getConnection(){
        return $this->handler;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.

        $this->conn->close();
    }

}

?>
