<?php

require_once "classes/role.php";

class Profil
{
    /**
     * @var string
     */
    private $login;

    /**
     * @var Role
     */
    private $role;

    /**
     * @var string
     */
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
