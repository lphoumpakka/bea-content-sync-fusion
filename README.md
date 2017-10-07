BEA Content Sync Fusion
=======================

Manage content synchronisation across a WordPress multisite.

## Compatibility

Compatible up to WordPress 4.9.x

## Changelog

### 3.0.5 - 06 oct. 2017
 * Allow content resync during blog/site creation
 * Refactoring code for all CLI tools
 * Add blog widget for counter info, and add button for force sync
 * Fix media sync, use a shared folder between blogs
 * Add link action for "resync content" into sites list
 * Add button on queue network page for exec CRON (for debug usage)

### 3.0.4 - 27 Sept 2017
 * Fix display admin emitters / receivers column
 
### 3.0.3 - 27 Sept 2017
 * Fix infinite loop insertion for taxonomies
 
### 3.0.2 - 5 Sept 2017
 * Handle multiple networks for admin option

### 3.0.1 - 26 July 2017
 * Fix unserialised datas of media after synch
 * Fix conflict with polylang on sync terms
 
### 3.0.0 - 29 June 2017
 * Work only on relations table, do not use old meta _origin_key
 * Synchronisations are bidirectional
 * Remove old code from notifications

### 2.0.2
 * Add filter bea_csf.client.post_type.allow_bidirectional_sync to allow bidirectional synchronisation

### 2.0.1
 * Fix P2P synchronisation

### 2.0.0
 * Remove media synchronisation using symlink. Use shared uploads folder.
 * Remove old code for old term meta API.
 * Use term_id instead tt_id.

### 1.1
 * Stable version using WordPress metadata API for Taxonomy.
