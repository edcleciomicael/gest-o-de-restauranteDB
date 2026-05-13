<?php

class Database
{
    private static $conn = null;

    public static function connect()
    {
        if (self::$conn === null) {

            try {

                self::$conn = new PDO(
                    "pgsql:host=localhost;
                    port=5432;
                    dbname=restaurante_db",
                    "postgres",
                    "310395"
                );

                self::$conn->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

            } catch(PDOException $e) {

                die(
                    "Erro conexão: " .
                    $e->getMessage()
                );
            }
        }

        return self::$conn;
    }
}