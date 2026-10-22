<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Messages Language Lines
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'sending_failed' => 'SMS sending failed.',
    ],

    'otp' => [
        'message' => 'Your verification code is: :code :app_key',
    ],

    'validation_error' => 'The given data was invalid.',
    'unknown_error' => 'An unknown error occurred.',

    'auth' => [
        'register_success' => 'Registered successfully. A verification code has been sent to your phone.',
        'otp_verified' => 'Verification code confirmed successfully',
        'otp_sent' => 'Verification code sent successfully',
        'login_success' => 'Logged in successfully',
        'password_reset_success' => 'Password reset successfully',
        'logout_success' => 'Logged out successfully',
        'account_deleted' => 'Account deleted successfully',
        'language_updated' => 'Language updated successfully',
        'profile_retrieved' => 'Profile retrieved successfully',
        'profile_image_updated' => 'Profile image updated successfully',
        'password_changed' => 'Password changed successfully',
        'token_refreshed' => 'Token refreshed successfully',
        'account_blocked' => 'Your account has been blocked. :reason',
        'invalid_otp' => 'The verification code is invalid or has expired.',
        'phone_already_taken' => 'This phone number is already in use.',
        'invalid_otp_type' => 'Invalid verification code type.',
        'phone_not_found' => 'No account was found for this phone number.',
        'phone_already_registered' => 'This phone number is already verified.',
        'invalid_credentials' => 'The phone number or password is incorrect.',
        'phone_not_verified' => 'Your phone number is not verified. A verification code has been sent.',
        'current_password_incorrect' => 'The current password is incorrect.',
        'same_password' => 'The new password must be different from the current password.',
        'token_expired_or_invalid' => 'The token is invalid or has expired.',
        'invalid_token_type' => 'Invalid token type.',
        'user_not_found' => 'User not found.',
        'unauthenticated' => 'Unauthenticated.',
        'invalid_google_token' => 'Invalid Google token.',
        'google_token_expired' => 'The Google token has expired.',
        'google_keys_fetch_failed' => 'Could not fetch the Google public keys.',
    ],

    'notification' => [
        'device_token_stored' => 'Device token saved successfully',
        'device_token_removed' => 'Device token removed successfully',
        'retrieved' => 'Notifications retrieved successfully',
        'unread_count_retrieved' => 'Unread notifications count retrieved successfully',
        'not_found' => 'Notification not found',
        'marked_read' => 'Notification marked as read',
        'all_marked_read' => 'All notifications marked as read',
        'deleted' => 'Notification deleted successfully',
        'status_updated' => 'Notification settings updated successfully',
    ],

    'fcm' => [
        'not_configured' => 'Push notifications are not configured.',
        'credentials_file_not_found' => 'The FCM credentials file was not found.',
        'invalid_json_credentials' => 'The FCM credentials file is not valid JSON.',
        'missing_required_credentials' => 'The FCM credentials are missing required fields.',
        'oauth_token_request_failed' => 'The FCM access token request failed: :error',
        'no_access_token_in_response' => 'The FCM response did not contain an access token.',
        'failed_to_sign_jwt' => 'Failed to sign the FCM JWT.',
    ],

    'product' => [
        'retrieved' => 'Products retrieved successfully',
    ],

    'order' => [
        'retrieved' => 'Orders retrieved successfully',
        'created' => 'Order created successfully',
        'updated' => 'Order updated successfully',
        'deleted' => 'Order deleted successfully',
        'shown' => 'Order retrieved successfully',
        'not_editable' => 'Order items can only be changed while the order is pending.',
        'has_payments' => 'This order has payments and cannot be deleted.',
        'invalid_transition' => 'The order status cannot be changed from :from to :to.',
        'cannot_cancel' => 'An order that is paid or has a payment in progress cannot be cancelled.',
    ],

    'payment' => [
        'retrieved' => 'Payments retrieved successfully',
        'shown' => 'Payment retrieved successfully',
        'processed' => 'Payment processed successfully',
        'processing' => 'Payment is being processed. Its final status will be confirmed shortly.',
        'failed' => 'Payment failed',
        'order_not_payable' => 'Only pending orders can be paid.',
        'order_already_paid' => 'This order has already been paid.',
        'already_active' => 'This order already has a payment in progress.',
        'gateway_timeout' => 'The payment gateway did not respond in time.',
    ],

    'webhook' => [
        'received' => 'Webhook received',
        'gateway_not_found' => 'Payment gateway not found.',
        'payment_not_found' => 'No payment matches this reference yet. Please retry later.',
        'invalid_status' => 'The webhook status must be successful or failed.',
        'amount_mismatch' => 'The webhook amount or currency does not match the payment.',
    ],

    'errors' => [
        'validation' => 'The given data was invalid.',
        'bad_request' => 'Bad request.',
        'unauthenticated' => 'Unauthenticated. Please log in and try again.',
        'forbidden' => 'You are not allowed to perform this action.',
        'not_found' => 'The requested resource was not found.',
        'method_not_allowed' => 'This HTTP method is not allowed for this endpoint.',
        'too_many_requests' => 'Too many requests. Please slow down and try again later.',
        'server_error' => 'Something went wrong. Please try again later.',
        'http' => 'The request could not be completed.',
    ],

    'enum' => [
        'userstatus' => [
            'active' => 'Active',
            'blocked' => 'Blocked',
            'incomplete' => 'Incomplete',
        ],
        'devicetype' => [
            'android' => 'Android',
            'ios' => 'iOS',
            'web' => 'Web',
        ],
        'otptype' => [
            'registration' => 'Registration',
            'login' => 'Login',
            'password_reset' => 'Password reset',
            'change_phone' => 'Change phone',
            'complete_registration' => 'Complete registration',
        ],
        'smsprovider' => [
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
        ],
        'notificationtype' => [
            'order_placed' => 'Order placed',
            'order_confirmed' => 'Order confirmed',
            'order_preparing' => 'Order preparing',
            'order_ready' => 'Order ready',
            'order_out_for_delivery' => 'Out for delivery',
            'order_delivered' => 'Order delivered',
            'order_cancelled' => 'Order cancelled',
            'order_edited' => 'Order edited',
            'loyalty_points_earned' => 'Loyalty points earned',
            'loyalty_points_expiring' => 'Loyalty points expiring',
            'review_request' => 'Review request',
            'driver_assigned' => 'Driver assigned',
            'address_update_request' => 'Address update request',
            'branch_available' => 'Branch available',
            'general' => 'General',
        ],
        'orderstatus' => [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
        ],
        'paymentstatus' => [
            'pending' => 'Pending',
            'successful' => 'Successful',
            'failed' => 'Failed',
        ],
    ],

];
