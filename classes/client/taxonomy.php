<?php
class BEA_CSF_Client_Taxonomy {
	/**
	 * Add term on DB
	 */
	public static function new_term( $datas = false ) {
		// Test datas validity
		if ( $datas == false || !is_array($datas) ) {
			return new WP_Error('invalid_datas', __('Bad call, invalid datas.'));
		}
		
		// Get local ID for parent, from TT_ID
		$datas['parent_tt_id'] = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $datas['parent_tt_id'] );
		if ( $datas['parent_tt_id'] > 0 ) {
			$datas['parent'] = (int) get_term_id_from_term_taxonomy_id( $datas['taxonomy'], $datas['parent_tt_id'] );
		}
		
		// Term exists ?
		$local_term_id = 0;
		$local_tt_id = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $datas['term_taxonomy_id'] );
		if ( $local_tt_id > 0 ) {
			$local_term_id = (int) get_term_id_from_term_taxonomy_id( $datas['taxonomy'], $local_tt_id );
		}
		
		if ( $local_term_id == 0 ) {
			//$edit = false;
			$new_term_id = wp_insert_term( $datas['name'], $datas['taxonomy'], array( 'description' => $datas['description'], 'slug' => $datas['slug'], 'parent' => $datas['parent']) );
			
			// try to manage error when term already exist with the same name !
			if( is_wp_error($new_term_id) && $new_term_id->get_error_code() == 'term_exists' ) {
				$term_exists_result = term_exists($datas['name'],  $datas['taxonomy'], $datas['parent']);
				if ( $term_exists_result != false ) {
					$local_tt_id = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $term_exists_result['term_taxonomy_id'] );
					if ( $local_tt_id == 0 ) { // No master ID? no sync item !
						$new_term_id = $term_exists_result;
						update_term_meta( $datas['taxonomy'], $term_exists_result['term_id'], 'already_exists', 1 );
					}
				}
			}
		} else {
			//$edit = true;
			$new_term_id = wp_update_term( $local_term_id, $datas['taxonomy'], array( 'name' => $datas['name'], 'description' => $datas['description'], 'slug' => $datas['slug'], 'parent' => $datas['parent']) );
		}
		
		// Test merge/insertion
		if ( is_wp_error($new_term_id) ) {
			return new WP_Error('term_insertion', $new_term_id->get_error_message() );
		} elseif ( is_array($new_term_id) && isset($new_term_id['term_id']) ) {
			$new_term_id = (int) $new_term_id['term_id'];
		} elseif ( (int) $new_term_id != 0 ) {
			$new_term_id = (int) $new_term_id;
		}
		
		// Always valid ?
		if ( $new_term_id == 0 ) {
			return new WP_Error('term_id_invalid', 'Error - Term ID is egal to 0' );
		}
		
		// Save master id
		update_term_meta( $datas['taxonomy'], $new_term_id, 'master_id', $datas['term_taxonomy_id'] );
		
		// Get full term datas
		$new_term = get_term( $new_term_id, $datas['taxonomy'] );
		
		// An image related to
		if ( isset($datas['image']) && !empty($datas['image']) ) {
			$new_image_id = $datas['image']['ID'];
		} else {
			$new_image_id = 0;
		}
		
		// A media exist for this term on this client ?
		$current_media_id = self::get_term_media_id( $new_term->term_taxonomy_id );
		if ( $current_media_id != $new_image_id ) {
			wp_delete_attachment( $current_media_id, true ); // Delete current media if a new media is loaded !
		}
		
		// Medias exist ?
		if ( $new_image_id != 0 ) {
			$new_media_id = self::mediaSideloadImage( esc_url(trailingslashit($datas['upload_url']).$datas['image']['meta']['_wp_attached_file'][0]), 0, null );
			if ( !is_wp_error($new_media_id) ) {
				self::set_term_media( $new_term->term_taxonomy_id, $new_media_id );
			}
		}
		
		return (int) $new_term->term_id;
	}

	/**
	 * Delete a term, take the master id, try to find the new ID and delete local term
	 * 
	 * @param array $datas
	 * @return \WP_Error|boolean
	 */
	public static function remove_term( $datas = false ) {
		// Test datas validity
		if ( $datas == false || !is_array($datas) ) {
			return new WP_Error('invalid_datas', __('Bad call, invalid datas.'));
		}
		
		// Term exists ?
		$local_tt_id = (int) get_term_taxonomy_id_from_meta( 'master_id', (int) $datas['term_taxonomy_id'] );
		if ( $local_tt_id > 0 ) {
			// Term already exist before sync, keep it !
			$already_exists = (int) get_term_taxonomy_id_from_meta( 'already_exists', (int) $datas['term_taxonomy_id'] );
			if ( $already_exists == 1 ) {
				return false;
			}
			
			$local_term_id = (int) get_term_id_from_term_taxonomy_id( $datas['taxonomy'], $local_tt_id );
			if ( $local_term_id > 0 ) {
				wp_delete_term( $local_term_id, $datas['taxonomy'] );
			}
		}
		
		return true;
	}
		
	/**
	 * Get the term image with the taxonomy image plugin
	 *
	 * @param int $tt_id Term Taxonomy Id, required
	 * @return integer|bool False on failure. The MediaID.
	 * 
	 * @author Amaury Balmer
	 */
	public static function get_term_media_id( $tt_id = 0 ) {
		if ( (int) $tt_id == 0 || !function_exists('taxonomy_image_plugin_get_associations') ) {
			return false;
		}
			
		// Get associations in the admin
		$assocs = taxonomy_image_plugin_get_associations();
		
		// Check if the term is associated yet
		if ( isset($assocs[$tt_id]) ) {
			return $assocs[$tt_id];
		}
		
		return false;
	}

	/**
	 * Get the term image with the taxonomy image plugin
	 *
	 * @param int $tt_id Term Taxonomy Id, required
	 * @param int $image_id Media ID, required
	 * @return integer|bool False on failure. The MediaID.
	 * 
	 * @author Amaury Balmer
	 */
	public static function set_term_media( $tt_id = 0, $image_id = 0 ) {
		if ( (int) $tt_id == 0 || (int) $image_id == 0 || !function_exists('taxonomy_image_plugin_get_associations') ) {
			return false;
		}
			
		$assoc = taxonomy_image_plugin_get_associations();
		$assoc[$tt_id] = $image_id;
		if ( update_option( 'taxonomy_image_plugin', taxonomy_image_plugin_sanitize_associations( $assoc ) ) ) {
			return true;
		}
		
		return false;
	}
}