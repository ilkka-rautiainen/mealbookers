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

    public function sendSuggestionInviteEmail(Suggestion $suggestion, $hash)
    {
        Logger::info(__METHOD__ . " inviting user {$this->id} to suggestion {$suggestion->id}");
        global $language, $config;
        $creator = new User();
        $creator->fetch($suggestion->creator_id);
        $restaurant = new Restaurant();
        $restaurant->fetch($suggestion->restaurant_id);

        require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
        $mail = new PHPMailer();
        $mail->CharSet = 'utf-8';
        // $mail->SMTPDebug = true;
        $mail->Port = $config['mail']['smtp_port'];
        $mail->SMTPAuth = true;
        $mail->IsSMTP();
        $mail->SMTPSecure = $config['mail']['smtp_secure'];
        $mail->Host = $config['mail']['smtp_host'];
        $mail->Username = $config['mail']['smtp_username'];
        $mail->Password = $config['mail']['smtp_password'];
        $mail->Mailer = 'smtp';
        $body = "Hei,<br /><br />" . $creator->getName() . " on menossa " . $suggestion->getDate()
            . " syömään ravintolaan " . $restaurant->name . "."
            . " Hän ehdottaa sinulle aikaa " . $suggestion->getTime() . "."
            . " <br /><br />Hyväksy ehdotus klikkaamalla"
            . " <a href=\"http://" . $_SERVER['HTTP_HOST'] . "/#/app/suggestion/accept?hash={$hash}\">tästä</a>."
            . " <br /><br />Palvelun etusivulle"
            . " <a href=\"http://" . $_SERVER['HTTP_HOST'] . "/#/app/menu\">tästä</a>."
            . "<br /><br />- Mealbookers<br /><br />"
            . "<small>Tämä on automaattinen viesti, johon ei tarvitse vastata.</small>";
        $mail->SetFrom('mailer@mealbookers.net', $language[$this->language]['mailer_sender_name']);
        $mail->AddAddress($this->email_address, $this->getName());
        $mail->Subject = str_replace(
            '{suggester}',
            $creator->getName(),
            $language[$this->language]['mailer_subject_suggestion']
        );
        $mail->MsgHTML($body);
        Logger::debug(__METHOD__ . " sending invitation message to {$this->email_address}");
        $result = $mail->Send();
        if (!$result)
            Logger::error(__METHOD__ . " sending failed");
        else
            Logger::debug(__METHOD__ . " sending succeeded");
        return $result;
    }
    
    public function notifyAcceptedSuggestion(Suggestion $suggestion)
    {
        global $language, $config;
        Logger::debug(__METHOD__ . " notifying user {$this->id} for having a suggestion accepted");

        if (!$user_id = DB::inst()->getOne("SELECT user_id FROM suggestions_users WHERE
            suggestion_id = {$suggestion->id} AND accepted = 1 AND user_id != {$suggestion->creator_id}
            LIMIT 1")) {
            Logger::error(__METHOD__ . " no accepted user found for the suggestion {$suggestion->id}");
            return;
        }
        $accepter = new User();
        $accepter->fetch($user_id);
        $restaurant = new Restaurant();
        $restaurant->fetch($suggestion->restaurant_id);

        require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
        $mail = new PHPMailer();
        $mail->CharSet = 'utf-8';
        // $mail->SMTPDebug = true;
        $mail->Port = $config['mail']['smtp_port'];
        $mail->SMTPAuth = true;
        $mail->IsSMTP();
        $mail->SMTPSecure = $config['mail']['smtp_secure'];
        $mail->Host = $config['mail']['smtp_host'];
        $mail->Username = $config['mail']['smtp_username'];
        $mail->Password = $config['mail']['smtp_password'];
        $mail->Mailer = 'smtp';
        $body = "Hei,<br /><br />" . $accepter->getName() . " on hyväksynyt ehdotuksesi mennä "
            . $suggestion->getDate() . " syömään ravintolaan " . $restaurant->name . "."
            . " aikaan " . $suggestion->getTime() . "."
            . " <br /><br />Voit siirtyä palvelun etusivulle"
            . " <a href=\"http://" . $_SERVER['HTTP_HOST'] . "/#/app/menu?day="
            . ($suggestion->getWeekDay() + 1) . "\">tästä</a>."
            . "<br /><br />- Mealbookers<br /><br />"
            . "<small>Tämä on automaattinen viesti, johon ei tarvitse vastata.</small>";
        $mail->SetFrom('mailer@mealbookers.net', $language[$this->language]['mailer_sender_name']);
        $mail->AddAddress($this->email_address, $this->getName());
        $mail->Subject = str_replace(
            '{accepter}',
            $accepter->getName(),
            $language[$this->language]['mailer_subject_suggestion_accepted']
        );
        $mail->MsgHTML($body);
        Logger::debug(__METHOD__ . " sending suggestion acceptance message to {$this->email_address}");
        $result = $mail->Send();
        if (!$result)
            Logger::error(__METHOD__ . " sending failed");
        else
            Logger::debug(__METHOD__ . " sending succeeded");
        return $result;
    }
}