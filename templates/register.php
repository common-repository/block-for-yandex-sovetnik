<div class="postbox">
	<div class="inside">
		<form action="<?php echo esc_url(add_query_arg('page','antisovet.php', get_admin_url().'admin.php')); ?>" onsubmit="sendform()" method="post">
			<p class="post-attributes-label-wrapper menu-order-label-wrapper">
				<label class="post-attributes-label"><?php _e('Your E-mail','antisovet'); ?> <sup>*</sup></label>
			</p>
			<?php 
			wp_nonce_field('antisovet','check_form_ref');
			$antisovet_email = get_option('antisovet_email');
			$admin_email = get_bloginfo('admin_email');
			?>
			<input name="email" type="email" value="<?php echo esc_html($antisovet_email ? $antisovet_email : $admin_email); ?>" class="regular-text" required placeholder="<?php echo esc_html($admin_email); ?>">
			<span>
				<input type="submit" name="register" id="register" class="button button-primary button-large" value="<?php _e('Install code','antisovet'); ?>">
				<div class="spinner"></div>
			</span>
			<p class="post-attributes-help-text">
				<i><?php _e('Required field for registration','antisovet'); ?></i>. 
			</p>
			<span class="dashicons dashicons-calendar-alt"></span> <b><?php _e('Free period - 5 days','antisovet'); ?></b>						
		</form>						
	</div>
</div>				

<?php include dirname( __FILE__ )."/support.php"; ?>
<script>
	function sendform() {
		document.querySelector('.wrap-as #register').classList.add("disabled");
		document.querySelector('.wrap-as  .spinner').classList.add("is-active");
	}
</script>