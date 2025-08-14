#!/usr/bin/env php
<?php

/**
 * Initialize CURL handler
 */
function curl(string $command, string $request_json) {
    global $key, $secret;
    $ch = curl_init($command);

    //curl_setopt($ch, CURLOPT_VERBOSE, true);
    if (empty($request_json)) {
        curl_setopt_array($ch, [
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "$key:$secret",
            // Verify server certificate. Must be false if you use self-signed certificate
            CURLOPT_SSL_VERIFYPEER => true,
            // Verify that hostname and and certificate name match
            // must be false if you use the IP of OPNsense for example
            CURLOPT_SSL_VERIFYHOST=> true,
            CURLOPT_TIMEOUT => 5]);
    } else {
        curl_setopt_array($ch, [
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "$key:$secret",
            // Verify server certificate. Must be false if you use self-signed certificate
            CURLOPT_SSL_VERIFYPEER => true,
            // Verify that hostname and and certificate name match
            // must be false if you use the IP of OPNsense for example
            CURLOPT_SSL_VERIFYHOST=> true,
            CURLOPT_TIMEOUT => 5,
	    CURLOPT_POSTFIELDS => $request_json,
	]);
    }

    return $ch;
}

/**
 * GET request function
 */
function get(string $command, string $request_json)
{
    $ch = curl($command, $request_json);
    if (! $response = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response);
}

/**
 * POST request function
 */
function post(string $command, string $request_json) {
    echo "POST command is: " . $command . "\n";
    if (empty($request_json)) {
        $ch = curl($command, '');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
        ]);
    } else {
        $ch = curl($command, $request_json);
        $headers = [
            "Content-type: application/json",
            "Content-Length: " . strlen($request_json)
        ];
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request_json,
            CURLOPT_HTTPHEADER => $headers,
        ]);
    }

    if (! $response = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response);
}

/**
 * Process the command line options.
 * All options are mandatory.
 */
$cl_long_options = array("key:", "secret:", "url:", "portal:");
$all_options_provided = true;

$cl_options = getopt("", $cl_long_options);
foreach ($cl_options as $cl_option => $cl_value) {
    $$cl_option = $cl_value;
}
foreach ($cl_long_options as $cl_option) {
    $option_name = rtrim($cl_option, ":");
    if (empty($$option_name)) {
        echo "ERROR - option --" . $option_name . " is required\n";
	$all_options_provided = false;
    }
}
if ($all_options_provided) {
    $api_url = $url . "api/";
} else {
    exit(2);
}

/*
 * Retrieve the certificate refid currently in use by the WebUI.
 */
require_once "config.inc";
$webui_cert_refid = $config['system']['webgui']['ssl-certref'];

/**
 * Retrieve the settings of the Captive Portal zone for Wireguard
 */
$captport_zones = get($api_url . "captiveportal/settings/search_zones?searchPhrase=" . $portal, '');
$captport_zone = json_decode(json_encode($captport_zones), true);  // convert object(stdClass) to array
if ($captport_zone['rowCount'] === 1) {
    echo "SUCCESS - The Captive Portal zone definition for Wireguard has been retrieved.\n";
    $captport_zone_rows = $captport_zone['rows']['0'];    // retrieve the record out of the zone structure
    $captport_zone_uuid = $captport_zone_rows['uuid'];    // save the uuid because we need it later
    $captport_zone_id   = $captport_zone_rows['zoneid'];  // save the zoneid because we need it later
    foreach ($captport_zone_rows as $captport_zone_param => $captport_zone_value) {
        if (str_starts_with($captport_zone_param, '%')) {
            unset($captport_zone_rows[$captport_zone_param]);
        }
    }
    unset($captport_zone_rows['zoneid']);   // The zone will be selected via the URI request
    unset($captport_zone_rows['uuid']);     // The zone will be selected via the URI request
} else {
    echo "ERROR - Captive Portal zone definition for Wireguard not found!\n";
    var_dump($captport_zone);
    exit(2);
}

if ($captport_zone_rows['certificate'] === $webui_cert_refid) {
    echo "The WebUI certificate (" . $webui_cert_refid . ") did not change.  We are done.\n";
    exit(0);
} else {
    // Update the cert refid in the payload record with the one from the WebUI.
    $captport_zone_rows['certificate'] = $webui_cert_refid;
}

/**
 * Send the request updating the certificate of the Captive Zone for Wireguard.
 */
$captport_update_request = "captiveportal/settings/set_zone/" . $captport_zone_uuid ;
$captport_zone_payload_array["zone"] = $captport_zone_rows;
$captport_update_reply = post($api_url . $captport_update_request, json_encode($captport_zone_payload_array));
if ($captport_update_reply->result == "saved") {
    echo "SUCCESS - The configuration of zone " . $captport_zone_id . " has been modified.\n";
} else {
    echo "ERROR - Failed to modify the configuration of zone " . $captport_zone_id . ".\n";
    var_dump($captport_update_reply);
    exit(2);
}

/**
 * Perform the APPLY.
 * This activates the modified configuration.
 */
$captport_apply_request = "captiveportal/service/reconfigure";
$captport_apply_reply = post($api_url . $captport_apply_request, "");
if ($captport_apply_reply->status == "ok") {
    echo "SUCCESS - The configuration of zone " . $captport_zone_id . " has been applied.\n";
} else {
    echo "ERROR - Failed to apply the configuration of zone " . $captport_zone_id . ".\n";
    var_dump($captport_apply_reply);
    exit(2);
}
