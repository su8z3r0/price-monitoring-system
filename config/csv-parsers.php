<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CSV Parser Types
    |--------------------------------------------------------------------------
    |
    | This configuration defines the available CSV parser implementations.
    | Each type maps to a concrete parser class.
    |
    | To add a new parser (e.g., SFTP):
    | 1. Create the parser class: App\Services\Csv\SftpCsvParser
    | 2. Add mapping here: 'sftp' => App\Services\Csv\SftpCsvParser::class
    | 3. No changes needed in CsvParserFactory!
    |
    */

    'types' => [
        'local' => App\Services\Csv\LocalCsvParser::class,
        'ftp' => App\Services\Csv\FtpCsvParser::class,
        'http' => App\Services\Csv\HttpCsvParser::class,

        // Future parsers can be added here:
        // 'sftp' => App\Services\Csv\SftpCsvParser::class,
        // 's3' => App\Services\Csv\S3CsvParser::class,
        // 'google_drive' => App\Services\Csv\GoogleDriveCsvParser::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Parser
    |--------------------------------------------------------------------------
    |
    | The default parser type to use when none is specified.
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Parser Options
    |--------------------------------------------------------------------------
    |
    | Global options for all CSV parsers.
    |
    */

    'options' => [
        'encoding' => 'UTF-8',
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
    ],
];
