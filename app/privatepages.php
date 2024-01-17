<?php
class privatepages extends authentication {
	function beforeroute($f3) {
		
		if (!$this->checklogged($f3)) {
			$f3->reroute("/logout");
		}

		$current_user = $f3->get('active_user');

		$lists_raw = $f3->get('DB')->exec("SELECT distinct list FROM link_list where user_upd = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$lists = [];
		foreach ($lists_raw as $item) {
			$lists[] = $item['list'];
		}
		$tags = array_unique($lists);
		asort($lists);
		$f3->set('list', $lists);

		$tags_raw = $f3->get('DB')->exec("SELECT distinct tags FROM link_list where user_upd = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$tags = [];
		foreach ($tags_raw as $item) {
			foreach (explode(',', $item['tags']) as $subitem) {
				$tags[] = $subitem;
			}
		}
		$tags = array_unique($tags);
		asort($tags);
		$f3->set('tags', $tags);
	}

	function afterroute($f3) {
		$f3->set('site_url', $this->siteURL());
		echo Template::instance()->render('private-layout.html');
	}

	function allview($f3) {
		$current_user = $f3->get('active_user');

		$results = $f3->get('DB')->exec("SELECT * FROM link_list where user_upd = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$f3->set('link_list', $results);

		$f3->set('content', 'private-item.html');
	}

	function lists($f3) {
		$current_user = $f3->get('active_user');

		$results = $f3->get('DB')->exec("SELECT distinct list FROM link_list where user_upd = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$alllist = [];
		foreach ($results as $result) {
			$alllist[] = $result['list'];
		}
		
		if(($key = array_search("", $alllist)) !== false) {
		    unset($alllist[$key]);
		}

		$f3->set('list_item', $alllist);

		$f3->set('content', 'private-list.html');
	}

	function listview($f3) {
		$current_user = $f3->get('active_user');
		$id = $f3->get('PARAMS.id');

		$results = $f3->get('DB')->exec("SELECT * FROM link_list where user_upd = ? and list = ?", array($current_user['user_id'], $id));
		$f3->set('link_list', $results);

		$f3->set('content', 'private-item.html');
	}

	function tags($f3) {
		$current_user = $f3->get('active_user');

		$results = $f3->get('DB')->exec("SELECT distinct tags FROM link_list where user_upd = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$alltags = [];

		foreach ($results as $result) {
			$alltags = array_merge($alltags, explode(',', $result['tags']));
		}
		
		if(($key = array_search("", $alltags)) !== false) {
		    unset($alltags[$key]);
		}

		$f3->set('list_item', array_unique($alltags));

		$f3->set('content', 'private-tag.html');
	}

	function tagview($f3) {
		$current_user = $f3->get('active_user');
		$id = $f3->get('PARAMS.id');

		$results = $f3->get('DB')->exec("SELECT * FROM link_list where user_upd = ? and (',' || tags || ',') LIKE ?", array($current_user['user_id'], '%,'.$id.',%'));
		$f3->set('link_list', $results);

		$f3->set('content', 'private-item.html');
	}

	function linkedit($f3) {
		$current_user = $f3->get('active_user');

		$id = $f3->get('POST.id');
		$task = $f3->get('POST.task');

		if ($task == 'delete-link') {
			$f3->get('DB')->exec("DELETE FROM link_list WHERE id=?", $id);
		} elseif ($task == 'edit-link') {
			if($f3->get('POST.tags') == '') {
				$tags = null;
			} else {
				$tags = implode(',', $f3->get('POST.tags'));
			}
			
			if(!empty($f3->get('POST.share'))){
				$share = 1;
			}else{
				$share = 0;
			}

			if ($id == 0) {
				$f3->get('DB')->exec("INSERT INTO link_list (name, link, tags, list, group_id, share, user_ins, time_ins, user_upd, time_upd) VALUES (?,?,?,?,?,?,?,?,?,?)", array($f3->get('POST.name'), $f3->get('POST.link'), $tags, $f3->get('POST.list'), $current_user['group_id'], $share, $current_user['user_id'], date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s")));
			} else {
				$f3->get('DB')->exec("UPDATE link_list SET name=?, link=?, tags=?, list=?, share=?, user_upd=?, time_upd=? WHERE id=?", array($f3->get('POST.name'), $f3->get('POST.link'), $tags, $f3->get('POST.list'), $share, $current_user['user_id'], date("Y-m-d H:i:s"), $id));
			}
		}

		$f3->reroute($f3->get('URI'));
	}

	function settings($f3) {
		$current_user = $f3->get('active_user');

		$f3->set('content', 'private-settings.html');
	}

	function settingsedit($f3) {
		$current_user = $f3->get('active_user');
		$task = $f3->get('POST.task');

		if ($task == 'token-refresh') {
			$check = true;
			while ($check) {
				$bearer = $this->generateRandomString(50);
				$result = $f3->get('DB')->exec("SELECT count(1) as occurence FROM user WHERE bearer=?", $bearer);
				if ($result[0]['occurence'] == 0) {
					$check = false;
				}
			}
			$f3->get('DB')->exec("UPDATE user SET bearer=? where user_id=?", array($bearer, $current_user['user_id']));
		} elseif ($task == 'password-change') {
			$password_old = $f3->get('POST.password-old');
			$password_new = $f3->get('POST.password-new');

			if ($this->encriptDecript($f3, $current_user['password'], 'd') !== $password_old) {
				$f3->reroute('/settings?status=password-error');
			} else {
				$f3->get('DB')->exec("UPDATE user SET password=? where user_id=?", array($this->encriptDecript($f3, $password_new), $current_user['user_id']));
				$f3->reroute('/settings?status=password-changed');
			}
		}

		$f3->reroute('/settings');
	}

	function supersettings($f3) {
		$current_user = $f3->get('active_user');
		
		if($current_user['superadmin'] != 1){
			$f3->reroute("/");
		}
		
		$result = $f3->get('DB')->exec("SELECT count(1) as rows FROM user_session WHERE token_expire>=?", date("Y-m-d H:i:s"));
		$f3->set('active_session', $result[0]['rows']);

		$results = $f3->get('DB')->exec("SELECT * FROM user");
		$f3->set('users', $results);

		$f3->set('content', 'private-super-settings.html');
	}

	function supersettingsedit($f3) {
		$current_user = $f3->get('active_user');
		$task = $f3->get('POST.task');
		
		if($current_user['superadmin'] != 1){
			$f3->reroute("/");
		}

		if ($task == 'delete') {
			$user_id = $f3->get('POST.delete-id');

			$f3->get('DB')->exec("DELETE FROM user WHERE id = ?", $user_id);
		} elseif ($task == 'edit') {
			$user_id = $f3->get('POST.user-id');

			if ($user_id == 0) {
				$result = $f3->get('DB')->exec("SELECT count(1) as rows FROM user WHERE user_id = ?", $f3->get('POST.user-user'));
				if ($result[0]['rows'] > 0) {
					$f3->reroute('/supersettings?result=same-userid');
				}
				if ($f3->get('POST.user-superadmin') == '1') {
					$f3->get('DB')->exec("INSERT INTO user(user_id, group_id, password, bearer, superadmin) VALUES(?,?,?,?,?)",
						array($f3->get('POST.user-user'), $f3->get('POST.user-group'), $this->encriptDecript($f3, $f3->get('POST.user-password')), $this->generateRandomString(50), 1));
				} else {
					$f3->get('DB')->exec("INSERT INTO user(user_id, group_id, password, bearer, superadmin) VALUES(?,?,?,?,?)",
						array($f3->get('POST.user-user'), $f3->get('POST.user-group'), $this->encriptDecript($f3, $f3->get('POST.user-password')), $this->generateRandomString(50), 0));
				}
			} else {
				if ($f3->get('POST.user-password') != '') {
					$f3->get('DB')->exec("UPDATE user SET password=? WHERE id=?", array($this->encriptDecript($f3, $f3->get('POST.user-password')), $user_id));
				}
				if ($f3->get('POST.user-superadmin') == '1') {
					$f3->get('DB')->exec("UPDATE user SET superadmin=? WHERE id=?",
						array(1, $user_id));
				} else {
					$f3->get('DB')->exec("UPDATE user SETsuperadmin=? WHERE id=?",
						array(0, $user_id));
				}
			}
		} elseif ($task == 'end-session') {
			$f3->get('DB')->exec("DELETE FROM user_session");
			$f3->get('DB')->exec("UPDATE sqlite_sequence SET seq=? where name=?", array(1, 'user_session'));
		} elseif ($task == 'delete-campaign-hits') {
			$f3->get('DB')->exec("UPDATE campaign SET hit=0");
		} elseif ($task == 'delete-data-visit') {
			$f3->get('DB')->exec("DELETE FROM visitor");
			$f3->get('DB')->exec("DELETE FROM page_view");
			$f3->get('DB')->exec("UPDATE sqlite_sequence SET seq=? where name=?", array(1, 'page_view'));
			$f3->get('DB')->exec("DELETE FROM referrer");
			$f3->get('DB')->exec("UPDATE sqlite_sequence SET seq=? where name=?", array(1, 'referrer'));
		}

		$f3->reroute('/supersettings');
	}
}