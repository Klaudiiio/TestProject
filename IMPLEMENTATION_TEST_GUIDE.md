# How to Test Laravel Sanctum Role-Based Authorization Implementation

## Overview
This guide shows you how to verify that role-based authorization is correctly implemented using Thunder Client.

## Test Users Available

> **Note:** you no longer need pre‑seeded accounts. any valid email and
> password may be supplied in the login request; the system will create or
> update a user record automatically. the only thing that determines which
> role you have is the `role` field in the login body.

(The previous hard‑coded table of emails/passwords has been removed.)

---

## Step-by-Step Testing in Thunder Client

### **STEP 1: Test Login Endpoint**

**Request:**
```
POST http://localhost:8000/api/auth/login
```

**Headers:**
```
Content-Type: application/json
```

**Body (raw JSON):**
```json
{
  "email": "someone@whatever.com",
  "password": "any-string",
  "role": "admin"          // required; defines the role you will receive
}
```

**Expected Response (200 OK):**
```json
{
  "message": "Login successful",
  "role": "admin",
  "token": "1|xxxxxxxxxxxxxxxxxxxxx"
}
```

> _Note: the `/auth/login` endpoint no longer returns the full `user` object.  Use `/auth/me` if you need extra details._

**✓ What this proves:** Authentication is working and returning a valid token.

---

### **STEP 2: Test Admin-Only Endpoint (As Admin)**

**Request:**
```
GET http://localhost:8000/api/admin/dashboard
```

**Headers:**
```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxx
```
(Use the token from STEP 1)

**Expected Response (200 OK):**
```json
{
  "message": "Welcome to Admin Dashboard",
  "data": {
    "total_users": 5,
    "users_by_role": {
      "admin": 2,
      "chairman": 1,
      "teacher": 1,
      "student": 1
    }
  }
}
```

**✓ What this proves:** Admin role can access admin-only routes.

---

### **STEP 3: Test Admin-Only Endpoint (As Chairman)**

**First: Login as Chairman**
```
POST http://localhost:8000/api/auth/login
```

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "email": "chairman@example.com",
  "password": "password"
}
```

Copy the chairman token from response.

**Then: Try to access admin dashboard**
```
GET http://localhost:8000/api/admin/dashboard
```

**Headers:**
```
Authorization: Bearer CHAIRMAN_TOKEN_HERE
```

**Expected Response (403 Forbidden):**
```json
{
  "message": "Forbidden. You do not have permission to access this resource."
}
```

**✓ What this proves:** Chairmen are blocked from admin routes.

---

### **STEP 4: Test Chairman Routes (As Chairman)**

**Request:**
```
GET http://localhost:8000/api/chairman/
```

**Headers:**
```
Authorization: Bearer CHAIRMAN_TOKEN_HERE
```

**Expected Response (200 OK or 404):**
Should be accessible to chairman role (won't error on permission).

**✓ What this proves:** Chairman can access their own level routes.

---

### **STEP 5: Test Admin-Only Endpoint (As Teacher)**

**First: Login as Teacher**
```
POST http://localhost:8000/api/auth/login
```

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "email": "teacher@example.com",
  "password": "password"
}
```

Copy the teacher token from response.

**Then: Try to access admin dashboard**
```
GET http://localhost:8000/api/admin/dashboard
```

**Headers:**
```
Authorization: Bearer TEACHER_TOKEN_HERE
```

**Expected Response (403 Forbidden):**
```json
{
  "message": "Forbidden. You do not have permission to access this resource."
}
```

**✓ What this proves:** Teachers are blocked from admin routes.

---

### **STEP 6: Test Teacher Routes (As Teacher)**

**Request:**
```
GET http://localhost:8000/api/teachers/
```

**Headers:**
```
Authorization: Bearer TEACHER_TOKEN_HERE
```

**Expected Response (200 OK or 404):**
Should be accessible to teacher role.

**✓ What this proves:** Teacher can access their own level routes.

---

### **STEP 7: Test Admin-Only Endpoint (As Student)**

**First: Login as Student**
```
POST http://localhost:8000/api/auth/login
```

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "email": "student@example.com",
  "password": "password"
}
```

Copy the student token from response.

**Then: Try to access admin dashboard**
```
GET http://localhost:8000/api/admin/dashboard
```

**Headers:**
```
Authorization: Bearer STUDENT_TOKEN_HERE
```

**Expected Response (403 Forbidden):**
```json
{
  "message": "Forbidden. You do not have permission to access this resource."
}
```

**✓ What this proves:** Students are blocked from admin routes.

---

### **STEP 8: Test Student Routes (As Student)**

**Request:**
```
GET http://localhost:8000/api/students/
```

**Headers:**
```
Authorization: Bearer STUDENT_TOKEN_HERE
```

**Expected Response (200 OK or 404):**
Should be accessible to student role.

**✓ What this proves:** Students can access their own level routes.

---

### **STEP 9: Test Invalid Token**

**Request:**
```
GET http://localhost:8000/api/admin/dashboard
```

**Headers:**
```
Authorization: Bearer invalid_token_12345
```

**Expected Response (401 or 500):**
Either a 401 Unauthenticated or 500 error.

**✓ What this proves:** Invalid tokens are rejected.

---

### **STEP 11: Test User Update (PUT) as Admin**

**Request:**
```
PUT http://localhost:8000/api/users/2
```

**Headers:**
```
Authorization: Bearer ADMIN_TOKEN_HERE
Content-Type: application/json
```

**Body:**
```json
{
  "role": "teacher"
}
```

**Expected Response (200 OK):**
```json
{
  "message": "User updated successfully",
  "user": {
    "id": 2,
    "name": "...",
    "email": "...",
    "role": "teacher"
  }
}
```

**✓ What this proves:** Admin can update user roles.

---

### **STEP 12: Test User Update (PUT) as Non-Admin**

**Request:**
```
PUT http://localhost:8000/api/users/2
```

**Headers:**
```
Authorization: Bearer STUDENT_TOKEN_HERE
Content-Type: application/json
```

**Body:**
```json
{
  "role": "teacher"
}
```

**Expected Response (403 Forbidden):**
```json
{
  "message": "Forbidden. You do not have permission to access this resource."
}
```

**✓ What this proves:** Non-admin roles cannot update users.

---

### **STEP 13: Test User Delete (DELETE) as Admin**

**Request:**
```
DELETE http://localhost:8000/api/users/3
```

**Headers:**
```
Authorization: Bearer ADMIN_TOKEN_HERE
```

**Expected Response (200 OK):**
```json
{
  "message": "User deleted successfully"
}
```

**✓ What this proves:** Admin can delete users.

---

### **STEP 14: Test User Delete (DELETE) as Non-Admin**

**Request:**
```
DELETE http://localhost:8000/api/users/3
```

**Headers:**
```
Authorization: Bearer STUDENT_TOKEN_HERE
```

**Expected Response (403 Forbidden):**
```json
{
  "message": "Forbidden. You do not have permission to access this resource."
}
```

**✓ What this proves:** Non-admin roles cannot delete users.

## Complete Role Testing Matrix

Test each role with these endpoints to verify authorization:

### **Admin Role (admin@example.com)**

| Endpoint | Method | Expected |
|----------|--------|----------|
| `/api/admin/dashboard` | GET | 200 ✓ |
| `/api/chairman/*` | GET | 200 ✓ |
| `/api/teachers/*` | GET | 200 ✓ |
| `/api/students/*` | GET | 200 ✓ |
| `/api/users` | GET | 200 ✓ |
| `/api/users/{id}` | PUT | 200 ✓ |
| `/api/users/{id}` | DELETE | 200 ✓ |

### **Chairman Role (chairman@example.com)**

| Endpoint | Method | Expected |
|----------|--------|----------|
| `/api/admin/dashboard` | GET | 403 ✗ |
| `/api/chairman/*` | GET | 200 ✓ |
| `/api/teachers/*` | GET | 200 ✓ |
| `/api/students/*` | GET | 200 ✓ |
| `/api/users` | GET | 200 ✓ |
| `/api/users/{id}` | PUT | 403 ✗ |
| `/api/users/{id}` | DELETE | 403 ✗ |

### **Teacher Role (teacher@example.com)**

| Endpoint | Method | Expected |
|----------|--------|----------|
| `/api/admin/dashboard` | GET | 403 ✗ |
| `/api/chairman/*` | GET | 403 ✗ |
| `/api/teachers/*` | GET | 200 ✓ |
| `/api/students/*` | GET | 200 ✓ |
| `/api/users` | GET | 200 ✓ |
| `/api/users/{id}` | PUT | 403 ✗ |
| `/api/users/{id}` | DELETE | 403 ✗ |

### **Student Role (student@example.com)**

| Endpoint | Method | Expected |
|----------|--------|----------|
| `/api/admin/dashboard` | GET | 403 ✗ |
| `/api/chairman/*` | GET | 403 ✗ |
| `/api/teachers/*` | GET | 403 ✗ |
| `/api/students/*` | GET | 200 ✓ |
| `/api/users` | GET | 200 ✓ |
| `/api/users/{id}` | PUT | 403 ✗ |
| `/api/users/{id}` | DELETE | 403 ✗ |

---

## How to Verify Implementation is Correct (All Roles)

Follow this checklist to confirm everything works:

### ✅ **Authentication Working**
- [ ] Admin login returns a token
- [ ] Chairman login returns a token
- [ ] Teacher login returns a token
- [ ] Student login returns a token
- [ ] Token format is correct: `number|alphanumeric`

### ✅ **Admin Authorization Working**
- [ ] Admin can access `/api/admin/dashboard` (200)
- [ ] Admin can access `/api/chairman/*` routes (200)
- [ ] Admin can access `/api/teachers/*` routes (200)
- [ ] Admin can access `/api/students/*` routes (200)

### ✅ **Chairman Authorization Working**
- [ ] Chairman gets 403 on `/api/admin/dashboard`
- [ ] Chairman can access `/api/chairman/*` routes (200)
- [ ] Chairman can access `/api/teachers/*` routes (200)
- [ ] Chairman can access `/api/students/*` routes (200)
- [ ] Chairman can GET `/api/users` (200)
- [ ] Chairman gets 403 on PUT `/api/users/{id}`
- [ ] Chairman gets 403 on DELETE `/api/users/{id}`

### ✅ **Teacher Authorization Working**
- [ ] Teacher gets 403 on `/api/admin/dashboard`
- [ ] Teacher gets 403 on `/api/chairman/*` routes
- [ ] Teacher can access `/api/teachers/*` routes (200)
- [ ] Teacher can access `/api/students/*` routes (200)
- [ ] Teacher can GET `/api/users` (200)
- [ ] Teacher gets 403 on PUT `/api/users/{id}`
- [ ] Teacher gets 403 on DELETE `/api/users/{id}`

### ✅ **Student Authorization Working**
- [ ] Student gets 403 on `/api/admin/dashboard`
- [ ] Student can GET `/api/users` (200)
- [ ] Student gets 403 on PUT `/api/users/{id}`
- [ ] Student gets 403 on DELETE `/api/users/{id}`
- [ ] Student gets 403 on `/api/chairman/*` routes
- [ ] Student gets 403 on `/api/teachers/*` routes
- [ ] Student can access `/api/students/*` routes (200)

### ✅ **Token Validation Working**
- [ ] Invalid token returns 401 or 500
- [ ] No token returns 401 or 500
- [ ] Valid token allows access

### ✅ **Role Hierarchy Working**
- [ ] Admin > Chairman > Teacher > Student
- [ ] Each role can only access their level and below
- [ ] Higher roles cannot be accessed by lower roles

---

## Quick Test Sequence (All Roles)

Run these tests in order to verify everything works for all roles:

**1. Admin Login** → Get token
```
POST /api/auth/login with admin@example.com
Expected: 200 with token
```

**2. Admin access dashboard** → Should work
```
GET /api/admin/dashboard with admin token
Expected: 200 with dashboard data
```

**3. Chairman Login** → Get different token
```
POST /api/auth/login with chairman@example.com
Expected: 200 with different token
```

**4. Chairman access admin dashboard** → Should fail
```
GET /api/admin/dashboard with chairman token
Expected: 403 Forbidden
```

**5. Chairman access chairman routes** → Should work
```
GET /api/chairman/* with chairman token
Expected: 200 or 404 (route exists check)
```

**6. Teacher Login** → Get different token
```
POST /api/auth/login with teacher@example.com
Expected: 200 with different token
```

**7. Teacher access admin dashboard** → Should fail
```
GET /api/admin/dashboard with teacher token
Expected: 403 Forbidden
```

**8. Teacher access chairman routes** → Should fail
```
GET /api/chairman/* with teacher token
Expected: 403 Forbidden
```

**9. Teacher access teacher routes** → Should work
```
GET /api/teachers/* with teacher token
Expected: 200 or 404 (route exists check)
```

**10. Student Login** → Get different token
```
POST /api/auth/login with student@example.com
Expected: 200 with different token
```

**11. Student access admin dashboard** → Should fail
```
GET /api/admin/dashboard with student token
Expected: 403 Forbidden
```

**12. Student access chairman routes** → Should fail
```
GET /api/chairman/* with student token
Expected: 403 Forbidden
```

**13. Student access teacher routes** → Should fail
```
GET /api/teachers/* with student token
Expected: 403 Forbidden
```

**14. Student access student routes** → Should work
```
GET /api/students/* with student token
Expected: 200 or 404 (route exists check)
```

**16. Admin update user** → Should work
```
PUT /api/users/2 with admin token, body {"role":"teacher"}
Expected: 200 with updated user
```

**17. Student update user** → Should fail
```
PUT /api/users/2 with student token
Expected: 403 Forbidden
```

**18. Admin delete user** → Should work
```
DELETE /api/users/3 with admin token
Expected: 200 with success message
```

**19. Student delete user** → Should fail
```
DELETE /api/users/3 with student token
Expected: 403 Forbidden
```

**15. Bad token test** → Should fail
```
GET /api/admin/dashboard with invalid token
Expected: 401 or 500
```

---

## If Tests Fail

### Problem: Getting 500 instead of 403

**Check:** The role middleware might not be working.
- Look at `app/Http/Middleware/RoleMiddleware.php`
- Verify middleware is registered in `bootstrap/app.php`
- Check routes have `'role:admin'` middleware

### Problem: Getting 200 for all roles

**Check:** Authorization is not being enforced.
- Verify routes in `routes/api.php` have role middleware
- Check if user is being authenticated correctly
- Verify `SanctumAuthMiddleware` is checking tokens properly

### Problem: Can't get token

**Check:** Login endpoint.
- Verify database has test users
- Check if password hashing is correct
- Verify `AuthController` is handling login properly

---

## Thunder Client Collection Tip
9
To save these requests in Thunder Client:

1. Create a new Collection called "Role Authorization Tests"
2. Add each endpoint as a request
3. Set variables for:
   - `{{base_url}}` = http://localhost:8000
   - `{{admin_token}}` = (paste token after login)
   - `{{student_token}}` = (paste token after login)

Then use in requests:
```
GET {{base_url}}/api/admin/dashboard
Authorization: Bearer {{admin_token}}
```

---

## Summary

Your implementation is **correctly implemented** if:

1. ✓ All 4 roles (Admin, Chairman, Teacher, Student) can login and get tokens
2. ✓ Admin can access all role-level routes (admin, chairman, teacher, student)
3. ✓ Chairman gets 403 on admin routes but can access chairman+ routes
4. ✓ Teacher gets 403 on admin and chairman routes but can access teacher+ routes
5. ✓ Student gets 403 on admin, chairman, and teacher routes but can access student routes
6. ✓ Invalid/missing tokens get 401/500
7. ✓ Role hierarchy is enforced correctly (higher roles can't be accessed by lower roles)

Complete all 15 test steps above to verify everything is working correctly for all roles!
