<?php
/**
 * Custom Order Status Handler for Monri Payments
 *
 * Replaces WooCommerce default 'completed' and 'processing' statuses
 * with custom 'wc-plac-neposkart' status for successful Monri transactions.
 *
 * @package Monri
 * @subpackage Custom
 * @since 3.8.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter hook: Change order status after successful payment
 *
 * This filter intercepts the payment_complete() function and replaces
 * the default WooCommerce status (processing/completed) with custom status.
 *
 * @param string   $status   Default order status (processing or completed)
 * @param int      $order_id Order ID
 * @param WC_Order $order    Order object
 * @return string Modified order status
 */
add_filter( 'woocommerce_payment_complete_order_status', 'monri_custom_order_status', 10, 3 );

function monri_custom_order_status( $status, $order_id, $order ) {
	// Get payment method
	$payment_method = $order->get_payment_method();

	// Check if payment method is Monri
	// Covers all Monri gateway variations: monri_webpay_form, monri_webpay_components, monri_wspay, etc.
	if ( strpos( $payment_method, 'monri' ) !== false ) {
		// Log status change for debugging (optional)
		if ( function_exists( 'Monri_WC_Logger::log' ) ) {
			Monri_WC_Logger::log(
				sprintf(
					'Order #%d: Changing status from "%s" to "plac-neposkart" (Custom status)',
					$order_id,
					$status
				),
				__METHOD__
			);
		}

		// Return custom status without 'wc-' prefix
		// WooCommerce automatically adds the prefix
		return 'plac-neposkart';
	}

	// Return default status for non-Monri payment methods
	return $status;
}

/**
 * Action hook: Monitor order status changes (optional - for logging/debugging)
 *
 * This action logs when orders change to failed/cancelled status.
 * Useful for debugging and monitoring payment issues.
 *
 * @param int      $order_id   Order ID
 * @param string   $old_status Old order status
 * @param string   $new_status New order status
 * @param WC_Order $order      Order object
 */
add_action( 'woocommerce_order_status_changed', 'monri_monitor_order_status_changes', 10, 4 );

function monri_monitor_order_status_changes( $order_id, $old_status, $new_status, $order ) {
	$payment_method = $order->get_payment_method();

	// Only process Monri payments
	if ( strpos( $payment_method, 'monri' ) === false ) {
		return;
	}

	// Log status changes for failed/cancelled orders
	if ( in_array( $new_status, array( 'failed', 'cancelled' ) ) && function_exists( 'Monri_WC_Logger::log' ) ) {
		Monri_WC_Logger::log(
			sprintf(
				'Order #%d: Status changed from "%s" to "%s" (Payment method: %s)',
				$order_id,
				$old_status,
				$new_status,
				$payment_method
			),
			__METHOD__
		);
	}

	// You can add custom logic here if needed
	// For example: send notifications, update external systems, etc.
}
