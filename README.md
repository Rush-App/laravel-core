## RushApp Core package
Extending Laravel Model and Controller to simplify setup CRUD operations.

## Installation guide:
1. Install "Core" package with composer:
   ```composer require rush-app/core```
2. Run installation command (this will publish config and language files):
   ```php artisan core:install```
3. Run migrations:
   ```php artisan migrate```


## Example for creating CRUD operations with translation:
1. Create endpoint for entity (you can use Laravel resource routes):
```php
Route::resource('posts', 'PostController');
```
2. Create migration:

2.1. Create posts table:
```php
Schema::create('posts', function (Blueprint $table) {
   $table->id();
   $table->boolean('published')->default(false);
   $table->timestamp('published_at')->nullable();
   $table->foreignId('user_id');
   $table->timestamps();
});
```
2.2. Create post_translations table (IMPORTANT: name of translation table should be set in format: <entity name in singular form>_translations)
Note that you should use "language_id" key for relation with languages table:
```php
Schema::create('post_translations', function (Blueprint $table) {
   $table->id();
   $table->string('title')->nullable();
   $table->string('description')->nullable();
   $table->foreignId('post_id')->constrained()->onDelete('cascade');
   $table->foreignId('language_id');
});
```
3. Create model (with translation model):
   3.1. Post model:
```php
    class Post extends RushApp\Core\Models\BaseModel
    {
       use HasFactory;
   
       protected $fillable = [
          'published',
          'published_at',
          'user_id',
       ];
   
       protected $dates = [
            'published_at',
       ];
   
       public function user(): BelongsTo
       {
            return $this->belongsTo(User::class);
       }
    }
```
3.2. PostTranslation model (IMPORTANT: use name for translation model in format: model class with suffix "Translation")
Note that you should use "language_id" key for relation with languages table.
```php
    class PostTranslation extends RushApp\Core\Models\BaseModel
    {
       use HasFactory;
   
       protected $fillable = [
          'title',
          'description',
          'post_id',
          'language_id',
       ];
   
       public $timestamps = false;
    }
```
4. Create CRUD controller:
```php
    class PostController extends RushApp\Core\Controllers\BaseController
    {
   
       // The name of the model must be indicated in each controller
       protected string $modelClassController = Post::class;
   
       // FormRequest class for validation store process.
       protected ?string $storeRequestClass = StorePostRequest::class;
   
       // FormRequest class for validation update process.
       protected ?string $updateRequestClass = UpdatePostRequest::class;
   
       // Relations of model that can be attached to response (available for 'index' and 'show' method).
       // NOTE: this names should be the same with method in model (Eloquent relations).
       protected array $withRelationNames = [
            'user',
       ];
    }
```

## Role management system
 IMPORTANT: To use role management system you need to define middleware "check-user-action" for route.
 There are such tables that are responsible for role management system:
 1. roles - Contains role names which available for users. Roles can be attach to user with user_role table.
 2. actions - Contains action_name and entity_name. Action name defined as config rushapp_core.action_names
 and they match for CRUD operation names. Entity name - name of entity tables (example: posts, categories, etc.).
 Actions can be attached to roles with role_action table.
 Example:
 If role "Admin" contains action with action_name "index", "show", "store" and entity_name "posts" this means that user with
 such role can get all posts and create new post. And any other CRUD operations ("update", "destroy") are forbidden.
 3. properties - Contains columns for custom permission logic.
 There is one property predefined and provides by package: "is_owner". This property used to mark that CRUD operation can be performed only by owner
 By default it will check "user_id" as owner identifier.
 Example: If is_owner is true and linked to action this means that CRUD operations can be performed only by owner (except "store" operation),
 but if is_owner is false this means that ownership will be not checked for CRUD operations. This can be used, for "Admin" roles, which can
 perform CRUD operations for any entity.
 NOTE: If is_owner is true for "index" operation this means that user will get only entities where he set as owner.

## Additional abilities for requests
 1. To perform CRUD operations with specified language, you need to set "Language" key in request header.
 2. Filtering abilities for "index" or "show" requests:
 
 - "paginate" Example: http://127.0.0.1:8000/posts?paginate=2&page=1

 - "order_by_field" Example: http://127.0.0.1:8000/posts?order_by_field=year:desc
 
 - "with" Example: http://127.0.0.1:8000/posts?with=user:id,email|categories:id,title . 
     Where "user" and "categories" are model relation names and all parameters after ":" are relation fields.
   NOTE: If you want to get specified fields "id" field is required or you can keep it without any specific 
   fields to get all entity fields

 - "limit" Example: http://127.0.0.1:8000/posts?limit=2

 - "selected_fields" Example: http://127.0.0.1:8000/posts?selected_fields=year,id,name

 - "where_not_null" Example: http://127.0.0.1:8000/posts?where_not_null=year,id,name

 - "where_null" Example: http://127.0.0.1:8000/posts?where_null=year,id,name

 - "where_between" Example: http://127.0.0.1:8000/posts?where_between=year:2018,2020|create_at:2020-01-01,2021-01-01

 - "where_in" Example: http://127.0.0.1:8000/posts?where_in=year:2018,2014,2020|user_id:2,2,5,6

 - "where_not_in" Example: http://127.0.0.1:8000/posts?where_not_in=year:2018,2014,2020|user_id:2,2,5,6

 - "offset" Example: http://127.0.0.1:8000/posts?offset=5

## Registration and authorization
 1. Registration example. Returns JWT token (['token' => 'test-token'])
 // TODO
 2. To perform authorization you can add route with BaseAuthController, example:
 ```php Route::post('login', [\RushApp\Core\Controllers\BaseAuthController:class,'login']);```
 It returns JWT token (['token' => 'test-token']). To perform authorization request you need to set "email" and "password" fields.

## P.S.
Detailed examples can be found here: https://github.com/Rush-App/laravel-core-example
