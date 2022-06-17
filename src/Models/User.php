<?php
namespace Models;
use PDO;
use PDOException;
class User
{
    public function __construct()
    {
        $this->dbh = new PDO('mysql:host=localhost;dbname=forum', 'root', 'root');
    }
    private string $salt = "_yZK(g3Me*Kg0ux?z8izQ5N=lWD@Xa5ym+Y7h2c!C-)SEN9EQN";
    private string $login;
    private string $pass;
    private string $loginHash;
    private PDO $dbh;

    private function findByLogin(){
        $sql="SELECT * FROM `forum`.`user` WHERE userName=:userName";
        $stmt=$this->dbh->prepare($sql);
        $stmt->bindParam(':userName', $this->login);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByID($ID){
        $sql="SELECT userName from forum.user WHERE id=:id";
        $stmt=$this->dbh->prepare($sql);
        $stmt->bindValue(':id', $ID);
        $stmt->execute();
        $result=$stmt->fetchAll();
        if (count($result)>0){
            $newUser = new User();
            $newUser->setLogin($result[0]['userName']);
            return $newUser;
        } else{
            return null;
        }

    }

    public function AllUsers(){
        $sql="SELECT userName from forum.user;";
        $stmt=$this->dbh->prepare($sql);
        $stmt->execute();
        $users = array();
        $results = $stmt->fetchAll();
        foreach ($results as $result){
            $newUser = new User();
            $newUser->setLogin($result['userName']);
            array_push($users, $newUser);
        }
        return $users;
    }

    public function findByLoginPass(){
        $sql="SELECT * FROM `forum`.`user` WHERE userName=:userName and password=:password";
        $stmt=$this->dbh->prepare($sql);
        $stmt->bindParam(':userName', $this->login);
        $stmt->bindParam(':password', $this->pass);
        $stmt->execute();
        $arr=$stmt->fetchAll();
        if(count($arr)>0){
            $User = new User();
            $User->setPass($this->pass);
            $User->setLogin($this->login);
            return $User;
        } else {
            return null;
        }
    }

    public function delete(){
        $sql="DELETE FROM `forum`.`user` WHERE userName=:userName;";
        $stmt=$this->dbh->prepare($sql);
        $stmt->bindParam(':userName', $this->login);
        $stmt->execute();
    }

    public function save(){
        if(count($this->findByLogin())>0){
            return 0;
        }
        else {
            $sql="INSERT INTO `forum`.`user` (`userName`, `password`, `loginHash`) VALUES (:userName, :password, 0);";
            $stmt=$this->dbh->prepare($sql);
            $stmt->bindParam(':userName', $this->login);
            $hashedPass = $this->pass;
            $stmt->bindParam(':password', $hashedPass);
            $stmt->execute();
        }
    }

    public function newLogin($clientIP){
        try {
            $sql = "UPDATE `forum`.`user` SET `loginHash` = :loginHash, `loginIP` = :loginIP, `isLoged` = 1, `loginTime`=:loginTime WHERE (`userName` = :userName);";
            $stmt=$this->dbh->prepare($sql);
            $stmt->bindParam(':userName', $this->login);
            $stmt->bindParam(':loginIP', $clientIP);
            $stmt->bindParam(':loginTime', time());
            $HashedIP = md5(time().$this->login.$this->salt.$clientIP);
            $stmt->bindParam(':loginHash', $HashedIP);
            $stmt->execute();
            return $HashedIP;
        }
        catch( PDOException $Exception ) {
            throw new MyDatabaseException( $Exception->getMessage( ) , (int)$Exception->getCode( ) );
        }
    }

    public function unLogin(){
        $sql = "UPDATE `forum`.`user` SET `loginHash` = :loginHash, `loginIP` = :loginIP, `isLoged` = 0, `loginTime`=:loginTime WHERE (`userName` = :userName AND `loginHash`=:oldHash);";
        $stmt=$this->dbh->prepare($sql);
        $null = null;
        $stmt->bindParam(':userName', $this->login);
        $stmt->bindParam(':loginIP', $null);
        $stmt->bindParam(':loginTime', $null);
        $stmt->bindParam(':loginHash', $null);
        $stmt->bindParam(':oldHash', $this->loginHash);
        $stmt->execute();
    }
    public function checkLoginValid(){
        $sql="SELECT * FROM `forum`.`user` where `loginHash` = :loginHash AND `userName` = :userName AND `isLoged`=1 AND `loginIP`=:loginIP";
        $stmt=$this->dbh->prepare($sql);
        $stmt->bindParam(':userName', $this->login);
        $stmt->bindParam(':loginHash', $this->loginHash);
        $stmt->bindParam(':loginIP', $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        $arr=$stmt->fetchAll();
        if(count($arr)>0){
            return true;
        } else {
            return false;
        }
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function setLogin($login): void
    {
        $this->login = $login;
    }

    public function setPass(string $pass): void
    {
        $this->pass = md5($pass.$this->salt);
    }

    /**
     * @return string
     */
    public function getLoginHash(): string
    {
        return $this->loginHash;
    }

    /**
     * @param string $loginHash
     */
    public function setLoginHash(string $loginHash): void
    {
        $this->loginHash = $loginHash;
    }
}