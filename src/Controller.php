<?php

class Controller
{
    private $login;
    private $dispatch;

    public function __construct()
    {
        $this->login = new Login();
        $this->dispatch = new Dispatch();
    }
    public function render ($view, $parameter)
    {
        extract($parameter);
        include (__DIR__."/view/{$view}.php");
        return;
    }

    public function login()
    {
        if(!empty($_POST['username']) && !empty($_POST['password'])){

            if($this->login->login($_POST['username'], $_POST['password'])) {

                return Header("Location: dispatch");
            } 
        } 
        return $this->render("login", ["login" => "Password oder Email falsch! "]);
    }
    
    public function dispatch()
    {
        if ($this->login->check()) {
            if (!empty($_FILES)) {
               
                if(isset($_FILES['htmlfile']['name'])) 
                {
                    if ($this->dispatch->moveUploadedFile($_FILES['htmlfile']['tmp_name'],
                    $_FILES['htmlfile']['name'])) {
                        return $this->render("dispatch", ["statement" => "Die Datei wurde erfolgreich hochgeladen!"]);
                    } else {
                        return $this->render("dispatch", ["statement" => "Die Datei konnte nicht hochgeladen werden !"]);
                    }
                }

                if(isset($_FILES['csvfile']['name']))
                {
                    if($this->dispatch->moveUploadedFile($_FILES['csvfile']['tmp_name'],
                    $_FILES['csvfile']['name'])) {
                        return $this->render("dispatch", ["statement" => "Die Datei wurde erfolgreich hochgeladen!"]);
                    } else {
                        return $this->render("dispatch", ["statement" => "Die Datei konnte nicht hochgeladen werden !"]);
                    }
                }
            }

            if (isset($_POST['templateShow'])) {
                $templates = $this->dispatch->fetchTemplates();
                if ($templates) {
                    return $this->render("dispatch", ["templates" => $templates]);
                } else {
                    return $this->render("dispatch", ["statement" => "Es wurden keine Templates gefunden !"]);
                }
            }

            if (isset($_POST['templates1'])) {

                $i = 1;
                while (isset($_POST['templates'.$i])) 
                {
                    $this->dispatch->templateName[] = $_POST['templates'.$i];
                    $i++;
                }
            }

            if (!empty($_POST['subject'])) {
    
                $this->dispatch->subject = $_POST['subject'];
            }

            if (isset($_POST['dispatch'])) {

                if (isset($_POST['trialDispatch']))
                {
                    $this->dispatch->address = [$_POST['receiveremail'], $_POST['receivername']];
                    if ($this->dispatch->dispatch()) {
                        return $this->render("dispatch", ["statement" => "Die Email wurde versendet !"]);
                    } else {
                        if($this->dispatch->serverstatus == false) {
                            return $this->render("dispatch", ["statement" => "Der Server ist zurzeit nicht erreichbar! "]);
                        } else {
                            return $this->render("dispatch", ["statement" => "Die Email konnte nicht versendet werden !"]);
                        } 
                    }
                }

                if (isset($_POST['customerDispatch']))
                {
                    $this->dispatch->addresses = file(__DIR__."/storage/addresses.csv");
                    $i = 0;
                    $count = 0;
                    while($this->dispatch->addresses[$i] != NULL) {
                        $this->dispatch->address = explode(";" ,$this->dispatch->addresses[$i]);
                        if ($this->dispatch->Dispatch()) {
                            $count++;
                            if ($this->dispatch->serverstatus == false){
                                break;
                                return $this->render("dispatch", ["statement" => "Der Server ist zurzeit nicht erreichbar! "]);
                            }
                        }
                        $i++;
                    }
                    if ($count == $i ) {
                        return $this->render("dispatch", ["statement" => "Alle Email wurden versendet !"]);
                    } elseif ($count > 0) {
                        return $this->render("dispatch", ["statement" => "Es konnte nicht alle Emails versendet werden! Eventuell Log prüfen! "]);
                    } else {
                        return $this->render("dispatch", ["statement" => "Es konnte keine Email versandt werden! Eventuell Log prüfen! "]);
                    }
                }
            }
            return $this->render("dispatch", []);
        }
        else 
        {
            return Header("Location: login");
        }
    }

    public function logout()
    {
        return $this->login->logout();
    }
}