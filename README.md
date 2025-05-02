

# Laravel Firestore Wrapper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bensontrent/firestore-php.svg?style=flat-square)](https://packagist.org/packages/bensontrent/firestore-php)
[![Total Installs](https://img.shields.io/packagist/dt/bensontrent/firestore-php?color=green&label=installs)](https://packagist.org/packages/bensontrent/firestore-php)
[![Total Downloads](https://img.shields.io/github/downloads/bensontrent/firestore-php/total?color=green&label=downloads)](https://github.com/bensontrent/firestore-php)
[![License](https://poser.pugx.org/bensontrent/firestore-php/license?format=flat-square)](https://packagist.org/packages/bensontrent/firestore-php)

A Laravel-friendly wrapper for Google Firestore, providing an Eloquent-like experience without requiring gRPC. Perfect for shared hosting environments.

## Installation

You can install the package via composer:

```bash
composer require frakt24/laravel-firestore
```

## Configuration

1. First, publish the configuration file:

```bash
php artisan vendor:publish --provider="Frakt24\LaravelPHPFirestore\Support\ServiceProvider" --tag="firestore-config"
```

2. Add your Firestore credentials to your `.env` file:

```env
FIRESTORE_PROJECT_ID=your-project-id
FIRESTORE_PRIVATE_KEY_ID=your-private-key-id
FIRESTORE_PRIVATE_KEY="your-private-key"
FIRESTORE_CLIENT_EMAIL=your-client-email
FIRESTORE_CLIENT_ID=your-client-id
FIRESTORE_CLIENT_CERT_URL=your-client-cert-url
```

You can get these credentials from your [Google Cloud Console](https://console.cloud.google.com/apis/credentials) by creating a service account key.

## Usage

### Creating a Model

```php
use Frakt24\LaravelPHPFirestore\Models\FirestoreModel;

class Post extends FirestoreModel
{
    protected string $collection = 'posts';
    
    protected array $fillable = [
        'title',
        'content',
        'author_id'
    ];
    
    protected array $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean'
    ];
}
```

### Basic Operations

```php
// Create
$post = Post::create([
    'title' => 'My First Post',
    'content' => 'Hello World!',
    'author_id' => 1
]);

// Find
$post = Post::find('document-id');

// Update
$post->update([
    'title' => 'Updated Title'
]);

// Delete
$post->delete();

// Soft Delete (if enabled)
$post->delete(); // Will set deletedAt timestamp

// Query
$posts = Post::where('author_id', '==', 1)
    ->where('is_published', '==', true)
    ->orderBy('published_at', 'desc')
    ->limit(10)
    ->get();

// First or Create
$post = Post::firstOrCreate(
    ['title' => 'My Post'], // search attributes
    ['content' => 'Default content'] // attributes to set if not found
);

// Update or Create
$post = Post::updateOrCreate(
    ['title' => 'My Post'], // search attributes
    ['content' => 'Updated content'] // attributes to update or create with
);
```

### Collections

```php
// Convert collection to Firestore format
$collection = collect($posts)->toFirestore();
```

### Timestamps

By default, the package will automatically manage `createdAt` and `updatedAt` timestamps. You can customize this in the config:

```php
// config/firestore.php
'models' => [
    'timestamps' => true,
    'created_at' => 'createdAt',
    'updated_at' => 'updatedAt',
    'deleted_at' => 'deletedAt',
]
```

Or per model:

```php
class Post extends FirestoreModel
{
    protected bool $timestamps = false;
}
```

### Soft Deletes

To enable soft deletes for a model:

```php
use Frakt24\LaravelPHPFirestore\Models\Concerns\SoftDeletes;

class Post extends FirestoreModel
{
    use SoftDeletes;
}
```

Then you can use:

```php
// Soft delete
$post->delete();

// Force delete
$post->forceDelete();

// Include soft deleted models
Post::withTrashed()->get();

// Get only soft deleted models
Post::onlyTrashed()->get();

// Restore soft deleted model
$post->restore();
```

### Working with Nested Collections

The package provides two approaches for working with nested collections: User-scoped models and general nested collections.

#### User-Scoped Models

If you have collections that always live under a user document (e.g., `users/{userId}/documents`), you can use the user-scoped pattern:

```php
abstract class UserScopedModel extends FirestoreModel
{
    protected ?string $userId = null;

    public function forUser(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    protected function getCollectionPath(): string
    {
        if (!$this->userId) {
            throw new RuntimeException('User ID not set. Call forUser() first.');
        }
        return "users/{$this->userId}/{$this->collection}";
    }

    // Helper for current user
    public function forCurrentUser(): self
    {
        return $this->forUser(Auth::id());
    }
}

// Example model
class UserDocument extends UserScopedModel
{
    protected string $collection = 'documents';
    
    protected array $fillable = ['title', 'content'];
}

// Usage
$docs = UserDocument::forUser('user-123')->get();
$myDocs = UserDocument::forCurrentUser()->get();
```

#### Nested Collections

For more flexible nested collections (e.g., `organizations/{orgId}/teams/{teamId}/members`), use the `HasNestedCollections` trait:

```php
use Frakt24\LaravelPHPFirestore\Models\Concerns\HasNestedCollections;

class Member extends FirestoreModel
{
    use HasNestedCollections;

    protected string $collection = 'members';
}

// Usage with path segments
$members = Member::in(['organizations', 'org-123', 'teams', 'team-456'])->get();

// Or with path string
$members = Member::in('organizations/org-123/teams/team-456')->get();

// Create in nested path
Member::in('organizations/org-123/teams/team-456')->create([
    'name' => 'John Doe',
    'role' => 'developer'
]);

// Get parent document
$member = Member::in('organizations/org-123/teams/team-456')->first();
$team = $member->parent(); // Returns Team model instance if findModelForCollection is implemented
```

For better organization, you can create base models for each level:

```php
abstract class OrganizationResource extends FirestoreModel
{
    use HasNestedCollections;

    protected function getBasePath(): string
    {
        return "organizations/{$this->organizationId}";
    }

    protected static function findModelForCollection(string $collection): ?string
    {
        return match($collection) {
            'organizations' => Organization::class,
            'teams' => Team::class,
            'members' => Member::class,
            default => null
        };
    }
}

class Team extends OrganizationResource
{
    protected string $collection = 'teams';
}

class Member extends OrganizationResource
{
    protected string $collection = 'members';
}

// Usage
$team = Team::in("organizations/org-123")->find('team-456');
$members = Member::in("organizations/org-123/teams/team-456")->get();
```

This provides a clean, type-safe way to work with nested collections while maintaining the Eloquent-like API.

### Authentication

The package supports multiple authentication methods:

```php
// Using Facade
use Firestore;

// Email/Password Authentication
Firestore::authenticator()->signInEmailPassword('user@example.com', 'password');

// Anonymous Authentication
Firestore::authenticator()->signInAnonymously();

// Service Account Authentication (recommended for backend)
// This is automatic when you set up your .env credentials
```

You can also get the auth token manually:

```php
$token = Firestore::authenticator()->getAuthToken();
```

For backend applications, we recommend using Service Account authentication by setting up your credentials in the `.env` file as shown in the Configuration section above.

### Advanced Usage

#### Custom Field Types

```php
use Frakt24\LaravelPHPFirestore\Fields\FirestoreTimestamp;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreGeoPoint;
use Frakt24\LaravelPHPFirestore\Fields\FirestoreReference;

class Location extends FirestoreModel
{
    protected array $casts = [
        'opened_at' => FirestoreTimestamp::class,
        'coordinates' => FirestoreGeoPoint::class,
        'parent_location' => FirestoreReference::class,
    ];
}
```

#### Direct Firestore Access

While the Eloquent-like model API covers most use cases, you can also access the Firestore client directly:

```php
// Using Facade
$document = Firestore::collection('users')
    ->document('user-id')
    ->get();

// Add document with custom ID
Firestore::collection('users')
    ->document('custom-id')
    ->set([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);

// Add document with auto-generated ID
$ref = Firestore::collection('users')->add([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);
```

## Todo List

Here are some features we're planning to add:

- [ ] Relationships between models
  - [ ] hasMany
  - [ ] belongsTo
  - [ ] hasOne
  - [ ] belongsToMany
- [ ] Real-time listeners for Firestore updates
- [ ] Better caching integration with Laravel's cache system
- [ ] Batch operations support
- [ ] Transaction support
- [ ] Array contains and array contains any queries
- [ ] Composite indexes support
- [ ] Automatic index management
- [ ] Command to generate models from existing Firestore collections
- [ ] Support for Laravel's notification system
- [ ] Queue integration for background operations
- [ ] Better handling of nested objects and arrays
- [ ] Support for Firestore Security Rules generation
- [ ] Integration with Laravel's authorization system
- [ ] Support for computed properties
- [ ] Support for model observers
- [ ] Support for model events broadcasting
- [ ] Improved testing utilities

### Testing

``` bash
composer test
```

## Credits

This package is based on [bensontrent/firestore-php](https://github.com/bensontrent/firestore-php), which provides a PHP client for Firestore without gRPC requirements.

Fork from [bensontrent/firestore-php](https://github.com/bensontrent/firestore-php)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
