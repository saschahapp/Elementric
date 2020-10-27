<?php

class Dispatch
{
    public $addresses = [];
    private $templates;
    public $address = [];
    public $templateName = [];
    private $log;

    public function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/storage");
        $dotenv->load();
        $this->log = new Monolog\Logger('newsletter');
        $this->log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__."/storage/log/newsletter.log", Monolog\Logger::INFO));
        $this->log->pushHandler(new Monolog\Handler\FirePHPHandler());
    }

    public function moveUploadedFile($source, $destination)
    {
        $destination = __DIR__."/storage/template/{$destination}";
        if (move_uploaded_file($source, $destination)) {
            return true;
        } else {
            return false;
        }
    }

    public function Dispatch()
    {
        $i = 1;
        $receiverEmail = $this->address[0];
        $receiverName = $this->address[1];
        $random = rand(0, (count($this->templateName) - 1));

        $template = file_get_contents(__DIR__."/storage/template/".$this->templateName[$random].".html");
        $template = str_replace("{{ name }}", $this->address[1], $template);
        $template = str_replace("{{ id }}", $this->address[2], $template);
        $template = str_replace("{{ code }}", $this->address[3], $template);

        $message = new Swift_Message($_ENV['SUBJECT_EMAIL']);
        $message->setFrom(array($_ENV['SENDER_EMAIL'] => $_ENV['SENDER_NAME']));
        $message->setTo(array($receiverEmail => $receiverName));
        $message->addPart($template, 'text/html');

        if (($i % 4000) !== 0) {
            $transport = (new Swift_SmtpTransport($_ENV['SMTP_IP'], $_ENV['SMTP_PORT']))
            ->setUsername($_ENV['SMTP_USERNAME'])
            ->setPassword($_ENV['SMTP_PASSWORD']);
            $mailer = new Swift_Mailer($transport);
        } 
        $result = $mailer->send($message);
        $i++;
        
        if ($result == 0) {
            $this->log->info("Email an: ".$receiverEmail." konnte nicht versandt werden!");
            return false;
        } else {
            $this->log->info("receiverEmail: ".$receiverEmail." ,receiverTemplate: ".$this->templateName[$random]);
            return true;
        }
    }

    public function fetchTemplates()
    {
        if(is_dir(__DIR__."/storage/template")) {
            $handle = opendir(__DIR__."/storage/template");
            while (($file = readdir($handle)) !== false){
                $search = "/.html$/";
                if (preg_match($search, $file)) 
                {
                    $this->templateName[] = str_replace(".html", NULL, $file);
                }
            }
            closedir($handle);
            return $this->templateName;
        } else {
            return false;
        }
    }
}