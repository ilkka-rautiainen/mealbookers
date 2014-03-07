<?php

$GLOBALS['language']['en'] = array(
    'app_name' => 'Mealbookers',
    'cafe' => 'Café',
    'alacarte' => 'À la carte',
    'bistro' => 'Bistro',
    'tune_own_burger' => 'Fine-tune your own hamburger',
    'today' => 'Today',
    'tomorrow' => 'Tomorrow',
    'weekday_1' => 'Monday',
    'weekday_2' => 'Tuesday',
    'weekday_3' => 'Wednesday',
    'weekday_4' => 'Thursday',
    'weekday_5' => 'Friday',
    'weekday_6' => 'Saturday',
    'weekday_7' => 'Sunday',
    'in_otaniemi' => 'Otaniemi menu\'s on',
    'log_in' => 'Log in',
    'email' => 'Email',
    'password' => 'Password',
    'log_in_remember_me' => 'Remember me',
    'and' => 'and',
    'close' => 'Close',
    'cancel' => 'Cancel',
    'register' => 'Register',
    'register_full_name' => 'Full name',
    'register_first_name' => 'First name',
    'register_last_name' => 'Last name',
    'suggest_eating_time' => 'Suggest eating time',
    'suggest' => 'Suggest',
    'suggest_time' => 'Time',
    'suggest_time_placeholder' => 'tt:mm',
    'suggest_friends' => 'Suggest to friends',
    'suggestion_save_error' => 'Error while saving the suggestion',
    'suggestion_too_early' => 'You cannot make a suggestion with a time more than 5 min ago',
    'suggest_failed_to_send_invitation_email' => 'Failed to send invitation email:',
    'suggest_sending' => 'Sending suggestion...',
    'suggestion_created' => 'Suggestion created!',
    'suggestion_been_deleted' => 'The suggestion you accepted had been deleted...',
    'suggestion_accept_succeeded' => 'Suggestion accepted',
    'suggestion_accept' => 'Accept',
    'suggestion_cancel' => 'Cancel',
    'suggestion_outside_members' => '+ from other groups',
    'suggestion_accept_failed' => 'Handling the suggestion failed.',
    'suggestion_accept_gone' => 'Time of the suggestion has already past.',
    'suggestion_accepting' => 'Accepting the suggestion...',
    'suggestion_manage_canceled' => 'Canceled!',
    'suggestion_manage_canceled_and_deleted' => 'Canceled! The whole suggestion was removed as you was its last member.',
    'suggestion_manage_accepted' => 'Accepted!',
    'action_failed' => 'Action failed',
    'backend_only' => array(
        'mailer_sender_name' => 'Mealbookers',
        'mailer_subject_suggestion' => '{suggester} wants to eat with you',
        'mailer_body_suggestion' => 
            'Hello!<br /><br />{suggester} is going to eat at {restaurant} on {suggestion_date}.'
            . ' He suggests you to go there at {suggestion_time}.<br /><br /><b>{restaurant} - menu</b><br />'
            . ' {menu}<br /><br />Accept the suggestion by clicking'
            . ' <a href="http://{server_hostname}/#/app/suggestion/accept?hash={hash}">here</a>.'
            . ' <br /><br />Go to Mealbookers without accepting the suggestion'
            . ' <a href="http://{server_hostname}/#/app/menu">here</a>.<br /><br />- Mealbookers<br /><br />'
            . ' <small>This is an automatic mail. You don\'t need to reply to it.</small>',
        'mailer_subject_suggestion_accepted_creator' => '{accepter} has accepted your suggestion',
        'mailer_body_suggestion_accepted_creator' => 
            'Hello!<br /><br />{accepter} has accepted your suggestion to go to eat at {restaurant}'
            . ' on {suggestion_date} at {suggestion_time}.<br /><br />You can go to Mealbookers'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">here</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>This is an automatic mail.'
            . ' You don\'t need to reply to it.</small>',
        'mailer_subject_suggestion_accepted_other' => '{accepter} is coming to eat with you',
        'mailer_body_suggestion_accepted_other' => 
            'Hello!<br /><br />{accepter} is coming to eat with you at {restaurant} on'
            . ' {suggestion_date} at {suggestion_time}.<br /><br />You can go to Mealbookers'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">here</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>This is an automatic mail.'
            . ' You don\'t need to reply to it.</small>',
        'mailer_subject_suggestion_left_alone' => '{canceler} can\'t eat with you though',
        'mailer_body_suggestion_left_alone' => 
            'Hello!<br /><br />{canceler} can\'t come to eat with you at {restaurant}'
            . ' on {suggestion_date} at {suggestion_time}.<br /><br />You can go to Mealbookers'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">here</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>This is an automatic mail.'
            . ' You don\'t need to reply to it.</small>',
        'mailer_subject_suggestion_deleted' => '{canceler} canceled the suggestion',
        'mailer_body_suggestion_deleted' => 
            'Hello!<br /><br />{canceler} canceled his/her suggestion to go to eat at {restaurant}'
            . ' on {suggestion_date} at {suggestion_time}.<br /><br />You can go to Mealbookers'
            . ' <a href="http://{server_hostname}/#/app/menu?day={day}">here</a>.'
            . ' <br /><br />- Mealbookers<br /><br /><small>This is an automatic mail.'
            . ' You don\'t need to reply to it.</small>',
    ),
);