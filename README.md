# Firestore Client for PHP without gRPC

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bensontrent/firestore-php.svg?style=flat-square)](https://packagist.org/packages/bensontrent/firestore-php)
[![Total Installs](https://img.shields.io/packagist/dt/bensontrent/firestore-php?color=green&label=installs)](https://packagist.org/packages/bensontrent/firestore-php)
[![Total Downloads](https://img.shields.io/github/downloads/bensontrent/firestore-php/total?color=green&label=downloads)](https://github.com/bensontrent/firestore-php)
[![License](https://poser.pugx.org/bensontrent/firestore-php/license?format=flat-square)](https://packagist.org/packages/bensontrent/firestore-php)

Fork from [bensontrent/firestore-php](https://github.com/bensontrent/firestore-php)

Use Google Firebase without the requirement of having the gRPC extension for php installed.  This is ideal for shared hosting environments. This package is totally based on [Firestore REST API](https://firebase.google.com/docs/firestore/use-rest-api)

## Authentication / Generate API Key

1) Visit [Google Cloud Firestore API](https://console.cloud.google.com/projectselector/apis/api/firestore.googleapis.com/overview)  
2) Select your desired project.  
3) Select `Credentials` from left menu and select `API Key` from Server key or `Create your own credentials`  

## Installation

You can install the package via composer:

```bash
composer require bensontrent/laravel-firestore-php
```

or install it by adding it to `composer.json` then run `composer update`

```javascript
"require": {
    "bensontrent/laravel-firestore-php": "^3.0",
}
```

## Dependencies

 - PHP 7.3 and above (PHP 8+ supported)

The bindings require the following extensions in order to work properly:

- [`curl`](https://secure.php.net/manual/en/book.curl.php)
- [`json`](https://secure.php.net/manual/en/book.json.php)
- [`guzzlehttp/guzzle`](https://packagist.org/packages/guzzlehttp/guzzle)

If you use Composer, these dependencies should be handled automatically. If you install manually, you'll want to make sure that these extensions are available.

## Usage

#### Initialization

```php

require 'vendor/autoload.php';

use MrShan0\PHPFirestore\FirestoreClient;

$firestoreClient = new FirestoreClient('my-project-id', 'MY-API-KEY-xxxxxxxxxxxxxxxxxxxxxxx', [
    'database' => '(default)',
]);
```
Note: You likely won't need to change the  `'database' => '(default)'` line.


#### Adding a document
Make sure your Firebase Rules allow you to write to the $collection you wish to modify or you will get an error: `You do not have permission to access the requested resource`

```php

require 'vendor/autoload.php';

use MrShan0\PHPFirestore\FirestoreClient;

// Optional, depending on your usage
use MrShan0\PHPFirestore\Fields\FirestoreTimestamp;
use MrShan0\PHPFirestore\Fields\FirestoreArray;
use MrShan0\PHPFirestore\Fields\FirestoreBytes;
use MrShan0\PHPFirestore\Fields\FirestoreGeoPoint;
use MrShan0\PHPFirestore\Fields\FirestoreObject;
use MrShan0\PHPFirestore\Fields\FirestoreReference;
use MrShan0\PHPFirestore\Attributes\FirestoreDeleteAttribute;

$collection = 'myCollectionName';

$firestoreClient->addDocument($collection, [
    'myBooleanTrue' => true,
    'myBooleanFalse' => false,
    'null' => null,
    'myString' => 'abc123',
    'myInteger' => 123456,
    'arrayRaw' => [
        'string' => 'abc123',
    ],
    'bytes' => new FirestoreBytes('bytesdata'),
    'myArray' => new FirestoreArray([
        'firstName' => 'Jane',
    ]),
    'reference' => new FirestoreReference('/users/23'),
    'myObject' => new FirestoreObject(['nested1' => new FirestoreObject(
        ['nested2' => new FirestoreObject(
            ['nested3' => 'test'])
        ])
     ]),
    'timestamp' => new FirestoreTimestamp,
    'geopoint' => new FirestoreGeoPoint(1,1),
]);
```

**NOTE:** Pass third argument if you want your custom **document id** to set else auto-id will generate it for you. For example:

```php
$firestoreClient->addDocument('customers', [
    'firstName' => 'Jeff',
], 'myOptionalUniqueID0123456789')
```

Or

```php

use MrShan0\PHPFirestore\FirestoreDocument;

$document = new FirestoreDocument;
$document->setObject('myNestedObject', new FirestoreObject(
    ['nested1' => new FirestoreObject(
        ['nested2' => new FirestoreObject(
            ['nested3' => 'test'])
            ])
        ]
    ));
$document->setBoolean('myBooleanTrue', true);
$document->setBoolean('myBooleanFalse', false);
$document->setNull('null', null);
$document->setString('myString', 'abc123');
$document->setInteger('myInteger', 123456);
$document->setArray('myArrayRaw', ['string'=>'abc123']);
$document->setBytes('bytes', new FirestoreBytes('bytesdata'));
$document->setArray('arrayObject', new FirestoreArray(['string' => 'abc123']));
$document->setTimestamp('timestamp', new FirestoreTimestamp);
$document->setGeoPoint('geopoint', new FirestoreGeoPoint(1.11,1.11));

$firestoreClient->addDocument($collection, $document, 'customDocumentId');
```

And..

```php
$document->fillValues([
    'myString' => 'abc123',
    'myBoolean' => true,
    'firstName' => 'Jane',
]);
```

####  Special characters in the field name

If you want to use special characters in the field name, you have to use backticks.

```php
$document->fillValues([
    '`teléfono`' => '1234567890',
    '`contraseña`' => 'secretPassword',
]);
```

You could use `addNestedDocuments` if you have multiple nested objects 


```php

require 'vendor/autoload.php';

use MrShan0\PHPFirestore\FirestoreClient;

$collection = 'myCollectionName';

$originalData = [
    'name' => 'My Application',
    'emails' => [
        'support' => 'support@example.com',
        'sales' => 'sales@example.com',
    ],
    'website' => 'https://app.example.com',
    'myObject' => [
        'nested1' => [
            'name' => 'My Application',
            'emails' => [
                'support' => 'support@example.com',
                'sales' => 'sales@example.com',
            ],
            'nested2' => [
                'name' => 'My Application',
                'emails' => [
                    'support' => 'support@example.com',
                    'sales' => 'sales@example.com',
                ],
                'nested3' => [
                    'name' => 'My Application',
                    'emails' => [
                        'support' => 'support@example.com',
                        'sales' => 'sales@example.com',
                    ]
                ]
            ]
        ]
    ]
];

$firestoreClient->addNestedDocuments($collection, $originalData);
```
This just formats the array before using the `addDocument` function

```php

require 'vendor/autoload.php';

use MrShan0\PHPFirestore\FirestoreClient;
use MrShan0\PHPFirestore\Fields\FirestoreObject;

$collection = 'myCollectionName';

$originalData = [
    'name' => 'My Application',
    'emails' => new FirestoreObject( [
        'support' => 'support@example.com',
        'sales' => 'sales@example.com',
    ]),
    'website' => 'https://app.example.com',
    'myObject' => new FirestoreObject([
        'nested1' => new FirestoreObject([
            'name' => 'My Application',
            'emails' => new FirestoreObject([
                'support' => 'support@example.com',
                'sales' => 'sales@example.com',
            ]),
            'nested2' => new FirestoreObject([
                'name' => 'My Application',
                'emails' => new FirestoreObject([
                    'support' => 'support@example.com',
                    'sales' => 'sales@example.com',
                ]),
                'nested3' => new FirestoreObject([
                    'name' => 'My Application',
                    'emails' => new FirestoreObject( [
                        'support' => 'support@example.com',
                        'sales' => 'sales@example.com',
                    ])
                ])
            ])
        ])
    ])
];
```

#### Inserting/Updating a document

- Update (Merge) or Insert document

Following will merge document (if exist) else insert the data.

```php
use MrShan0\PHPFirestore\Attributes\FirestoreDeleteAttribute;

$firestoreClient->updateDocument($documentRoot, [
    'newFieldToAdd' => 'Jane Doe',
    'existingFieldToRemove' => new FirestoreDeleteAttribute
]);
```

**NOTE:** Passing 3rd argument as a boolean _true_ will force check that document must exist and vice-versa in order to perform update operation.

For example: If you want to update document only if exist else `MrShan0\PHPFirestore\Exceptions\Client\NotFound` (Exception) will be thrown.

```php
use MrShan0\PHPFirestore\Attributes\FirestoreDeleteAttribute;

$firestoreClient->updateDocument($documentPath, [
    'newFieldToAdd' => 'Jane Doe',
    'existingFieldToRemove' => new FirestoreDeleteAttribute
], true);
```

format for documentPath:

```
<collectionName>/<documentName>
```


- Overwirte or Insert document

```php
use MrShan0\PHPFirestore\Attributes\FirestoreDeleteAttribute;

$firestoreClient->setDocument($collection, $documentId, [
    'newFieldToAdd' => 'Jane Doe',
    'existingFieldToRemove' => new FirestoreDeleteAttribute
], [
    'exists' => true, // Indicate document must exist
]);
```

#### Deleting a document

```php
$collection = 'collection/document/innerCollection';
$firestoreClient->deleteDocument($collection, $documentId);
```

#### List documents with pagination (or custom parameters)

```php
$collections = $firestoreClient->listDocuments('users', [
    'pageSize' => 1,
    'pageToken' => 'nextpagetoken'
]);
```

**Note:** You can pass custom parameters as supported by [firestore list document](https://firebase.google.com/docs/firestore/reference/rest/v1/projects.databases.documents/list#query-parameters)

#### Get field from document

```php
$document->get('bytes')->parseValue(); // will return bytes decoded value.

// Catch field that doesn't exist in document
try {
    $document->get('allowed_notification');
} catch (\Frakt24\LaravelPHPFirestore\Exceptions\Client\FieldNotFound $e) {
    // Set default value
}
```

### Firebase Authentication

#### Sign in with Email and Password.

```php
$firestoreClient
    ->authenticator()
    ->signInEmailPassword('testuser@example.com', 'abc123');
```

#### Sign in Anonymously.

```php
$firestoreClient
    ->authenticator()
    ->signInAnonymously();
```

### Retrieve Auth Token

```php
$authToken = $firestoreClient->authenticator()->getAuthToken();
```

### TODO
- [x] Added delete attribute support.
- [x] Add Support for Object, Boolean, Null, String, Integer, Array, Timestamp, GeoPoint, Bytes
- [x] Add Exception Handling.
- [x] List all documents.
- [ ] List all collections.
- [x] Filters and pagination support.
- [ ] Structured Query support.
- [ ] Transaction support.
- [ ] Indexes support.
- [ ] Entire collection delete support.

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please use the issue tracker.

## Credits
- [Benson Trent](https://github.com/bensontrent)
- [Ahsaan Muhammad Yousuf](https://ahsaan.me)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
