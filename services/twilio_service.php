<?php
require_once __DIR__ . '/../config/config.php';

function twilio_verify_request($phone, $code = null) {
    $accountSid = TWILIO_ACCOUNT_SID;
    $apiKeySid = TWILIO_API_KEY_SID;
    $apiKeySecret = TWILIO_API_KEY_SECRET;
    $serviceSid = TWILIO_VERIFY_SERVICE_SID;

    if (!$accountSid || !$apiKeySid || !$apiKeySecret || !$serviceSid) {
        return ['success' => false, 'message' => 'Twilio Verify is not configured on the server'];
    }

    $endpoint = $code === null
        ? '/Verifications.json'
        : '/VerificationCheck.json';
    $payload = $code === null
        ? ['To' => $phone, 'Channel' => 'sms']
        : ['To' => $phone, 'Code' => $code];
    $url = 'https://verify.twilio.com/v2/Services/' . rawurlencode($serviceSid) . $endpoint;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $apiKeySid . ':' . $apiKeySecret,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || $status < 200 || $status >= 300) {
        $data = json_decode($response ?: '', true);
        return ['success' => false, 'message' => $data['message'] ?? 'Twilio could not process the verification request'];
    }

    $data = json_decode($response, true) ?: [];
    return ['success' => $code === null || ($data['status'] ?? '') === 'approved', 'status' => $data['status'] ?? ''];
}

function twilio_send_sms($phone, $message) {
    if (!TWILIO_ACCOUNT_SID || !TWILIO_AUTH_TOKEN || !TWILIO_FROM_NUMBER) {
        return ['success' => false, 'message' => 'Twilio SMS is not configured on the server'];
    }
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode(TWILIO_ACCOUNT_SID) . '/Messages.json';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['To' => $phone, 'From' => TWILIO_FROM_NUMBER, 'Body' => $message]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($response ?: '', true) ?: [];
    if ($error || $status < 200 || $status >= 300) {
        return ['success' => false, 'message' => $data['message'] ?? 'Twilio could not send the SMS'];
    }
    return ['success' => true, 'sid' => $data['sid'] ?? null, 'status' => $data['status'] ?? null];
}
