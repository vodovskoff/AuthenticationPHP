<?php

namespace MyControllers;

use Models\Message;
use Models\MessageRepository;
use Models\User;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;

class MainController{
    const SITE_URL = 'http://localhost';

    function logout(){
        $user = new User();
        $user->setLogin($_COOKIE['loginName']);
        $user->setLoginHash($_COOKIE['loginHash']);
        $user->unLogin();
        setcookie('loginName', '', time() - 36000, '/');
        setcookie('loginHash', '', time() - 36000, '/');
        header('Location: '.self::SITE_URL);
    }

    function login($login, $password){
        $User = new User();
        $User->setLogin($login);
        $User->setPass($password);
        $currentUser=$User->findByLoginPass();
        if ($currentUser!=NULL){
            setcookie('loginHash', $currentUser->newLogin($_SERVER['REMOTE_ADDR']));
            setcookie('loginName', $currentUser->getLogin());
        }
        header('Location: '.self::SITE_URL);
        var_dump(count($User->findByLoginPass()));
    }

    private function readSQL(){
        $messagesRep = new MessageRepository();
        $messagesObjects = $messagesRep->All();
        $arr = array();
        foreach ($messagesObjects as $messagesObject){
            $message=[
                'text'=>$messagesObject->getMessage(),
                'date'=>$messagesObject->getDate(),
                'from'=>$messagesObject->getAuthor()
            ];
            array_push($arr, $message);
        }
        return($arr);
    }

    private function readMessagesByAuthor($Author){
        $messagesRep = new MessageRepository();
        $messagesObjects = $messagesRep->getByAuthor($Author);
        $arr = array();
        foreach ($messagesObjects as $messagesObject){
            $message=[
                'text'=>$messagesObject->getMessage(),
                'date'=>$messagesObject->getDate(),
                'from'=>$messagesObject->getAuthor()
            ];
            array_push($arr, $message);
        }
        return($arr);
    }

    function showIndex(){

        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/src/Views/');
        $twig = new Environment($loader);
        $template = $twig->load('main.html.twig');
        
        $logger = new Logger('main');
        $logger->pushHandler(new StreamHandler(dirname(__DIR__, 2) . '/logs/app.log', Logger::INFO));
        $User = new User();
        $loged = false;
        if(isset($_COOKIE['loginName']) && isset($_COOKIE['loginHash'])){
            $User->setLogin($_COOKIE['loginName']);
            $User->setLoginHash($_COOKIE['loginHash']);
            $loged = $User->checkLoginValid();
        }
        echo $template->render(['logged' => $loged,
                                'loginName'=>$_COOKIE['loginName'],
                                'users'=>$User->AllUsers(),
                                'messages'=>$this->readSQL(),
                                'myMessages'=>$this->readMessagesByAuthor($_COOKIE['loginName'])
                                ]);
    }

    function getUserById($UserID){
        $User = new User();
        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/src/Views/');
        $twig = new Environment($loader);
        $template = $twig->load('main.html.twig');

        $logger = new Logger('main');
        $logger->pushHandler(new StreamHandler(dirname(__DIR__, 2) . '/logs/app.log', Logger::INFO));

        $User = new User();

        echo $template->render(['logged' => isset($_COOKIE['loginName']),
            'loginName'=>$_COOKIE['loginName'],
            'users'=>$User->AllUsers(),
            'messages'=>$this->readSQL(),
            'SearchedLoginById'=>$User->findByID($UserID),
            'isLoginSearched'=>true
        ]);
    }

    function send($message_text, $author){
        $User = new User();
        $User->setLogin($author);
        if(isset($_COOKIE['loginHash'])){
            $User->setLoginHash($_COOKIE['loginHash']);
        }
        if($User->checkLoginValid()){
            $message = new Message(date("Y-m-d H:i:s"), $author, $message_text, null);
            $mr = new MessageRepository();
            $mr->add($message);
        }
        header('Location: '.self::SITE_URL);
    }

    function registration($login, $password){
        $newUser = new User();
        $newUser->setLogin($login);
        $newUser->setPass($password);
        $newUser->save();
        header('Location: '.self::SITE_URL);
    }

    function delete($login){
        $User = new User();
        $User->setLogin($login);
        $User->setLoginHash($_COOKIE['loginHash']);
        if($User->checkLoginValid()){
            $User->delete();
            $this->logout();
        }
    }

    function deleteAllMessages($login){
        $User = new User();
        $User->setLogin($login);
        if(isset($_COOKIE['loginHash'])){
            $User->setLoginHash($_COOKIE['loginHash']);
        }
        if($User->checkLoginValid()){
            $mr = new MessageRepository();
            $mr->deleteAllByLoginName($login);
        }
        header('Location: '.self::SITE_URL);
    }

    function getMessageById($id){
        $mr = new MessageRepository();
        $loader = new FilesystemLoader(dirname(__DIR__, 2).'/src/Views/');
        $twig = new Environment($loader);
        $template = $twig->load('main.html.twig');

        $logger = new Logger('main');
        $logger->pushHandler(new StreamHandler(dirname(__DIR__, 2) . '/logs/app.log', Logger::INFO));

        $User = new User();
        $message = null;

        if($mr->getById($id)!=null){
            $message=[
                'text'=>$mr->getById($id)->getMessage(),
                'date'=>$mr->getById($id)->getDate(),
                'from'=>$mr->getById($id)->getAuthor()
            ];
        }


        echo $template->render(['logged' => isset($_COOKIE['loginName']),
            'loginName'=>$_COOKIE['loginName'],
            'users'=>$User->AllUsers(),
            'messages'=>$this->readSQL(),
            'SearchedMessageById'=>$message,
            'isMessageSearched'=>true
        ]);
    }
}