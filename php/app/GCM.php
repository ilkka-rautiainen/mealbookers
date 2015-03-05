<?php

// //------------------------------
// // Payload data you want to send 
// // to Android device (will be
// // accessible via intent extras)
// //------------------------------

// $data = array( 'message' => 'Hello World!' );

// //------------------------------
// // The recipient registration IDs
// // that will receive the push
// // (Should be stored in your DB)
// // 
// // Read about it here:
// // http://developer.android.com/google/gcm/
// //------------------------------

// $ids = array( 'abc', 'def' );

// //------------------------------
// // Call our custom GCM function
// //------------------------------

// sendGoogleCloudMessage(  $data, $ids );

class GCM
{
    private static $instance = null;

    /**
     * Singleton pattern: private constructor
     */
    private function __construct()
    {

    }
    
    /**
     * Singleton pattern: instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new GCM();
        
        return self::$instance;
    }

    public function send(User $user, $data)
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                return $this->_send($user, $data);
            }
            catch (GCMRetryableException $e) {
                usleep(100);
            }
        }
    }

    private function _send(User $user, $data)
    {
        Logger::info(__METHOD__ . " sending GCM message to user {$user->id}");
        //------------------------------
        // Replace with real GCM API 
        // key from Google APIs Console
        // 
        // https://code.google.com/apis/console/
        //------------------------------

        $apiKey = Conf::inst()->get('gcm.api_key');

        //------------------------------
        // Define URL to GCM endpoint
        //------------------------------

        $url = 'https://android.googleapis.com/gcm/send';

        //------------------------------
        // Set GCM post variables
        // (Device IDs and push payload)
        //------------------------------

        $post = array(
                        'registration_ids'  => array(DB::inst()->getOne("SELECT android_gcm_regid FROM users WHERE id = {$user->id}")),
                        'data'              => $data,
                        );

        //------------------------------
        // Set CURL request headers
        // (Authentication and type)
        //------------------------------

        $headers = array( 
                            'Content-Type:application/json',
                            'Authorization:key=' . $apiKey,
                        );
        // Logger::info(__METHOD__ . " requst data " . print_r($post, true) . " headers: " . print_r($headers, true));

        //------------------------------
        // Initialize curl handle
        //------------------------------

        $ch = curl_init();

        //------------------------------
        // Set URL to GCM endpoint
        //------------------------------

        curl_setopt( $ch, CURLOPT_URL, $url );

        //------------------------------
        // Set request method to POST
        //------------------------------

        curl_setopt( $ch, CURLOPT_POST, true );

        //------------------------------
        // Set our custom headers
        //------------------------------

        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        //------------------------------
        // Get the response back as 
        // string instead of printing it
        //------------------------------

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        //------------------------------
        // Set post data as JSON
        //------------------------------

        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );

        //------------------------------
        // Actually send the push!
        //------------------------------

        $result = curl_exec( $ch );

        // Check status code
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (mb_substr($http_status, 0, 1) != '2') {
            Logger::error(__METHOD__ . " GCM error. Status code was $http_status, result was " . print_r($result, true));
            throw new GCMRetryableException("Status code was $http_status");
        }
        else {
            Logger::info(__METHOD__ . " GCM request status code: $http_status");
        }
        
        $result_object = json_decode($result, true);
        // Logger::info(__METHOD__ . " GCM result: " . print_r($result, true) . " , result object: " . print_r($result_object, true));
        if ($result_object) {
            // {"multicast_id":9212869635928457591,"success":0,"failure":1,"canonical_ids":0,"results":[{"error":"NotRegistered"}]}
            
            if ($result_object['results'][0]['error']) {
                Logger::warn(__METHOD__ . " GCM: user {$user->id} gcm failed with error " . $result_object['results'][0]['error']);
                if ($result_object['results'][0]['error'] == 'Unavailable') {
                    throw new GCMRetryableException("Error was set: " . $result_object['results'][0]['error']);
                }
                else {
                    Logger::info(__METHOD__ . " Setting user {$user->id} android_gcm_regid to null");
                    DB::inst()->query("UPDATE users SET android_gcm_regid = NULL, suggestion_method = 'email' WHERE id = {$user->id}");
                }
            }
        }

        //------------------------------
        // Error? Display it!
        //------------------------------

        if ( curl_errno( $ch ) )
        {
            Logger::error(__METHOD__ . "Curl error: " . curl_error( $ch ));
            throw new GCMRetryableException("Curl error: " . curl_error( $ch ));
            
        }

        //------------------------------
        // Close curl handle
        //------------------------------

        curl_close( $ch );

        //------------------------------
        // Debug GCM response
        //------------------------------

        return $result;
    }
}