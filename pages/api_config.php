<?php
// api_config.php
define('DAILY_API_KEY', 'bb1097f9148c394111f81c798b8681fd2def6d15d60b915beaf2f392bbf4a30e'); // Using Daily.co as free video meeting API

class MeetingAPI {
    private $api_key;
    
    public function __construct() {
        $this->api_key = DAILY_API_KEY;
    }
    
    public function createMeeting() {
        $ch = curl_init('https://api.daily.co/v1/rooms');
        
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ];
        
        $data = [
            'properties' => [
                'enable_chat' => true,
                'enable_screenshare' => true,
                'start_audio_off' => true,
                'start_video_off' => true
            ]
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        return isset($result['url']) ? 'https://your-domain.daily.co/' . $result['name'] : false;
    }
}