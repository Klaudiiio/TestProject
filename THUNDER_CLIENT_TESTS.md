# Thunder Client Test Guide for Laravel Sanctum

## Prerequisites
1. Start your Laravel server: `php artisan serve`
2. Server should be running at `http://127.0.0.1:8000`

---

## Test 1: Register a User (Admin)

**Method:** POST
**URL:** `http://127.0.0.1:8000/api/auth/register`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "name": "Admin User",
  "email": "admin@test.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "admin"
}
```

**Expected Response:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@test.com",
    "role": "admin"
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Important:** Copy the `token` value from the response!

---

## Test 2: Register Another User (Student)

**Method:** POST
**URL:** `http://127.0.0.1:8000/api/auth/register`

**Body (JSON):**
```json
{
  "name": "John Student",
  "email": "john@test.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "student"
}
```

---

## Test 3: Login

**Method:** POST
**URL:** `http://127.0.0.1:8000/api/auth/login`

**Body (JSON):**
```json
{
  "email": "whatever@example.com",
  "password": "doesntmatter",
  "role": "admin"   // this field determines the role you will receive
}
```

> any valid email/password is accepted; the server creates or updates a user
> record behind the scenes but uses only the `role` value for authorization.

**Expected Response:**
```json
{
  "message": "Login successful",
  "role": "admin",
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

> You can hit `/api/auth/me` afterwards if you want the full user object.

---

## Test 4: Get Current User (Protected)

**Method:** GET
**URL:** `http://127.0.0.1:8000/api/auth/me`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Expected Response:**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@test.com",
    "role": "admin"
  }
}
```

---

## Test 5: Logout

**Method:** POST
**URL:** `http://127.0.0.1:8000/api/auth/logout`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

**Expected Response:**
```json
{
  "message": "Logout successful"
}
```

---

## Test 6: Get All Users (Protected)

**Method:** GET
**URL:** `http://127.0.0.1:8000/api/users`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## Test 7: Create User (Public)

**Method:** POST
**URL:** `http://127.0.0.1:8000/api/users`

**Body (JSON):**
```json
{
  "name": "New User",
  "email": "newuser@test.com",
  "password": "password123"
}
```

---

## Quick Thunder Client Setup

1. Open Thunder Client in VSCode
2. Create a new Collection
3. Add requests for each test above
4. Use Environment Variables:
   - Set `base_url` = `http://127.0.0.1:8000`
   - Set `token` = (paste your token from login/register)

---

## Role Middleware Test

To test role-based access, add this route to `routes/api.php`:

```php
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin-only', function () {
    return response()->json(['message' => 'Admin only route']);
});
```

Then test with:
- Admin token → 200 OK
- Student token → 403 Forbidden
