<div class="wrap">
	<h2><?php _e( "Content Sync: Queue", 'bea-content-sync-fusion' ); ?></h2>

	<?php
	global $wpdb;
	$results = $wpdb->get_results( 'SELECT COUNT(id) as nbqueue FROM ' . $GLOBALS['wpdb']->bea_csf_queue, OBJECT );

	echo '<p>' . __( 'Cron process 300 items by 300 items', 'bea-content-sync-fusion' ) . '</p>';

	echo __( 'Number of items in the queue : ', 'bea-content-sync-fusion' ) . reset( $results )->nbqueue;

	$lock_file = sys_get_temp_dir() . '/bea-content-sync-fusion.lock';
	if ( file_exists( $lock_file ) ) {
		echo '<p>' . sprintf( __( 'The file %s has been modified : ', 'bea-content-sync-fusion' ), $lock_file ) . date( "d F Y H:i:s.", filemtime( $lock_file ) ) . '</p>';
		?>
		<form action="" method="post">
			<p class="submit">
				<?php wp_nonce_field( 'delete-bea-csf-file-lock' ); ?>
				<input type="submit" class="button-primary" name="delete-bea-csf-file-lock"
				       value="<?php _e( 'Delete file lock', 'bea-content-sync-fusion' ); ?>"/>
			</p>
		</form>
		<?php
	}
	if ( isset( $_GET['message'] ) && $_GET['message'] == 'deleted' ) {
		echo '<p>' . sprintf( __( 'The file %s has been deleted', 'bea-content-sync-fusion' ), $lock_file ) . '</p>';
	}

	// Maintenance
	$results_maintenance = $wpdb->get_results( 'SELECT COUNT(id) as nbqueue FROM ' . $GLOBALS['wpdb']->bea_csf_queue_maintenance, OBJECT );
	$nb_queue_maintenance = reset( $results_maintenance )->nbqueue;
	if ( '0' != $nb_queue_maintenance ) {
		echo '<p>' . __( 'Number of items in the queue of maintenance : ', 'bea-content-sync-fusion' ) . $nb_queue_maintenance . '</p>';
	}

	?>
</div>