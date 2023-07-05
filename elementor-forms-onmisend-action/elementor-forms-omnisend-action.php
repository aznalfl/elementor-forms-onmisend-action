<?php
/**
 * Plugin Name: Elementor Forms Omnisend Action
 * Description: Custom addon which adds new subscriber to Omnisend after form submission.
 * Plugin URI:  https://elementor.com/
 * Version:     1.0.0
 * Author:      Astie Design Studio
 * Author URI:  https://astie.design/
 * Text Domain: elementor-forms-omnisend-action
 *
 * Elementor tested up to: 3.7.0
 * Elementor Pro tested up to: 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add new subscriber to Omnisend.
 *
 * @since 1.0.0
 * @param ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar $form_actions_registrar
 * @return void
 */
function add_new_omnisend_form_action( $form_actions_registrar ) {

	include_once( __DIR__ .  '/form-actions/omnisend.php' );

	$form_actions_registrar->register( new Omnisend_Action_After_Submit() );

}
add_action( 'elementor_pro/forms/actions/register', 'add_new_omnisend_form_action' );

