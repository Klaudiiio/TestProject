# Role-Based Authorization Testing Guide

## Overview

This guide explains how to test each role and their functions in the Laravel Sanctum authorization system.

## Test Users

> *There are no fixed test accounts anymore.* any login attempt will be
accepted as long as the email is formatted correctly and a password string is
provided. the `role` parameter in the request payload dictates which role the
server assigns.

(Existing records are still kept so `/auth/me` works and you can look them up
via tinker if needed.)

> _Note: previous versions of this guide referenced a helper script such as
> `test_roles.php` but that file has been deleted to reduce clutter._

## Testing Methods

### Method 1: Automated Tests (Recommended)

```bash
# Run all tests
php artisan test

# Run only auth tests
php artisan test tests/Feature/AuthTest.php

# Run with verbose output
php artisan test --verbose
```

### Method 2: Quick manual examples
Below are minimal curl examples showing how each HTTP verb behaves for each role.
Replace `TOKEN` with the bearer token returned by `/api/auth/login`.

#### Admin (role=admin)
```bash
# create user
curl -X POST http://localhost:8000/api/users -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -d '{"name":"x","email":"x@a.com","password":"p","role":"student"}'

# read list
curl -X GET http://localhost:8000/api/users -H "Authorization: Bearer TOKEN"

# update user
curl -X PUT http://localhost:8000/api/users/2 -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -d '{"role":"teacher"}'

# delete user
curl -X DELETE http://localhost:8000/api/users/2 -H "Authorization: Bearer TOKEN"

# special admin route
curl -X GET http://localhost:8000/api/admin/dashboard -H "Authorization: Bearer TOKEN"
```

#### Chairman (role=chairman)
```bash
# cannot POST/PUT/DELETE users (403), but can GET
curl -X GET http://localhost:8000/api/users -H "Authorization: Bearer TOKEN"

# attempt admin route
curl -X GET http://localhost:8000/api/admin/dashboard -H "Authorization: Bearer TOKEN"  # => 403

# attempt to update user
curl -X PUT http://localhost:8000/api/users/2 -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -d '{"role":"student"}'  # => 403

# attempt to delete user
curl -X DELETE http://localhost:8000/api/users/2 -H "Authorization: Bearer TOKEN"  # => 403
```

#### Teacher (role=teacher)
```bash
# similar to chairman: GET list only, others 403
curl -X GET http://localhost:8000/api/users -H "Authorization: Bearer TOKEN"
curl -X POST http://localhost:8000/api/users -H "Authorization: Bearer TOKEN" ...  # => 403
curl -X PUT http://localhost:8000/api/users/2 -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -d '{"role":"student"}'  # => 403
curl -X DELETE http://localhost:8000/api/users/2 -H "Authorization: Bearer TOKEN"  # => 403
```

#### Student (role=student)
```bash
# can only GET own-level routes; user list still accessible but other actions 403
curl -X DELETE http://localhost:8000/api/users/1 -H "Authorization: Bearer TOKEN"  # => 403
curl -X GET http://localhost:8000/api/users -H "Authorization: Bearer TOKEN"
curl -X PUT http://localhost:8000/api/users/1 -H "Authorization: Bearer TOKEN" ...  # => 403
```
### Method 3: Manual Testing with cURL

#### Step 1: Login and Get Token

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "anyone@domain.com",
    "password": "anything",
    "role": "admin"       # role is what matters
  }'
```

(You can substitute any email/password pair; the server ignores them when
deciding the role.)


**Response:**
```json
{
  "message": "Login successful",
  "role": "admin",
  "token": "1|abcd1234efgh5678ijkl9012mnop3456"
}
```

#### Step 2: Test Endpoints with Token

```bash
# Save token
TOKEN="1|abcd1234efgh5678ijkl9012mnop3456"

# Test admin endpoint (allowed for admin)
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

## Role Hierarchies & Access Control

### Admin (Full Access)
```
✓ GET    /api/admin/dashboard
✓ GET    /api/users
✓ POST   /api/users
✓ GET    /api/users/{id}
✓ PUT    /api/users/{id}          (can change any role)
✓ DELETE /api/users/{id}
✓ GET    /api/chairman/*
✓ GET    /api/teachers/*
✓ GET    /api/students/*
```

**Test Commands:**
```bash
TOKEN="ADMIN_TOKEN"

# Test dashboard access
curl http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer $TOKEN"

# Test create user
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"New User","email":"user@test.com","password":"pass123","role":"student"}'

# Test update user role
curl -X PUT http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role":"teacher"}'

# Test delete user
curl -X DELETE http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer $TOKEN"
```

### Chairman (Level 2)
```
✗ GET    /api/admin/dashboard     (→ 403)
✓ GET    /api/chairman/*
✓ GET    /api/teachers/*
✓ GET    /api/students/*
✓ GET    /api/users               (view only)
```

**Test Commands:**
```bash
TOKEN="CHAIRMAN_TOKEN"

# This should work
curl http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN"

# This should return 403
curl http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

### Teacher (Level 3)
```
✗ GET    /api/admin/dashboard     (→ 403)
✗ GET    /api/chairman/*          (→ 403)
✓ GET    /api/teachers/*
✓ GET    /api/students/*
✓ GET    /api/users               (view only)
```

### Student (Level 4)
```
✗ GET    /api/admin/dashboard     (→ 403)
✗ GET    /api/chairman/*          (→ 403)
✗ GET    /api/teachers/*          (→ 403)
✓ GET    /api/students/*
✓ GET    /api/users               (view only)
```

## Testing Scenarios

### Scenario 1: Admin User Management

**Goal:** Verify admin can create, update, and delete users

```bash
# 1. Login as admin
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | grep -o '"token":"[^"]*' | cut -d'"' -f4)

# 2. Create new user
USER=$(curl -s -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"test123","role":"student"}')

USER_ID=$(echo $USER | grep -o '"id":[0-9]*' | cut -d':' -f2)
echo "Created user ID: $USER_ID"

# 3. Update user to teacher
curl -X PUT http://localhost:8000/api/users/$USER_ID \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role":"teacher"}'

# 4. Delete user
curl -X DELETE http://localhost:8000/api/users/$USER_ID \
  -H "Authorization: Bearer $TOKEN"
```

### Scenario 2: Verify Role-Based Restrictions

**Goal:** Ensure non-admin users cannot access admin features

```bash
# Get token for student
STUDENT_TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"student@example.com","password":"password"}' \
  | grep -o '"token":"[^"]*' | cut -d'"' -f4)

# Try to access admin dashboard (should fail with 403)
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer $STUDENT_TOKEN" \
  -v
```

Expected response:
```
< HTTP/1.1 403 Forbidden
{
  "message": "Forbidden. You do not have permission to access this resource."
}
```

### Scenario 3: Invalid Token Handling

**Goal:** Verify API rejects invalid/missing tokens

```bash
# No token (should return 401)
curl http://localhost:8000/api/admin/dashboard \
  -v

# Invalid token (should return 401)
curl http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer invalid_token" \
  -v

# Expired token (should return 401)
curl http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer 1|abc123" \
  -v
```

Expected responses:
```
401: Unauthenticated
```

## Testing User Profile Operations

### Get Current User Info

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TOKEN"
```

### Update Own Profile

```bash
curl -X PUT http://localhost:8000/api/users/{id} \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"New Name"}'
```

### List All Users (Authenticated Users Only)

```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer TOKEN"
```

## Verifying Role Methods

Test the role methods in the User model:

```php
php artisan tinker

# Test role methods
$user = \App\Models\User::first();
$user->isAdmin();      // boolean
$user->isChairman();   // boolean
$user->isTeacher();    // boolean
$user->isStudent();    // boolean
$user->hasRole(['admin', 'chairman']); // boolean
```

## Expected Test Results

### Passing Tests ✓

- Admin can access all endpoints
- Chairman is blocked from `/api/admin/*`
- Teacher is blocked from `/api/admin/*` and `/api/chairman/*`
- Student can only access `/api/students/*`
- Invalid tokens return 401
- Missing authentication returns 401
- Admin can create/update/delete users
- Non-admin cannot delete users or change roles
- All role methods return correct values

### Common Issues & Solutions

**Issue:** Getting 500 errors instead of 403/401

**Solution:** Check the Laravel log:
```bash
tail -f storage/logs/laravel.log
```

**Issue:** Token not being recognized

**Solution:** Ensure token format is correct:
```
Authorization: Bearer 1|abc123def456...
```

Not:
```
Authorization: 1|abc123def456...
```

**Issue:** CORS errors when testing from browser

**Solution:** Server is running. Make sure you're using the correct API URL:
```
http://localhost:8000/api/...
```

## Summary

Your role-based authorization system has:

- ✓ 4 defined roles with clear hierarchies
- ✓ Route protection using middleware
- ✓ Database-backed user roles
- ✓ Token-based authentication (Sanctum)
- ✓ Comprehensive test coverage
- ✓ Clear permission boundaries

Use the testing methods above to verify all functionality works as expected!
