<?php
class privatepages extends authentication {
	function beforeroute($f3) {
		if (!$this->checklogged($f3)) {
			$f3->reroute("/login");
		}

		$current_user = $f3->get('active_user');

		$lists_raw = $f3->get('DB')->exec("SELECT distinct list FROM link_list where user_upd = ?", $current_user['user_id']);
		$lists = [];
		foreach ($lists_raw as $item) {
			$lists[] = $item['list'];
		}
		$tags=array_unique($lists);
		asort($lists);
		$f3->set('list', $lists);

		$tags_raw = $f3->get('DB')->exec("SELECT distinct tags FROM link_list where user_upd = ?", $current_user['user_id']);
		$tags = [];
		foreach ($tags_raw as $item) {
			foreach (explode(',', $item['tags']) as $subitem) {
				$tags[] = $subitem;
			}
		}
		$tags=array_unique($tags);
		asort($tags);
		$f3->set('tags', $tags);
	}

	function afterroute($f3) {
		$f3->set('site_url', $this->siteURL());
		echo Template::instance()->render('private-layout.html');
	}

	function allview($f3) {
		$current_user = $f3->get('active_user');

		$results = $f3->get('DB')->exec("SELECT * FROM link_list where user_upd = ?", $current_user['user_id']);
		$f3->set('link_list', $results);

		$f3->set('content', 'private-link-list.html');
	}
	
	function listview($f3) {
		$current_user = $f3->get('active_user');
		$id = $f3->get('PARAMS.id');

		$results = $f3->get('DB')->exec("SELECT * FROM link_list where user_upd = ? and list = ?", array($current_user['user_id'], $id));
		$f3->set('link_list', $results);

		$f3->set('content', 'private-link-list.html');
	}
	
	function tagview($f3) {
		$current_user = $f3->get('active_user');
		$id = $f3->get('PARAMS.id');

		$results = $f3->get('DB')->exec("SELECT * FROM link_list where user_upd = ? and (',' || tags || ',') LIKE '%,?,%'", array($current_user['user_id'], $id));
		$f3->set('link_list', $results);

		$f3->set('content', 'private-link-list.html');
	}

	function linkedit($f3) {
		$current_user = $f3->get('active_user');

		$id = $f3->get('POST.id');
		$task = $f3->get('POST.task');

		if ($task == 'delete-link') {
			$f3->get('DB')->exec("DELETE FROM link_list WHERE id=?", $id);
		} elseif ($task == 'edit-link') {
			if ($id == 0) {
				$f3->get('DB')->exec("INSERT INTO link_list (name, link, tags, list, status, user_ins, time_ins, user_upd, time_upd) VALUES (?,?,?,?,?,?,?,?,?)", array($f3->get('POST.name'), $f3->get('POST.link'), implode(',', $f3->get('POST.tags')), $f3->get('POST.list'), '', $current_user['user_id'], date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s")));
			} else {
				$f3->get('DB')->exec("UPDATE link_list SET name=?, link=?, tags=?, list=?, user_upd=?, time_upd=? WHERE id=?", array($f3->get('POST.name'), $f3->get('POST.link'), implode(',', $f3->get('POST.tags')), $f3->get('POST.list'), $current_user['user_id'], date("Y-m-d H:i:s"), $id));
			}
		}

		$f3->reroute($f3->get('URI'));
	}

	function settings($f3) {
		$result = $f3->get('DB')->exec("SELECT count(1) as rows FROM user_session WHERE token_expire>=?", date("Y-m-d H:i:s"));
		$f3->set('active_session', $result[0]['rows']);

		$f3->set('content', 'private-settings.html');
	}

	function settingsedit($f3) {
		$task = $f3->get('POST.task');

		if ($task == 'end-session') {
			$f3->get('DB')->exec("DELETE FROM user_session");
			$f3->get('DB')->exec("UPDATE sqlite_sequence SET seq=? where name=?", array(1, 'user_session'));
		}

		$f3->reroute('/admin/settings');
	}

	function user($f3) {
		$results = $f3->get('DB')->exec("SELECT * FROM user");
		$f3->set('users', $results);

		$f3->set('content', 'private-users.html');
	}

	function useredit($f3) {
		$task = $f3->get('POST.task');

		if ($task == 'delete') {
			$user_id = $f3->get('POST.delete-id');

			$f3->get('DB')->exec("DELETE FROM user WHERE id = ?", $user_id);
		} elseif ($task == 'edit') {
			$user_id = $f3->get('POST.user-id');

			if ($user_id == 0) {
				$f3->get('DB')->exec("INSERT INTO user(user_id, password) VALUES(?,?)", array($f3->get('POST.user-user'), $this->encriptDecript($f3->get('POST.user-password'))));
			} else {
				$f3->get('DB')->exec("UPDATE user SET password=? WHERE id=?", array($this->encriptDecript($f3->get('POST.user-password')), $user_id));
			}
		}

		$f3->reroute('/admin/users');
	}
}