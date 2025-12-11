<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | Download from: Firebase Console > Project Settings > Service Accounts
    | Generate New Private Key
    |
    */
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | Your Firebase Realtime Database URL (if using Realtime Database)
    |
    */
    'database_url' => env('FIREBASE_DATABASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID', 'reservacanchas-f288c'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Bucket
    |--------------------------------------------------------------------------
    |
    | Your Firebase Storage bucket
    |
    */
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'reservacanchas-f288c.firebasestorage.app'),

    /*
    |--------------------------------------------------------------------------
    | Firebase API Key
    |--------------------------------------------------------------------------
    |
    | Your Firebase Web API Key
    |
    */
    'api_key' => env('FIREBASE_API_KEY', 'AIzaSyAs_PFiwd8ZBWiSxyq2F-_8INoaMT4umL8'),
];
