<?php
declare(strict_types = 1);
if (!defined('ABSPATH')) exit;
// phpcs:disable Generic.Files.InlineHTML.Found
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
$language_attributes = get_language_attributes();
$language_attributes = trim( (string) preg_replace( '/\s?dir=(["\']).*?\1/i', '', $language_attributes ) );
$language_attributes = trim( $language_attributes . ' dir="' . esc_attr( $rendering_context->get_text_direction() ) . '"' );
?><!DOCTYPE html>
<html <?php echo $language_attributes; ?>>
<head>
 <title><?php echo esc_html( $subject ); ?></title>
 <meta charset="<?php bloginfo( 'charset' ); ?>" />
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <meta name="viewport" content="width=device-width, initial-scale=1" />
 <meta http-equiv="X-UA-Compatible" content="IE=edge" />
 <meta name="format-detection" content="telephone=no" />
 <?php echo $meta_robots; ?>
 <!-- Forced Styles -->
</head>
<body dir="<?php echo esc_attr( $rendering_context->get_text_direction() ); ?>">
 <!--[if mso | IE]><table align="center" role="presentation" border="0" cellpadding="0" cellspacing="0" width="<?php echo esc_attr( $layout['contentSize'] ); ?>" style="width:<?php echo esc_attr( $layout['contentSize'] ); ?>"><tr><td><![endif]-->
 <div class="email_layout_wrapper" style="max-width: <?php echo esc_attr( $layout['contentSize'] ); ?>">
 <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
 <tbody>
 <tr>
 <td class="email_preheader" height="1">
 <?php echo esc_html( wp_strip_all_tags( $pre_header ) ); ?>
 </td>
 </tr>
 <tr>
 <td class="email_content_wrapper">
 <?php echo $template_html; ?>
 </td>
 </tr>
 </tbody>
 </table>
 </div>
 <!--[if mso | IE]></td></tr></table><![endif]-->
</body>
</html>
