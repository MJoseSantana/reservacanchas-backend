<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Contract\Storage;
use Kreait\Firebase\Contract\Auth;

class FirebaseService
{
    protected $firestore;
    protected $storage;
    protected $auth;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');
        
        // Solo inicializar Firebase si el archivo de credenciales existe
        if (file_exists($credentialsPath)) {
            $factory = (new Factory)
                ->withServiceAccount($credentialsPath);

            $this->firestore = $factory->createFirestore()->database();
            $this->storage = $factory->createStorage();
            $this->auth = $factory->createAuth();
        } else {
            // Firebase no configurado - los métodos devolverán null
            $this->firestore = null;
            $this->storage = null;
            $this->auth = null;
        }
    }

    /**
     * Get Firestore instance
     */
    public function firestore()
    {
        return $this->firestore;
    }

    /**
     * Get Storage instance
     */
    public function storage(): Storage
    {
        return $this->storage;
    }

    /**
     * Get Auth instance
     */
    public function auth(): Auth
    {
        return $this->auth;
    }

    /**
     * Get a Firestore collection
     */
    public function collection(string $collectionName)
    {
        return $this->firestore()->collection($collectionName);
    }

    /**
     * Get a document from a collection
     */
    public function getDocument(string $collectionName, string $documentId)
    {
        return $this->collection($collectionName)->document($documentId)->snapshot();
    }

    /**
     * Create or update a document
     */
    public function setDocument(string $collectionName, string $documentId, array $data)
    {
        return $this->collection($collectionName)->document($documentId)->set($data);
    }

    /**
     * Delete a document
     */
    public function deleteDocument(string $collectionName, string $documentId)
    {
        return $this->collection($collectionName)->document($documentId)->delete();
    }

    /**
     * Query a collection
     */
    public function queryCollection(string $collectionName, array $filters = [])
    {
        $query = $this->collection($collectionName);

        foreach ($filters as $filter) {
            $query = $query->where($filter['field'], $filter['operator'], $filter['value']);
        }

        return $query->documents();
    }

    /**
     * Upload file to Firebase Storage
     */
    public function uploadFile(string $path, $file, array $options = [])
    {
        $bucket = $this->storage->getBucket();
        return $bucket->upload($file, [
            'name' => $path,
            ...$options
        ]);
    }

    /**
     * Get file URL from Firebase Storage
     */
    public function getFileUrl(string $path)
    {
        $bucket = $this->storage->getBucket();
        $object = $bucket->object($path);
        
        if ($object->exists()) {
            return $object->signedUrl(new \DateTime('+1 hour'));
        }
        
        return null;
    }

    /**
     * Delete file from Firebase Storage
     */
    public function deleteFile(string $path)
    {
        $bucket = $this->storage->getBucket();
        $object = $bucket->object($path);
        
        if ($object->exists()) {
            return $object->delete();
        }
        
        return false;
    }

    /**
     * Verify Firebase ID Token
     */
    public function verifyIdToken(string $idToken)
    {
        try {
            return $this->auth->verifyIdToken($idToken);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get user by UID
     */
    public function getUserByUid(string $uid)
    {
        try {
            return $this->auth->getUser($uid);
        } catch (\Exception $e) {
            return null;
        }
    }
}
