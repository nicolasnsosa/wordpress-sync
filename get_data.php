<?php

header('Content-Type: application/json');

$ret = array();
$ret['files'] = array();

include_once 'mysql.php';

$query = 'SELECT * FROM `posts` WHERE post_type = \'post\'';
$result = executeArray($query);

// Posts
foreach($result as $ip=>$post) {
	$query = 'SELECT * FROM `postmeta` WHERE post_id = ' . $post['ID'];
	$metaResult = executeArray($query);

	// Metadata Posts
	foreach($metaResult as $ipm=>$postmeta) {
		$query = 'SELECT * FROM `posts` WHERE ID = ' . $postmeta['meta_value'] . ' AND post_type = \'attachment\';';
		$metaPostResult = executeArray($query);

		if(is_array($metaPostResult)) {
			// Posts (From Metadata)
			foreach($metaPostResult as $ip2=>$post2) {
				$query = 'SELECT * FROM `postmeta` WHERE post_id = ' . $post2['ID'];
				$metaPost2Result = executeArray($query);

				foreach($metaPost2Result as $ipm2=>$postmeta2) {					
					if($postmeta2['meta_key'] == '_wp_attached_file') {
						$ret['files'][] = $postmeta2['meta_value'];
					}
				}

				$metaPostResult[$ip2]['post_meta'] = $metaPost2Result;
			}
		}

		$metaResult[$ipm]['post'] = $metaPostResult;
	}
	
	$result[$ip]['post_meta'] = $metaResult;
}
$ret['entries'] = $result;

echo json_encode($ret);

?>
