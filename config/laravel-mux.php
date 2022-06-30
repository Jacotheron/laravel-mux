<?php

return [
    'authentication' => [
        'mux_token_id' => env('MUX_TOKEN_ID'),
        'mux_token_secret' => env('MUX_TOKEN_SECRET')
    ],
    /*
     * This is what is used by default
     * can be 'public' or 'signed
     */
    'default_playback_policy' => ['public']
];