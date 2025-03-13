<?php

namespace Frakt24\LaravelPHPFirestore\Helpers;

use Frakt24\LaravelPHPFirestore\Attributes\FirestoreDeleteAttribute;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreArray;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreGeoPoint;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreObject;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreReference;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreTimestamp;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreBytes;

class FirestoreHelper
{
    /**
     * Decode payload to object
     *
     * @param  string
     *
     * @return object
     */
    public static function decode($value)
    {
        return json_decode($value, true, JSON_FORCE_OBJECT);
    }

    /**
     * Encode payload to post on firestore.
     *
     * @param  object
     * @return string
     */
    public static function encode($value)
    {
        return json_encode($value);
    }

    /**
     * Encode value to base64
     *
     * @param string $value
     *
     * @return string
     */
    public static function base64encode($value)
    {
        return base64_encode($value);
    }

    /**
     * Decode base64 into plain-text value
     *
     * @param string $value
     *
     * @return string
     */
    public static function base64decode($value)
    {
        return base64_decode($value);
    }

    /**
     * Remove heading slash for collection
     *
     * @param  string
     *
     * @return string
     */
    public static function normalizeCollection($value)
    {
        return ltrim($value, '/');
    }

    /**
     * Filter will filter out those values which is not needed to send to server
     *
     * @param  array $value
     *
     * @return array
     */
    public static function filter($value)
    {
        return array_filter($value, function($v) {
            return in_array(self::getType($v), ['delete']) ? false : true;
        });
    }

    /**
     * Decides which class to call when field matched.
     *
     * @param  string $value
     *
     * @return string
     */
    public static function getType($value)
    {
        $type = gettype($value);

        if ( $type === 'object' ) {
            if ( $value instanceof FirestoreReference ) {
                return 'reference';
            }

            if ( $value instanceof FirestoreTimestamp ) {
                return 'timestamp';
            }

            if ( $value instanceof FirestoreArray ) {
                return 'array';
            }

            if ( $value instanceof FirestoreGeoPoint ) {
                return 'geoPoint';
            }

            if ( $value instanceof FirestoreBytes ) {
                return 'bytes';
            }

            if ( $value instanceof FirestoreDeleteAttribute ) {
                return 'delete';
            }
        }

        return $type;
    }

    /**
     * This will recursively add FirestoreObject to a nested array
     *
     * @param  array $value
     *
     * @return array
     */
    public static function normalizedNestedArray(array $data)
    {
        foreach ($data as $key => $value){

            if (is_array($value)) {
                $data[$key] = new FirestoreObject(self::normalizedNestedArray($value));
            }
        }

        return $data;
    }


}
