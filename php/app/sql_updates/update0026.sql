DELETE FROM  `restaurants` WHERE  `id` =2 LIMIT 1;

DELETE FROM  `restaurants` WHERE  `id` =4 LIMIT 1;

UPDATE `users` SET  `role` =  'admin' WHERE  `id` =1;

UPDATE  `users` SET  `email_address` =  'ilkka.rautiainen@yap.fi' WHERE `id` =3;

UPDATE `users` SET  `notify_suggestion_received` =  '1',
`notify_suggestion_accepted` =  '1',
`notify_suggestion_left_alone` =  '1',
`notify_suggestion_deleted` =  '1',
`notify_group_memberships` =  '1';