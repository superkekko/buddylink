ALTER TABLE link_list RENAME COLUMN status TO group_id;
ALTER TABLE link_list ADD share INTEGER;
update user set group_id=user_id where group_id is null;
update link_list set group_id=(select u.group_id from user u where u.user_id=user_ins) where group_id is null;