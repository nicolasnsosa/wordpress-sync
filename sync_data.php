<?php

include_once 'mysql.php';

function object_to_array($data) {
    if (is_array($data) || is_object($data)) {
        $result = array();
        foreach ($data as $key => $value) {
            $result[$key] = object_to_array($value);
        }
        return $result;
    }
    return $data;
}

// URL: Wordpress to Get Data
$server_url = 'http://localhost/';

// Direct to get_data.php File
$server_json = file_get_contents($server_url . '/get_data.php');
$server_data = json_decode($server_json);

// Check if files exists and download if not
$files = $server_data->files;
if(is_array($files)) {
	echo 'Procesando archivos<br/><br/>';

	foreach($files as $i=>$filename) {
		$path = '../wp-content/uploads/' . $filename;

		// If file not exists, we going to download it
		if(!realpath($path)) {
			echo $path . ' -> Descargando archivo (' . realpath('../wp-content/uploads/')  . '/' . $filename . ')';

			file_put_contents(realpath('../wp-content/uploads/')  . '/' . $filename, fopen($server_url . 'wp-content/uploads/' . $filename, 'r'));
			
			echo ' / Descargado.<br/>';
		}
	}

	echo '<br/>Archivos descargados<br/><br/><br/>';
}

// Check if entries exists and add if not
$entries = $server_data->entries;
if(is_array($entries)) {
	echo 'Procesando entradas<br/><br/>';

	foreach($entries as $i=>$entry) {
		echo '- Procesando entrada:' . $entry->post_name . '<br/>';
		
		$query = 'SELECT * FROM `posts` WHERE `post_name` = \'' . $entry->post_name . '\';';
		$exists = executeScalarRow($query);

		if($exists) {
			echo '-- Entrada existente.<br/><br/>';
			continue;
		}

		echo '-- Creando entrada...<br/>';

		$entryArray = object_to_array($entry);
		
		// Entry
		unset($entryArray['ID']);
		$postId = executeInsertWithValues('posts', $entryArray);

		if(!$postId) {
			echo 'Error<br/><br/>';
			continue;
		}

		// Post Categories
		if(is_array($entry->post_term_relationships)) {
			foreach($entry->post_term_relationships as $ic=>$category) {
				$metaTermRelationships = object_to_array($category);
				$metaTermRelationships['object_id'] = $postId;

				executeInsertWithValues('term_relationships', $metaTermRelationships );
			}
		}

		// Post Meta
		if(is_array($entry->post_meta)) {
			foreach($entry->post_meta as $im=>$meta) {
				$hasPost = false;

				$metaArray = object_to_array($meta);
				$metaArray['post_id'] = $postId;

				if($meta->post && is_array($meta->post) && count($meta->post) == 1) {
					$metaPostArray = object_to_array($meta->post[0]);
					unset($metaPostArray['ID']);
					$postRelId = executeInsertWithValues('osts', $metaPostArray);

					// Meta from attachment post
					if(is_array($meta->post[0]->post_meta)) {
						foreach($meta->post[0]->post_meta as $im2=>$meta2) {
							$meta2Array = object_to_array($meta2);
							$meta2Array['post_id'] = $postRelId;

							executeInsertWithValues('postmeta', $meta2Array);
						}
					}

					$hasPost = true;
				}

				if($postRelId && $hasPost) {
					$metaArray['meta_value'] = $postRelId;
				}

				executeInsertWithValues('postmeta', $metaArray);
			}
		}
	}

	echo '<br/>Entradas agregadas';
}

?>