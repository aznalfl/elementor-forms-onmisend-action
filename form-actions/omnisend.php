<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Omnisend_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	public function get_name() {
		return 'omnisend';
	}

	public function get_label() {
		return esc_html__( 'Omnisend', 'elementor-forms-omnisend-action' );
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_omnisend',
			[
				'label' => esc_html__( 'Omnisend', 'elementor-forms-omnisend-action' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'omnisend_api_key',
			[
				'label' => esc_html__( 'Omnisend API Key', 'elementor-forms-omnisend-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'description' => esc_html__( 'Enter your Omnisend API Key.', 'elementor-forms-omnisend-action' ),
			]
		);

		$widget->add_control(
			'omnisend_email_field',
			[
				'label' => esc_html__( 'Email Field ID', 'elementor-forms-omnisend-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$widget->add_control(
			'omnisend_first_name_field',
			[
				'label' => esc_html__( 'First Name Field ID', 'elementor-forms-omnisend-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$widget->add_control(
			'omnisend_last_name_field',
			[
				'label' => esc_html__( 'Last Name Field ID', 'elementor-forms-omnisend-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$widget->add_control(
			'omnisend_tags',
			[
				'label' => esc_html__( 'Tags', 'elementor-forms-omnisend-action' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'description' => esc_html__( 'Enter tags separated by comma.', 'elementor-forms-omnisend-action' ),
			]
		);

		$widget->add_control(
			'omnisend_send_welcome_message',
			[
				'label' => esc_html__( 'Send Welcome Message', 'elementor-forms-omnisend-action' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementor-forms-omnisend-action' ),
				'label_off' => esc_html__( 'No', 'elementor-forms-omnisend-action' ),
				'return_value' => 'yes',
				'default' => 'no',
			]
		);

		$widget->end_controls_section();
	}

	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );
		$fields = $record->get( 'fields' );

	        // Prepare Omnisend data
	        $email = sanitize_email( $fields[ $settings['omnisend_email_field'] ]['value'] );
	        $firstName = isset( $settings['omnisend_first_name_field'] ) ? sanitize_text_field( $fields[ $settings['omnisend_first_name_field'] ]['value'] ) : '';
	        $lastName = isset( $settings['omnisend_last_name_field'] ) ? sanitize_text_field( $fields[ $settings['omnisend_last_name_field'] ]['value'] ) : '';
	        $sendWelcomeMessage = isset($settings['omnisend_send_welcome_message']) ? $settings['omnisend_send_welcome_message'] : false;
	
	        $omnisend_data = [
	            'identifiers' => [
	                [
	                    'type' => 'email',
	                    'channels' => [
	                        'email' => [
	                            'status' => 'subscribed',
				    'statusDate' => gmdate('Y-m-d\TH:i:s\Z')
	                        ]
	                    ],
	                    'id' => $email,
	                    'sendWelcomeMessage' => $sendWelcomeMessage === "yes" ? true : false // Ensuring sendWelcomeMessage is a boolean.
	                ]
	            ],
	            'tags' => isset( $settings['omnisend_tags'] ) ? array_map( 'trim', explode( ',', sanitize_text_field( $settings['omnisend_tags'] ) ) ) : [],
	            'firstName' => $firstName,
	            'lastName' => $lastName,
	        ];
	
		// Send request to Omnisend
		$response = wp_remote_post(
			'https://api.omnisend.com/v3/contacts',
			[
				'body' => json_encode( $omnisend_data ),
				'headers' => [
					'Content-Type' => 'application/json',
					'X-API-KEY' => sanitize_text_field( $settings['omnisend_api_key'] ),
				],
			]
		);

		// Error handling
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$ajax_handler->add_error_message( 'Failed to add subscriber to Omnisend. ' . $error_message );
			return;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( $http_code != 200 ) {
			$ajax_handler->add_error_message( 'Failed to add subscriber to Omnisend. Response Code: ' . $http_code . ' Response Body: ' . wp_remote_retrieve_body( $response ) );
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['error'] ) ) {
			$ajax_handler->add_error_message( 'Failed to add subscriber to Omnisend. ' . $body['error'] );
			return;
		}
	}

	public function on_export( $element ) {
		unset(
			$element['omnisend_api_key'],
			$element['omnisend_email_field'],
			$element['omnisend_first_name_field'],
			$element['omnisend_last_name_field'],
			$element['omnisend_tags'],
			$element['omnisend_send_welcome_message']
		);
		return $element;
	}
}

function add_omnisend_action_after_plugins_loaded() {
	\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( 'omnisend', new Omnisend_Action_After_Submit() );
}

add_action( 'plugins_loaded', 'add_omnisend_action_after_plugins_loaded' );
