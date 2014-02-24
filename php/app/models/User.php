<?php

class User
{
    
    public $id;
    public $email_address;
    public $first_name;
    public $last_name;
    public $language;
    public $active;

    public function fetch($id)
    {
        $result = DB::inst()->query("SELECT * FROM users WHERE id = '" . ((int)$id) . "' LIMIT 1");
        if (!DB::inst()->getRowCount())
            throw new Exception("Unable to find user with id $id");
        $row = DB::inst()->fetchAssoc($result);
        $this->populateFromRow($row);
        if (!$this->id)
            throw new Exception("Error fetching user: id is null");
    }

    public function populateFromRow($row)
    {
        $this->id = $row['id'];
        $this->email_address = $row['email_address'];
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->language = $row['language'];
        $this->active = $row['active'];
    }

    public function getName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAsArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->getName(),
            'initials' => $this->getInitials(),
        );
    }

    public function getInitials() {
        return substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1);
    }

    public function inviteToSuggestion(Suggestion $suggestion, $hash)
    {
        Logger::info(__METHOD__ . " POST inviting user {$this->id} to suggestion {$suggestion->id}");
        global $language;
        $creator = new User();
        $creator->fetch($suggestion->creator_id);
        $restaurant = new Restaurant();
        $restaurant->fetch($suggestion->restaurant_id);

        require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
        $mail = new PHPMailer();
        $mail->CharSet = 'utf-8';
        $mail->Host = 'smtp.ayy.fi';
        $mail->Mailer = 'smtp';
        $body = "Hei,<br />" . $creator->getName() . " on menossa " . $suggestion->getDate()
            . " syömään ravintolaan " . $restaurant->name . "."
            . " Hän ehdottaa sinulle aikaa " . $suggestion->getTime() . "."
            . " Hyväksy ehdotus klikkaamalla"
            . " <a href=\"http://" . $_SERVER['HTTP_HOST'] . "/#/app/menu?hash={$hash}\">tästä</a>"
            . "<br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti, älä vastaa siihen</small>";
        $mail->SetFrom('mailer@mealbookers.net', $language[$this->language]['mailer_sender_name']);
        $mail->AddAddress($this->email_address, $this->getName());
        $mail->Subject = str_replace('{suggester}', $creator->getName(), $language[$this->language]['mailer_subject']);
        $mail->MsgHTML($body);
        Logger::debug(__METHOD__ . " sending invitation message to {$this->email_address}");
        $result = $mail->Send();
        if (!$result)
            Logger::error(__METHOD__ . " sending failed");
        else
            Logger::debug(__METHOD__ . " sending succeeded");
        return $result;
    }
}