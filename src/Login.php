<?php

class Login
{
    public function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/storage");
        $dotenv->load();
    }

    public function login($username, $password)
    {
        if (($username == $_ENV["LOGIN_USERNAME1"] && $password == $_ENV["LOGIN_PASSWORD1"]) ||
            ($username == $_ENV["LOGIN_USERNAME2"] && $password == $_ENV["LOGIN_PASSWORD2"]) ||
            ($username == $_ENV["LOGIN_USERNAME3"] && $password == $_ENV["LOGIN_PASSWORD3"]))
            {
                $_SESSION["user"] = $_POST['username'];
                session_regenerate_id(true);
                return true;
            } 
            else
            {
                return false;
            }
    }

    public function check()
    {
        if(isset($_SESSION["user"])) {
            return true;
        } else 
        {
            return false;
        }
    }

    public function logout()
    {
        unset($_SESSION["user"]);
        session_regenerate_id();
        return Header("Location: login");
    }
}