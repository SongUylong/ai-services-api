# Conversation & Message API Flow Documentation

This document traces the complete request lifecycle for each conversation and message endpoint, showing the "domino effect" from route through all layers back to the response.

---

## 1. GET /api/v1/conversations - List All Conversations

### 🎯 Purpose
Retrieve a paginated list of all conversations belonging to the authenticated user. This endpoint supports filtering by title, sorting, and pagination.

### 📊 Flow Diagram
```
HTTP Request
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. ROUTE MATCHING (routes/api/conversation.php:16)                     │
│    - Pattern: GET /api/v1/conversations                                │
│    - Named: conversations.index                                        │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 2. MIDDLEWARE STACK                                                     │
│    ┌──────────────────────────────────────────────────────────────┐   │
│    │ a) auth.api (AuthenticateApi.php:13)                         │   │
│    │    - Forces 'api' guard (Laravel Passport OAuth2)            │   │
│    │    - Validates Bearer token from Authorization header        │   │
│    │    - Sets authenticated user in Auth facade                  │   │
│    │    - Returns 401 if token invalid/missing                    │   │
│    └──────────────────────────────────────────────────────────────┘   │
│    ┌──────────────────────────────────────────────────────────────┐   │
│    │ b) throttle:conversation                                      │   │
│    │    - Rate limits requests per user/IP                         │   │
│    │    - Returns 429 Too Many Requests if exceeded               │   │
│    └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 3. FORM REQUEST VALIDATION (GetConversationsRequest.php)               │
│    - Laravel automatically instantiates this before controller          │
│    - Extends BaseFormRequest (custom error formatting)                 │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 4. CONTROLLER (ConversationController.php:22)                          │
│    - Method: index(GetConversationsRequest $request)                   │
│    - Orchestrates the request flow                                     │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 5. AUTHORIZATION POLICY (ConversationPolicy.php:13)                    │
│    - Method: viewAny(User $user)                                       │
│    - Permission-based authorization via Spatie Laravel-Permission      │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 6. SERVICE LAYER (ConversationService.php:16)                          │
│    - Business logic layer (thin in this case)                          │
│    - Could add: caching, event firing, logging, analytics              │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 7. REPOSITORY (ConversationRepository.php:13)                          │
│    - Data access layer                                                 │
│    - Uses Spatie Query Builder for advanced filtering                  │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 8. MODEL & DATABASE (Conversation.php)                                 │
│    - Eloquent ORM model                                                │
│    - Executes SQL query against conversations table                    │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 9. RESPONSE TRANSFORMATION (ConversationResource.php)                  │
│    - Converts Eloquent models to JSON structure                        │
│    - Formats timestamps to ISO 8601                                    │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
HTTP Response (JSON)
```

---

### 🔍 DEEP DIVE: Step-by-Step Breakdown

---

#### **STEP 1: Route Registration & Matching**

**File**: `routes/api/conversation.php:16`

```php
Route::get('conversations', [ConversationController::class, 'index'])
    ->name('conversations.index');
```

**What Happens Here**:
1. **Laravel Boot Process**: When Laravel starts, it loads all route files defined in `routes/api.php:18`
2. **Route Registration**: This route is registered in Laravel's route collection with:
   - HTTP Method: `GET`
   - URI: `/api/v1/conversations` (prefixed by middleware group)
   - Controller Action: `ConversationController@index`
   - Route Name: `conversations.index` (used for URL generation)
3. **Middleware Assignment**: Applied from the parent group on line 10:
   - `auth.api`: Custom authentication middleware
   - `throttle:conversation`: Rate limiting
4. **Prefix**: The `v1` prefix comes from `->prefix('v1')` on line 10
5. **Request Matching**: When a request comes in with `GET /api/v1/conversations`, Laravel's router matches it to this route

**Why This Matters**:
- Route names allow you to generate URLs: `route('conversations.index')` → `/api/v1/conversations`
- Middleware order matters: auth runs before throttle (authenticated users get better rate limits)
- Versioning (`v1`) allows multiple API versions to coexist

---

#### **STEP 2a: Authentication Middleware**

**File**: `app/Http/Middleware/AuthenticateApi.php:13`

```php
public function handle(Request $request, Closure $next): Response
{
    // Force the 'api' guard for the current request
    Auth::shouldUse('api');

    if (!auth('api')->check()) {
        return response()->json([
            'message' => 'Unauthenticated',
        ], 401);
    }

    return $next($request);
}
```

**What Happens Here - Line by Line**:

1. **`Auth::shouldUse('api')`** (Line 16):
   - Forces Laravel to use the 'api' guard for this request
   - The 'api' guard is configured in `config/auth.php:43-46`:
     ```php
     'api' => [
         'driver' => 'passport',
         'provider' => 'users',
     ],
     ```
   - This means authentication is handled by Laravel Passport (OAuth2)
   - The 'users' provider tells it to use the `App\Models\Users\User` model

2. **`auth('api')->check()`** (Line 18):
   - Checks if a valid authentication token exists
   - **How Passport Validates**:
     a. Extracts the Bearer token from the `Authorization` header
        - Example: `Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...`
     b. Queries the `oauth_access_tokens` table to find the token
     c. Checks if token is not expired
     d. Checks if token is not revoked
     e. Loads the associated User model
     f. Returns `true` if valid, `false` otherwise

3. **If Not Authenticated** (Lines 19-21):
   - Returns a 401 Unauthorized JSON response
   - Request is terminated here, never reaches the controller
   - Response format:
     ```json
     {
       "message": "Unauthenticated"
     }
     ```

4. **If Authenticated** (Line 23):
   - `$next($request)` passes the request to the next middleware/controller
   - The authenticated User model is now available via:
     - `Auth::user()`
     - `Auth::id()`
     - `auth()->user()`
     - `$request->user()`

**Why This Matters**:
- Separates authentication from controller logic
- Passport uses industry-standard OAuth2 protocol
- Tokens are stateless (no session storage needed)
- Failed auth stops request immediately (performance optimization)

---

#### **STEP 2b: Throttle Middleware**

**Middleware**: `throttle:conversation`

**What Happens Here**:
1. **Rate Limit Configuration**: The `conversation` limiter is likely defined in `RouteServiceProvider` or config
2. **Tracking**: Uses cache (Redis/Memcached) to track requests per user/IP
3. **Headers Added**: Response includes rate limit headers:
   ```
   X-RateLimit-Limit: 60
   X-RateLimit-Remaining: 59
   X-RateLimit-Reset: 1702741200
   ```
4. **If Exceeded**: Returns 429 Too Many Requests with retry-after header

**Why This Matters**:
- Prevents API abuse (DoS attacks, spam)
- Different limits for different endpoint groups
- Authenticated users can get higher limits than anonymous
- Protects database from being overwhelmed

---

#### **STEP 3: Form Request Validation**

**File**: `app/Http/Requests/Conversation/GetConversationsRequest.php`

Laravel's **dependency injection** automatically instantiates this class before the controller method runs.

**3.1 Authorization Check** (Lines 11-14):
```php
public function authorize(): bool
{
    return true;
}
```
- Returns `true`: Anyone who passed middleware can make this request
- Could return policy checks here for additional authorization
- If returns `false`, throws 403 Forbidden

**3.2 Validation Rules** (Lines 15-23):
```php
public function rules(): array
{
    return [
        'filter' => ['nullable', 'array'],
        'filter.title' => ['nullable', 'string', 'max:255'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'sort' => ['nullable', 'string'],
    ];
}
```

**Breaking Down Each Rule**:

| Field | Rules | Meaning | Example Valid | Example Invalid |
|-------|-------|---------|---------------|-----------------|
| `filter` | `nullable`, `array` | Optional; if provided must be array | `filter[title]=Chat` | `filter=string` |
| `filter.title` | `nullable`, `string`, `max:255` | Optional; if provided must be string ≤255 chars | `filter[title]=My%20Chat` | `filter[title]={"nested":"obj"}` |
| `per_page` | `nullable`, `integer`, `min:1`, `max:100` | Optional; if provided must be 1-100 | `per_page=25` | `per_page=0`, `per_page=500` |
| `sort` | `nullable`, `string` | Optional; if provided must be string | `sort=-updated_at` | `sort[]=array` |

**3.3 Custom Validation Logic** (Lines 25-32):
```php
public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $validator) {
        // Validate allowed fields
        $allowedFields = ['per_page', 'sort', 'filter'];
        AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
    });
}
```

**What This Does**:
- **`$validator->after()`**: Runs custom validation AFTER standard rules
- **Calls**: `AllowedFieldsValidator::validate()` from `app/Helpers/Validation/AllowedFieldsValidator.php:10`
- **Purpose**: Ensures NO extra fields are in the request
- **Example**: If request has `?hacker_field=true`, validation fails with:
  ```json
  {
    "success": false,
    "error": {
      "code": "VALIDATION_ERROR",
      "message": "The following fields are not allowed: hacker_field"
    }
  }
  ```

**3.4 Validation Failure Handling** (BaseFormRequest.php:17):
```php
protected function failedValidation(Validator $validator): void
{
    $errors = $validator->errors();
    $allMessages = $errors->all();
    
    $message = count($allMessages) === 1
        ? $allMessages[0]
        : 'The given data was invalid';
    
    $response = [
        'success' => false,
        'error' => [
            'code' => ErrorCode::VALIDATION_ERROR,
            'message' => $message,
            'details' => [
                'fields' => $errors->messages(),
            ],
        ],
    ];
    
    throw new HttpResponseException(
        response()->json($response, JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
    );
}
```

**What Happens on Validation Failure**:
1. Collects all error messages
2. If single error: uses that message directly
3. If multiple errors: uses generic message
4. Formats in consistent API error structure
5. Throws `HttpResponseException` (stops request, returns 422)

**Example Validation Error Response**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "fields": {
        "per_page": ["The per page must be at least 1."],
        "filter.title": ["The filter.title must not be greater than 255 characters."]
      }
    }
  }
}
```

**Why This Matters**:
- Validation happens BEFORE controller (fails fast)
- Consistent error format across all endpoints
- Prevents SQL injection, XSS, and other attacks
- Validated data is type-safe (integers are integers, etc.)
- Extra field validation prevents parameter pollution attacks

---

#### **STEP 4: Controller Method**

**File**: `app/Http/Controllers/Api/v1/Conversations/ConversationController.php:22-33`

```php
public function index(GetConversationsRequest $request)
{
    $validated = $request->validated();

    $this->authorize('viewAny', Conversation::class);

    $userId = Auth::id();

    $conversations = $this->conversationService->listUserConversations($userId, $validated);

    return $this->okWithData(ConversationResource::collection($conversations));
}
```

**Line-by-Line Breakdown**:

**Line 22**: `public function index(GetConversationsRequest $request)`
- **Type-hinting**: Automatically triggers validation (Laravel's Service Container)
- If validation fails, this method is never called
- `$request` contains only validated data

**Line 24**: `$validated = $request->validated()`
- Returns array of only validated fields
- Example result:
  ```php
  [
      'filter' => ['title' => 'My Chat'],
      'per_page' => 25,
      'sort' => '-updated_at'
  ]
  ```
- Accessing `$request->all()` would include unvalidated fields (unsafe!)

**Line 26**: `$this->authorize('viewAny', Conversation::class)`
- Calls the authorization system
- **What happens internally**:
  1. Laravel looks for `ConversationPolicy` (auto-discovery via naming convention)
  2. Calls the `viewAny` method on the policy
  3. Passes the authenticated user (from `Auth::user()`)
  4. Passes `Conversation::class` as the model class
  5. If policy returns `false`, throws `AuthorizationException` (403 Forbidden)
  6. If returns `true`, continues execution

**Line 28**: `$userId = Auth::id()`
- Gets the authenticated user's ID
- Equivalent to: `auth()->user()->id`
- Returns integer (e.g., `42`)
- This was set by the authentication middleware

**Line 30**: `$conversations = $this->conversationService->listUserConversations($userId, $validated)`
- Delegates business logic to the service layer
- **Dependency Injection**: `$this->conversationService` was injected via constructor:
  ```php
  public function __construct(protected ConversationService $conversationService) {}
  ```
- Laravel's Service Container automatically resolves and injects this
- Returns: `LengthAwarePaginator` instance with Conversation models

**Line 32**: `return $this->okWithData(ConversationResource::collection($conversations))`
- **`ConversationResource::collection($conversations)`**:
  - Transforms paginated Eloquent models to JSON-serializable format
  - Calls `toArray()` on each Conversation model
  - Preserves pagination metadata
- **`$this->okWithData()`**: Defined in parent `ApiController.php:67-73`:
  ```php
  protected function okWithData($data = null, $msg = null)
  {
      return response()->json([
          'data' => $data,
          'message' => $msg ?? 'Request successfully',
      ], Response::HTTP_OK);
  }
  ```
- Returns: `JsonResponse` with 200 status code

**Why This Matters**:
- **Thin Controller**: No business logic, just orchestration
- **Separation of Concerns**: Auth, validation, logic, data access all separated
- **Dependency Injection**: Easy to test, swap implementations
- **Type Safety**: PHPStan/Psalm can verify types at each step

---

#### **STEP 5: Authorization Policy**

**File**: `app/Policies/Conversations/ConversationPolicy.php:13-22`

```php
public function viewAny(User $user): bool
{
    // Users with permission can view any conversation
    if ($user->hasPermissionTo('view any conversation')) {
        return true;
    }

    // Users can view their own conversations
    return $user->hasPermissionTo('view own conversations');
}
```

**Deep Dive**:

**Line 13**: `public function viewAny(User $user): bool`
- Called by `$this->authorize('viewAny', Conversation::class)` in controller
- `$user`: The authenticated user (from Auth)
- Must return boolean (true = authorized, false = forbidden)

**Line 16**: `if ($user->hasPermissionTo('view any conversation'))`
- **Spatie Laravel-Permission Package**: Provides permission system
- **How it works**:
  1. Queries `model_has_permissions` table for direct permissions
  2. Queries `model_has_roles` + `role_has_permissions` for role-based permissions
  3. Uses caching to avoid repeated database hits
  4. Returns `true` if user has the permission
- **Use case**: Admin users might have "view any conversation" to see all users' chats

**Line 17**: `return true`
- If admin permission found, immediately authorize
- Skips checking "own conversations" permission

**Line 21**: `return $user->hasPermissionTo('view own conversations')`
- Checks if regular user has permission to view their own conversations
- Most users should have this permission
- If neither permission exists, returns `false` → 403 Forbidden

**Permission Structure**:
```
Database: permissions table
┌────┬──────────────────────────────┬────────────┐
│ id │ name                         │ guard_name │
├────┼──────────────────────────────┼────────────┤
│  1 │ view any conversation        │ api        │
│  2 │ view own conversations       │ api        │
│  3 │ create conversation          │ api        │
│  4 │ update own conversations     │ api        │
│  5 │ delete own conversations     │ api        │
└────┴──────────────────────────────┴────────────┘

Database: role_has_permissions
┌─────────────┬───────────────┐
│ permission  │ role_id       │
├─────────────┼───────────────┤
│ 1           │ 1 (Admin)     │  ← Admin can view any
│ 2           │ 2 (User)      │  ← User can view own
│ 3           │ 2 (User)      │  ← User can create
└─────────────┴───────────────┘
```

**Error Response if Unauthorized**:
```json
{
  "message": "This action is unauthorized."
}
```
HTTP Status: 403 Forbidden

**Why This Matters**:
- **Centralized Authorization**: All permission logic in one place
- **Flexible**: Can easily add/remove permissions without changing code
- **Role-Based**: Assign permissions via roles (Admin, User, Moderator, etc.)
- **Cacheable**: Permissions are cached for performance
- **Testable**: Easy to test different permission scenarios

---

#### **STEP 6: Service Layer**

**File**: `app/Services/Conversations/ConversationService.php:16-19`

```php
public function listUserConversations(int $userId, array $validatedData): LengthAwarePaginator
{
    return $this->conversationRepository->getConversations($userId);
}
```

**Deep Dive**:

**Constructor Dependency Injection** (Lines 11-13):
```php
public function __construct(
    protected ConversationRepository $conversationRepository
) {}
```
- **`protected`**: PHP 8 promoted property (declares and assigns in one line)
- **Laravel Service Container**: Automatically resolves `ConversationRepository` and injects it
- **Why Inject**: Makes testing easy (can mock repository), loose coupling

**Method Signature**:
- **Parameter 1**: `int $userId` - The authenticated user's ID
- **Parameter 2**: `array $validatedData` - Validated request data (currently unused)
- **Return Type**: `LengthAwarePaginator` - Laravel's pagination object

**Current Implementation**:
- Simple pass-through to repository
- No business logic currently

**Why Have This Layer?**
Even though it's simple now, this layer allows you to add:

1. **Caching**:
```php
public function listUserConversations(int $userId, array $validatedData): LengthAwarePaginator
{
    $cacheKey = "user.{$userId}.conversations";
    
    return Cache::remember($cacheKey, 60, function() use ($userId) {
        return $this->conversationRepository->getConversations($userId);
    });
}
```

2. **Event Firing**:
```php
public function listUserConversations(int $userId, array $validatedData): LengthAwarePaginator
{
    event(new ConversationsViewed($userId));
    
    return $this->conversationRepository->getConversations($userId);
}
```

3. **Analytics Tracking**:
```php
public function listUserConversations(int $userId, array $validatedData): LengthAwarePaginator
{
    Analytics::track('conversations.list', ['user_id' => $userId]);
    
    return $this->conversationRepository->getConversations($userId);
}
```

4. **Business Rules**:
```php
public function listUserConversations(int $userId, array $validatedData): LengthAwarePaginator
{
    // Premium users see archived conversations too
    if ($this->userService->isPremium($userId)) {
        return $this->conversationRepository->getConversationsWithArchived($userId);
    }
    
    return $this->conversationRepository->getConversations($userId);
}
```

**Why This Matters**:
- **Future-Proof**: Easy to add features without touching controller/repository
- **Testable**: Can unit test business logic separately from data access
- **Reusable**: Other controllers/commands can use the same service
- **Single Responsibility**: Each layer has one job

---

#### **STEP 7: Repository Layer**

**File**: `app/Repositories/ConversationRepository.php:13-23`

```php
public function getConversations(int $userId): LengthAwarePaginator
{
    $query = QueryBuilder::for(Conversation::class)
        ->where('user_id', $userId)
        ->allowedFilters([
            AllowedFilter::partial('title'), // Partial match (LIKE %value%)
        ])
        ->allowedSorts(['created_at', 'updated_at'])
        ->defaultSort('-updated_at');
    return $query->paginate(request()->query('per_page', 15));
}
```

**Deep Dive - Line by Line**:

**Line 15**: `$query = QueryBuilder::for(Conversation::class)`
- **Spatie Laravel Query Builder**: Advanced query building package
- **What it does**: Creates a query builder instance for the Conversation model
- **Under the hood**: Starts with `Conversation::query()` (Eloquent query builder)
- **Why use it**: Automatic filtering, sorting, includes from query parameters

**Line 16**: `->where('user_id', $userId)`
- **Security Critical**: Ensures users only see their own conversations
- **SQL**: `WHERE user_id = ?` (parameterized query, safe from SQL injection)
- **Bindings**: `[42]` (example user ID)
- **Why first**: Applied before any user-controlled filters

**Line 17-19**: `->allowedFilters([AllowedFilter::partial('title')])`
- **Whitelist Approach**: Only `title` can be filtered
- **`AllowedFilter::partial()`**: Uses `LIKE %value%` (substring match)
- **Query Parameter**: `?filter[title]=Chat`
- **SQL Generated**: `WHERE title LIKE ?` with binding `['%Chat%']`
- **Example matches**: "My Chat", "Chat with AI", "Important Chat"
- **Case sensitivity**: Depends on database collation (usually case-insensitive)
- **Other filter types available**:
  - `AllowedFilter::exact('field')`: Exact match (`WHERE field = ?`)
  - `AllowedFilter::scope('active')`: Custom scope (`whereActive()`)
  - `AllowedFilter::callback('custom', fn($query, $value) => ...)`

**Line 20**: `->allowedSorts(['created_at', 'updated_at'])`
- **Whitelist**: Only these fields can be sorted
- **Query Parameter**: `?sort=created_at` (ascending) or `?sort=-created_at` (descending)
- **SQL Generated**: `ORDER BY created_at ASC` or `ORDER BY created_at DESC`
- **Security**: Prevents sorting by sensitive fields or SQL injection
- **Multiple sorts**: `?sort=created_at,-updated_at` (comma-separated)

**Line 21**: `->defaultSort('-updated_at')`
- **Default Behavior**: If no `?sort=` parameter, use this
- **`-updated_at`**: The minus sign means descending (newest first)
- **SQL**: `ORDER BY updated_at DESC`
- **Why**: Users typically want to see most recent conversations first

**Line 22**: `return $query->paginate(request()->query('per_page', 15))`
- **`request()->query('per_page', 15)`**: Gets `per_page` from query string, defaults to 15
- **`$query->paginate()`**: Laravel pagination
- **What happens**:
  1. Counts total matching records: `SELECT COUNT(*) FROM conversations WHERE ...`
  2. Calculates total pages: `ceil(total / per_page)`
  3. Adds `LIMIT` and `OFFSET` to query
  4. Executes query: `SELECT * FROM conversations WHERE ... LIMIT 15 OFFSET 0`
  5. Returns `LengthAwarePaginator` instance

**LengthAwarePaginator Structure**:
```php
object(LengthAwarePaginator) {
    items: [Conversation, Conversation, Conversation, ...],  // Current page items
    total: 42,                  // Total conversations
    per_page: 15,               // Items per page
    current_page: 1,            // Current page number
    last_page: 3,               // Total pages (ceil(42/15) = 3)
    from: 1,                    // First item number on page
    to: 15,                     // Last item number on page
    path: '/api/v1/conversations',  // Base URL
}
```

**Example SQL Queries Generated**:

**No filters/sorting** (`GET /api/v1/conversations`):
```sql
-- Count query
SELECT COUNT(*) as aggregate 
FROM conversations 
WHERE user_id = 42 
  AND deleted_at IS NULL;

-- Data query
SELECT * 
FROM conversations 
WHERE user_id = 42 
  AND deleted_at IS NULL 
ORDER BY updated_at DESC 
LIMIT 15 OFFSET 0;
```

**With filters** (`GET /api/v1/conversations?filter[title]=Chat&per_page=10`):
```sql
SELECT COUNT(*) as aggregate 
FROM conversations 
WHERE user_id = 42 
  AND deleted_at IS NULL
  AND title LIKE '%Chat%';

SELECT * 
FROM conversations 
WHERE user_id = 42 
  AND deleted_at IS NULL
  AND title LIKE '%Chat%'
ORDER BY updated_at DESC 
LIMIT 10 OFFSET 0;
```

**With sorting** (`GET /api/v1/conversations?sort=created_at`):
```sql
SELECT * 
FROM conversations 
WHERE user_id = 42 
  AND deleted_at IS NULL 
ORDER BY created_at ASC 
LIMIT 15 OFFSET 0;
```

**Page 2** (`GET /api/v1/conversations?page=2`):
```sql
SELECT * 
FROM conversations 
WHERE user_id = 42 
  AND deleted_at IS NULL 
ORDER BY updated_at DESC 
LIMIT 15 OFFSET 15;  -- Offset = (page - 1) * per_page
```

**Why This Matters**:
- **Data Access Isolation**: All database logic in one place
- **Security**: User ID filter prevents unauthorized access
- **Performance**: Indexes on `user_id` and `updated_at` (from migration line 20)
- **Flexibility**: Easy to add more filters/sorts without changing controller
- **Soft Deletes**: `deleted_at IS NULL` automatically added (SoftDeletes trait)

---

#### **STEP 8: Model & Database**

**File**: `app/Models/Conversations/Conversation.php`

```php
class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
```

**Deep Dive**:

**`use SoftDeletes`** (Line 15):
- **Trait from Eloquent**: Adds soft delete functionality
- **What it does**:
  - Adds `deleted_at` column handling
  - `delete()` sets `deleted_at = NOW()` instead of actually deleting
  - Automatically adds `WHERE deleted_at IS NULL` to all queries
  - Provides `restore()`, `forceDelete()`, `trashed()` methods
- **Why**: Allows "undo" of deletions, keeps data for analytics/compliance

**`protected $fillable`** (Lines 17-20):
- **Mass Assignment Protection**: Only these fields can be set via `create()` or `update()`
- **Security**: Prevents users from setting unintended fields
- **Example Attack Prevented**:
  ```php
  // Without $fillable, this could work:
  Conversation::create($request->all());  // User sends 'is_admin' => true
  
  // With $fillable, 'is_admin' is ignored (not in array)
  ```

**`public function user(): BelongsTo`** (Lines 23-26):
- **Eloquent Relationship**: Defines that conversation belongs to a user
- **Usage**: `$conversation->user` loads the User model
- **Foreign Key**: Assumes `user_id` column (or can specify: `belongsTo(User::class, 'custom_user_id')`)
- **Lazy Loading**: Query runs only when accessed
- **Eager Loading**: Can prevent N+1: `Conversation::with('user')->get()`

**`public function messages(): HasMany`** (Lines 28-31):
- **Eloquent Relationship**: Conversation has many messages
- **Usage**: `$conversation->messages` loads Message collection
- **Foreign Key**: Assumes `conversation_id` on messages table
- **Lazy Loading Example**:
  ```php
  $conversation = Conversation::find(1);
  $messages = $conversation->messages;  // Executes: SELECT * FROM messages WHERE conversation_id = 1
  ```

**Database Table Structure**:

**Migration**: `database/migrations/2025_12_12_072010_create_conversations_table.php`

```php
Schema::create('conversations', function (Blueprint $table) {
    $table->id();                                               // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
    $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Foreign key with cascade delete
    $table->string('title');                                    // VARCHAR(255) NOT NULL
    $table->timestamps();                                       // created_at, updated_at TIMESTAMP
    $table->softDeletes();                                      // deleted_at TIMESTAMP NULL
    
    $table->index(['user_id', 'updated_at']);                  // Composite index for performance
});
```

**Actual SQL (MySQL)**:
```sql
CREATE TABLE conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_updated (user_id, updated_at)
);
```

**Index Explanation**:
- **`index(['user_id', 'updated_at'])`**: Composite index
- **Why**: Our query filters by `user_id` AND sorts by `updated_at`
- **Performance**: 
  - Without index: Full table scan (slow for millions of rows)
  - With index: Uses B-tree, logarithmic time complexity O(log n)
- **Query Benefit**: 
  ```sql
  EXPLAIN SELECT * FROM conversations WHERE user_id = 42 ORDER BY updated_at DESC;
  -- type: ref (good!)
  -- key: idx_user_updated (using our index)
  -- rows: 10 (not scanning entire table)
  ```

**Foreign Key Constraint**:
- **`->constrained()`**: Creates foreign key to `users.id`
- **`->onDelete('cascade')`**: When user is deleted, all their conversations are deleted too
- **Database Enforced**: Can't create conversation with invalid user_id
- **Referential Integrity**: Prevents orphaned records

**Why This Matters**:
- **ORM Abstraction**: Write PHP instead of SQL
- **Type Safety**: IDE autocomplete, static analysis
- **Relationships**: Easy to traverse: `$conversation->user->name`
- **Soft Deletes**: Data preservation without complex queries
- **Mass Assignment Protection**: Security built-in

---

#### **STEP 9: Resource Transformation**

**File**: `app/Http/Resources/Conversations/ConversationResource.php:11-36`

```php
public function toArray(Request $request): array
{
    $data = [
        'id' => $this->id,
        'title' => $this->title,
        'created_at' => $this->created_at?->toIso8601String(),
        'updated_at' => $this->updated_at?->toIso8601String(),
    ];

    // Handle paginated messages
    if ($this->relationLoaded('messages')) {
        $messages = $this->messages;
        
        // Check if messages are paginated
        if ($messages instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data['messages'] = MessageResource::collection($messages->items());
            $data['total_messages'] = $messages->total();
        } else {
            // Non-paginated messages (backward compatibility)
            $data['messages'] = MessageResource::collection($messages);
        }
    }

    return $data;
}
```

**Deep Dive**:

**Purpose of Resources**:
- **Transformation Layer**: Converts Eloquent models to JSON-ready arrays
- **API Contract**: Defines what clients see (hides internal structure)
- **Flexibility**: Can show/hide fields based on user, permissions, etc.
- **Consistency**: All conversations return same structure

**Line 11**: `public function toArray(Request $request): array`
- Called automatically when resource is returned from controller
- `$this`: The Conversation model instance
- `$request`: Current HTTP request (access to user, query params, etc.)

**Lines 13-17**: Building base data structure
```php
$data = [
    'id' => $this->id,
    'title' => $this->title,
    'created_at' => $this->created_at?->toIso8601String(),
    'updated_at' => $this->updated_at?->toIso8601String(),
];
```

**`$this->id`, `$this->title`**:
- Direct access to model properties
- Magic property access via Eloquent

**`$this->created_at?->toIso8601String()`**:
- **`?->`**: Null-safe operator (PHP 8.0+)
- **`created_at`**: Carbon instance (Laravel's datetime wrapper)
- **`toIso8601String()`**: Converts to ISO 8601 format
- **Example**: `2025-12-16T10:30:00Z`
- **Why ISO 8601**: 
  - International standard
  - Unambiguous timezone info
  - JavaScript `new Date()` parses it correctly
  - Sortable as strings

**Without ISO 8601 transformation**:
```json
{
  "created_at": {
    "date": "2025-12-16 10:30:00.000000",
    "timezone_type": 3,
    "timezone": "UTC"
  }
}
```
**With ISO 8601**:
```json
{
  "created_at": "2025-12-16T10:30:00Z"
}
```

**Lines 21-32**: Conditional message loading
```php
if ($this->relationLoaded('messages')) {
    // ... handle messages
}
```

**`$this->relationLoaded('messages')`**:
- Checks if messages relationship was eager-loaded
- **Returns `true`** if: `Conversation::with('messages')->find(1)`
- **Returns `false`** if: `Conversation::find(1)` (no messages loaded)
- **Why check**: Avoid N+1 queries, only include if already loaded
- **In this route**: Messages are NOT loaded (index doesn't need them)
- **Other routes**: `show` route loads messages

**Lines 24-27**: Handling paginated messages
```php
if ($messages instanceof \Illuminate\Pagination\LengthAwarePaginator) {
    $data['messages'] = MessageResource::collection($messages->items());
    $data['total_messages'] = $messages->total();
}
```

**`MessageResource::collection()`**:
- Transforms array of Message models
- Calls `MessageResource::toArray()` on each message
- Returns collection of transformed messages

**`$messages->items()`**:
- Extracts actual models from paginator
- Paginator has metadata + items

**`$messages->total()`**:
- Total messages across all pages
- Used for showing "10 of 52 messages" in UI

**Why This Matters**:
- **API Versioning**: Change internal structure without breaking clients
- **Conditional Fields**: Show different data to different users
- **Nested Resources**: Can include related data selectively
- **Performance**: Only load what's needed
- **Documentation**: Clear API contract

---

#### **STEP 10: Final Response**

**File**: `app/Http/Controllers/Api/ApiController.php:67-73`

```php
protected function okWithData($data = null, $msg = null)
{
    return response()->json([
        'data' => $data,
        'message' => $msg ?? 'Request successfully',
    ], Response::HTTP_OK);
}
```

**What Happens**:
1. **`ConversationResource::collection($conversations)`** returns transformed data
2. **Laravel automatically serializes** the ResourceCollection:
   - Calls `toArray()` on each resource
   - Wraps in pagination structure
   - Converts to JSON
3. **`okWithData()`** wraps in consistent API format
4. **HTTP Response** is built:
   - Status: `200 OK`
   - Content-Type: `application/json`
   - Body: JSON string

**Final JSON Structure**:
```json
{
  "data": {
    "data": [
      {
        "id": 1,
        "title": "My First Chat",
        "created_at": "2025-12-15T10:30:00Z",
        "updated_at": "2025-12-16T14:22:00Z"
      },
      {
        "id": 2,
        "title": "Project Discussion",
        "created_at": "2025-12-16T09:15:00Z",
        "updated_at": "2025-12-16T12:45:00Z"
      }
    ],
    "links": {
      "first": "http://api.example.com/api/v1/conversations?page=1",
      "last": "http://api.example.com/api/v1/conversations?page=3",
      "prev": null,
      "next": "http://api.example.com/api/v1/conversations?page=2"
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 3,
      "path": "http://api.example.com/api/v1/conversations",
      "per_page": 15,
      "to": 15,
      "total": 42
    }
  },
  "message": "Request successfully"
}
```

**Response Headers**:
```
HTTP/1.1 200 OK
Content-Type: application/json; charset=UTF-8
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Cache-Control: no-cache, private
Date: Tue, 16 Dec 2025 12:00:00 GMT
Content-Length: 1234
```

**Why This Matters**:
- **Consistent Format**: All successful responses have same structure
- **Pagination Metadata**: Clients can build pagination UI
- **Links**: HATEOAS principle (hypermedia as the engine of application state)
- **Extensible**: Easy to add more fields to envelope

---

## 2. POST /api/v1/conversations - Create New Conversation

### 🎯 Purpose
Create a new conversation for the authenticated user. The conversation can be created with a custom title or will default to "New Conversation" if no title is provided.

### 📊 Flow Diagram
```
HTTP Request (POST with JSON body)
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. ROUTE MATCHING (routes/api/conversation.php:19)                     │
│    - Pattern: POST /api/v1/conversations                               │
│    - Named: conversations.store                                        │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 2. MIDDLEWARE STACK                                                     │
│    - auth.api (Authentication)                                          │
│    - throttle:conversation (Rate limiting)                              │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 3. FORM REQUEST VALIDATION (StoreConversationRequest.php)              │
│    - Validates title field                                              │
│    - Ensures no extra fields present                                    │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 4. CONTROLLER (ConversationController.php:36)                          │
│    - Method: store(StoreConversationRequest $request)                  │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 5. AUTHORIZATION POLICY (ConversationPolicy.php:39)                    │
│    - Method: create(User $user)                                        │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 6. SERVICE LAYER (ConversationService.php:22)                          │
│    - Prepares conversation data with defaults                          │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 7. REPOSITORY (ConversationRepository.php:32)                          │
│    - Creates conversation in database                                   │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 8. MODEL (Conversation.php)                                             │
│    - Eloquent creates new record                                        │
│    - Returns new Conversation instance                                  │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 9. RESPONSE TRANSFORMATION (ConversationResource.php)                  │
│    - Transforms new conversation to JSON                                │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
HTTP Response (201 Created with JSON body)
```

---

### 🔍 DEEP DIVE: Step-by-Step Breakdown

---

#### **STEP 1: Route Registration & Matching**

**File**: `routes/api/conversation.php:19`

```php
Route::post('conversations', [ConversationController::class, 'store'])
    ->name('conversations.store');
```

**What Happens Here**:
1. **HTTP Method**: `POST` (indicates resource creation)
2. **URI**: `/api/v1/conversations` (same as GET but different HTTP method)
3. **Laravel Routing**: Routes are method-specific (POST /conversations ≠ GET /conversations)
4. **RESTful Convention**: POST to collection endpoint creates new resource
5. **Route Name**: `conversations.store` follows Laravel resource naming convention

**Request Body Expected**:
```json
{
  "title": "My New Conversation"
}
```

---

#### **STEP 2: Middleware Stack**

Same middleware as GET route:
- **`auth.api`**: Validates Bearer token, ensures user is authenticated
- **`throttle:conversation`**: Rate limits POST requests (typically lower limit than GET)

**Why Rate Limiting Matters for POST**:
- Prevents spam conversation creation
- Protects database from rapid inserts
- Prevents abuse of storage resources

---

#### **STEP 3: Form Request Validation**

**File**: `app/Http/Requests/Conversation/StoreConversationRequest.php:16-21`

```php
public function rules(): array
{
    return [
        'title' => ['nullable', 'string', 'max:255'],
    ];
}
```

**Validation Rules Breakdown**:

| Field | Rules | Meaning | Example Valid | Example Invalid |
|-------|-------|---------|---------------|-----------------|
| `title` | `nullable`, `string`, `max:255` | Optional; if provided must be string ≤255 chars | `{"title": "My Chat"}` or `{}` | `{"title": 12345}`, `{"title": "x".repeat(256)}` |

**Key Points**:
- **`nullable`**: Field is completely optional, can be omitted or null
- If omitted, service layer will provide default value
- Empty string `""` is valid (but service provides default)
- Maximum 255 characters prevents database overflow

**Custom Validation** (Lines 23-30):
```php
public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $validator) {
        $allowedFields = ['title'];
        AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
    });
}
```

**What This Does**:
- Only `title` field is allowed in request
- **Example Blocked Request**:
```json
{
  "title": "My Chat",
  "user_id": 999,
  "is_admin": true
}
```
- **Validation Error**: "The following fields are not allowed: user_id, is_admin"
- **Security**: Prevents mass assignment attacks

**Validation Success Example**:
```php
// Input
{
  "title": "Project Discussion"
}

// $request->validated() returns
[
  'title' => 'Project Discussion'
]
```

**Validation Success (Empty)**:
```php
// Input
{}

// $request->validated() returns
[]
```

---

#### **STEP 4: Controller Method**

**File**: `app/Http/Controllers/Api/v1/Conversations/ConversationController.php:36-48`

```php
public function store(StoreConversationRequest $request)
{
    $this->authorize('create', Conversation::class);

    $userId = Auth::id();

    $conversation = $this->conversationService->createConversation($userId, $request->validated());

    return $this->created(
        new ConversationResource($conversation),
        'Conversation created successfully'
    );
}
```

**Line-by-Line Breakdown**:

**Line 36**: `public function store(StoreConversationRequest $request)`
- Type-hinted request automatically validates before method runs
- Method name `store` follows Laravel resource controller convention

**Line 38**: `$this->authorize('create', Conversation::class)`
- Checks if authenticated user has permission to create conversations
- Calls `ConversationPolicy::create()` method
- Passes `Conversation::class` (not an instance, since we're creating new)
- Throws 403 if user lacks permission

**Line 40**: `$userId = Auth::id()`
- Gets authenticated user's ID from token
- This ensures conversation is created for the authenticated user
- User can't create conversations for other users (security)

**Line 42**: `$conversation = $this->conversationService->createConversation($userId, $request->validated())`
- Delegates to service layer
- Passes:
  - User ID (who owns this conversation)
  - Validated data (may be empty array)
- Returns: New `Conversation` model instance

**Lines 44-47**: `return $this->created(...)`
- **`$this->created()`**: Defined in `ApiController.php:18-24`
- Returns HTTP 201 Created status (indicates resource was created)
- Wraps conversation in `ConversationResource` for transformation
- Includes success message

**`created()` Method** (ApiController.php:18-24):
```php
protected function created($data, $msg = null)
{
    return response()->json([
        'data' => $data,
        'message' => $msg ?? 'Create successfully',
    ], Response::HTTP_CREATED);  // 201 status code
}
```

---

#### **STEP 5: Authorization Policy**

**File**: `app/Policies/Conversations/ConversationPolicy.php:39-42`

```php
public function create(User $user): bool
{
    return $user->hasPermissionTo('create conversation');
}
```

**What Happens Here**:
- **Simple Permission Check**: Only checks one permission
- **Permission**: `create conversation`
- **No Ownership Check**: Creating a new resource doesn't involve existing data

**Permission Structure**:
- Database table: `permissions`
- Permission name: `create conversation`
- Assigned to role: `User` (most users should have this)
- Admin role also has this permission

**If Unauthorized**:
```json
{
  "message": "This action is unauthorized."
}
```
HTTP Status: 403 Forbidden

**Why This Check**:
- Could restrict conversation creation to premium users
- Could limit based on user's plan (free users: 5 conversations, premium: unlimited)
- Could disable for suspended users
- Centralized control point for feature access

---

#### **STEP 6: Service Layer**

**File**: `app/Services/Conversations/ConversationService.php:22-30`

```php
public function createConversation(int $userId, array $data): Conversation
{
    $conversationData = [
        'user_id' => $userId,
        'title' => $data['title'] ?? 'New Conversation',
    ];

    return $this->conversationRepository->create($conversationData);
}
```

**Line-by-Line Breakdown**:

**Line 22**: `public function createConversation(int $userId, array $data): Conversation`
- **Parameter 1**: `$userId` - Authenticated user's ID
- **Parameter 2**: `$data` - Validated request data (may be empty array)
- **Return Type**: `Conversation` - Eloquent model instance

**Lines 24-27**: Building conversation data
```php
$conversationData = [
    'user_id' => $userId,
    'title' => $data['title'] ?? 'New Conversation',
];
```

**What This Does**:
- **`user_id`**: Always set from authenticated user (not from request)
- **`title`**: Uses provided title OR defaults to "New Conversation"
- **Null Coalescing Operator (`??`)**: If `$data['title']` doesn't exist or is null, use default

**Examples**:

1. **With Title**:
```php
// Input: $data = ['title' => 'Project Planning']
// Result: ['user_id' => 42, 'title' => 'Project Planning']
```

2. **Without Title**:
```php
// Input: $data = []
// Result: ['user_id' => 42, 'title' => 'New Conversation']
```

3. **Null Title**:
```php
// Input: $data = ['title' => null]
// Result: ['user_id' => 42, 'title' => 'New Conversation']
```

4. **Empty String Title**:
```php
// Input: $data = ['title' => '']
// Result: ['user_id' => 42, 'title' => '']  // Empty string is NOT null
```

**Line 29**: `return $this->conversationRepository->create($conversationData)`
- Delegates database insertion to repository
- Returns the created Conversation model

**Business Logic Here**:
This is where you could add:
- Event firing: `event(new ConversationCreated($conversation))`
- Quota checks: "User has reached conversation limit"
- Default settings: Add default conversation settings
- Analytics: Track conversation creation rate

---

#### **STEP 7: Repository Layer**

**File**: `app/Repositories/ConversationRepository.php:32-35`

```php
public function create(array $data): Conversation
{
    return Conversation::create($data);
}
```

**What Happens Here**:
- **`Conversation::create($data)`**: Eloquent mass assignment method
- **Database INSERT**: Creates new record in `conversations` table
- **Timestamps**: Automatically sets `created_at` and `updated_at`
- **Returns**: New Conversation instance with database-generated ID

**Mass Assignment Protection**:
- Only fields in `$fillable` array can be set
- From `Conversation.php:17-20`:
```php
protected $fillable = [
    'user_id',
    'title',
];
```
- Any other fields in `$data` are silently ignored
- Prevents accidental setting of `id`, `deleted_at`, etc.

**What Gets Inserted**:
```php
// Input
[
    'user_id' => 42,
    'title' => 'Project Planning'
]

// Database INSERT
INSERT INTO conversations (user_id, title, created_at, updated_at)
VALUES (42, 'Project Planning', '2025-12-16 12:00:00', '2025-12-16 12:00:00')

// Auto-generated
- id: 123 (auto-increment)
- created_at: current timestamp
- updated_at: current timestamp
- deleted_at: NULL
```

**Returned Conversation Model**:
```php
Conversation {
    id: 123,
    user_id: 42,
    title: "Project Planning",
    created_at: Carbon @1702728000,
    updated_at: Carbon @1702728000,
    deleted_at: null
}
```

---

#### **STEP 8: Model**

**File**: `app/Models/Conversations/Conversation.php`

The Eloquent model handles:
1. **Mass Assignment**: Via `$fillable` array
2. **Timestamps**: Automatically managed
3. **Soft Deletes**: `deleted_at` field managed by trait
4. **Type Casting**: `created_at`/`updated_at` cast to Carbon instances
5. **Relationships**: User and messages relationships defined

**After Creation**:
- Model is hydrated with all database values
- Relationships are available (but not loaded yet)
- Can access: `$conversation->id`, `$conversation->user`, etc.

---

#### **STEP 9: Resource Transformation**

**File**: `app/Http/Resources/Conversations/ConversationResource.php:11-18`

```php
public function toArray(Request $request): array
{
    $data = [
        'id' => $this->id,
        'title' => $this->title,
        'created_at' => $this->created_at?->toIso8601String(),
        'updated_at' => $this->updated_at?->toIso8601String(),
    ];

    // Messages relationship not loaded for create operation
    // (would add messages array if loaded)

    return $data;
}
```

**Transformation Example**:
```php
// Conversation Model
Conversation {
    id: 123,
    user_id: 42,
    title: "Project Planning",
    created_at: Carbon("2025-12-16 12:00:00"),
    updated_at: Carbon("2025-12-16 12:00:00"),
}

// Transformed to JSON-ready array
[
    'id' => 123,
    'title' => 'Project Planning',
    'created_at' => '2025-12-16T12:00:00Z',
    'updated_at' => '2025-12-16T12:00:00Z'
]
```

**Note**: 
- `user_id` is NOT included in response (internal field)
- Timestamps converted to ISO 8601 format
- Messages not included (no messages yet in new conversation)

---

#### **STEP 10: Final Response**

**Controller Response** (ConversationController.php:44-47):
```php
return $this->created(
    new ConversationResource($conversation),
    'Conversation created successfully'
);
```

**HTTP Response**:
```http
HTTP/1.1 201 Created
Content-Type: application/json
Location: /api/v1/conversations/123
Date: Tue, 16 Dec 2025 12:00:00 GMT

{
  "data": {
    "id": 123,
    "title": "Project Planning",
    "created_at": "2025-12-16T12:00:00Z",
    "updated_at": "2025-12-16T12:00:00Z"
  },
  "message": "Conversation created successfully"
}
```

**Response Details**:
- **Status Code**: `201 Created` (not 200 OK)
- **Location Header**: Could include URL to new resource (optional)
- **Response Body**: Contains created resource with generated ID
- **Client Usage**: Client can immediately use returned ID for subsequent requests

---

### 📝 Example API Usage

**Request with Title**:
```http
POST /api/v1/conversations HTTP/1.1
Host: api.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
Accept: application/json

{
  "title": "Planning Q4 Projects"
}
```

**Response**:
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "data": {
    "id": 125,
    "title": "Planning Q4 Projects",
    "created_at": "2025-12-16T14:30:00Z",
    "updated_at": "2025-12-16T14:30:00Z"
  },
  "message": "Conversation created successfully"
}
```

**Request without Title** (uses default):
```http
POST /api/v1/conversations HTTP/1.1
Host: api.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
Accept: application/json

{}
```

**Response**:
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "data": {
    "id": 126,
    "title": "New Conversation",
    "created_at": "2025-12-16T14:31:00Z",
    "updated_at": "2025-12-16T14:31:00Z"
  },
  "message": "Conversation created successfully"
}
```

**Error Response - Validation Failed**:
```http
POST /api/v1/conversations HTTP/1.1
Content-Type: application/json

{
  "title": "This title is way too long and exceeds the maximum allowed length of 255 characters which will cause a validation error because we have a max:255 rule on the title field and Laravel will reject this request before it even reaches the controller method or service layer",
  "unauthorized_field": "hacker"
}
```

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "fields": {
        "title": ["The title must not be greater than 255 characters."],
        "invalid_fields": ["The following fields are not allowed: unauthorized_field"]
      }
    }
  }
}
```

**Error Response - Unauthorized**:
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "This action is unauthorized."
}
```

---

## 3. GET /api/v1/conversations/{conversation} - Show Specific Conversation with Messages

### 🎯 Purpose
Retrieve a specific conversation including its messages. Messages are paginated and loaded with their attachments, feedback, and AI model information. This endpoint is used to display a conversation's full chat history.

### 📊 Flow Diagram
```
HTTP Request (GET /api/v1/conversations/123)
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. ROUTE MATCHING (routes/api/conversation.php:22)                     │
│    - Pattern: GET /api/v1/conversations/{conversation}                 │
│    - Named: conversations.show                                         │
│    - Route Model Binding: {conversation} → Conversation model          │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 2. MIDDLEWARE STACK                                                     │
│    - auth.api (Authentication)                                          │
│    - throttle:conversation (Rate limiting)                              │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 3. ROUTE MODEL BINDING                                                  │
│    - Laravel queries: SELECT * FROM conversations WHERE id = 123        │
│    - If not found: Returns 404 Not Found                                │
│    - If found: Injects Conversation model into controller               │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 4. FORM REQUEST VALIDATION (GetConversationRequest.php)                │
│    - Validates per_page parameter                                       │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 5. CONTROLLER (ConversationController.php:51)                          │
│    - Method: show(GetConversationRequest, Conversation)                │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 6. AUTHORIZATION POLICY (ConversationPolicy.php:25)                    │
│    - Method: view(User $user, Conversation $conversation)              │
│    - Checks: User owns conversation OR has view any permission          │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 7. SERVICE LAYER (ConversationService.php:33)                          │
│    - Coordinates loading conversation with messages                     │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 8. REPOSITORY (ConversationRepository.php:50)                          │
│    - Loads conversation with paginated messages                         │
│    - Eager loads: attachments, feedback, aiModel relationships          │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 9. DATABASE QUERIES                                                     │
│    - Main conversation already loaded (route model binding)             │
│    - Query 1: Count messages                                            │
│    - Query 2: Fetch messages with relationships                         │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ 10. RESPONSE TRANSFORMATION                                             │
│     - ConversationResource with nested MessageResource collection       │
│     - Includes pagination metadata                                      │
└─────────────────────────────────────────────────────────────────────────┘
    ↓
HTTP Response (200 OK with conversation + messages)
```

---

### 🔍 DEEP DIVE: Step-by-Step Breakdown

---

#### **STEP 1: Route Registration & Model Binding**

**File**: `routes/api/conversation.php:22`

```php
Route::get('conversations/{conversation}', [ConversationController::class, 'show'])
    ->name('conversations.show');
```

**What Happens Here**:
1. **Route Parameter**: `{conversation}` is a route parameter
2. **Laravel's Naming Convention**: Parameter named `conversation` triggers automatic model binding
3. **Model Binding**: Laravel automatically:
   - Extracts ID from URL (e.g., `/conversations/123` → `123`)
   - Queries: `SELECT * FROM conversations WHERE id = 123 AND deleted_at IS NULL`
   - If found: Creates Conversation model, injects into controller
   - If not found: Returns 404 automatically (never reaches controller)

**Example URLs**:
- `/api/v1/conversations/123` → Loads conversation ID 123
- `/api/v1/conversations/999` → 404 if doesn't exist
- `/api/v1/conversations/abc` → 404 (not a valid ID)

**Why This Matters**:
- No manual database query needed in controller
- Automatic 404 handling
- Type-safe: Controller receives `Conversation` model, not integer
- Soft deletes respected: Deleted conversations return 404

---

#### **STEP 3: Route Model Binding Deep Dive**

**How Laravel Resolves the Model**:

1. **Route Definition**: `{conversation}` parameter
2. **Type Hinting**: Controller method has `Conversation $conversation` parameter
3. **Laravel Magic**: Sees matching names → triggers implicit binding
4. **Query Execution**: 
   - Uses primary key (`id`) by default
   - Respects soft deletes (via `SoftDeletes` trait)
   - Loads only non-deleted records

**Customization Options** (not used here, but available):
```php
// In routes file
Route::get('conversations/{conversation:uuid}', ...);  // Use UUID instead of ID

// In Conversation model
public function getRouteKeyName() {
    return 'uuid';  // Custom lookup column
}
```

**If Model Not Found**:
```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "message": "No query results for model [App\\Models\\Conversations\\Conversation] 999"
}
```

---

#### **STEP 4: Form Request Validation**

**File**: `app/Http/Requests/Conversation/GetConversationRequest.php:15-20`

```php
public function rules(): array
{
    return [
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
    ];
}
```

**Validation Rules**:

| Field | Rules | Meaning | Example Valid | Example Invalid |
|-------|-------|---------|---------------|-----------------|
| `per_page` | `nullable`, `integer`, `min:1`, `max:100` | Optional; controls messages per page | `?per_page=20` | `?per_page=0`, `?per_page=500` |

**Allowed Fields** (Lines 22-29):
```php
public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $validator) {
        $allowedFields = ['per_page'];
        AllowedFieldsValidator::validate($validator, $this->all(), $allowedFields);
    });
}
```

**Why Only per_page**:
- This endpoint loads one specific conversation (ID from URL)
- No filtering needed (not a collection)
- No sorting needed (messages always sorted by created_at)
- Only pagination control needed

**Examples**:
- `GET /conversations/123` → Default 20 messages per page
- `GET /conversations/123?per_page=50` → 50 messages per page
- `GET /conversations/123?per_page=5` → 5 messages per page (useful for mobile)

---

#### **STEP 5: Controller Method**

**File**: `app/Http/Controllers/Api/v1/Conversations/ConversationController.php:51-61`

```php
public function show(GetConversationRequest $request, Conversation $conversation)
{
    $this->authorize('view', $conversation);

    $validated = $request->validated();
    $perPage = $validated['per_page'] ?? null;

    $conversationWithMessages = $this->conversationService->getConversation($conversation->id, $perPage);

    return $this->okWithData(new ConversationResource($conversationWithMessages));
}
```

**Line-by-Line Breakdown**:

**Line 51**: `public function show(GetConversationRequest $request, Conversation $conversation)`
- **Parameter 1**: `GetConversationRequest` - Validated request
- **Parameter 2**: `Conversation $conversation` - Auto-injected from route model binding
- **At this point**: Conversation already loaded from database, guaranteed to exist

**Line 53**: `$this->authorize('view', $conversation)`
- Calls `ConversationPolicy::view()` method
- **Passes conversation instance** (not just class name)
- Checks if authenticated user can view THIS specific conversation
- Throws 403 if unauthorized

**Line 55**: `$validated = $request->validated()`
- Gets validated data
- May be empty array or `['per_page' => 20]`

**Line 56**: `$perPage = $validated['per_page'] ?? null`
- Extracts per_page value or defaults to null
- `null` will use service/repository default (20)

**Line 58**: `$conversationWithMessages = $this->conversationService->getConversationWithMessages($conversation, $perPage)`
- **Optimized**: Passes the already-loaded Conversation model (not ID)
- **Avoids duplicate query**: Route model binding already fetched the conversation
- **Performance benefit**: Saves one database query
- Returns: Same conversation instance with messages relationship loaded

**Line 60**: `return $this->okWithData(new ConversationResource($conversationWithMessages))`
- Transforms conversation + messages to JSON
- Returns 200 OK

---

#### **STEP 6: Authorization Policy**

**File**: `app/Policies/Conversations/ConversationPolicy.php:25-34`

```php
public function view(User $user, Conversation $conversation): bool
{
    // Users with permission can view any conversation
    if ($user->hasPermissionTo('view any conversation')) {
        return true;
    }

    // Users can only view their own conversations
    return $user->id === $conversation->user_id && $user->hasPermissionTo('view own conversations');
}
```

**Deep Dive**:

**Line 25**: `public function view(User $user, Conversation $conversation): bool`
- **Receives conversation instance** (unlike `viewAny` which receives class)
- Can check ownership: `$conversation->user_id`

**Line 28**: `if ($user->hasPermissionTo('view any conversation'))`
- **Admin/Moderator Permission**: Can view any user's conversations
- Use case: Customer support, moderation, admin panel
- **Security Critical**: Only admins should have this

**Line 32-33**: Ownership + Permission Check
```php
return $user->id === $conversation->user_id && $user->hasPermissionTo('view own conversations');
```
- **Two Conditions (AND)**:
  1. `$user->id === $conversation->user_id`: User owns this conversation
  2. `$user->hasPermissionTo('view own conversations')`: User has base permission

**Authorization Scenarios**:

| User ID | Conversation Owner | Has "view any" | Has "view own" | Result |
|---------|-------------------|----------------|----------------|---------|
| 42 | 42 | No | Yes | ✅ Authorized (owns it) |
| 42 | 99 | No | Yes | ❌ Forbidden (doesn't own) |
| 42 | 99 | Yes | Yes | ✅ Authorized (admin) |
| 42 | 42 | No | No | ❌ Forbidden (no permission) |

**Why Check Both Ownership AND Permission**:
- Could revoke "view own conversations" from suspended users
- Ownership alone isn't enough (need permission too)
- Allows granular control

---

#### **STEP 7: Service Layer**

**File**: `app/Services/Conversations/ConversationService.php:36-40`

```php
public function getConversationWithMessages(Conversation $conversation, ?int $perPage = null): Conversation
{
    return $this->conversationRepository->loadMessages($conversation, $perPage);
}
```

**What This Does**:
- **Receives already-loaded Conversation model**: No need to fetch again
- **Optimized approach**: Avoids duplicate database query
- **Pass-through to repository**: Delegates message loading
- **Return Type**: `Conversation` (guaranteed to exist - came from route binding)

**Why This Is Better**:
- **Performance**: One less database query (route binding already loaded conversation)
- **Efficiency**: Uses existing model instance instead of re-fetching
- **Cleaner**: No unnecessary ID extraction

**Potential Enhancements**:
```php
public function getConversationWithMessages(Conversation $conversation, ?int $perPage = null): Conversation
{
    // Could add caching
    $cacheKey = "conversation.{$conversation->id}.messages.{$perPage}";
    return Cache::remember($cacheKey, 300, function() use ($conversation, $perPage) {
        return $this->conversationRepository->loadMessages($conversation, $perPage);
    });
    
    // Could track views
    event(new ConversationViewed($conversation->id, auth()->id()));
    
    // Could mark as read
    $this->markConversationAsRead($conversation, auth()->id());
}
```

---

#### **STEP 8: Repository Layer - The Complex Part**

**File**: `app/Repositories/ConversationRepository.php:78-101`

```php
public function loadMessages(Conversation $conversation, ?int $perPage = null): Conversation
{
    // Set default per_page if not provided
    $perPage = $perPage ?? 20;

    // Get paginated messages (most recent first) with relationships
    $messages = $conversation->messages()
        ->with([
            'attachments.media',
            'feedback',
            'aiModel',
            'originalMessage.feedback', // Load feedback from original message for regenerations
        ])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

    // Set the paginated messages to the conversation
    $conversation->setRelation('messages', $messages);

    return $conversation;
}
```

**Optimization Explained**:

**Key Difference from Old Approach**:
- **Old**: `getWithMessages($id)` → `Conversation::find($id)` (duplicate query)
- **New**: `loadMessages($conversation)` → Uses already-loaded model

**Why This Matters**:
- **Route model binding** already executed: `SELECT * FROM conversations WHERE id = 123`
- **Old approach** re-executed: `SELECT * FROM conversations WHERE id = 123` (wasteful)
- **New approach** skips the duplicate query entirely


**Line 82**: `$perPage = $perPage ?? 20`
- Default to 20 messages per page if not specified
- User can override via query parameter

**Lines 85-92**: Building messages query with eager loading
```php
$messages = $conversation->messages()
    ->with([
        'attachments.media',
        'feedback',
        'aiModel',
        'originalMessage.feedback',
    ])
    ->orderBy('created_at', 'desc')
    ->paginate($perPage);
```

**Breaking this down**:

**`$conversation->messages()`**:
- Returns query builder for relationship
- Automatically adds `WHERE conversation_id = ?`
- Doesn't execute yet

**`->with(['attachments.media', ...])`**:
- **Eager Loading**: Loads related data to prevent N+1 queries
- **Nested Eager Loading**: `attachments.media` loads attachments and their media files

**Relationships Being Loaded**:

1. **`attachments.media`**:
   - Loads all attachments for each message
   - Then loads media files for each attachment (using Spatie Media Library)
   - Prevents N+1: One query for all attachments, one for all media

2. **`feedback`**:
   - Loads user feedback (like/dislike) for each message
   - One query for all feedback

3. **`aiModel`**:
   - Loads AI model info (GPT-4, Claude, etc.) for each message
   - One query for all AI models

4. **`originalMessage.feedback`**:
   - For regenerated messages: loads original message's feedback
   - Regenerations share feedback with their original message
   - Nested relationship: originalMessage → its feedback

**`->orderBy('created_at', 'desc')`**:
- Sorts messages newest first
- Users typically want to see latest messages at top
- Conversation history flows bottom-to-top in UI

**`->paginate($perPage)`**:
- Paginates the messages
- Returns `LengthAwarePaginator` instance
- Automatically handles page from query string (`?page=2`)

**Query Count** (with eager loading):
1. Count messages: `SELECT COUNT(*)`
2. Fetch messages: `SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at DESC LIMIT 20`
3. Fetch attachments: `SELECT * FROM message_attachments WHERE message_id IN (1,2,3,...)`
4. Fetch media: `SELECT * FROM media WHERE model_id IN (1,2,3,...) AND model_type = 'MessageAttachment'`
5. Fetch feedback: `SELECT * FROM message_feedback WHERE message_id IN (1,2,3,...)`
6. Fetch AI models: `SELECT * FROM ai_models WHERE id IN (1,2,3,...)`
7. Fetch original messages (if any regenerations): `SELECT * FROM messages WHERE id IN (...)`
8. Fetch original feedback: `SELECT * FROM message_feedback WHERE message_id IN (...)`

**Total: ~8 queries** (regardless of number of messages on page)

**Without Eager Loading** (N+1 Problem):
- 1 query for messages
- N queries for attachments (one per message)
- N queries for feedback
- N queries for AI models
- Total: **3N + 1 queries** (for 20 messages = 61 queries!)

**Line 72**: `$conversation->setRelation('messages', $messages)`
- **Manually Sets Relationship**: Attaches paginated messages to conversation
- `$conversation->messages` now returns paginator (not query builder)
- Paginator includes items + metadata (total, current_page, etc.)

**Line 74**: `return $conversation`
- Returns conversation with messages relationship loaded
- Messages are paginated
- All nested relationships are eager-loaded

---

#### **STEP 9: Response Transformation**

**File**: `app/Http/Resources/Conversations/ConversationResource.php:11-36`

```php
public function toArray(Request $request): array
{
    $data = [
        'id' => $this->id,
        'title' => $this->title,
        'created_at' => $this->created_at?->toIso8601String(),
        'updated_at' => $this->updated_at?->toIso8601String(),
    ];

    // Handle paginated messages
    if ($this->relationLoaded('messages')) {
        $messages = $this->messages;
        
        // Check if messages are paginated
        if ($messages instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data['messages'] = MessageResource::collection($messages->items());
            $data['total_messages'] = $messages->total();
        } else {
            // Non-paginated messages (backward compatibility)
            $data['messages'] = MessageResource::collection($messages);
        }
    }

    return $data;
}
```

**Breakdown**:

**Lines 13-17**: Base conversation data (same as create/index)

**Line 21**: `if ($this->relationLoaded('messages'))`
- Checks if messages were loaded
- **True** for show route (we loaded them)
- **False** for index/create routes (we didn't)

**Line 25**: `if ($messages instanceof \Illuminate\Pagination\LengthAwarePaginator)`
- Checks if messages are paginated
- **True** for show route
- **False** if loaded without pagination

**Lines 26-27**: Transform paginated messages
```php
$data['messages'] = MessageResource::collection($messages->items());
$data['total_messages'] = $messages->total();
```
- **`$messages->items()`**: Extracts actual Message models from paginator
- **`MessageResource::collection()`**: Transforms each message
- **`$messages->total()`**: Total message count (not just on this page)

**MessageResource Transformation** (`app/Http/Resources/Messages/MessageResource.php:10-49`):

Each message is transformed to:
```php
[
    'id' => $message->id,
    'conversation_id' => $message->conversation_id,
    'sender' => $message->sender,  // 'user' or 'bot'
    'content' => $message->content,
    'ai_model_id' => $message->ai_model_id,
    'parent_message_id' => $message->parent_message_id,
    'original_message_id' => $message->original_message_id,
    'regeneration_index' => $message->regeneration_index,
    'status' => $message->status,
    'created_at' => '2025-12-16T12:00:00Z',
    'updated_at' => '2025-12-16T12:00:00Z',
    'attachments' => [...],  // If loaded
    'feedback' => {...},      // If exists
    'ai_model' => {           // If loaded
        'id' => 1,
        'name' => 'GPT-4'
    },
    'regenerations' => [...]  // If loaded
]
```

**Feedback Logic** (MessageResource.php:12-20):
```php
// Determine which feedback to show (original message feedback if this is a regeneration)
$feedbackToShow = null;
if ($this->original_message_id && $this->relationLoaded('originalMessage') && $this->originalMessage) {
    // This is a regenerated message, use original message's feedback
    $feedbackToShow = $this->originalMessage->feedback;
} elseif ($this->relationLoaded('feedback')) {
    // This is an original message or no original loaded, use own feedback
    $feedbackToShow = $this->feedback;
}
```

**Why This Complexity**:
- Regenerated messages **share feedback** with original message
- User likes/dislikes the original, not each regeneration individually
- Must look up original message's feedback for regenerations

---

#### **STEP 10: Final Response**

**Example Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": {
    "id": 123,
    "title": "Project Planning",
    "created_at": "2025-12-16T10:00:00Z",
    "updated_at": "2025-12-16T14:30:00Z",
    "total_messages": 45,
    "messages": [
      {
        "id": 501,
        "conversation_id": 123,
        "sender": "bot",
        "content": "I can help you plan your project. What's the timeline?",
        "ai_model_id": 1,
        "parent_message_id": 500,
        "original_message_id": null,
        "regeneration_index": 0,
        "status": "completed",
        "created_at": "2025-12-16T14:30:00Z",
        "updated_at": "2025-12-16T14:30:00Z",
        "attachments": [],
        "feedback": {
          "id": 10,
          "message_id": 501,
          "rating": "like",
          "comment": "Very helpful!",
          "created_at": "2025-12-16T14:31:00Z"
        },
        "ai_model": {
          "id": 1,
          "name": "GPT-4"
        }
      },
      {
        "id": 500,
        "conversation_id": 123,
        "sender": "user",
        "content": "Help me plan a project",
        "ai_model_id": null,
        "parent_message_id": null,
        "original_message_id": null,
        "regeneration_index": 0,
        "status": "completed",
        "created_at": "2025-12-16T14:29:00Z",
        "updated_at": "2025-12-16T14:29:00Z",
        "attachments": [
          {
            "id": 50,
            "message_id": 500,
            "file_name": "requirements.pdf",
            "file_size": 245678,
            "mime_type": "application/pdf",
            "url": "https://storage.example.com/attachments/requirements.pdf",
            "created_at": "2025-12-16T14:29:00Z"
          }
        ],
        "feedback": null,
        "ai_model": null
      }
    ]
  },
  "message": "Request successfully"
}
```

**Response Structure Notes**:
- **Messages ordered newest first** (most recent at index 0)
- **`total_messages`**: Total across all pages (useful for "Showing 1-20 of 45")
- **User messages**: No AI model, no feedback (feedback is for bot responses)
- **Bot messages**: Has AI model, may have feedback
- **Attachments**: Only on messages that have them
- **No pagination metadata**: Unlike collection endpoints, pagination info not in response (client uses total_messages + per_page to calculate)

---

### 📝 Example API Usage

**Basic Request**:
```http
GET /api/v1/conversations/123 HTTP/1.1
Host: api.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/json
```

**With Pagination**:
```http
GET /api/v1/conversations/123?per_page=10&page=2 HTTP/1.1
Host: api.example.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/json
```

**Error - Not Found**:
```http
GET /api/v1/conversations/999999 HTTP/1.1
```

```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "message": "No query results for model [App\\Models\\Conversations\\Conversation] 999999"
}
```

**Error - Unauthorized (viewing another user's conversation)**:
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "This action is unauthorized."
}
```

---

## 4. PATCH /api/v1/conversations/{conversation} - Update Conversation Title

### 🎯 Purpose
Update a conversation's title. This is typically used when users want to rename a conversation for better organization.

### 📊 Flow Diagram
```
HTTP Request (PATCH /api/v1/conversations/123 with JSON body)
    ↓
Route Matching → Middleware → Route Model Binding → Validation → Controller → Policy → Service → Repository → Model → Database
                                                                                                                         ↓
HTTP Response (200 OK) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 KEY POINTS

**Route**: `routes/api/conversation.php:25`
```php
Route::patch('conversations/{conversation}', [ConversationController::class, 'update'])
    ->name('conversations.update');
```

**Request Validation** (`UpdateConversationRequest.php:18-20`):
```php
'title' => ['required', 'string', 'max:255'],
```
- **`required`**: Title MUST be provided (unlike create where it's optional)
- **Update semantics**: User is explicitly changing the title
- Empty request body will fail validation

**Controller** (`ConversationController.php:64-74`):
```php
public function update(UpdateConversationRequest $request, Conversation $conversation)
{
    $this->authorize('update', $conversation);

    $this->conversationService->updateConversation($conversation, $request->validated());

    return $this->updated(
        new ConversationResource($conversation->fresh()),
        'Conversation updated successfully'
    );
}
```

**Key Details**:
- **`$conversation->fresh()`**: Reloads model from database to get updated values
- **Why refresh**: Ensures timestamps (`updated_at`) reflect database state
- **Authorization**: Checks ownership via policy

**Policy** (`ConversationPolicy.php:45-54`):
```php
public function update(User $user, Conversation $conversation): bool
{
    // Users with permission can update any conversation
    if ($user->hasPermissionTo('update any conversation')) {
        return true;
    }

    // Users can only update their own conversations
    return $user->id === $conversation->user_id && $user->hasPermissionTo('update own conversations');
}
```

**Service** (`ConversationService.php:39-42`):
```php
public function updateConversation(Conversation $conversation, array $data): bool
{
    return $this->conversationRepository->update($conversation, $data);
}
```

**Repository** (`ConversationRepository.php:38-41`):
```php
public function update(Conversation $conversation, array $data): bool
{
    return $conversation->update($data);
}
```
- **`$conversation->update($data)`**: Eloquent update method
- Updates only fields in `$data` array
- Automatically updates `updated_at` timestamp
- Returns `true` on success

**Example Request**:
```http
PATCH /api/v1/conversations/123 HTTP/1.1
Content-Type: application/json
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

{
  "title": "Q4 Planning - Updated"
}
```

**Example Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": {
    "id": 123,
    "title": "Q4 Planning - Updated",
    "created_at": "2025-12-15T10:00:00Z",
    "updated_at": "2025-12-16T16:00:00Z"
  },
  "message": "Conversation updated successfully"
}
```

**Error - Validation**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The title field is required.",
    "details": {
      "fields": {
        "title": ["The title field is required."]
      }
    }
  }
}
```

---

## 5. DELETE /api/v1/conversations/{conversation} - Soft Delete Conversation

### 🎯 Purpose
Soft delete a conversation. The conversation is not permanently removed from the database, but marked as deleted and hidden from user's list. Can potentially be restored later.

### 📊 Flow Diagram
```
HTTP Request (DELETE /api/v1/conversations/123)
    ↓
Route Matching → Middleware → Route Model Binding → Controller → Policy → Service → Repository → Model → Database
                                                                                                            ↓
HTTP Response (200 OK with message) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 KEY POINTS

**Route**: `routes/api/conversation.php:28`
```php
Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy'])
    ->name('conversations.destroy');
```

**Controller** (`ConversationController.php:77-84`):
```php
public function destroy(Conversation $conversation)
{
    $this->authorize('delete', $conversation);

    $this->conversationService->deleteConversation($conversation);

    return $this->deleted('Conversation deleted successfully');
}
```

**Key Details**:
- **No request validation**: DELETE requests don't have body
- **No resource returned**: Just success message
- **`deleted()` method**: Returns 200 OK (not 204 No Content)

**Policy** (`ConversationPolicy.php:58-67`):
```php
public function delete(User $user, Conversation $conversation): bool
{
    // Users with permission can delete any conversation
    if ($user->hasPermissionTo('delete any conversation')) {
        return true;
    }

    // Users can only delete their own conversations
    return $user->id === $conversation->user_id && $user->hasPermissionTo('delete own conversations');
}
```

**Service** (`ConversationService.php:45-48`):
```php
public function deleteConversation(Conversation $conversation): bool
{
    return $this->conversationRepository->delete($conversation);
}
```

**Repository** (`ConversationRepository.php:44-47`):
```php
public function delete(Conversation $conversation): bool
{
    return $conversation->delete();
}
```
- **Soft Delete**: Sets `deleted_at` to current timestamp
- **Not permanently removed**: Record still in database
- **Cascade behavior**: Messages remain (not deleted with conversation)

**What Happens in Database**:
```
Before: deleted_at = NULL
After:  deleted_at = '2025-12-16 16:00:00'
```

**Effect on Queries**:
- `Conversation::find(123)`: Returns null (soft-deleted excluded by default)
- `Conversation::withTrashed()->find(123)`: Returns the conversation
- `Conversation::onlyTrashed()->find(123)`: Returns only if deleted

**Example Request**:
```http
DELETE /api/v1/conversations/123 HTTP/1.1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Example Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "message": "Conversation deleted successfully"
}
```

**Error - Not Found** (already deleted):
```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "message": "No query results for model [App\\Models\\Conversations\\Conversation] 123"
}
```

**Error - Unauthorized**:
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "This action is unauthorized."
}
```

---

## 6. POST /api/v1/conversations/{conversation}/messages - Send Message

### 🎯 Purpose
Send a message to a conversation. This creates a user message and automatically generates a bot response. Supports file attachments.

### 📊 Flow Diagram
```
HTTP Request (POST with JSON/multipart body)
    ↓
Route Matching → Middleware → Route Model Binding → Validation → Controller → Policies → Service → Repository → Database
                                                                                    ↓
                                                                           Generate Bot Response
                                                                                    ↓
HTTP Response (201 Created with both messages) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 DEEP DIVE

**Route**: `routes/api/conversation.php:32`
```php
Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])
    ->name('messages.store');
```

**Request Validation** (`StoreMessageRequest.php:16-24`):
```php
public function rules(): array
{
    return [
        'content' => ['required', 'string'],
        'ai_model_id' => ['required', 'integer', 'exists:ai_models,id'],
        'attachments' => ['nullable', 'array'],
        'attachments.*' => ['file', 'max:10240'], // 10MB max per file
    ];
}
```

**Validation Breakdown**:

| Field | Rules | Meaning |
|-------|-------|---------|
| `content` | `required`, `string` | Message text, cannot be empty |
| `ai_model_id` | `required`, `integer`, `exists:ai_models,id` | Must be valid AI model ID from database |
| `attachments` | `nullable`, `array` | Optional array of files |
| `attachments.*` | `file`, `max:10240` | Each file max 10MB (10240 KB) |

**Key Points**:
- **`exists:ai_models,id`**: Validates AI model exists in database
- **User chooses AI model**: GPT-4, Claude, etc.
- **File validation**: Automatic mime type and size checking
- **Multiple files**: Array allows multiple attachments

**Controller** (`MessageController.php:20-38`):
```php
public function store(StoreMessageRequest $request, Conversation $conversation)
{
    // Check if user can create messages in this conversation
    $this->authorize('view', $conversation);
    $this->authorize('create', Message::class);

    $message = $this->messageService->createUserMessage($conversation, $request->validated());

    // Generate bot response
    $botMessage = $this->messageService->generateBotResponse($conversation, $message);

    return $this->created(
        [
            'user_message' => new MessageResource($message),
            'bot_message' => new MessageResource($botMessage),
        ],
        'Message sent successfully'
    );
}
```

**Line-by-Line**:

**Line 23**: `$this->authorize('view', $conversation)`
- Ensures user has access to this conversation
- Can only send messages to own conversations
- Reuses conversation view policy

**Line 24**: `$this->authorize('create', Message::class)`
- Checks user has permission to create messages
- Could restrict based on user tier, subscription, etc.

**Line 26**: `$message = $this->messageService->createUserMessage($conversation, $request->validated())`
- Creates user's message in database
- Handles file attachments
- Returns Message model with attachments loaded

**Line 29**: `$botMessage = $this->messageService->generateBotResponse($conversation, $message)`
- Generates AI response (currently placeholder)
- TODO: Integrate with actual AI service (OpenAI, Claude, etc.)
- Links bot message to user message via `parent_message_id`

**Lines 31-37**: Returns both messages
- **User message**: What the user sent
- **Bot message**: AI's response
- **Status 201**: Resource created
- **Why return both**: Client can immediately display full conversation turn

**Service - Create User Message** (`MessageService.php:23-50`):
```php
public function createUserMessage(Conversation $conversation, array $data): Message
{
    $messageData = [
        'conversation_id' => $conversation->id,
        'sender' => 'user',
        'content' => $data['content'],
        'ai_model_id' => $data['ai_model_id'], // User specifies which AI to use
        'status' => 'completed',
    ];

    $message = $this->messageRepository->create($messageData);

    // Handle attachments if provided
    if (!empty($data['attachments'])) {
        foreach ($data['attachments'] as $file) {
            // Create attachment record
            $attachment = $message->attachments()->create([]);
            
            // Add file to media library
            $attachment->addMedia($file)->toMediaCollection('attachments');
        }
    }

    return $message->load('attachments.media');
}
```

**Message Data Structure**:
- **`conversation_id`**: Links to conversation
- **`sender`**: `'user'` (bot messages have `'bot'`)
- **`content`**: User's message text
- **`ai_model_id`**: Which AI model to use for response
- **`status`**: `'completed'` (user messages don't have pending state)

**Attachment Handling** (Lines 36-44):
- **Spatie Media Library**: Package for handling file uploads
- **Process**:
  1. Create `MessageAttachment` record
  2. Upload file to storage (S3, local, etc.)
  3. Store metadata (filename, size, mime type) in `media` table
  4. Link media to attachment
- **Storage**: Files stored outside database (filesystem/cloud)
- **Database**: Only stores paths and metadata

**Service - Generate Bot Response** (`MessageService.php:53-67`):
```php
public function generateBotResponse(Conversation $conversation, Message $userMessage): Message
{
    // This would integrate with your AI service
    // For now, creating a placeholder bot message
    $botMessageData = [
        'conversation_id' => $conversation->id,
        'sender' => 'bot',
        'content' => 'This is a placeholder bot response. Integrate with AI service.',
        'ai_model_id' => $userMessage->ai_model_id, // Use same AI model as user message
        'parent_message_id' => $userMessage->id,
        'status' => 'completed',
    ];

    return $this->messageRepository->create($botMessageData);
}
```

**Bot Message Structure**:
- **`sender`**: `'bot'` (distinguishes from user messages)
- **`parent_message_id`**: Links to user's message (creates message thread)
- **`ai_model_id`**: Same as user message (GPT-4 responds to GPT-4 question)
- **`content`**: AI-generated response (placeholder for now)

**Real AI Integration Would**:
1. Send user message + conversation history to AI API
2. Stream response back (for real-time display)
3. Handle errors, timeouts, rate limits
4. Save final response to database
5. Update status: `pending` → `streaming` → `completed` or `failed`

**Example Request - Text Only**:
```http
POST /api/v1/conversations/123/messages HTTP/1.1
Content-Type: application/json
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

{
  "content": "Explain quantum computing",
  "ai_model_id": 1
}
```

**Example Request - With Attachments** (multipart/form-data):
```http
POST /api/v1/conversations/123/messages HTTP/1.1
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

------WebKitFormBoundary
Content-Disposition: form-data; name="content"

Review this document and summarize
------WebKitFormBoundary
Content-Disposition: form-data; name="ai_model_id"

1
------WebKitFormBoundary
Content-Disposition: form-data; name="attachments[]"; filename="document.pdf"
Content-Type: application/pdf

[binary file data]
------WebKitFormBoundary--
```

**Example Response**:
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "data": {
    "user_message": {
      "id": 501,
      "conversation_id": 123,
      "sender": "user",
      "content": "Explain quantum computing",
      "ai_model_id": 1,
      "parent_message_id": null,
      "original_message_id": null,
      "regeneration_index": 0,
      "status": "completed",
      "created_at": "2025-12-16T16:30:00Z",
      "updated_at": "2025-12-16T16:30:00Z",
      "attachments": [],
      "feedback": null,
      "ai_model": null
    },
    "bot_message": {
      "id": 502,
      "conversation_id": 123,
      "sender": "bot",
      "content": "This is a placeholder bot response. Integrate with AI service.",
      "ai_model_id": 1,
      "parent_message_id": 501,
      "original_message_id": null,
      "regeneration_index": 0,
      "status": "completed",
      "created_at": "2025-12-16T16:30:01Z",
      "updated_at": "2025-12-16T16:30:01Z",
      "attachments": [],
      "feedback": null,
      "ai_model": {
        "id": 1,
        "name": "GPT-4"
      }
    }
  },
  "message": "Message sent successfully"
}
```

**Policy Checks**:

**Conversation Access** (`ConversationPolicy::view()`):
- User must own conversation OR have admin permission
- Prevents sending messages to other users' conversations

**Message Creation** (`MessagePolicy::create()` - line 14-17):
```php
public function create(User $user): bool
{
    return $user->hasPermissionTo('create message');
}
```
- Basic permission check
- Could add rate limiting logic here
- Could check user's subscription tier

**Error Responses**:

**Invalid AI Model**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The selected ai model id is invalid.",
    "details": {
      "fields": {
        "ai_model_id": ["The selected ai model id is invalid."]
      }
    }
  }
}
```

**File Too Large**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The attachments.0 must not be greater than 10240 kilobytes.",
    "details": {
      "fields": {
        "attachments.0": ["The attachments.0 must not be greater than 10240 kilobytes."]
      }
    }
  }
}
```

**Unauthorized Conversation Access**:
```json
{
  "message": "This action is unauthorized."
}
```

---

## 7. POST /api/v1/messages/{message}/regenerate - Regenerate Bot Response

### 🎯 Purpose
Regenerate a bot's response to a user message. Creates a new version of the bot response without deleting the original. All regenerations share the same feedback (like/dislike) as the original.

### 📊 Flow Diagram
```
HTTP Request (POST /api/v1/messages/502/regenerate)
    ↓
Route Matching → Middleware → Route Model Binding → Controller → Policy → Service → Repository → Database
                                                                                              ↓
HTTP Response (200 OK with new message) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 DEEP DIVE

**Route**: `routes/api/conversation.php:35`
```php
Route::post('messages/{message}/regenerate', [MessageController::class, 'regenerate'])
    ->name('messages.regenerate');
```

**Route Model Binding**:
- `{message}` parameter automatically loads Message model
- 404 if message doesn't exist
- No additional validation needed (no request body)

**Controller** (`MessageController.php:41-51`):
```php
public function regenerate(Message $message)
{
    $this->authorize('regenerate', $message);

    $newMessage = $this->messageService->regenerateMessage($message);

    return $this->okWithData(
        new MessageResource($newMessage),
        'Message regenerated successfully'
    );
}
```

**Simple Flow**:
1. Authorize (check if user can regenerate THIS message)
2. Call service to create new regeneration
3. Return new message (not the old one)

**Policy** (`MessagePolicy.php:32-46`):
```php
public function regenerate(User $user, Message $message): bool
{
    // Can only regenerate bot messages
    if ($message->sender !== 'bot') {
        return false;
    }

    // Users with permission can regenerate any message
    if ($user->hasPermissionTo('regenerate any message')) {
        return true;
    }

    // Users can only regenerate messages in their own conversations
    return $user->id === $message->conversation->user_id && $user->hasPermissionTo('regenerate own messages');
}
```

**Authorization Rules**:
1. **Must be bot message**: Can't regenerate user messages (line 35-37)
2. **Admin permission**: Can regenerate any bot message
3. **Ownership**: Can only regenerate in own conversations

**Why Can't Regenerate User Messages**:
- User messages are user's input (immutable)
- Only bot responses can be regenerated
- Regeneration = asking AI to try again

**Service** (`MessageService.php:70-98`):
```php
public function regenerateMessage(Message $message): Message
{
    // Get the parent user message
    $userMessage = $message->parent;
    
    // Determine the original message ID and next regeneration index
    $originalMessageId = $message->original_message_id ?? $message->id;
    
    // Get the highest regeneration index for this original message
    $maxIndex = Message::where('original_message_id', $originalMessageId)
        ->orWhere('id', $originalMessageId)
        ->max('regeneration_index');
    
    $nextIndex = ($maxIndex ?? 0) + 1;

    // Generate a new bot response (keep the old one, don't delete)
    $botMessageData = [
        'conversation_id' => $message->conversation_id,
        'sender' => 'bot',
        'content' => 'This is regenerated response #' . $nextIndex . '. Integrate with AI service.',
        'ai_model_id' => $message->ai_model_id,
        'parent_message_id' => $userMessage->id,
        'original_message_id' => $originalMessageId,
        'regeneration_index' => $nextIndex,
        'status' => 'completed',
    ];

    return $this->messageRepository->create($botMessageData);
}
```

**Regeneration Logic Explained**:

**Line 73**: `$userMessage = $message->parent`
- Gets the user message that prompted this bot response
- Example: User asks "What is AI?" → Bot responds → User regenerates
- Need original user question to regenerate response

**Line 76**: `$originalMessageId = $message->original_message_id ?? $message->id`
- **If regenerating a regeneration**: Use its `original_message_id`
- **If regenerating original**: Use its own `id`
- Ensures all regenerations point to the first bot response

**Example Regeneration Chain**:
```
User Message (500) → Bot Response (501, index=0, original=null)
                  → Regeneration 1 (502, index=1, original=501)
                  → Regeneration 2 (503, index=2, original=501)
                  → Regeneration 3 (504, index=3, original=501)
```

**Lines 79-81**: Find highest regeneration index
```php
$maxIndex = Message::where('original_message_id', $originalMessageId)
    ->orWhere('id', $originalMessageId)
    ->max('regeneration_index');
```
- Finds all regenerations of this message
- Gets the highest `regeneration_index`
- **Why needed**: Ensures unique, sequential indexing

**Line 83**: `$nextIndex = ($maxIndex ?? 0) + 1`
- Increments index for new regeneration
- First regeneration = 1, second = 2, etc.

**New Message Data** (Lines 86-95):
- **`original_message_id`**: Links to first bot response
- **`regeneration_index`**: Sequential number
- **`parent_message_id`**: Same as original (same user message)
- **`ai_model_id`**: Same AI model as original
- **`conversation_id`**: Same conversation

**Why Keep Old Messages**:
- User might prefer an earlier version
- Can compare responses side-by-side
- History preservation
- Undo capability

**Feedback Inheritance**:
- All regenerations share feedback of original message
- User likes/dislikes the original, applies to all versions
- See `MessageResource.php:12-20` for feedback resolution logic

**Example Request**:
```http
POST /api/v1/messages/502/regenerate HTTP/1.1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Example Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": {
    "id": 503,
    "conversation_id": 123,
    "sender": "bot",
    "content": "This is regenerated response #1. Integrate with AI service.",
    "ai_model_id": 1,
    "parent_message_id": 500,
    "original_message_id": 502,
    "regeneration_index": 1,
    "status": "completed",
    "created_at": "2025-12-16T16:35:00Z",
    "updated_at": "2025-12-16T16:35:00Z",
    "attachments": [],
    "feedback": {
      "id": 10,
      "message_id": 502,
      "rating": "like",
      "comment": null,
      "created_at": "2025-12-16T16:31:00Z"
    },
    "ai_model": {
      "id": 1,
      "name": "GPT-4"
    }
  },
  "message": "Message regenerated successfully"
}
```

**Note**: Feedback shows ID 502 (original message), but applies to this regeneration too.

**Error - Not a Bot Message**:
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "This action is unauthorized."
}
```
(Policy returns false for user messages)

**Error - Not Your Conversation**:
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "This action is unauthorized."
}
```

---

## 8. POST /api/v1/messages/{message}/attachments - Upload Attachments

### 🎯 Purpose
Add file attachments to an existing message. Supports uploading multiple files at once (max 5). Uses Spatie Media Library for file handling.

### 📊 Flow Diagram
```
HTTP Request (POST with multipart/form-data)
    ↓
Route Matching → Middleware → Route Model Binding → Controller → Validation → Authorization → File Upload → Database
                                                                                                              ↓
HTTP Response (201 Created with attachment URLs) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 KEY POINTS

**Route**: `routes/api/conversation.php:39`
```php
Route::post('messages/{message}/attachments', [MessageAttachmentController::class, 'store'])
    ->name('messages.attachments.store');
```

**Controller** (`MessageAttachmentController.php:15-63`):
```php
public function store(Request $request, Message $message)
{
    // Check if user owns the conversation this message belongs to
    $this->authorize('view', $message->conversation);

    // Validate the request
    $validator = Validator::make($request->all(), [
        'files' => 'required|array|max:5',
        'files.*' => 'required|file|max:' . config('support.maximum_attachment_size'),
    ]);

    if ($validator->fails()) {
        return $this->unprocessableEntity($validator->errors());
    }

    $attachments = [];

    foreach ($request->file('files') as $file) {
        // Create a new attachment record
        $attachment = MessageAttachment::create([
            'message_id' => $message->id,
        ]);

        // Add the file to media library
        try {
            $attachment->addMedia($file)
                ->toMediaCollection('attachments');
            
            $attachments[] = $attachment->fresh();
        } catch (\Exception $e) {
            // If media upload fails, delete the attachment record
            $attachment->delete();
            
            return $this->serverError('Failed to upload file: ' . $e->getMessage());
        }
    }

    return $this->created(
        MessageAttachmentResource::collection($attachments),
        'Attachment(s) uploaded successfully'
    );
}
```

**Validation Rules**:
- **`files`**: Required array, max 5 files
- **`files.*`**: Each file validated (size from config)
- **Config-based size limit**: Centralized in `config/support.php`

**Authorization**:
- Checks if user has access to conversation (via `view` policy)
- Only conversation owner can add attachments

**File Upload Process**:
1. Create `MessageAttachment` record (database)
2. Upload file to storage (Spatie Media Library)
3. Link file to attachment
4. On error: Rollback (delete attachment record)

**Spatie Media Library**:
- Handles file storage (local, S3, etc.)
- Generates unique filenames
- Stores metadata (size, mime type, etc.)
- Automatic URL generation
- Can generate thumbnails for images

**Error Handling**:
- Try-catch around file upload
- If upload fails: Clean up database record
- Prevents orphaned database entries

**Example Request**:
```http
POST /api/v1/messages/500/attachments HTTP/1.1
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

------WebKitFormBoundary
Content-Disposition: form-data; name="files[]"; filename="document.pdf"
Content-Type: application/pdf

[binary file data]
------WebKitFormBoundary
Content-Disposition: form-data; name="files[]"; filename="image.jpg"
Content-Type: image/jpeg

[binary file data]
------WebKitFormBoundary--
```

**Example Response**:
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "data": [
    {
      "id": 50,
      "message_id": 500,
      "file_name": "document.pdf",
      "file_size": 245678,
      "mime_type": "application/pdf",
      "url": "https://storage.example.com/attachments/abc123-document.pdf",
      "created_at": "2025-12-16T17:00:00Z"
    },
    {
      "id": 51,
      "message_id": 500,
      "file_name": "image.jpg",
      "file_size": 123456,
      "mime_type": "image/jpeg",
      "url": "https://storage.example.com/attachments/def456-image.jpg",
      "created_at": "2025-12-16T17:00:00Z"
    }
  ],
  "message": "Attachment(s) uploaded successfully"
}
```

**Error - Too Many Files**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "You can upload a maximum of 5 files at once",
    "details": {
      "fields": {
        "files": ["You can upload a maximum of 5 files at once"]
      }
    }
  }
}
```

**Error - File Too Large**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Each file must not exceed 10MB",
    "details": {
      "fields": {
        "files.0": ["Each file must not exceed 10MB"]
      }
    }
  }
}
```

---

## 9. DELETE /api/v1/attachments/{attachment} - Delete Attachment

### 🎯 Purpose
Delete a file attachment from a message. Removes both the database record and the physical file from storage.

### 📊 Flow Diagram
```
HTTP Request (DELETE /api/v1/attachments/50)
    ↓
Route Matching → Middleware → Route Model Binding → Controller → Authorization → Delete File → Delete Record
                                                                                                     ↓
HTTP Response (204 No Content) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 KEY POINTS

**Route**: `routes/api/conversation.php:42`
```php
Route::delete('attachments/{attachment}', [MessageAttachmentController::class, 'destroy'])
    ->name('attachments.destroy');
```

**Controller** (`MessageAttachmentController.php:66-75`):
```php
public function destroy(MessageAttachment $attachment)
{
    // Check if user owns the conversation
    $this->authorize('view', $attachment->message->conversation);

    // Delete the attachment (media library will automatically delete the files)
    $attachment->delete();

    return $this->noContent('Attachment deleted successfully');
}
```

**Simple Flow**:
1. Route model binding loads attachment
2. Authorization checks conversation ownership
3. Delete attachment (cascade deletes media files)
4. Return 204 No Content

**Authorization Chain**:
- `$attachment->message->conversation` traverses relationships
- Checks if user owns conversation
- Uses conversation `view` policy

**Cascade Deletion**:
- Spatie Media Library has observers
- When `MessageAttachment` deleted → triggers media deletion
- Physical files removed from storage (S3, local, etc.)
- Media table records also deleted

**Why 204 No Content**:
- RESTful standard for successful deletion
- No response body needed (resource is gone)
- Different from 200 OK which expects body

**Example Request**:
```http
DELETE /api/v1/attachments/50 HTTP/1.1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Example Response**:
```http
HTTP/1.1 204 No Content
```
(No response body)

**Error - Not Found**:
```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "message": "No query results for model [App\\Models\\Messages\\MessageAttachment] 50"
}
```

**Error - Unauthorized**:
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "This action is unauthorized."
}
```

---

## 10. PUT /api/v1/messages/{message}/feedback - Set/Update Feedback

### 🎯 Purpose
Give feedback (like/dislike) on a bot message. If feedback already exists, updates it. Feedback is applied to the original message (shared across regenerations).

### 📊 Flow Diagram
```
HTTP Request (PUT with JSON body)
    ↓
Route Matching → Middleware → Route Model Binding → Validation → Controller → Policy → Service → Database
                                                                                                    ↓
                                                                            Check if feedback exists
                                                                                    ↓
                                                                      Update existing OR Create new
                                                                                    ↓
HTTP Response (200 OK with feedback) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 DEEP DIVE

**Route**: `routes/api/conversation.php:46`
```php
Route::put('messages/{message}/feedback', [MessageFeedbackController::class, 'update'])
    ->name('messages.feedback.update');
```

**Request Validation** (`StoreFeedbackRequest.php:17-22`):
```php
public function rules(): array
{
    return [
        'feedback_type' => ['required', Rule::in(['like', 'dislike'])],
    ];
}
```

**Validation Details**:
- **`feedback_type`**: Must be exactly `'like'` or `'dislike'`
- **`Rule::in()`**: Enum-style validation
- **Only allowed field**: Extra fields rejected

**Controller** (`MessageFeedbackController.php:20-33`):
```php
public function update(StoreFeedbackRequest $request, Message $message)
{
    $this->authorize('create', [MessageFeedback::class, $message]);

    $user = Auth::user();
    $feedbackType = $request->validated()['feedback_type'];

    $feedback = $this->feedbackService->giveFeedback($message, $user, $feedbackType);

    return $this->okWithData(
        new MessageFeedbackResource($feedback),
        'Feedback updated successfully'
    );
}
```

**Line-by-Line**:

**Line 22**: `$this->authorize('create', [MessageFeedback::class, $message])`
- Passes both class and message to policy
- Policy can check message ownership
- Array syntax: First item is class, rest are additional parameters

**Line 24**: `$user = Auth::user()`
- Gets authenticated user
- Feedback linked to user (one feedback per user per message)

**Line 25**: `$feedbackType = $request->validated()['feedback_type']`
- Extracts validated feedback type (`'like'` or `'dislike'`)

**Line 27**: `$feedback = $this->feedbackService->giveFeedback($message, $user, $feedbackType)`
- Delegates to service layer
- Handles both create and update logic

**Service** (`MessageFeedbackService.php:12-34`):
```php
public function giveFeedback(Message $message, User $user, string $feedbackType): MessageFeedback
{
    // If this is a regenerated message, apply feedback to the original message
    $targetMessageId = $message->original_message_id ?? $message->id;
    
    // Check if user already gave feedback to this message (or its original)
    $existingFeedback = MessageFeedback::where('message_id', $targetMessageId)
        ->where('user_id', $user->id)
        ->first();

    if ($existingFeedback) {
        // Update existing feedback
        $existingFeedback->update(['feedback_type' => $feedbackType]);
        return $existingFeedback->fresh();
    }

    // Create new feedback on the original message
    return MessageFeedback::create([
        'message_id' => $targetMessageId,
        'user_id' => $user->id,
        'feedback_type' => $feedbackType,
    ]);
}
```

**Critical Logic**:

**Line 15**: `$targetMessageId = $message->original_message_id ?? $message->id`
- **If regeneration**: Use original message ID
- **If original**: Use own ID
- **Result**: All regenerations share one feedback record

**Example**:
```
Original message (501)  → User likes → Feedback on 501
Regeneration 1 (502)    → User likes → Updates feedback on 501 (not creates new)
Regeneration 2 (503)    → User dislikes → Updates same feedback on 501
```

**Lines 18-20**: Check existing feedback
- Query by message ID and user ID
- One user can only have one feedback per message
- Returns existing or null

**Lines 22-25**: Update path
- User already gave feedback
- Change like → dislike or vice versa
- Returns updated feedback

**Lines 28-32**: Create path
- No existing feedback
- Create new record
- Links to original message (not regeneration)

**Why This Design**:
- Feedback represents user's opinion on the response content
- Regenerations are different wordings of same answer
- User likes/dislikes the answer itself, not each version
- Changing feedback on regeneration updates for all versions

**Example Request**:
```http
PUT /api/v1/messages/502/feedback HTTP/1.1
Content-Type: application/json
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

{
  "feedback_type": "like"
}
```

**Example Response** (First time):
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": {
    "id": 10,
    "message_id": 502,
    "user_id": 42,
    "feedback_type": "like",
    "created_at": "2025-12-16T17:30:00Z",
    "updated_at": "2025-12-16T17:30:00Z"
  },
  "message": "Feedback updated successfully"
}
```

**Example Request** (Change to dislike):
```http
PUT /api/v1/messages/502/feedback HTTP/1.1
Content-Type: application/json
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

{
  "feedback_type": "dislike"
}
```

**Example Response** (Update):
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": {
    "id": 10,
    "message_id": 502,
    "user_id": 42,
    "feedback_type": "dislike",
    "created_at": "2025-12-16T17:30:00Z",
    "updated_at": "2025-12-16T17:31:00Z"
  },
  "message": "Feedback updated successfully"
}
```

**Error - Invalid Feedback Type**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The selected feedback type is invalid.",
    "details": {
      "fields": {
        "feedback_type": ["The selected feedback type is invalid."]
      }
    }
  }
}
```

---

## 11. DELETE /api/v1/messages/{message}/feedback - Remove Feedback

### 🎯 Purpose
Remove feedback from a message. User can take back their like/dislike.

### 📊 Flow Diagram
```
HTTP Request (DELETE /api/v1/messages/502/feedback)
    ↓
Route Matching → Middleware → Route Model Binding → Controller → Find Feedback → Authorization → Delete
                                                                                                    ↓
HTTP Response (200 OK) ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
```

### 🔍 KEY POINTS

**Route**: `routes/api/conversation.php:49`
```php
Route::delete('messages/{message}/feedback', [MessageFeedbackController::class, 'destroy'])
    ->name('messages.feedback.destroy');
```

**Controller** (`MessageFeedbackController.php:36-52`):
```php
public function destroy(Message $message)
{
    // Get the user's feedback for this message
    $feedback = $message->feedback()
        ->where('user_id', Auth::id())
        ->first();

    if (!$feedback) {
        return $this->notFound('Feedback not found');
    }

    $this->authorize('delete', $feedback);

    $this->feedbackService->removeFeedback($feedback);

    return $this->deleted('Feedback removed successfully');
}
```

**Flow Details**:

**Lines 39-41**: Find user's feedback
- Query feedback relationship on message
- Filter by authenticated user ID
- User can only delete own feedback

**Lines 43-45**: Handle not found
- If no feedback exists, return 404
- Custom error message
- Prevents authorization on null

**Line 47**: Authorization check
- Verifies user can delete this feedback
- Policy checks ownership

**Line 49**: Delete via service
```php
// MessageFeedbackService.php:37-40
public function removeFeedback(MessageFeedback $feedback): bool
{
    return $feedback->delete();
}
```
- Simple deletion
- Returns boolean

**Example Request**:
```http
DELETE /api/v1/messages/502/feedback HTTP/1.1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Example Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "message": "Feedback removed successfully"
}
```

**Error - No Feedback Exists**:
```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "message": "Feedback not found"
}
```

**Error - Trying to Delete Another User's Feedback**:
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "This action is unauthorized."
}
```

---

## Complete API Summary

### Conversation Routes
1. **GET /conversations** - List all conversations (paginated, filterable)
2. **POST /conversations** - Create new conversation
3. **GET /conversations/{id}** - Get conversation with messages
4. **PATCH /conversations/{id}** - Update conversation title
5. **DELETE /conversations/{id}** - Soft delete conversation

### Message Routes
6. **POST /conversations/{id}/messages** - Send message (creates user + bot message)
7. **POST /messages/{id}/regenerate** - Regenerate bot response

### Attachment Routes
8. **POST /messages/{id}/attachments** - Upload file attachments
9. **DELETE /attachments/{id}** - Delete attachment

### Feedback Routes
10. **PUT /messages/{id}/feedback** - Like/dislike message
11. **DELETE /messages/{id}/feedback** - Remove feedback

### Architecture Layers
- **Routes** → **Middleware** → **Validation** → **Controller** → **Policy** → **Service** → **Repository** → **Model** → **Database**
- Each layer has single responsibility
- Separation enables testing, maintenance, scalability

---

