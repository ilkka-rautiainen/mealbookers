<?php

class Mailer
{
    private static $instance = null;
    private $phpMailer;

    /**
     * Singleton pattern: private constructor
     */
    private function __construct()
    {
        require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
        $this->phpMailer = new PHPMailer();
        $this->phpMailer->CharSet = 'utf-8';
        $this->phpMailer->Port = Conf::inst()->get('mail.smtp_port');
        $this->phpMailer->IsSMTP();
        $this->phpMailer->Mailer = 'smtp';
        $this->phpMailer->SMTPSecure = Conf::inst()->get('mail.smtp_secure');
        // $this->phpMailer->SMTPDebug = true;
        $this->phpMailer->Host = Conf::inst()->get('mail.smtp_host');
        $this->phpMailer->SMTPAuth = Conf::inst()->get('mail.smtp_auth');
        $this->phpMailer->Username = Conf::inst()->get('mail.smtp_username');
        $this->phpMailer->Password = Conf::inst()->get('mail.smtp_password');
    }

    /**
     * Singleton pattern: Instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new Mailer();

        return self::$instance;
    }

    /**
     * Sends message with subject, body to given recipient
     * @return boolean
     */
    public function send($subject, $body, User $recipient)
    {
        $this->phpMailer->SetFrom(
            Conf::inst()->get('mail.from_address'),
            Lang::inst()->get('mailer_sender_name', $recipient)
        );
        $this->phpMailer->clearAddresses();
        $this->phpMailer->AddAddress($recipient->email_address, $recipient->getName());
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->MsgHTML(
            Lang::inst()->get('mailer_header')
            . $body
            . Lang::inst()->get('mailer_footer'));

        if ($this->phpMailer->send()) {
            Logger::info(__METHOD__ . " sending message to {$recipient->email_address} succeeded");
            return true;
        }
        else {
            Logger::error(__METHOD__ . " sending message to {$recipient->email_address} failed");
            return false;
        }
    }

    public function sendToAddress($subject, $body, $email_address, User $language_user)
    {
        $this->phpMailer->SetFrom(
            Conf::inst()->get('mail.from_address'),
            Lang::inst()->get('mailer_sender_name', $language_user)
        );
        $this->phpMailer->AddAddress($email_address);
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->MsgHTML(
            Lang::inst()->get('mailer_header')
            . $body
            . Lang::inst()->get('mailer_footer'));

        if ($this->phpMailer->send()) {
            Logger::info(__METHOD__ . " sending message to {$email_address} succeeded");
            return true;
        }
        else {
            Logger::error(__METHOD__ . " sending message to {$email_address} failed");
            return false;
        }
    }
}