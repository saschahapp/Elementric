<?php

class Login
{
    public function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/storage");
        $dotenv->load();
    }

    public function login($email, $password)
    {
        $ds = ldap_connect($_ENV['LDAP_IP']);
        if (ldap_bind($ds, $email, $password))
        {
            $_SESSION["user"] = $email;
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