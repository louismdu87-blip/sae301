<?php
/** Itération 8.
 * @version 10.7.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$store_name                 = $store_name ?? get_bloginfo( 'name', 'display' );

$header_image_url = apply_filters( 'woocommerce_email_header_image_url', home_url() );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html( $store_name ); ?></title>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<table width="100%" id="outer_wrapper" role="presentation">
			<tr>
				<td></td>
				<td width="600">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper" role="presentation">
							<tr>
								<td align="center" valign="top">
									<?php
									$img = get_option( 'woocommerce_email_header_image' );

									if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
										$img_transient = get_transient( 'woocommerce_email_header_image' );
										$img           = false !== $img_transient ? $img_transient : $img;
									}

									if ( $email_improvements_enabled ) :
										?>
										<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
											<tr>
												<td id="template_header_image">
													<?php
													if ( $img ) {
														$image_html = '<img class="email-brand__logo" width="160" src="' . esc_url( $img ) . '" alt="' . esc_attr( $store_name ) . '" />';
														if ( $header_image_url ) {

															echo '<p style="margin-top:0;"><a href="' . esc_url( $header_image_url ) . '" style="display: inline-block; text-decoration: none;" target="_blank">' . $image_html . '</a></p>';
														} else {

															echo '<p style="margin-top:0;">' . $image_html . '</p>';
														}
													} elseif ( $header_image_url ) {
														echo '<p class="email-logo-text"><a href="' . esc_url( $header_image_url ) . '" style="color: inherit; text-decoration: none;" target="_blank">' . esc_html( $store_name ) . '</a></p>';
													} else {
														echo '<p class="email-logo-text">' . esc_html( $store_name ) . '</p>';
													}
													?>
												</td>
											</tr>
										</table>
									<?php else : ?>
										<div id="template_header_image">
											<?php
											if ( $img ) {
												$image_html = '<img class="email-brand__logo" width="160" src="' . esc_url( $img ) . '" alt="' . esc_attr( $store_name ) . '" />';
												if ( $header_image_url ) {

													echo '<p style="margin-top:0;"><a href="' . esc_url( $header_image_url ) . '" style="display: inline-block; text-decoration: none;" target="_blank">' . $image_html . '</a></p>';
												} else {

													echo '<p style="margin-top:0;">' . $image_html . '</p>';
												}
											}
											?>
										</div>
									<?php endif; ?>
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" role="presentation">
										<tr>
											<td align="center" valign="top">

												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" role="presentation">
													<tr>
														<td id="header_wrapper">
															<h1><?php echo esc_html( $email_heading ); ?></h1>
														</td>
													</tr>
												</table>

											</td>
										</tr>
										<tr>
											<td align="center" valign="top">

												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" role="presentation">
													<tr>
														<td valign="top" id="body_content">

															<table border="0" cellpadding="20" cellspacing="0" width="100%" role="presentation">
																<tr>
																	<td valign="top" id="body_content_inner_cell">
																		<div id="body_content_inner">
