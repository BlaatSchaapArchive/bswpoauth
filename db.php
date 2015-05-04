<?php
    function  blaatoauth_install_database() {
      if (!get_option("bs_auth_signup_user_email")) 
        update_option("bs_auth_signup_user_email","Required");

      global $wpdb;
      global $bs_oauth_plugin;
      // dbver in sync with plugin ver
      $dbver = 44;
      $live_dbver = get_option( "bs_oauth_dbversion" );
      
      if (($dbver != $live_dbver) || get_option("bs_debug_updatedb") ) {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        $table_name = $wpdb->prefix . "bs_oauth_tokens";
        $query = "CREATE TABLE $table_name (
                  `token_id` INT NOT NULL AUTO_INCREMENT   ,
                  `wordpress_id` INT NOT NULL DEFAULT 0,
                  `service_id` INT NOT NULL ,
                  `token` TEXT NOT NULL ,
                  `secret` TEXT NOT NULL ,
                  `authorized` BOOLEAN NOT NULL ,
                  `expiry` DATETIME NULL DEFAULT NULL ,
                  `type` TEXT NULL DEFAULT NULL ,
                  `refresh` TEXT NULL DEFAULT NULL,
                  `scope` TEXT NOT NULL DEFAULT '',
                  KEY (token(256)),
                  PRIMARY KEY  (token_id)
                  );";
        dbDelta($query);
  // Note: we should use KEY in stead of the usual INDEX when using DbDelta

  // how big is bigint, is it enough?
        $table_name = $wpdb->prefix . "bs_oauth_accounts";
        $query = "CREATE TABLE $table_name (
                  `account_id` INT NOT NULL AUTO_INCREMENT   ,
                  `wordpress_id` INT NOT NULL DEFAULT 0,
                  `service_id` INT NOT NULL ,
                  `external_id_int` BIGINT NULL DEFAULT NULL,
                  `external_id_text` TEXT NULL DEFAULT NULL,
                  KEY (external_id_int),
                  KEY (external_id_text(256)),
                  PRIMARY KEY  (account_id)
                  );";
        dbDelta($query);


        $table_name = $wpdb->prefix . "bs_oauth_services_configured";
        $query = "CREATE TABLE $table_name (
                  `service_id` INT NOT NULL AUTO_INCREMENT  ,
                  `service_known_id` INT DEFAULT 0,
                  `oauth_version` ENUM('1.0','1.0a','2.0') DEFAULT '2.0',
                  `request_token_url` TEXT NULL DEFAULT NULL,
                  `dialog_url` TEXT NOT NULL,
                  `access_token_url` TEXT NOT NULL,
                  `userinfo_url` TEXT NOT NULL,
                  `url_parameters` BOOLEAN DEFAULT FALSE,
                  `authorization_header` BOOLEAN DEFAULT TRUE,
                  `append_state_to_redirect_uri` TEXT NULL DEFAULT NULL,
                  `pin_dialog_url` TEXT NULL DEFAULT NULL,
                  `offline_dialog_url` TEXT NULL DEFAULT NULL,
                  `client_id` TEXT NOT NULL ,
                  `client_secret` TEXT NOT NULL,
                  `fixed_redirect_url` BOOLEAN NOT NULL DEFAULT TRUE ,
                  `override_redirect_url` TEXT NULL DEFAULT NULL,
                  `request_method` ENUM('GET', 'POST') DEFAULT 'GET',
                  `data_format` ENUM('FORM','JSON','XML') DEFAULT 'JSON',
                  `external_id` TEXT NULL DEFAULT NULL,
                  `first_name`  TEXT NULL DEFAULT NULL,
                  `last_name`  TEXT NULL DEFAULT NULL,
                  `user_email`  TEXT NULL DEFAULT NULL,
                  `user_url`  TEXT NULL DEFAULT NULL,
                  `user_nicename`  TEXT NULL DEFAULT NULL,
                  `user_login`  TEXT NULL DEFAULT NULL,
                  `scope`  TEXT NULL DEFAULT NULL,
                  `email_verified`  TEXT NULL DEFAULT NULL,
                  `login_options_id` INT NOT NULL, 
                  PRIMARY KEY  (service_id)
                  );";
        dbDelta($query);
   
        update_option( "bs_oauth_dbversion" , $dbver);
      }

      $dataver = 44;
      $live_dataver = get_option( "bs_oauth_dataversion" );
      if ($dataver != $live_dataver) {
        $service_info = file_get_contents(  plugin_dir_path( __FILE__ ) . "/sql/service_data.sql");
        if ($wpdb->prefix != "wp_") $service_info = str_replace("wp_", $wpdb->prefix);
        $service_info_queries = explode(";", $service_info);
        foreach ($service_info_queries as $query) $wpdb->query($query);
        update_option( "bs_oauth_dataversion" , $dataver);

      }


    }
//------------------------------------------------------------------------------
?>
