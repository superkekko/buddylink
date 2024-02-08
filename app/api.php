<?php
class api extends controller {
	function beforeroute($f3) {
	}
	
	function afterroute($f3) {
	}
	
	function linkread($f3) {
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
			list($tokenType, $token) = explode(' ', $authorizationHeader);

			if ($tokenType === 'Bearer' && !empty($token)) {
				$user = $f3->get('DB')->exec("SELECT * FROM user where bearer=?", $token);
				
				if(!empty($user[0])){
					
					$lists_raw = $f3->get('DB')->exec("SELECT distinct list FROM link_list where user_upd = ? or (group_id = ? and share = ?)", array($user[0]['user_id'], $user[0]['group_id'], 1));
					$lists = [];
					$i=0;
					foreach ($lists_raw as $item) {
						$lists['id_'.$i] = $item['list'];
						$i++;
					}
					$lists = array_unique($lists);
					asort($lists);
			
					$tags_raw = $f3->get('DB')->exec("SELECT distinct tags FROM link_list where user_upd = ? or (group_id = ? and share = ?)", array($user[0]['user_id'], $user[0]['group_id'], 1));
					$tags = [];
					$i=0;
					foreach ($tags_raw as $item) {
						foreach (explode(',', $item['tags']) as $subitem) {
							$tags['id_'.$i] = $subitem;
							$i++;
						}
					}
					$tags = array_unique($tags);
					asort($tags);
		
					$postData = json_decode(file_get_contents('php://input'), true);

					if ($postData !== null) {
						$result = $f3->get('DB')->exec("SELECT * FROM link_list WHERE link = ? and (user_upd = ? or (group_id = ? and share = ?))", array($postData['url'], $user[0]['user_id'], $user[0]['group_id'], 1));
						
						if(!empty($result)){
							$return_array=['data'=> array('list'=>$result[0]['list'],'tags'=>$result[0]['tags'], 'alllist'=>$lists, 'alltags'=> $tags)];
							header('Content-Type: application/json');
							echo json_encode($return_array);
						}else{
							$return_array=['data'=> array('list'=>'','tags'=>'', 'alllist'=>$lists, 'alltags'=> $tags)];
							header('Content-Type: application/json');
							echo json_encode($return_array);
						}
					} else {
						$return_array=['data'=> array('list'=>'','tags'=>'', 'alllist'=>$lists, 'alltags'=> $tags)];
						header('Content-Type: application/json');
						echo json_encode($return_array);
					}
				}
			} else {
				header('Content-Type: application/json');
				http_response_code(400);
			}
		} else {
			header('Content-Type: application/json');
			http_response_code(400);
		}
	}	
	
	function linkadd($f3) {
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
			list($tokenType, $token) = explode(' ', $authorizationHeader);

			if ($tokenType === 'Bearer' && !empty($token)) {
				$user = $f3->get('DB')->exec("SELECT * FROM user where bearer=?", $token);
				
				if(!empty($user[0])){
					$postData = json_decode(file_get_contents('php://input'), true);

					if ($postData !== null) {
						$doc = new DOMDocument();
						@$doc->loadHTMLFile($postData['url']);
						$xpath = new DOMXPath($doc);
						$url_title = $xpath->query('//title')->item(0)->nodeValue."\n";
						
						$result = $f3->get('DB')->exec("SELECT * FROM link_list WHERE link = ? and group_id = ?", array($postData['url'], $user[0]['group_id']));
						
						if(empty($result[0])){
							$f3->get('DB')->exec("INSERT INTO link_list (name, link, tags, list, group_id, user_ins, time_ins, user_upd, time_upd) VALUES (?,?,?,?,?,?,?,?,?)", array($url_title, $postData['url'], $postData['tags'], $postData['list'], $user[0]['group_id'], $user[0]['user_id'], date("Y-m-d H:i:s"), $user[0]['user_id'], date("Y-m-d H:i:s")));
						}else{
							$f3->get('DB')->exec("UPDATE link_list SET name=?, link=?, tags=?, list=?, user_upd=?, time_upd=? WHERE id=?", array($url_title, $postData['url'], $postData['tags'], $postData['list'], $user[0]['user_id'], date("Y-m-d H:i:s"), $result[0]['id']));
						}
						
						$return_array=['data'=> 'insert ok'];
						header('Content-Type: application/json');
						echo json_encode($return_array);
						
					} else {
						$return_array=['data'=> ''];
						header('Content-Type: application/json');
						echo json_encode($return_array);
					}
				}
			} else {
				header('Content-Type: application/json');
				http_response_code(400);
			}
		} else {
			header('Content-Type: application/json');
			http_response_code(400);
		}
	}
}