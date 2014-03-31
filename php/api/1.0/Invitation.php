<?php

Flight::route('GET /invitation/@code', array('InvitationAPI', 'getInvitationInfo'));

class InvitationAPI
{
    function getInvitationInfo($code)
    {
        Logger::debug(__METHOD__ . " GET /invitation/$code called");

        $result = DB::inst()->query("SELECT email_address, group_id FROM invitations
            WHERE code = '" . DB::inst()->quote($code) . "'");

        if (!DB::inst()->getRowCount())
            Application::inst()->exitWithHttpCode(404, "No invitation found with the code");

        $invitation = DB::inst()->fetchAssoc($result);

        print json_encode(array(
            'status' => 'ok',
            'invitation' => array(
                'email_address' => $invitation['email_address'],
                'group_name' => DB::inst()->getOne("SELECT name FROM groups WHERE id = " . $invitation['group_id']),
            ),
        ));
    }
}