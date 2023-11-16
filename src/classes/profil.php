<?php

class Profil
{
    private $login;
    private $role;
    private $heslo;

    public function __construct($login, $role, $heslo)
    {
        $this->login = $login;
        $this->role = $role;
        $this->heslo = $heslo;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getHeslo()
    {
        return $this->heslo;
    }
}
?>