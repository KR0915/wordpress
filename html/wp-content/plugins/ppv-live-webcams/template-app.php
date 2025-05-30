<?php
/*
Template Name: VideoWhisper App - Full Page, without Site Template
*/

// as this full page app template does not include site header/footer, all scripts and css need to be loaded manually

defined( 'ABSPATH' ) or exit;

?><!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no"/>
		<link rel="manifest" href="./manifest.json"/>
		<link rel="stylesheet" href="<?php echo esc_url(plugin_dir_url( __FILE__ ) . '/scripts/semantic/semantic.min.css'); ?>">
		<link rel="stylesheet" href="<?php echo esc_url(plugin_dir_url( __FILE__ ) . '/scripts/video-js/video-js.min.css'); ?>">
<?php
		$CSSfiles = scandir( dirname( __FILE__ ) . '/static/css/' );
foreach ( $CSSfiles as $filename ) {
	if ( strpos( $filename, '.css' ) && ! strpos( $filename, '.css.map' ) ) {
		echo '<link rel="stylesheet" href="' . esc_url( plugin_dir_url( __FILE__ ) . '/static/css/' . $filename ) . '">';
	}
}
?>
		  <title><?php _e( 'Video Chat', 'ppv-live-webcams' ); ?></title>
		  <script src="<?php echo esc_url(includes_url() . 'js/jquery/jquery.js'); ?>" type="text/javascript"></script>
		  <script src="<?php echo esc_url(plugin_dir_url( __FILE__ ) . '/scripts/semantic/semantic.min.js'); ?>"></script>
		   <script src="<?php echo esc_url(plugin_dir_url( __FILE__ ) . '/scripts/video-js/video-js.min.js'); ?>"></script>

	</head>
		<body>

<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		the_content();
endwhile;
else :
	echo 'No content: ' . esc_html(get_the_ID());
endif;

		$JSfiles = scandir( dirname( __FILE__ ) . '/static/js/' );
foreach ( $JSfiles as $filename ) {
	if ( strpos( $filename, '.js' ) && ! strpos( $filename, '.js.map' ) ) { // && !strstr($filename,'runtime~')
		echo '<script type="text/javascript" src="' . esc_url(plugin_dir_url( __FILE__ ) . '/static/js/' . $filename) . '"></script>';
	}
}
?>
