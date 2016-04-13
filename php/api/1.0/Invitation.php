<?php

Flight::route('GET /invitation/@code', array('InvitationAPI', 'getInvitationInfo'));

class InvitationAPI
{
    public static function getInvitationInfo($code)
    {
        Logger::debug(__METHOD__ . " GET /invitation/$code called");

        $result = DB::inst()->query("SELECT email_address, group_id FROM invitations
            WHERE code = '" . DB::inst()->quote($code) . "'");

        if (!DB::inst()->getRowCount())
            throw new HttpException(404, 'invitation_not_found');

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