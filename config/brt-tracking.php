<?php

// config for SmartDato/BrtTracking
return [
    /*
    |--------------------------------------------------------------------------
    | Client ID
    |--------------------------------------------------------------------------
    |
    | Some WSDL operations require your BRT client ID.  Provide your unique
    | identifier here or via the BRT_CLIENT_ID environment variable.
    |
    */
    'client_id' => env('BRT_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    |
    | Default language to use when fetching legends and tracking information.
    | Use an ISO‑639‑1 code (such as 'EN') or leave blank for Italian.
    |
    */
    'language' => env('BRT_LANG', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Timeout & Retry
    |--------------------------------------------------------------------------
    |
    | Define how long to wait for a response and how many times to retry
    | transient errors.  The retry mechanism is left to the consumer of this
    | package (jobs, queues etc.), but these values provide sensible defaults.
    |
    */
    'timeout' => 10,

    'retry' => [
        'times' => 3,
        'delay_ms' => 400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | The BRT webservice allows 18,000 requests per hour.  Set the
    | maximum number of calls per minute below to ensure you remain well
    | under that threshold.  Calls beyond this limit will throw a
    | BrtException with a 429 status code.
    |
    */
    'throttle_per_minute' => env('BRT_THROTTLE_PER_MINUTE', 250),

    /*
    |--------------------------------------------------------------------------
    | WSDL Endpoints
    |--------------------------------------------------------------------------
    |
    | Define the endpoint URLs for each service.  These defaults point to the
    | production environment.  WSDLs are served via HTTPS but reference HTTP
    | endpoints internally; enabling 'cache_wsdl_locally' will patch those
    | addresses automatically.
    |
    */
    'wsdl' => [
        'tracking_by_id' => 'https://wsr.brt.it:10052/web/BRT_TrackingByBRTshipmentIDService/BRT_TrackingByBRTshipmentID?wsdl',
        'id_by_rmn' => 'https://wsr.brt.it:10052/web/GetIdSpedizioneByRMNService/GetIdSpedizioneByRMN?wsdl',
        'id_by_rma' => 'https://wsr.brt.it:10052/web/GetIdSpedizioneByRMAService/GetIdSpedizioneByRMA?wsdl',
        'id_by_collo' => 'https://wsr.brt.it:10052/web/GetIdSpedizioneByIdColloService/GetIdSpedizioneByIdCollo?wsdl',

        'legenda_esiti' => 'https://wsr.brt.it:10052/web/GetLegendaEsitiService/GetLegendaEsiti?wsdl',
        'legenda_eventi' => 'https://wsr.brt.it:10052/web/GetLegendaEventiService/GetLegendaEventi?wsdl',
    ],

    /*
     * --------------------------------------------------------------------------
     * Locations
     * --------------------------------------------------------------------------
     * |
     * | Some WSDL operations require a specific endpoint location.  You can
     * | define these locations here, mapping the WSDL key to the concrete
     * | endpoint URL.
     * |
     */
    'locations' => [
        'tracking_by_id' => 'https://wsr.brt.it:10052/web/BRT_TrackingByBRTshipmentIDService/BRT_TrackingByBRTshipmentID',
        'id_by_rmn' => 'https://wsr.brt.it:10052/web/GetIdSpedizioneByRMNService/GetIdSpedizioneByRMN',
        'id_by_rma' => 'https://wsr.brt.it:10052/web/GetIdSpedizioneByRMAService/GetIdSpedizioneByRMA',
        'id_by_collo' => 'https://wsr.brt.it:10052/web/GetIdSpedizioneByIdColloService/GetIdSpedizioneByIdCollo',

        'legenda_esiti' => 'https://wsr.brt.it:10052/web/GetLegendaEsitiService/GetLegendaEsiti',
        'legenda_eventi' => 'https://wsr.brt.it:10052/web/GetLegendaEventiService/GetLegendaEventi',
    ],

    /*
    |--------------------------------------------------------------------------
    | WSDL caching
    |--------------------------------------------------------------------------
    |
    | When enabled, WSDL files will be downloaded, patched from HTTP to HTTPS,
    | and stored locally.  You can change the location used to store WSDLs by
    | adjusting the 'wsdl_cache_path' below.
    |
    */
    'cache_wsdl_locally' => true,

    'wsdl_cache_path' => storage_path('app/brt/wsdl'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Choose the logging channel that the package should use.  Leave empty
    | to use the default stack.
    |
    */
    'log_channel' => env('BRT_LOG_CHANNEL'),
];
