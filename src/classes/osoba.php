<?php

require_once "classes/profil.php";

class Osoba
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $mail;

    /**
     * @var string
     */
    private $jmeno;

    /**
     * @var string
     */
    private $prijmeni;

    /**
     * @var Profil
     */
    private $profil;

    public function __construct($id, $mail, $jmeno, $prijmeni, $profil = null)
    {
        $this->id = $id;
        $this->mail = $mail;
        $this->jmeno = $jmeno;
        $this->prijmeni = $prijmeni;
        $this->profil = $profil;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMail()
    {
        return $this->mail;
    }

    public function getJmeno()
    {
        return $this->jmeno;
    }

    public function getPrijmeni()
    {
        return $this->prijmeni;
    }

    public function getProfil()
    {
        return $this->profil;
    }
}
