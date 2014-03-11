<?php

$GLOBALS['language']['fi'] = array(
    'app_name' => 'Mealbookers',
    'cafe' => 'Kahvila',
    'alacarte' => 'À la carte',
    'bistro' => 'Bistro',
    'tune_own_burger' => 'Tuunaa oma hampurilaisesi',
    'today' => 'Tänään',
    'tomorrow' => 'Huomenna',
    'weekday_1' => 'Maanantaina',
    'weekday_2' => 'Tiistaina',
    'weekday_3' => 'Keskiviikkona',
    'weekday_4' => 'Torstaina',
    'weekday_5' => 'Perjantaina',
    'weekday_6' => 'Lauantaina',
    'weekday_7' => 'Sunnuntaina',
    'in_otaniemi' => 'Otaniemessä',
    'log_in' => 'Kirjaudu sisään',
    'email' => 'Sähköposti',
    'password' => 'Salasana',
    'log_in_remember_me' => 'Muista minut',
    'forgot' => 'Unohditko salasanasi?',
    'and' => 'ja',
    'close' => 'Sulje',
    'cancel' => 'Peruuta',
    'delete' => 'Poista',
    'save' => 'Tallenna',
    'saving' => 'Tallennetaan...',
    'register' => 'Rekisteröidy',
    'register_full_name' => 'Koko nimi',
    'register_first_name' => 'Etunimi',
    'register_last_name' => 'Sukunimi',
    'account_settings' => 'Asetukset',
    'account_old_password' => 'Vanha salasana',
    'account_new_password' => 'Uusi salasana (väh. 5 merkkiä)',
    'account_new_password_repeat' => 'Uusi salasana uudestaan',
    'account_save_failed' => 'Käyttäjätietojen tallennus epäonnistui',
    'account_wrong_password' => 'Väärä salasana',
    'account_password_criteria' => 'Salasanan tulee olla vähintään 5 merkkiä pitkä eikä sen tule sisältää etu- tai sukunimeäsi',
    'account_save_succeeded' => 'Tiedot tallennettu!',
    'account_give_old_password' => 'Anna vanha salasana',
    'account_give_new_password' => 'Anna uusi salasana',
    'account_passwords_dont_match' => 'Antamasi uudet salasanat eivät täsmää',
    'account_saving' => 'Tallenetaan tietoja...',
    'account_remove' => 'Poista tili',
    'account_remove_cancel' => 'Peruuta',
    'account_remove_ok' => 'Poista',
    'account_remove_failed' => 'Tilin poistaminen epäonnistui',
    'account_remove_success' => 'Käyttäjätilisi on poistettu',
    'group_settings' => 'Ryhmät',
    'group_invite_member' => 'Kutsu jäsen',
    'group_edit_failed' => 'Ryhmän muokkaus epäonnistui',
    'group_edit_failed_invalid_name' => 'Ryhmän nimi ei ole kelvollinen',
    'group_add_member_failed' => 'Jäsenen kutsuminen epäonnistui',
    'group_add_member_failed_invalid_email' => 'Sähköpostiosoite ei ole kelvollinen',
    'group_add_member_success_invited_new' => 'Kutsu lähetetty',
    'group_add_member_success_joined_existing' => 'Jäsen liitetty ryhmään',
    'group_add_member_already_member' => 'Antamasi sähköpostiosoite kuuluu jo tähän ryhmään',
    'group_member_delete_failed' => 'Jäsenen poistaminen epäonnistui',
    'group_member_deleted_yourself' => 'Sinut poistettiin ryhmästä',
    'group_create_new' => 'Luo ryhmä',
    'group_name' => 'Ryhmän nimi',
    'group_add_group_failed' => 'Ryhmän lisääminen epäonnistui',
    'group_add_group_failed_invalid_name' => 'Ryhmän nimi ei ole kelvollinen',
    'suggest_eating_time' => 'Ehdota aikaa',
    'suggest' => 'Ehdota',
    'suggest_time' => 'Aika',
    'suggest_time_placeholder' => 'hh:mm',
    'suggest_friends' => 'Ehdota kavereille',
    'suggestion_save_error' => 'Virhe tallennettaessa ehdotusta',
    'suggestion_too_early' => 'Et voi luoda ehdotusta 5 min kauemmaksi menneisyyteen',
    'suggest_failed_to_send_invitation_email' => 'Kutsuviestien lähetys epäonnistui:',
    'suggest_sending' => 'Lähetetään ehdotusta...',
    'suggestion_created' => 'Ehdotus tehty!',
    'suggestion_been_deleted' => 'Hyväksymäsi ehdotus oli ehditty poistaa...',
    'suggestion_accept_succeeded' => 'Ehdotus hyväksytty',
    'suggestion_accept' => 'Hyväksy',
    'suggestion_cancel' => 'Peru',
    'suggestion_outside_members' => '+ muista ryhmistä',
    'suggestion_accept_failed' => 'Ehdotuksen käsittely epäonnistui.',
    'suggestion_accept_gone' => 'Ehdotuksen ajankohta on jo mennyt.',
    'suggestion_accepting' => 'Hyväksytään ehdotusta...',
    'suggestion_manage_canceled' => 'Peruttu!',
    'suggestion_manage_canceled_and_deleted' => 'Peruttu! Koko ehdotus poistettiin, koska olit sen viimeinen jäsen.',
    'suggestion_manage_accepted' => 'Hyväksytty!',
    'action_failed' => 'Toimenpide epäonnistui',
    'backend_only' => array(
        'mailer_sender_name' => 'Mealbookers',
        'mailer_subject_suggestion' => '{suggester} haluaa mennä kanssasi syömään',
        'mailer_body_suggestion' => 
            'Hei!<br /><br />{suggester} on menossa {suggestion_date} syömään ravintolaan {restaurant}.'
            . ' Hän ehdottaa sinulle aikaa {suggestion_time}.<br /><br /><b>{restaurant} - menu</b><br />'
            . ' {menu}<br /><br />Hyväksy ehdotus klikkaamalla'
            . ' <a href="http://{server_hostname}/#/app/suggestion/accept?hash={hash}">tästä</a>.'
            . ' <br /><br />Siirry Mealbookersiin hyväksymättä ehdotusta'
            . ' <a href="http://{server_hostname}/#/app/menu">tästä</a>.<br /><br />- Mealbookers<br /><br />'
            . ' <small>Tämä on automaattinen viesti, johon ei tarvitse vastata.</small>',
        'mailer_subject_suggestion_accepted_creator' => '{accepter} on hyväksynyt ehdotuksesi',
        'mailer_body_suggestion_accepted_creator' => 
            'Hei!<br /><br />{accepter} on hyväksynyt ehdotuksesi mennä {suggestion_date} syömään ravintolaan'
            . ' {restaurant} aikaan {suggestion_time}.<br /><br />Voit siirtyä Mealbookersiin'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">tästä</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti,'
            . ' johon ei tarvitse vastata.</small>',
        'mailer_subject_suggestion_accepted_other' => '{accepter} tulee kanssasi syömään',
        'mailer_body_suggestion_accepted_other' => 
            'Hei!<br /><br />{accepter} on tulossa kanssasi {suggestion_date} syömään ravintolaan'
            . ' {restaurant} aikaan {suggestion_time}.<br /><br />Voit siirtyä Mealbookersiin'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">tästä</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti,'
            . ' johon ei tarvitse vastata.</small>',
        'mailer_subject_suggestion_left_alone' => '{canceler} ei pääsekään kanssasi syömään',
        'mailer_body_suggestion_left_alone' => 
            'Hei!<br /><br />{canceler} ei pääsekään kanssasi {suggestion_date} syömään ravintolaan'
            . ' {restaurant} aikaan {suggestion_time}.<br /><br />Voit siirtyä Mealbookersiin'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">tästä</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti,'
            . ' johon ei tarvitse vastata.</small>',
        'mailer_subject_suggestion_deleted' => '{canceler} perui ehdotuksen',
        'mailer_body_suggestion_deleted' => 
            'Hei!<br /><br />{canceler} peruikin ehdotuksensa mennä {suggestion_date} syömään ravintolaan'
            . ' {restaurant} aikaan {suggestion_time}.<br /><br />Voit siirtyä Mealbookersiin'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">tästä</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti,'
            . ' johon ei tarvitse vastata.</small>',
        'mailer_subject_invite' => '{inviter} kutsuu sinut Mealbookersiin',
        'mailer_body_invite' => 
            'Hei!<br /><br />{inviter} kutsuu sinut Mealbookersiin käyttäjäryhmään {group_name}.<br /><br />'
            . ' Mealbookers tekee yhteisen lounasajan ehdottamisen ja löytämisen helpoksi.<br /><br />'
            . ' Liity jäseneksi ryhmään <a href="http://{server_hostname}/#/app/menu/register?invite={hash}">tästä</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti,'
            . ' johon ei tarvitse vastata.</small>',
        'mailer_subject_invite_notification' => '{inviter} liitti sinut ryhmään {group_name}',
        'mailer_body_invite_notification' => 
            'Hei!<br /><br />{inviter} liitti sinut käyttäjäryhmään {group_name} Mealbookersissa.<br /><br />'
            . ' Voit siirtyä Mealbookersiin <a href="http://{server_hostname}/#/app/menu">tästä</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti,'
            . ' johon ei tarvitse vastata.</small>',
        'mailer_subject_group_leave_notification' => '{deleter} poisti sinut ryhmästä {group_name}',
        'mailer_body_group_leave_notification' => 
            'Hei!<br /><br />{deleter} poisti sinut käyttäjäryhmästä {group_name} Mealbookersissa.<br /><br />'
            . ' Voit siirtyä Mealbookersiin <a href="http://{server_hostname}/#/app/menu">tästä</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>Tämä on automaattinen viesti,'
            . ' johon ei tarvitse vastata.</small>',
    ),
);