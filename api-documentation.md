# Learn Academy API Documentation

## Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
   - [Base URL & Authentication](#base-url--authentication)
   - [Quick Start Guide](#quick-start-guide)
3. [Authentication & Authorization](#authentication--authorization)
   - [Authentication Flow](#authentication-flow)
   - [Role-Based Access Control](#role-based-access-control)
   - [Token Management](#token-management)
4. [API Standards](#api-standards)
   - [Response Format](#response-format)
   - [Error Handling](#error-handling)
   - [Pagination](#pagination)
5. [Core Endpoints](#core-endpoints)
   - [Health & System](#health--system-endpoints)
   - [Authentication](#authentication-endpoints)
   - [Translation Management](#translation-management)
6. [Administrative Functions](#administrative-functions)
   - [User Management](#user-management)
   - [Enrollment Management](#enrollment-management)
   - [Program Management](#program-management)
   - [Language Management](#language-management)
   - [Quiz Administration](#quiz-administration)
   - [Settings Management](#settings-management)
7. [Teacher Operations](#teacher-operations)
   - [Profile Management](#profile-management)
   - [Quiz Management](#quiz-management)
   - [Meeting Management](#meeting-management)
   - [Language Access](#language-access)
8. [Student Operations](#student-operations)
   - [Enrollment & Programs](#enrollment--programs)
   - [Quiz Taking](#quiz-taking)
   - [Meeting Access](#meeting-access)
9. [Guest Access](#guest-access)
   - [Guest Language Access](#guest-language-access)
   - [Guest Teacher Access](#guest-teacher-access)
   - [Guest Quiz Access](#guest-quiz-access)
   - [Legacy Guest Quiz Endpoints](#legacy-guest-quiz-endpoints)
   - [Guest Access Configuration](#guest-access-configuration)
10. [Notification System](#notification-system)
11. [Practical Usage Examples](#practical-usage-examples--workflows)
    - [Complete User Journey Examples](#complete-user-journey-examples)
    - [cURL Command Examples](#curl-command-examples)
    - [File Upload Examples](#file-upload-examples)
    - [Common Workflow Patterns](#common-workflow-patterns)
12. [Appendices](#appendices)
    - [Data Models Reference](#data-models-reference)
    - [Error Codes Reference](#error-codes-reference)
    - [Rate Limiting](#rate-limiting)

---

## Overview

The Learn Academy API is a comprehensive RESTful web service built with Laravel that powers a modern learning management system. This API enables developers to build educational applications with robust user management, content delivery, and assessment capabilities.

### Key Features

- **Multi-Role Support**: Administrators, Teachers, Students, and Guest users
- **Secure Authentication**: Token-based authentication with Laravel Sanctum
- **Internationalization**: Multi-language support with dynamic translations
- **Assessment System**: Comprehensive quiz creation and management
- **Meeting Management**: Scheduling and coordination tools
- **Real-time Notifications**: User notification system with preferences
- **Flexible Access Control**: Role-based and permission-based authorization

### User Roles Overview

| Role | Capabilities | Access Level |
|------|-------------|--------------|
| **Administrator** | Full system management, user administration, system configuration | Complete access to all endpoints |
| **Teacher** | Content creation, student management, quiz administration | Program and language-specific access |
| **Student** | Learning activities, quiz taking, progress tracking | Enrollment-based access |
| **Guest** | Public content viewing, anonymous quiz taking | Limited public access (configurable) |

---

## Getting Started

### Base URL & Authentication

**Base URL**: `https://your-domain.com/api`  
**API Version**: 1.0.0  
**Authentication**: Laravel Sanctum (Bearer Token)  
**Content Type**: `application/json`

### Quick Start Guide

1. **Register or Login** to obtain an access token
2. **Include the token** in the Authorization header for protected endpoints
3. **Use standard HTTP methods** (GET, POST, PUT, DELETE) with JSON payloads
4. **Handle responses** using the consistent JSON format structure

**Example Authentication Flow**:

```bash
# 1. Register a new user
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123",
    "password_confirmation": "SecurePass123",
    "role": "student"
  }'

# 2. Use the returned token for authenticated requests
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Content-Type: application/json"
```

---

## API Standards

### Response Format Standards

All API responses follow a consistent JSON structure to ensure predictable integration:

#### Success Response Structure
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data object or array
    }
}
```

#### Error Response Structure
```json
{
    "success": false,
    "message": "Human-readable error description",
    "errors": {
        "field_name": ["Specific validation error messages"]
    }
}
```

#### Paginated Response Structure
```json
{
    "success": true,
    "data": {
        "data": [...],
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| `200` | OK | Successful GET, PUT, PATCH requests |
| `201` | Created | Successful POST requests |
| `204` | No Content | Successful DELETE requests |
| `400` | Bad Request | Invalid request format |
| `401` | Unauthorized | Authentication required |
| `403` | Forbidden | Insufficient permissions |
| `404` | Not Found | Resource not found |
| `422` | Unprocessable Entity | Validation errors |
| `500` | Internal Server Error | Server errors |

---

## Authentication & Authorization

The Learn Academy API uses Laravel Sanctum for token-based authentication with comprehensive role-based access control. The system supports multiple authentication patterns and authorization levels to ensure secure access to resources.

### Authentication Flow Overview

1. **Registration**: Users register with required information and receive an immediate access token
2. **Login**: Users authenticate with email/password and receive a Bearer token
3. **Token Usage**: Include the token in the Authorization header for protected endpoints
4. **Logout**: Invalidate the current token to end the session

### Authentication Types

#### Public Endpoints

-   No authentication required
-   Accessible to all users including unauthenticated visitors
-   Examples: Health check, public translations, guest content (when enabled)

#### Protected Endpoints

-   Require `Authorization: Bearer {token}` header
-   User must be authenticated with a valid token
-   Basic authentication without specific role requirements

#### Role-Based Access

-   Require specific user roles in addition to authentication
-   Roles: `admin`, `teacher`, `student`
-   Multiple roles can be specified (e.g., teacher OR admin access)

#### Permission-Based Access

-   Require specific permissions beyond role-based access
-   Fine-grained control over specific actions
-   Used for advanced authorization scenarios

#### Middleware Validation

-   Custom middleware for specialized access control
-   Examples: Language access validation for teachers, guest access settings

### Authorization Header Format

All protected endpoints require the following header:

```
Authorization: Bearer {your_access_token}
```

**Example**:

```
Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
```

### Role Hierarchy & Permissions

#### Admin Role

-   **Full system access** to all endpoints and resources
-   **User management**: Create, read, update, delete users
-   **System configuration**: Manage settings, translations, system health
-   **Enrollment management**: Approve/deny enrollments, bulk operations
-   **Content oversight**: Access to all programs, quizzes, and meetings
-   **Analytics & reporting**: System-wide statistics and monitoring

**Example Admin-Only Endpoints**:

-   `GET /admin/users` - User management
-   `POST /admin/bulk-grant-access` - Bulk enrollment operations
-   `PUT /translations/{locale}` - Translation management
-   `GET /admin/enrollment-statistics` - System analytics

#### Teacher Role

-   **Content creation**: Create and manage quizzes, meetings
-   **Student interaction**: View student progress, manage enrollments
-   **Language-specific access**: Access content for assigned languages only
-   **Profile management**: Update teacher profile and preferences
-   **Limited administrative functions**: Program and language management within scope

**Example Teacher Endpoints**:

-   `POST /teacher/quizzes` - Create quizzes
-   `GET /teacher/meetings` - Manage meetings
-   `GET /teacher/languages/{language}/content` - Language-specific content
-   `PUT /teacher/profile` - Profile management

#### Student Role

-   **Learning activities**: Take quizzes, view results, access meetings
-   **Enrollment viewing**: See approved programs and enrollment status
-   **Progress tracking**: View attempt history and learning progress
-   **Profile management**: Update personal information and preferences
-   **Limited content access**: Only enrolled programs and assigned content

**Example Student Endpoints**:

-   `GET /student/quizzes` - Available quizzes
-   `POST /student/quizzes/{quiz}/attempt` - Submit quiz attempts
-   `GET /student/enrollments` - View enrollments
-   `GET /student/meetings` - Access meetings

#### Guest Access (Conditional)

-   **Public content access**: When enabled by system settings
-   **Anonymous interactions**: Take public quizzes, view public content
-   **No authentication required**: Access without user account
-   **Limited functionality**: Read-only access to designated public resources

**Example Guest Endpoints** (when enabled):

-   `GET /guest/languages` - Public language list
-   `GET /guest/teachers` - Public teacher profiles
-   `POST /guest/quizzes/{quiz}/attempt` - Anonymous quiz attempts

### Token Management

#### Token Generation

-   Tokens are generated upon successful login or registration
-   Each token is unique and tied to a specific user session
-   Tokens include user identity and role information

#### Token Validation

-   Tokens are validated on each request to protected endpoints
-   Invalid or expired tokens result in 401 Unauthorized responses
-   Token validation includes user existence and role verification

#### Token Invalidation

-   Tokens are invalidated upon logout
-   Users can have multiple active tokens (multiple device support)
-   Admins can revoke user access by disabling accounts

### Role-Based Access Examples

#### Multiple Role Access

Some endpoints allow access to users with multiple roles:

```php
// Teacher OR Admin access
Route::middleware(['role:teacher,admin'])->group(function () {
    Route::get('/teacher/profile', [TeacherController::class, 'getProfile']);
});
```

**cURL Example**:

```bash
# Teacher accessing their profile
curl -X GET "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Admin accessing teacher profile functionality
curl -X GET "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### Admin-Only Access

Strict admin-only endpoints:

```bash
# Only admins can access user management
curl -X GET "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### Student-Only Access

Student-specific functionality:

```bash
# Students accessing their enrollments
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

### Authorization Error Responses

#### 401 Unauthorized - Missing or Invalid Token

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

#### 403 Forbidden - Insufficient Role Permissions

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

#### 403 Forbidden - Insufficient Permissions

```json
{
    "success": false,
    "message": "Unauthorized. Required permissions: manage-quizzes"
}
```

### Security Best Practices

#### Token Security

-   Store tokens securely on the client side
-   Use HTTPS for all API communications
-   Implement token refresh mechanisms for long-lived applications
-   Never expose tokens in URLs or logs

#### Role Validation

-   Always validate user roles on the server side
-   Don't rely on client-side role checking
-   Implement principle of least privilege
-   Regularly audit user permissions

#### Access Control

-   Validate resource ownership (users can only access their own data)
-   Implement rate limiting to prevent abuse
-   Log security events for monitoring
-   Use middleware for consistent authorization checks

### Pagination

The API uses Laravel's built-in pagination for list endpoints. Pagination responses include comprehensive metadata for navigation and state management.

### Success Response Format

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data object or array
    }
}
```

### Paginated Response Format

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john.doe@example.com",
                "role": "student",
                "created_at": "2024-01-15T10:30:00.000000Z"
            },
            {
                "id": 2,
                "name": "Jane Smith",
                "email": "jane.smith@example.com", 
                "role": "teacher",
                "created_at": "2024-01-16T14:20:00.000000Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7,
        "from": 1,
        "to": 15,
        "path": "https://your-domain.com/api/admin/users",
        "first_page_url": "https://your-domain.com/api/admin/users?page=1",
        "last_page_url": "https://your-domain.com/api/admin/users?page=7",
        "next_page_url": "https://your-domain.com/api/admin/users?page=2",
        "prev_page_url": null,
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "https://your-domain.com/api/admin/users?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "https://your-domain.com/api/admin/users?page=2",
                "label": "2", 
                "active": false
            },
            {
                "url": "https://your-domain.com/api/admin/users?page=2",
                "label": "Next &raquo;",
                "active": false
            }
        ]
    },
    "meta": {
        "pagination": {
            "count": 15,
            "current_page": 1,
            "per_page": 15,
            "total": 100,
            "total_pages": 7
        },
        "filters_applied": {
            "search": null,
            "role": null,
            "sort_by": "created_at",
            "sort_order": "desc"
        }
    }
}
```

### Additional Pagination Examples

**Large Dataset Pagination (Page 5 of 20)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 61,
                "name": "Ahmed Hassan",
                "email": "ahmed.hassan@example.com",
                "role": "student",
                "phone": "+20-123-456-789",
                "preferred_language": "ar",
                "created_at": "2024-01-10T08:15:00.000000Z"
            },
            {
                "id": 62,
                "name": "Maria Garcia",
                "email": "maria.garcia@example.com",
                "role": "teacher",
                "phone": "+34-987-654-321",
                "preferred_language": "es",
                "created_at": "2024-01-10T09:30:00.000000Z"
            }
        ],
        "current_page": 5,
        "per_page": 15,
        "total": 300,
        "last_page": 20,
        "from": 61,
        "to": 75,
        "path": "https://your-domain.com/api/admin/users",
        "first_page_url": "https://your-domain.com/api/admin/users?page=1",
        "last_page_url": "https://your-domain.com/api/admin/users?page=20",
        "next_page_url": "https://your-domain.com/api/admin/users?page=6",
        "prev_page_url": "https://your-domain.com/api/admin/users?page=4"
    },
    "meta": {
        "pagination": {
            "count": 15,
            "current_page": 5,
            "per_page": 15,
            "total": 300,
            "total_pages": 20,
            "has_more_pages": true
        },
        "filters_applied": {
            "search": null,
            "role": null,
            "sort_by": "created_at",
            "sort_order": "desc"
        }
    }
}
```

**Last Page Pagination Example**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 298,
                "name": "Lisa Chen",
                "email": "lisa.chen@example.com",
                "role": "student",
                "created_at": "2024-01-20T16:45:00.000000Z"
            },
            {
                "id": 299,
                "name": "David Wilson",
                "email": "david.wilson@example.com",
                "role": "admin",
                "created_at": "2024-01-20T17:00:00.000000Z"
            }
        ],
        "current_page": 20,
        "per_page": 15,
        "total": 299,
        "last_page": 20,
        "from": 286,
        "to": 299,
        "path": "https://your-domain.com/api/admin/users",
        "first_page_url": "https://your-domain.com/api/admin/users?page=1",
        "last_page_url": "https://your-domain.com/api/admin/users?page=20",
        "next_page_url": null,
        "prev_page_url": "https://your-domain.com/api/admin/users?page=19"
    },
    "meta": {
        "pagination": {
            "count": 14,
            "current_page": 20,
            "per_page": 15,
            "total": 299,
            "total_pages": 20,
            "has_more_pages": false
        }
    }
}
```

**Empty Results Pagination**:

```json
{
    "success": true,
    "data": {
        "data": [],
        "current_page": 1,
        "per_page": 15,
        "total": 0,
        "last_page": 1,
        "from": null,
        "to": null,
        "path": "https://your-domain.com/api/admin/users",
        "first_page_url": "https://your-domain.com/api/admin/users?page=1",
        "last_page_url": "https://your-domain.com/api/admin/users?page=1",
        "next_page_url": null,
        "prev_page_url": null
    },
    "meta": {
        "pagination": {
            "count": 0,
            "current_page": 1,
            "per_page": 15,
            "total": 0,
            "total_pages": 1,
            "has_more_pages": false
        },
        "filters_applied": {
            "search": "nonexistent",
            "role": null,
            "sort_by": "created_at",
            "sort_order": "desc"
        }
    }
}
```

---

## Core Endpoints

### Error Handling

The Learn Academy API uses standard HTTP status codes and provides detailed error responses to help developers understand and resolve issues. All error responses follow a consistent format with descriptive messages and actionable resolution steps.

### HTTP Status Codes

#### Success Status Codes

-   **200 OK**: Successful GET, PUT, PATCH requests with response data
-   **201 Created**: Successful POST requests that create new resources
-   **204 No Content**: Successful DELETE requests or updates with no response body

#### Client Error Status Codes

-   **400 Bad Request**: Malformed request syntax, invalid request message framing, or deceptive request routing
-   **401 Unauthorized**: Authentication credentials are missing, invalid, or expired
-   **403 Forbidden**: Valid authentication but insufficient permissions for the requested resource
-   **404 Not Found**: Requested resource does not exist or user lacks permission to view it
-   **405 Method Not Allowed**: HTTP method not supported for the requested endpoint
-   **409 Conflict**: Request conflicts with current state of the resource (e.g., duplicate entries)
-   **413 Payload Too Large**: Request entity exceeds server limits (file uploads, request body size)
-   **415 Unsupported Media Type**: Request payload format not supported by the endpoint
-   **422 Unprocessable Entity**: Request syntax is correct but contains semantic validation errors
-   **429 Too Many Requests**: Rate limit exceeded for the client or user

#### Server Error Status Codes

-   **500 Internal Server Error**: Unexpected server error occurred during request processing
-   **502 Bad Gateway**: Invalid response from upstream server
-   **503 Service Unavailable**: Server temporarily unavailable due to maintenance or overload
-   **504 Gateway Timeout**: Upstream server failed to respond within timeout period

### Error Response Format

All error responses follow a consistent JSON structure:

```json
{
    "success": false,
    "message": "Human-readable error description",
    "errors": {
        "field_name": [
            "Specific validation error message 1",
            "Specific validation error message 2"
        ]
    },
    "error_code": "SPECIFIC_ERROR_CODE",
    "timestamp": "2024-01-15T10:30:00.000000Z",
    "request_id": "req_abc123def456"
}
```

**Response Fields**:

-   **success**: Always `false` for error responses
-   **message**: Primary error message describing what went wrong
-   **errors**: Object containing field-specific validation errors (present for 422 responses)
-   **error_code**: Machine-readable error code for programmatic handling (optional)
-   **timestamp**: ISO 8601 timestamp when the error occurred (optional)
-   **request_id**: Unique identifier for request tracking and debugging (optional)

### Authentication and Authorization Errors

#### 401 Unauthorized - Missing Authentication

**Scenario**: Request to protected endpoint without Authorization header

```json
{
    "success": false,
    "message": "Unauthenticated.",
    "error_code": "MISSING_TOKEN"
}
```

**Resolution Steps**:
1. Ensure you have a valid access token from login or registration
2. Include the token in the Authorization header: `Authorization: Bearer {token}`
3. Verify the token hasn't expired by checking the login response

**cURL Example**:
```bash
# Incorrect - Missing Authorization header
curl -X GET "https://your-domain.com/api/admin/users"

# Correct - With Authorization header
curl -X GET "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz"
```

#### 401 Unauthorized - Invalid Token

**Scenario**: Request with malformed, expired, or revoked token

```json
{
    "success": false,
    "message": "Unauthenticated.",
    "error_code": "INVALID_TOKEN"
}
```

**Resolution Steps**:
1. Verify the token format is correct (should start with number and pipe: `1|...`)
2. Check if the token has expired by attempting to refresh or re-login
3. Ensure the token wasn't revoked due to logout or account changes
4. Re-authenticate to obtain a new valid token

#### 401 Unauthorized - Expired Token

**Scenario**: Request with expired authentication token

```json
{
    "success": false,
    "message": "Token has expired.",
    "error_code": "TOKEN_EXPIRED",
    "expires_at": "2024-01-15T10:30:00.000000Z"
}
```

**Resolution Steps**:
1. Implement token refresh mechanism in your application
2. Re-authenticate the user to obtain a new token
3. Store the new token securely and retry the original request
4. Consider implementing automatic token refresh before expiration

#### 403 Forbidden - Insufficient Role Permissions

**Scenario**: Authenticated user lacks required role for endpoint access

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin",
    "error_code": "INSUFFICIENT_ROLE",
    "required_roles": ["admin"],
    "user_roles": ["student"]
}
```

**Resolution Steps**:
1. Verify the user account has the correct role assigned
2. Contact system administrator to request role upgrade if appropriate
3. Use alternative endpoints available to your current role
4. Check API documentation for role-specific endpoint alternatives

#### 403 Forbidden - Insufficient Permissions

**Scenario**: User has correct role but lacks specific permissions

```json
{
    "success": false,
    "message": "Unauthorized. Required permissions: manage-quizzes",
    "error_code": "INSUFFICIENT_PERMISSIONS",
    "required_permissions": ["manage-quizzes"],
    "user_permissions": ["view-quizzes", "take-quizzes"]
}
```

**Resolution Steps**:
1. Contact administrator to request additional permissions
2. Verify your account has the necessary permissions for the action
3. Use read-only endpoints if you only have view permissions
4. Check if the resource belongs to you (some actions require ownership)

#### 403 Forbidden - Resource Access Denied

**Scenario**: User cannot access specific resource due to ownership or enrollment restrictions

```json
{
    "success": false,
    "message": "Access denied. You can only access your own resources.",
    "error_code": "RESOURCE_ACCESS_DENIED",
    "resource_type": "quiz",
    "resource_id": 123
}
```

**Resolution Steps**:
1. Verify you're accessing resources you own or are enrolled in
2. Check if you have the correct resource ID in your request
3. Ensure you're enrolled in the program if accessing program-specific content
4. Contact teacher or administrator if you believe you should have access

### Validation Errors (422)

#### Basic Validation Error

**Scenario**: Required fields missing or invalid data types

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "email": [
            "The email field is required.",
            "The email must be a valid email address."
        ],
        "password": [
            "The password must be at least 8 characters.",
            "The password must contain at least one uppercase letter.",
            "The password must contain at least one number."
        ],
        "role": ["The selected role is invalid."]
    },
    "error_code": "VALIDATION_FAILED"
}
```

**Resolution Steps**:
1. Check each field in the `errors` object for specific requirements
2. Ensure all required fields are included in your request
3. Validate data types and formats before sending the request
4. Review API documentation for field-specific validation rules

#### User Registration Validation

**Scenario**: User registration with validation errors

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name must be at least 2 characters."],
        "email": ["The email has already been taken."],
        "password": ["The password confirmation does not match."],
        "phone": ["The phone format is invalid."],
        "preferred_language": ["The selected preferred language is invalid."]
    },
    "error_code": "REGISTRATION_VALIDATION_FAILED"
}
```

**Resolution Steps**:
1. **Name**: Provide a name with at least 2 characters
2. **Email**: Use a different email address that isn't already registered
3. **Password**: Ensure password and password_confirmation fields match exactly
4. **Phone**: Use valid phone format (e.g., +1-234-567-8900)
5. **Language**: Use supported language codes: ar, en, or es

#### Quiz Creation Validation

**Scenario**: Teacher creating quiz with invalid data

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": ["The title field is required."],
        "program_id": ["The selected program id is invalid."],
        "questions": ["The questions field is required."],
        "questions.0.question": ["The questions.0.question field is required."],
        "questions.0.answers": ["The questions.0.answers must have at least 2 items."],
        "questions.1.correct_answer": ["The questions.1.correct answer field is required."]
    },
    "error_code": "QUIZ_VALIDATION_FAILED"
}
```

**Resolution Steps**:
1. **Title**: Provide a descriptive quiz title
2. **Program ID**: Ensure the program exists and you have access to it
3. **Questions**: Include at least one question in the questions array
4. **Question Text**: Each question must have a question field
5. **Answers**: Each question must have at least 2 answer options
6. **Correct Answer**: Specify which answer is correct for each question

#### File Upload Validation

**Scenario**: File upload with size or type restrictions

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "image": [
            "The image must be a file of type: jpeg, png, jpg, gif, svg.",
            "The image may not be greater than 2048 kilobytes."
        ],
        "document": ["The document field is required when uploading materials."]
    },
    "error_code": "FILE_UPLOAD_VALIDATION_FAILED"
}
```

**Resolution Steps**:
1. **File Type**: Use supported image formats (JPEG, PNG, JPG, GIF, SVG)
2. **File Size**: Ensure file is under 2MB (2048 KB)
3. **Required Files**: Include all required file fields in multipart form data
4. **File Format**: Use proper multipart/form-data encoding for file uploads

### Client Error Examples

#### 400 Bad Request - Malformed JSON

**Scenario**: Invalid JSON syntax in request body

```json
{
    "success": false,
    "message": "Invalid JSON format in request body.",
    "error_code": "MALFORMED_JSON",
    "details": "Unexpected token '}' at position 45"
}
```

**Resolution Steps**:
1. Validate JSON syntax using a JSON validator
2. Check for missing commas, quotes, or brackets
3. Ensure proper escaping of special characters
4. Use proper Content-Type header: `application/json`

#### 404 Not Found - Resource Not Found

**Scenario**: Requesting non-existent resource

```json
{
    "success": false,
    "message": "Quiz not found.",
    "error_code": "RESOURCE_NOT_FOUND",
    "resource_type": "quiz",
    "resource_id": 999
}
```

**Resolution Steps**:
1. Verify the resource ID exists in the system
2. Check if you have permission to access the resource
3. Ensure the resource hasn't been deleted
4. Confirm you're using the correct endpoint URL

#### 405 Method Not Allowed

**Scenario**: Using incorrect HTTP method for endpoint

```json
{
    "success": false,
    "message": "The POST method is not supported for this route. Supported methods: GET, PUT, DELETE.",
    "error_code": "METHOD_NOT_ALLOWED",
    "allowed_methods": ["GET", "PUT", "DELETE"]
}
```

**Resolution Steps**:
1. Check API documentation for correct HTTP method
2. Use the appropriate method from the allowed_methods list
3. Verify the endpoint URL is correct
4. Ensure you're not confusing similar endpoints with different methods

#### 409 Conflict - Resource Conflict

**Scenario**: Attempting to create duplicate resource

```json
{
    "success": false,
    "message": "A quiz with this title already exists in the program.",
    "error_code": "RESOURCE_CONFLICT",
    "conflicting_resource": {
        "type": "quiz",
        "id": 45,
        "title": "Introduction to Programming"
    }
}
```

**Resolution Steps**:
1. Use a different title or identifier for the resource
2. Check if you intended to update the existing resource instead
3. Use PUT method to update existing resource if appropriate
4. Add unique identifiers or timestamps to avoid conflicts

#### 413 Payload Too Large

**Scenario**: Request body or file upload exceeds size limits

```json
{
    "success": false,
    "message": "Request entity too large. Maximum allowed size is 10MB.",
    "error_code": "PAYLOAD_TOO_LARGE",
    "max_size": "10MB",
    "received_size": "15MB"
}
```

**Resolution Steps**:
1. Reduce file size or compress files before uploading
2. Split large requests into smaller chunks
3. Check server configuration for upload limits
4. Use appropriate file formats and compression

#### 429 Too Many Requests - Rate Limit Exceeded

**Scenario**: Client exceeding API rate limits

```json
{
    "success": false,
    "message": "Too many requests. Rate limit exceeded.",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "limit": 100,
    "remaining": 0,
    "reset_time": "2024-01-15T11:00:00.000000Z"
}
```

**Resolution Steps**:
1. Implement exponential backoff in your client
2. Wait until reset_time before making new requests
3. Reduce request frequency to stay within limits
4. Cache responses to minimize API calls
5. Contact support if you need higher rate limits

### Server Error Examples

#### 500 Internal Server Error

**Scenario**: Unexpected server error during processing

```json
{
    "success": false,
    "message": "An unexpected error occurred. Please try again later.",
    "error_code": "INTERNAL_SERVER_ERROR",
    "request_id": "req_abc123def456"
}
```

**Resolution Steps**:
1. Retry the request after a brief delay
2. Check if the issue persists across multiple requests
3. Report the error with the request_id to support team
4. Verify your request format matches API documentation
5. Check system status page for known issues

#### 503 Service Unavailable

**Scenario**: Service temporarily unavailable due to maintenance

```json
{
    "success": false,
    "message": "Service temporarily unavailable due to maintenance.",
    "error_code": "SERVICE_UNAVAILABLE",
    "retry_after": 3600,
    "maintenance_window": {
        "start": "2024-01-15T10:00:00.000000Z",
        "end": "2024-01-15T12:00:00.000000Z"
    }
}
```

**Resolution Steps**:
1. Wait for the maintenance window to complete
2. Implement retry logic with exponential backoff
3. Check service status page for updates
4. Use the retry_after value to determine when to retry
5. Consider implementing graceful degradation in your application

### Error Handling Best Practices

#### Client-Side Error Handling

1. **Implement Proper Error Handling**:
```javascript
fetch('https://your-domain.com/api/admin/users', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    if (!response.ok) {
        return response.json().then(error => {
            throw new Error(`HTTP ${response.status}: ${error.message}`);
        });
    }
    return response.json();
})
.then(data => {
    console.log('Success:', data);
})
.catch(error => {
    console.error('Error:', error.message);
    // Handle specific error codes
    if (error.message.includes('401')) {
        // Redirect to login
        window.location.href = '/login';
    }
});
```

2. **Handle Validation Errors**:
```javascript
// Handle 422 validation errors
if (response.status === 422) {
    const errorData = await response.json();
    Object.keys(errorData.errors).forEach(field => {
        const fieldErrors = errorData.errors[field];
        displayFieldErrors(field, fieldErrors);
    });
}
```

3. **Implement Retry Logic**:
```javascript
async function apiRequestWithRetry(url, options, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            const response = await fetch(url, options);
            if (response.status === 429) {
                // Rate limited, wait and retry
                const retryAfter = response.headers.get('Retry-After') || 60;
                await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
                continue;
            }
            return response;
        } catch (error) {
            if (i === maxRetries - 1) throw error;
            await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, i)));
        }
    }
}
```

#### Server-Side Logging

All errors are logged with appropriate context for debugging:

-   **Request ID**: Unique identifier for request tracking
-   **User Context**: User ID and role information
-   **Request Details**: Method, URL, headers, and body
-   **Error Context**: Stack trace and error details
-   **Timestamp**: Precise error occurrence time

#### Monitoring and Alerting

-   **Error Rate Monitoring**: Track error rates by endpoint and status code
-   **Performance Monitoring**: Monitor response times and identify slow endpoints
-   **Alert Thresholds**: Automated alerts for high error rates or server errors
-   **Log Aggregation**: Centralized logging for error analysis and debugging

---

## Health & System Endpoints

The health and system endpoints provide essential information about the API status, version, and system health. These endpoints are primarily used for monitoring, load balancer health checks, and system diagnostics.

### GET /health

**Description**: Check API health and system status with version information

**Authentication**: None required (Public endpoint)

**Purpose**:

-   Monitor API availability and responsiveness
-   Verify system is operational for load balancers and monitoring tools
-   Provide version information for deployment tracking
-   Generate timestamps for system synchronization

**Response Format**:

| Field       | Type    | Description                                 |
| ----------- | ------- | ------------------------------------------- |
| `success`   | boolean | Always `true` when API is responding        |
| `message`   | string  | Human-readable status message               |
| `version`   | string  | Current API version (semantic versioning)   |
| `timestamp` | string  | Current server timestamp in ISO 8601 format |

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Learn Academy API is running",
    "version": "1.0.0",
    "timestamp": "2024-01-15T10:30:00.000000Z"
}
```

**Response Details**:

-   **success**: Indicates the API is operational and responding to requests
-   **message**: Confirms the Learn Academy API service is running normally
-   **version**: Current API version following semantic versioning (MAJOR.MINOR.PATCH)
-   **timestamp**: Server-generated timestamp in UTC timezone using ISO 8601 format

**Use Cases**:

1. **Load Balancer Health Checks**: Verify API instances are healthy and ready to receive traffic
2. **Monitoring Systems**: Automated health monitoring and alerting
3. **Deployment Verification**: Confirm successful deployments and version updates
4. **System Diagnostics**: Quick verification of API availability during troubleshooting
5. **Client Connectivity**: Test network connectivity and API responsiveness

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/health" \
  -H "Content-Type: application/json"
```

**JavaScript Fetch Example**:

```javascript
fetch("https://your-domain.com/api/health")
    .then((response) => response.json())
    .then((data) => {
        console.log("API Status:", data.success);
        console.log("Version:", data.version);
        console.log("Timestamp:", data.timestamp);
    })
    .catch((error) => console.error("Health check failed:", error));
```

**Python Requests Example**:

```python
import requests

try:
    response = requests.get('https://your-domain.com/api/health')
    data = response.json()

    print(f"API Status: {data['success']}")
    print(f"Version: {data['version']}")
    print(f"Message: {data['message']}")
    print(f"Timestamp: {data['timestamp']}")
except requests.exceptions.RequestException as e:
    print(f"Health check failed: {e}")
```

**Monitoring Integration Examples**:

**Uptime Robot Configuration**:

-   **URL**: `https://your-domain.com/api/health`
-   **Method**: GET
-   **Expected Status**: 200
-   **Keyword Monitoring**: Look for `"success":true` in response

**Nagios Check Command**:

```bash
check_http -H your-domain.com -u /api/health -s '"success":true'
```

**Docker Health Check**:

```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/api/health || exit 1
```

**Error Scenarios**:

While the `/health` endpoint is designed to always return a successful response when the API is operational, potential error scenarios include:

**Service Unavailable (503)**:

```json
{
    "error": "Service temporarily unavailable"
}
```

**Connection Timeout**: No response when API server is down or unreachable

**Network Error**: DNS resolution failures or network connectivity issues

**Performance Considerations**:

-   **Response Time**: Typically responds in < 50ms
-   **Resource Usage**: Minimal CPU and memory impact
-   **Caching**: No caching applied to ensure real-time status
-   **Rate Limiting**: No rate limiting applied for monitoring purposes

**Version Information**:
The version field follows semantic versioning principles:

-   **Major Version (1.x.x)**: Breaking changes to API structure or authentication
-   **Minor Version (x.1.x)**: New features and endpoints added (backward compatible)
-   **Patch Version (x.x.1)**: Bug fixes and minor improvements (backward compatible)

**System Status Information**:
The health endpoint provides basic operational status. For detailed system metrics and performance data, administrators should use:

-   Application logs via `php artisan pail`
-   Laravel Horizon for queue monitoring (if configured)
-   Database connection status through application monitoring
-   Cache system status through Redis/Memcached monitoring tools

**Integration with CI/CD**:

```yaml
# Example GitHub Actions health check
- name: Verify API Health
  run: |
      response=$(curl -s https://your-domain.com/api/health)
      success=$(echo $response | jq -r '.success')
      if [ "$success" != "true" ]; then
        echo "Health check failed: $response"
        exit 1
      fi
      echo "API is healthy"
```

---

## Translation Management

The Learn Academy API provides comprehensive translation management functionality supporting multi-language content with automatic locale detection. The system supports Arabic (ar), English (en), and Spanish (es) locales with intelligent locale detection through multiple methods including request parameters, Accept-Language headers, and user preferences.

### Locale Detection Middleware

All translation endpoints use the `locale` middleware that automatically detects the appropriate language based on:

1. **Explicit locale parameter**: `?locale=en` in query string or `Accept-Language-Locale` header
2. **Accept-Language header**: Standard browser language preferences (e.g., `Accept-Language: en-US,en;q=0.9,ar;q=0.8`)
3. **User preference**: Authenticated user's `preferred_locale` setting
4. **Default fallback**: English (en) when no other locale is detected

**Supported Locales**:

-   `ar` - Arabic
-   `en` - English (default)
-   `es` - Spanish

**Middleware Behavior**:

-   Sets application locale using `App::setLocale()`
-   Adds detected locale to request attributes for controller access
-   Validates locale against supported languages
-   Gracefully falls back to default locale for unsupported languages

---

### Public Translation Endpoints

These endpoints are publicly accessible and do not require authentication.

#### GET /translations/locales

**Description**: Get list of all supported locales in the system

**Authentication**: None required (Public endpoint)

**Purpose**: Retrieve available language options for client-side locale selection and validation

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "supported_locales": ["ar", "en", "es"]
    }
}
```

**Response Details**:

-   **supported_locales**: Array of ISO 639-1 language codes supported by the system

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/translations/locales" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
fetch("https://your-domain.com/api/translations/locales")
    .then((response) => response.json())
    .then((data) => {
        console.log("Supported locales:", data.data.supported_locales);
    });
```

---

#### GET /translations/{locale}

**Description**: Retrieve all translation key-value pairs for a specific locale

**Authentication**: None required (Public endpoint)

**Parameters**:

-   **Path**: `locale` (string, required) - Language code (ar, en, es)

**Query Parameters**:

-   `locale` (string, optional) - Override locale detection with explicit locale

**Headers**:

-   `Accept-Language` (string, optional) - Browser language preferences
-   `Accept-Language-Locale` (string, optional) - Explicit locale preference

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "locale": "en",
        "translations": {
            "welcome": "Welcome",
            "login": "Login",
            "register": "Register",
            "dashboard": "Dashboard",
            "profile": "Profile",
            "logout": "Logout",
            "quiz": "Quiz",
            "meeting": "Meeting",
            "program": "Program",
            "language": "Language"
        },
        "supported_locales": ["ar", "en", "es"]
    }
}
```

**Error Response - Invalid Locale (400)**:

```json
{
    "success": false,
    "error": {
        "code": "INVALID_LOCALE",
        "message": "Unsupported locale: fr",
        "supported_locales": ["ar", "en", "es"]
    }
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "error": {
        "code": "TRANSLATION_ERROR",
        "message": "Failed to load translations"
    }
}
```

**cURL Examples**:

```bash
# Get English translations
curl -X GET "https://your-domain.com/api/translations/en" \
  -H "Content-Type: application/json"

# Get Arabic translations with explicit locale
curl -X GET "https://your-domain.com/api/translations/ar?locale=ar" \
  -H "Content-Type: application/json"

# Get translations with Accept-Language header
curl -X GET "https://your-domain.com/api/translations/es" \
  -H "Accept-Language: es-ES,es;q=0.9,en;q=0.8" \
  -H "Content-Type: application/json"
```

---

#### GET /translations/{locale}/keys

**Description**: Get list of available translation keys for a specific locale

**Authentication**: None required (Public endpoint)

**Parameters**:

-   **Path**: `locale` (string, required) - Language code (ar, en, es)

**Purpose**: Retrieve available translation keys for validation, dynamic content loading, or debugging

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "locale": "en",
        "keys": [
            "welcome",
            "login",
            "register",
            "dashboard",
            "profile",
            "logout",
            "quiz",
            "meeting",
            "program",
            "language",
            "settings",
            "notifications"
        ],
        "count": 12
    }
}
```

**Error Response - Invalid Locale (400)**:

```json
{
    "success": false,
    "error": {
        "code": "INVALID_LOCALE",
        "message": "Unsupported locale: fr"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/translations/en/keys" \
  -H "Content-Type: application/json"
```

---

#### POST /translations/{locale}/translate

**Description**: Get translation for a specific key with optional parameter substitution

**Authentication**: None required (Public endpoint)

**Parameters**:

-   **Path**: `locale` (string, required) - Language code (ar, en, es)

**Request Body Parameters**:

| Field        | Type   | Required | Description                             |
| ------------ | ------ | -------- | --------------------------------------- |
| `key`        | string | Yes      | Translation key to retrieve             |
| `parameters` | object | No       | Parameters for translation substitution |

**Request Body Example**:

```json
{
    "key": "welcome_user",
    "parameters": {
        "name": "John Doe"
    }
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "locale": "en",
        "key": "welcome_user",
        "translation": "Welcome, John Doe!",
        "parameters": {
            "name": "John Doe"
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid request parameters",
        "details": {
            "key": ["The key field is required."]
        }
    }
}
```

**Error Response - Invalid Key Format (400)**:

```json
{
    "success": false,
    "error": {
        "code": "INVALID_KEY",
        "message": "Invalid translation key format"
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/translations/en/translate" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "welcome_user",
    "parameters": {
      "name": "John Doe"
    }
  }'
```

---

#### POST /translations/validate-key

**Description**: Validate translation key format and check if it follows system conventions

**Authentication**: None required (Public endpoint)

**Request Body Parameters**:

| Field | Type   | Required | Description                 |
| ----- | ------ | -------- | --------------------------- |
| `key` | string | Yes      | Translation key to validate |

**Request Body Example**:

```json
{
    "key": "welcome_user"
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "key": "welcome_user",
        "is_valid": true,
        "message": "Key is valid"
    }
}
```

**Invalid Key Response (200)**:

```json
{
    "success": true,
    "data": {
        "key": "invalid.key.format!",
        "is_valid": false,
        "message": "Key format is invalid"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid request parameters",
        "details": {
            "key": ["The key field is required."]
        }
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/translations/validate-key" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "welcome_user"
  }'
```

---

### Administrative Translation Management

These endpoints require admin authentication and are used for managing translation content.

#### PUT /translations/{locale}

**Description**: Update or create a translation key-value pair for a specific locale

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `locale` (string, required) - Language code (ar, en, es)

**Request Body Parameters**:

| Field   | Type   | Required | Description               |
| ------- | ------ | -------- | ------------------------- |
| `key`   | string | Yes      | Translation key to update |
| `value` | string | Yes      | Translation value/text    |

**Request Body Example**:

```json
{
    "key": "welcome_message",
    "value": "Welcome to Learn Academy - Your Learning Journey Starts Here!"
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "locale": "en",
        "key": "welcome_message",
        "value": "Welcome to Learn Academy - Your Learning Journey Starts Here!",
        "message": "Translation updated successfully"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid request parameters",
        "details": {
            "key": ["The key field is required."],
            "value": ["The value field is required."]
        }
    }
}
```

**Authorization Error Response (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Error Response - Invalid Key Format (400)**:

```json
{
    "success": false,
    "error": {
        "code": "INVALID_KEY",
        "message": "Invalid translation key format"
    }
}
```

**Error Response - Update Failed (500)**:

```json
{
    "success": false,
    "error": {
        "code": "UPDATE_FAILED",
        "message": "Failed to update translation"
    }
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/translations/en" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "welcome_message",
    "value": "Welcome to Learn Academy - Your Learning Journey Starts Here!"
  }'
```

---

#### POST /translations/cache/clear

**Description**: Clear translation cache for improved performance after updates

**Authentication**: Required (Admin role)

**Request Body Parameters** (Optional):

| Field    | Type   | Required | Description                                      |
| -------- | ------ | -------- | ------------------------------------------------ |
| `locale` | string | No       | Specific locale to clear (clears all if omitted) |

**Request Body Example (Clear Specific Locale)**:

```json
{
    "locale": "en"
}
```

**Request Body Example (Clear All Locales)**:

```json
{}
```

**Success Response - Specific Locale (200)**:

```json
{
    "success": true,
    "data": {
        "message": "Translation cache cleared for locale: en"
    }
}
```

**Success Response - All Locales (200)**:

```json
{
    "success": true,
    "data": {
        "message": "Translation cache cleared for all locales"
    }
}
```

**Error Response - Invalid Locale (400)**:

```json
{
    "success": false,
    "error": {
        "code": "INVALID_LOCALE",
        "message": "Unsupported locale"
    }
}
```

**Authorization Error Response (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Error Response - Cache Error (500)**:

```json
{
    "success": false,
    "error": {
        "code": "CACHE_ERROR",
        "message": "Failed to clear translation cache"
    }
}
```

**cURL Examples**:

```bash
# Clear cache for specific locale
curl -X POST "https://your-domain.com/api/translations/cache/clear" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "locale": "en"
  }'

# Clear cache for all locales
curl -X POST "https://your-domain.com/api/translations/cache/clear" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{}'
```

---

### Translation Management Workflows

#### Complete Translation Update Workflow

**Step 1: Validate Translation Key**

```bash
curl -X POST "https://your-domain.com/api/translations/validate-key" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "new_feature_message"
  }'
```

**Step 2: Update Translation for Each Locale**

```bash
# Update English translation
curl -X PUT "https://your-domain.com/api/translations/en" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "new_feature_message",
    "value": "Exciting new feature available!"
  }'

# Update Spanish translation
curl -X PUT "https://your-domain.com/api/translations/es" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "new_feature_message",
    "value": "¡Nueva función emocionante disponible!"
  }'

# Update Arabic translation
curl -X PUT "https://your-domain.com/api/translations/ar" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "new_feature_message",
    "value": "ميزة جديدة مثيرة متاحة!"
  }'
```

**Step 3: Clear Translation Cache**

```bash
curl -X POST "https://your-domain.com/api/translations/cache/clear" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 4: Verify Updates**

```bash
# Test English translation
curl -X POST "https://your-domain.com/api/translations/en/translate" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "new_feature_message"
  }'
```

#### Client-Side Locale Detection Integration

**JavaScript Example with Automatic Locale Detection**:

```javascript
// Get user's preferred language
const userLocale = navigator.language.substring(0, 2); // e.g., 'en' from 'en-US'

// Fetch translations with locale preference
fetch(`https://your-domain.com/api/translations/${userLocale}`, {
    headers: {
        "Accept-Language": navigator.language,
        "Content-Type": "application/json",
    },
})
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            // Use translations
            const translations = data.data.translations;
            document.getElementById("welcome").textContent =
                translations.welcome;
        }
    })
    .catch((error) => {
        // Fallback to English
        return fetch("https://your-domain.com/api/translations/en");
    });
```

#### Mobile App Integration Example

**React Native with AsyncStorage**:

```javascript
import AsyncStorage from "@react-native-async-storage/async-storage";

const getTranslations = async () => {
    try {
        // Get stored locale preference
        const storedLocale = await AsyncStorage.getItem("user_locale");
        const deviceLocale =
            Platform.OS === "ios"
                ? NativeModules.SettingsManager.settings.AppleLocale
                : NativeModules.I18nManager.localeIdentifier;

        const locale = storedLocale || deviceLocale.substring(0, 2) || "en";

        const response = await fetch(
            `https://your-domain.com/api/translations/${locale}`,
            {
                headers: {
                    "Accept-Language-Locale": locale,
                    "Content-Type": "application/json",
                },
            }
        );

        const data = await response.json();
        return data.success ? data.data.translations : {};
    } catch (error) {
        console.error("Translation fetch error:", error);
        return {};
    }
};
```

### Translation Key Naming Conventions

**Recommended Key Format**:

-   Use snake_case: `welcome_message`, `login_button`
-   Use descriptive names: `quiz_completion_message` instead of `msg1`
-   Group related keys: `auth_login`, `auth_register`, `auth_logout`
-   Use consistent prefixes: `error_`, `success_`, `validation_`

**Examples of Valid Keys**:

-   `welcome_message`
-   `auth_login_success`
-   `quiz_attempt_submitted`
-   `error_invalid_credentials`
-   `validation_email_required`

**Examples of Invalid Keys**:

-   `welcome.message` (contains dots)
-   `welcome message` (contains spaces)
-   `welcome-message!` (contains special characters)

---

## Authentication Endpoints

The authentication system provides secure user registration, login, logout, and profile management functionality. All authentication endpoints return consistent response formats and handle various error scenarios.

### POST /auth/register

**Description**: Register a new user account with automatic login

**Authentication**: None required

**Request Parameters**:

| Field                   | Type    | Required | Description                                            |
| ----------------------- | ------- | -------- | ------------------------------------------------------ |
| `name`                  | string  | Yes      | Full name (max 255 characters)                         |
| `email`                 | string  | Yes      | Valid email address (unique)                           |
| `password`              | string  | Yes      | Password (min 8 characters)                            |
| `password_confirmation` | string  | Yes      | Password confirmation (must match)                     |
| `phone`                 | string  | Yes      | Phone number (max 20 characters)                       |
| `preferred_language`    | string  | Yes      | Language code (must exist in system)                   |
| `level_id`              | integer | Yes      | Level ID (must exist in system)                        |
| `role`                  | string  | Optional | User role (student/teacher/admin, defaults to student) |
| `notify_email`          | boolean | Optional | Email notifications preference (default: true)         |
| `notify_whatsapp`       | boolean | Optional | WhatsApp notifications preference (default: false)     |

**Request Body Examples**:

**Student Registration**:
```json
{
    "name": "Maria Garcia",
    "email": "maria.garcia@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "phone": "+34-987-654-321",
    "preferred_language": "es",
    "level_id": 1,
    "role": "student",
    "notify_email": true,
    "notify_whatsapp": true
}
```

**Teacher Registration**:
```json
{
    "name": "Ahmed Hassan",
    "email": "ahmed.hassan@example.com",
    "password": "TeacherPass456!",
    "password_confirmation": "TeacherPass456!",
    "phone": "+20-123-456-789",
    "preferred_language": "ar",
    "level_id": 3,
    "role": "teacher",
    "notify_email": true,
    "notify_whatsapp": false
}
```

**Admin Registration**:
```json
{
    "name": "Sarah Johnson",
    "email": "sarah.admin@example.com",
    "password": "AdminSecure789!",
    "password_confirmation": "AdminSecure789!",
    "phone": "+1-555-0123",
    "preferred_language": "en",
    "level_id": 3,
    "role": "admin",
    "notify_email": true,
    "notify_whatsapp": false
}
```

**Success Response Examples**:

**Student Registration Success (201)**:
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 45,
            "name": "Maria Garcia",
            "email": "maria.garcia@example.com",
            "phone": "+34-987-654-321",
            "role": "student",
            "preferred_language": "es",
            "notify_email": true,
            "notify_whatsapp": true,
            "roles": ["student"],
            "level": {
                "id": 1,
                "name": "Beginner",
                "description": "Basic level for new learners"
            },
            "created_at": "2024-01-20T14:30:00.000000Z",
            "updated_at": "2024-01-20T14:30:00.000000Z"
        },
        "token": "45|def789ghi012jkl345mno678pqr901stu234vwx567yz890abc"
    }
}
```

**Teacher Registration Success (201)**:
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 22,
            "name": "Ahmed Hassan",
            "email": "ahmed.hassan@example.com",
            "phone": "+20-123-456-789",
            "role": "teacher",
            "preferred_language": "ar",
            "notify_email": true,
            "notify_whatsapp": false,
            "roles": ["teacher"],
            "level": {
                "id": 3,
                "name": "Advanced",
                "description": "Advanced level for experienced learners"
            },
            "permissions": ["create-quizzes", "manage-meetings", "view-students"],
            "created_at": "2024-01-20T15:45:00.000000Z",
            "updated_at": "2024-01-20T15:45:00.000000Z"
        },
        "token": "22|ghi345jkl678mno901pqr234stu567vwx890yz123abc456def"
    }
}
```

**Admin Registration Success (201)**:
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "Sarah Johnson",
            "email": "sarah.admin@example.com",
            "phone": "+1-555-0123",
            "role": "admin",
            "preferred_language": "en",
            "notify_email": true,
            "notify_whatsapp": false,
            "roles": ["admin"],
            "level": {
                "id": 3,
                "name": "Advanced",
                "description": "Advanced level for experienced learners"
            },
            "permissions": ["manage-users", "manage-programs", "manage-settings", "view-analytics"],
            "created_at": "2024-01-20T16:00:00.000000Z",
            "updated_at": "2024-01-20T16:00:00.000000Z"
        },
        "token": "1|jkl678mno901pqr234stu567vwx890yz123abc456def789ghi"
    }
}
```

**Validation Error Response Examples**:

**Multiple Field Validation Errors (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email has already been taken."],
        "password": ["The password must be at least 8 characters."],
        "password_confirmation": ["The password confirmation does not match."],
        "phone": ["The phone format is invalid."],
        "preferred_language": ["The selected preferred language is invalid."],
        "level_id": ["The selected level id is invalid."],
        "role": ["The selected role is invalid."]
    }
}
```

**Email Already Exists Error (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

**Password Validation Error (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "password": [
            "The password must be at least 8 characters.",
            "The password must contain at least one uppercase letter.",
            "The password must contain at least one number.",
            "The password must contain at least one special character."
        ],
        "password_confirmation": ["The password confirmation does not match."]
    }
}
```

**Phone Number Validation Error (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "phone": [
            "The phone field is required.",
            "The phone format is invalid.",
            "The phone has already been taken."
        ]
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "phone": "+1234567890",
    "preferred_language": "en",
    "level_id": 1
  }'
```

---

### POST /auth/login

**Description**: Authenticate user credentials and receive access token

**Authentication**: None required

**Request Parameters**:

| Field      | Type   | Required | Description          |
| ---------- | ------ | -------- | -------------------- |
| `email`    | string | Yes      | User's email address |
| `password` | string | Yes      | User's password      |

**Request Body Example**:

```json
{
    "email": "john@example.com",
    "password": "SecurePass123!"
}
```

**Success Response Examples**:

**Student Login Success (200)**:
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 45,
            "name": "Maria Garcia",
            "email": "maria.garcia@example.com",
            "phone": "+34-987-654-321",
            "role": "student",
            "preferred_language": "es",
            "notify_email": true,
            "notify_whatsapp": true,
            "roles": ["student"],
            "permissions": ["take-quizzes", "view-enrollments", "attend-meetings"],
            "last_login": "2024-01-20T14:30:00.000000Z",
            "enrollments_count": 3,
            "active_programs": 2
        },
        "token": "45|def789ghi012jkl345mno678pqr901stu234vwx567yz890abc",
        "expires_at": "2024-02-19T14:30:00.000000Z"
    }
}
```

**Teacher Login Success (200)**:
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 22,
            "name": "Ahmed Hassan",
            "email": "ahmed.hassan@example.com",
            "phone": "+20-123-456-789",
            "role": "teacher",
            "preferred_language": "ar",
            "notify_email": true,
            "notify_whatsapp": false,
            "roles": ["teacher"],
            "permissions": ["create-quizzes", "manage-meetings", "view-students", "grade-assignments"],
            "last_login": "2024-01-20T15:45:00.000000Z",
            "assigned_languages": ["Arabic", "English"],
            "active_programs": 5,
            "total_students": 67
        },
        "token": "22|ghi345jkl678mno901pqr234stu567vwx890yz123abc456def",
        "expires_at": "2024-02-19T15:45:00.000000Z"
    }
}
```

**Admin Login Success (200)**:
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Sarah Johnson",
            "email": "sarah.admin@example.com",
            "phone": "+1-555-0123",
            "role": "admin",
            "preferred_language": "en",
            "notify_email": true,
            "notify_whatsapp": false,
            "roles": ["admin"],
            "permissions": ["manage-users", "manage-programs", "manage-settings", "view-analytics", "system-admin"],
            "last_login": "2024-01-20T16:00:00.000000Z",
            "system_stats": {
                "total_users": 1247,
                "active_programs": 23,
                "pending_enrollments": 45
            }
        },
        "token": "1|jkl678mno901pqr234stu567vwx890yz123abc456def789ghi",
        "expires_at": "2024-02-19T16:00:00.000000Z"
    }
}
```

**Authentication Error Response Examples**:

**Invalid Credentials (401)**:
```json
{
    "success": false,
    "message": "Invalid credentials",
    "errors": {
        "email": ["These credentials do not match our records."]
    }
}
```

**Account Disabled (401)**:
```json
{
    "success": false,
    "message": "Account disabled",
    "errors": {
        "account": ["Your account has been disabled. Please contact an administrator."]
    }
}
```

**Too Many Login Attempts (429)**:
```json
{
    "success": false,
    "message": "Too many login attempts",
    "errors": {
        "throttle": ["Too many login attempts. Please try again in 60 seconds."]
    },
    "retry_after": 60
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123!"
  }'
```

---

### POST /auth/logout

**Description**: Logout user and invalidate current access token

**Authentication**: Required (Bearer token)

**Request Body**: None required

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Logout successful"
}
```

**Authentication Error Response (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/auth/logout" \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz" \
  -H "Content-Type: application/json"
```

---

### GET /auth/profile

**Description**: Get current authenticated user's profile information with roles and permissions

**Authentication**: Required (Bearer token)

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1234567890",
            "role": "student",
            "preferred_language": "en",
            "notify_email": true,
            "notify_whatsapp": false,
            "roles": ["student"],
            "permissions": ["take-quizzes", "view-enrollments"],
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    }
}
```

**Authentication Error Response (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/auth/profile" \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz" \
  -H "Content-Type: application/json"
```

---

### GET /user

**Description**: Get authenticated user information (Laravel Sanctum standard endpoint)

**Authentication**: Required (Bearer token)

**Success Response (200)**:

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "student",
    "preferred_language": "en",
    "notify_email": true,
    "notify_whatsapp": false,
    "email_verified_at": null,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

**Authentication Error Response (401)**:

```json
{
    "message": "Unauthenticated."
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/user" \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz" \
  -H "Content-Type: application/json"
```

---

### Authentication Workflow Examples

#### Complete Registration and Login Flow

**Step 1: Register New User**

```bash
# Register a new student
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Alice Johnson",
    "email": "alice@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "phone": "+1987654321",
    "preferred_language": "en",
    "level_id": 2,
    "role": "student"
  }'
```

**Step 2: Use Returned Token**

```bash
# Use the token from registration response to access protected endpoints
curl -X GET "https://your-domain.com/api/auth/profile" \
  -H "Authorization: Bearer {token_from_registration}" \
  -H "Content-Type: application/json"
```

**Step 3: Logout When Done**

```bash
# Logout to invalidate the token
curl -X POST "https://your-domain.com/api/auth/logout" \
  -H "Authorization: Bearer {token_from_registration}" \
  -H "Content-Type: application/json"
```

#### Subsequent Login Flow

**Step 1: Login with Credentials**

```bash
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "alice@example.com",
    "password": "SecurePass123!"
  }'
```

**Step 2: Access Protected Resources**

```bash
# Access role-specific endpoints
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer {token_from_login}" \
  -H "Content-Type: application/json"
```

#### Role-Based Access Examples

**Admin User Authentication**:

```bash
# Admin login
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "AdminPass123!"
  }'

# Access admin-only endpoint
curl -X GET "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Teacher User Authentication**:

```bash
# Teacher login
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teacher@example.com",
    "password": "TeacherPass123!"
  }'

# Access teacher endpoint
curl -X GET "https://your-domain.com/api/teacher/quizzes" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

## Administrative Functions

All administrative endpoints require admin role authentication.

### User Management

The User Management endpoints provide comprehensive CRUD operations for managing users in the Learn Academy system. These endpoints allow administrators to create, read, update, and delete user accounts, manage user roles and permissions, and handle user-related administrative tasks.

#### User Data Schema

**User Object Structure**:

| Field                | Type     | Description                      | Required          | Validation Rules                 |
| -------------------- | -------- | -------------------------------- | ----------------- | -------------------------------- |
| `id`                 | integer  | Unique user identifier           | Auto-generated    | Primary key                      |
| `name`               | string   | Full name of the user            | Yes               | 2-255 characters                 |
| `email`              | string   | Email address (unique)           | Yes               | Valid email format, unique       |
| `phone`              | string   | Phone number                     | No                | Valid phone format               |
| `password`           | string   | User password                    | Yes (create only) | Min 8 characters, hashed         |
| `role`               | string   | Primary user role                | Yes               | One of: admin, teacher, student  |
| `preferred_language` | string   | User's preferred language        | No                | One of: ar, en, es (default: en) |
| `preferred_locale`   | string   | User's preferred locale          | No                | One of: ar, en, es               |
| `notify_email`       | boolean  | Email notification preference    | No                | Default: true                    |
| `notify_whatsapp`    | boolean  | WhatsApp notification preference | No                | Default: false                   |
| `image_path`         | string   | Profile image path               | No                | Valid file path                  |
| `roles`              | array    | Array of assigned roles          | Auto-populated    | Spatie roles                     |
| `permissions`        | array    | Array of permissions             | Auto-populated    | Spatie permissions               |
| `created_at`         | datetime | Account creation timestamp       | Auto-generated    | ISO 8601 format                  |
| `updated_at`         | datetime | Last update timestamp            | Auto-generated    | ISO 8601 format                  |
| `deleted_at`         | datetime | Soft deletion timestamp          | Auto-generated    | ISO 8601 format (nullable)       |

---

#### GET /admin/users

**Description**: Retrieve a paginated list of all users in the system with optional filtering and search capabilities

**Authentication**: Required (Admin role)

**Purpose**:

-   View all system users for administrative management
-   Search and filter users by various criteria
-   Monitor user accounts and their status
-   Prepare data for bulk operations

**Query Parameters**:

| Parameter            | Type    | Required | Description                             | Default    | Validation                                  |
| -------------------- | ------- | -------- | --------------------------------------- | ---------- | ------------------------------------------- |
| `page`               | integer | No       | Page number for pagination              | 1          | Min: 1                                      |
| `per_page`           | integer | No       | Items per page                          | 20         | Min: 1, Max: 100                            |
| `search`             | string  | No       | Search term for name/email              | -          | Max: 255 chars                              |
| `role`               | string  | No       | Filter by user role                     | -          | One of: admin, teacher, student             |
| `preferred_language` | string  | No       | Filter by language preference           | -          | One of: ar, en, es                          |
| `notify_email`       | boolean | No       | Filter by email notification setting    | -          | true/false                                  |
| `notify_whatsapp`    | boolean | No       | Filter by WhatsApp notification setting | -          | true/false                                  |
| `sort_by`            | string  | No       | Sort field                              | created_at | One of: name, email, created_at, updated_at |
| `sort_order`         | string  | No       | Sort direction                          | desc       | One of: asc, desc                           |

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john.doe@example.com",
                "phone": "+1234567890",
                "role": "student",
                "preferred_language": "en",
                "preferred_locale": "en",
                "notify_email": true,
                "notify_whatsapp": false,
                "image_path": "/storage/profiles/john-doe.jpg",
                "roles": [
                    {
                        "id": 3,
                        "name": "student",
                        "guard_name": "api",
                        "created_at": "2024-01-01T00:00:00.000000Z",
                        "updated_at": "2024-01-01T00:00:00.000000Z",
                        "pivot": {
                            "model_type": "App\\Models\\User",
                            "model_id": 1,
                            "role_id": 3
                        }
                    }
                ],
                "permissions": [
                    {
                        "id": 15,
                        "name": "take-quizzes",
                        "guard_name": "api",
                        "created_at": "2024-01-01T00:00:00.000000Z",
                        "updated_at": "2024-01-01T00:00:00.000000Z",
                        "pivot": {
                            "model_type": "App\\Models\\User",
                            "model_id": 1,
                            "permission_id": 15
                        }
                    }
                ],
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z",
                "deleted_at": null,
                "email_verified_at": "2024-01-15T10:35:00.000000Z"
            },
            {
                "id": 2,
                "name": "Maria Rodriguez",
                "email": "maria.rodriguez@example.com",
                "phone": "+34612345678",
                "role": "teacher",
                "preferred_language": "es",
                "preferred_locale": "es",
                "notify_email": true,
                "notify_whatsapp": true,
                "image_path": "/storage/profiles/maria-rodriguez.jpg",
                "roles": [
                    {
                        "id": 2,
                        "name": "teacher",
                        "guard_name": "api",
                        "created_at": "2024-01-01T00:00:00.000000Z",
                        "updated_at": "2024-01-01T00:00:00.000000Z",
                        "pivot": {
                            "model_type": "App\\Models\\User",
                            "model_id": 2,
                            "role_id": 2
                        }
                    }
                ],
                "permissions": [
                    {
                        "id": 8,
                        "name": "manage-quizzes",
                        "guard_name": "api",
                        "created_at": "2024-01-01T00:00:00.000000Z",
                        "updated_at": "2024-01-01T00:00:00.000000Z",
                        "pivot": {
                            "model_type": "App\\Models\\User",
                            "model_id": 2,
                            "permission_id": 8
                        }
                    },
                    {
                        "id": 12,
                        "name": "manage-meetings",
                        "guard_name": "api",
                        "created_at": "2024-01-01T00:00:00.000000Z",
                        "updated_at": "2024-01-01T00:00:00.000000Z",
                        "pivot": {
                            "model_type": "App\\Models\\User",
                            "model_id": 2,
                            "permission_id": 12
                        }
                    }
                ],
                "created_at": "2024-01-14T08:15:00.000000Z",
                "updated_at": "2024-01-16T14:22:00.000000Z",
                "deleted_at": null,
                "email_verified_at": "2024-01-14T08:20:00.000000Z"
            },
            {
                "id": 3,
                "name": "Ahmed Hassan",
                "email": "ahmed.hassan@example.com",
                "phone": "+201234567890",
                "role": "student",
                "preferred_language": "ar",
                "preferred_locale": "ar",
                "notify_email": false,
                "notify_whatsapp": true,
                "image_path": null,
                "roles": [
                    {
                        "id": 3,
                        "name": "student",
                        "guard_name": "api",
                        "created_at": "2024-01-01T00:00:00.000000Z",
                        "updated_at": "2024-01-01T00:00:00.000000Z",
                        "pivot": {
                            "model_type": "App\\Models\\User",
                            "model_id": 3,
                            "role_id": 3
                        }
                    }
                ],
                "permissions": [
                    {
                        "id": 15,
                        "name": "take-quizzes",
                        "guard_name": "api",
                        "created_at": "2024-01-01T00:00:00.000000Z",
                        "updated_at": "2024-01-01T00:00:00.000000Z",
                        "pivot": {
                            "model_type": "App\\Models\\User",
                            "model_id": 3,
                            "permission_id": 15
                        }
                    }
                ],
                "created_at": "2024-01-16T12:45:00.000000Z",
                "updated_at": "2024-01-16T12:45:00.000000Z",
                "deleted_at": null,
                "email_verified_at": null
            }
        ],
        "current_page": 1,
        "per_page": 20,
        "total": 127,
        "last_page": 7,
        "from": 1,
        "to": 20,
        "path": "https://your-domain.com/api/admin/users",
        "first_page_url": "https://your-domain.com/api/admin/users?page=1",
        "last_page_url": "https://your-domain.com/api/admin/users?page=7",
        "next_page_url": "https://your-domain.com/api/admin/users?page=2",
        "prev_page_url": null,
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "https://your-domain.com/api/admin/users?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "https://your-domain.com/api/admin/users?page=2",
                "label": "2",
                "active": false
            },
            {
                "url": "https://your-domain.com/api/admin/users?page=3",
                "label": "3",
                "active": false
            }
        ]
    },
    "meta": {
        "pagination": {
            "count": 20,
            "current_page": 1,
            "per_page": 20,
            "total": 127,
            "total_pages": 7
        },
        "filters_applied": {
            "search": null,
            "role": null,
            "preferred_language": null,
            "notify_email": null,
            "notify_whatsapp": null,
            "sort_by": "created_at",
            "sort_order": "desc"
        },
        "summary": {
            "total_users": 127,
            "by_role": {
                "admin": 3,
                "teacher": 18,
                "student": 106
            },
            "by_language": {
                "en": 67,
                "es": 35,
                "ar": 25
            },
            "email_notifications_enabled": 89,
            "whatsapp_notifications_enabled": 42
        }
    }
}
```

**Filtered Response Examples**:

**Search by Name** (`?search=maria`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 2,
                "name": "Maria Rodriguez",
                "email": "maria.rodriguez@example.com",
                "role": "teacher",
                "preferred_language": "es"
            },
            {
                "id": 45,
                "name": "Maria Garcia",
                "email": "maria.garcia@example.com",
                "role": "student",
                "preferred_language": "es"
            }
        ],
        "total": 2
    }
}
```

**Filter by Role** (`?role=teacher`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 2,
                "name": "Maria Rodriguez",
                "email": "maria.rodriguez@example.com",
                "role": "teacher",
                "preferred_language": "es",
                "permissions": ["manage-quizzes", "manage-meetings", "view-students"]
            },
            {
                "id": 15,
                "name": "Sarah Johnson",
                "email": "sarah.johnson@example.com",
                "role": "teacher",
                "preferred_language": "en",
                "permissions": ["manage-quizzes", "manage-meetings", "view-students"]
            }
        ],
        "total": 18
    }
}
```

**Filter by Language and Notifications** (`?preferred_language=ar&notify_whatsapp=true`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 3,
                "name": "Ahmed Hassan",
                "email": "ahmed.hassan@example.com",
                "role": "student",
                "preferred_language": "ar",
                "notify_email": false,
                "notify_whatsapp": true
            },
            {
                "id": 67,
                "name": "Fatima Al-Zahra",
                "email": "fatima.alzahra@example.com",
                "role": "teacher",
                "preferred_language": "ar",
                "notify_email": true,
                "notify_whatsapp": true
            }
        ],
        "total": 8
    }
}
```

**Sort by Name Ascending** (`?sort_by=name&sort_order=asc`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 3,
                "name": "Ahmed Hassan",
                "email": "ahmed.hassan@example.com",
                "role": "student"
            },
            {
                "id": 89,
                "name": "Alice Johnson",
                "email": "alice.johnson@example.com",
                "role": "student"
            },
            {
                "id": 12,
                "name": "Carlos Rodriguez",
                "email": "carlos.rodriguez@example.com",
                "role": "teacher"
            }
        ]
    }
}
            },
            {
                "url": "https://your-domain.com/api/admin/users?page=2",
                "label": "Next &raquo;",
                "active": false
            }
        ]
    },
    "meta": {
        "pagination": {
            "count": 20,
            "current_page": 1,
            "per_page": 20,
            "total": 127,
            "total_pages": 7
        },
        "filters_applied": {
            "search": null,
            "role": null,
            "preferred_language": null,
            "sort_by": "created_at",
            "sort_order": "desc"
        },
        "summary": {
            "total_users": 127,
            "verified_users": 98,
            "unverified_users": 29,
            "by_role": {
                "admin": 3,
                "teacher": 24,
                "student": 100
            },
            "by_language": {
                "en": 67,
                "es": 35,
                "ar": 25
            },
            "notification_preferences": {
                "email_enabled": 89,
                "whatsapp_enabled": 45,
                "both_enabled": 32,
                "none_enabled": 13
            }
        }
    }
}
```

**Filtered Response Examples**:

**Search by Name** (`?search=maria`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 2,
                "name": "Maria Rodriguez",
                "email": "maria.rodriguez@example.com",
                "phone": "+34612345678",
                "role": "teacher",
                "preferred_language": "es",
                "preferred_locale": "es",
                "notify_email": true,
                "notify_whatsapp": true,
                "image_path": "/storage/profiles/maria-rodriguez.jpg",
                "roles": ["teacher"],
                "permissions": ["manage-quizzes", "manage-meetings"],
                "created_at": "2024-01-14T08:15:00.000000Z",
                "updated_at": "2024-01-16T14:22:00.000000Z",
                "deleted_at": null,
                "email_verified_at": "2024-01-14T08:20:00.000000Z"
            }
        ],
        "current_page": 1,
        "per_page": 20,
        "total": 1,
        "last_page": 1,
        "from": 1,
        "to": 1
    },
    "meta": {
        "filters_applied": {
            "search": "maria",
            "role": null,
            "preferred_language": null
        }
    }
}
```

**Filter by Role** (`?role=teacher`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 2,
                "name": "Maria Rodriguez",
                "email": "maria.rodriguez@example.com",
                "role": "teacher",
                "preferred_language": "es"
            },
            {
                "id": 5,
                "name": "Pierre Dubois",
                "email": "pierre.dubois@example.com",
                "role": "teacher",
                "preferred_language": "fr"
            }
        ],
        "current_page": 1,
        "per_page": 20,
        "total": 24,
        "last_page": 2
    },
    "meta": {
        "filters_applied": {
            "search": null,
            "role": "teacher",
            "preferred_language": null
        },
        "summary": {
            "total_teachers": 24,
            "active_teachers": 22,
            "inactive_teachers": 2
        }
    }
}
```

**Filter by Language and Role** (`?role=student&preferred_language=ar`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 3,
                "name": "Ahmed Hassan",
                "email": "ahmed.hassan@example.com",
                "role": "student",
                "preferred_language": "ar"
            },
            {
                "id": 15,
                "name": "Fatima Al-Zahra",
                "email": "fatima.alzahra@example.com",
                "role": "student",
                "preferred_language": "ar"
            }
        ],
        "current_page": 1,
        "per_page": 20,
        "total": 25,
        "last_page": 2
    },
    "meta": {
        "filters_applied": {
            "search": null,
            "role": "student",
            "preferred_language": "ar"
        }
    }
}
```

**Empty Results** (`?search=nonexistent`):

```json
{
    "success": true,
    "data": {
        "data": [],
        "current_page": 1,
        "per_page": 20,
        "total": 0,
        "last_page": 1,
        "from": null,
        "to": null
    },
    "meta": {
        "filters_applied": {
            "search": "nonexistent",
            "role": null,
            "preferred_language": null
        },
        "message": "No users found matching the search criteria"
    }
}
```

**Error Responses**:

**Validation Error (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "per_page": ["The per page field must be between 1 and 100."],
        "role": ["The selected role is invalid."],
        "preferred_language": ["The selected preferred language is invalid."],
        "sort_by": ["The selected sort by is invalid."],
        "sort_order": ["The sort order field must be either asc or desc."]
    }
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Validation Error (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "per_page": ["The per page must not be greater than 100."],
        "role": ["The selected role is invalid."]
    }
}
```

**cURL Examples**:

```bash
# Get all users with default pagination
curl -X GET "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Search for users by name or email
curl -X GET "https://your-domain.com/api/admin/users?search=maria" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Filter by role and language preference
curl -X GET "https://your-domain.com/api/admin/users?role=teacher&preferred_language=es" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Filter by notification preferences
curl -X GET "https://your-domain.com/api/admin/users?notify_email=true&notify_whatsapp=false" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Custom pagination and sorting
curl -X GET "https://your-domain.com/api/admin/users?page=2&per_page=50&sort_by=name&sort_order=asc" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Complex filtering with multiple parameters
curl -X GET "https://your-domain.com/api/admin/users?search=ahmed&role=student&preferred_language=ar&sort_by=created_at&sort_order=desc&per_page=25" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**JavaScript Integration Examples**:

```javascript
// User management class for API integration
class UserManager {
    constructor(apiBaseUrl, authToken) {
        this.apiBaseUrl = apiBaseUrl;
        this.authToken = authToken;
    }

    async getUsers(filters = {}) {
        const params = new URLSearchParams();
        
        // Add filters to params
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.append(key, value);
            }
        });
        
        const response = await fetch(`${this.apiBaseUrl}/admin/users?${params}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${this.authToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        return response.json();
    }

    async searchUsers(searchTerm, additionalFilters = {}) {
        return this.getUsers({ 
            search: searchTerm, 
            ...additionalFilters 
        });
    }

    async getUsersByRole(role, page = 1, perPage = 20) {
        return this.getUsers({ 
            role, 
            page, 
            per_page: perPage,
            sort_by: 'name',
            sort_order: 'asc'
        });
    }

    async getAllUsersAcrossPages(filters = {}) {
        let allUsers = [];
        let page = 1;
        let hasMorePages = true;

        while (hasMorePages) {
            const response = await this.getUsers({ 
                ...filters, 
                page, 
                per_page: 100 
            });

            if (response.success && response.data.data.length > 0) {
                allUsers = allUsers.concat(response.data.data);
                hasMorePages = page < response.data.last_page;
                page++;
            } else {
                hasMorePages = false;
            }
        }

        return allUsers;
    }
}

// Usage examples
const userManager = new UserManager('https://your-domain.com/api', adminToken);

// Get paginated users
const users = await userManager.getUsers({ page: 1, per_page: 20 });

// Search for specific users
const searchResults = await userManager.searchUsers('maria', { 
    role: 'teacher' 
});

// Get all teachers
const teachers = await userManager.getUsersByRole('teacher');

// Get all Arabic-speaking students
const arabicStudents = await userManager.getUsers({
    role: 'student',
    preferred_language: 'ar',
    sort_by: 'created_at',
    sort_order: 'desc'
});

// Get complete user list (all pages)
const allUsers = await userManager.getAllUsersAcrossPages();
console.log(`Total users: ${allUsers.length}`);
```

**Python Integration Examples**:

```python
import requests
from typing import Dict, List, Optional, Any

class UserManager:
    def __init__(self, api_base_url: str, auth_token: str):
        self.api_base_url = api_base_url
        self.auth_token = auth_token
        self.headers = {
            'Authorization': f'Bearer {auth_token}',
            'Content-Type': 'application/json'
        }

    def get_users(self, filters: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """Get users with optional filtering and pagination"""
        url = f"{self.api_base_url}/admin/users"
        params = {k: v for k, v in (filters or {}).items() if v is not None}
        
        response = requests.get(url, headers=self.headers, params=params)
        return response.json()

    def search_users(self, search_term: str, **kwargs) -> Dict[str, Any]:
        """Search users by name or email"""
        filters = {'search': search_term, **kwargs}
        return self.get_users(filters)

    def get_users_by_role(self, role: str, page: int = 1, per_page: int = 20) -> Dict[str, Any]:
        """Get users filtered by role with pagination"""
        return self.get_users({
            'role': role,
            'page': page,
            'per_page': per_page,
            'sort_by': 'name',
            'sort_order': 'asc'
        })

    def get_all_users_paginated(self, filters: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """Get all users across multiple pages"""
        all_users = []
        page = 1
        
        while True:
            current_filters = (filters or {}).copy()
            current_filters.update({'page': page, 'per_page': 100})
            
            response = self.get_users(current_filters)
            
            if not response.get('success') or not response['data']['data']:
                break
                
            all_users.extend(response['data']['data'])
            
            if page >= response['data']['last_page']:
                break
                
            page += 1
        
        return all_users

    def get_user_statistics(self, filters: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """Get user statistics from the meta information"""
        response = self.get_users(filters)
        
        if response.get('success') and 'meta' in response:
            return response['meta'].get('summary', {})
        
        return {}

# Usage examples
user_manager = UserManager('https://your-domain.com/api', 'your_admin_token')

# Get paginated users
users_response = user_manager.get_users({'page': 1, 'per_page': 20})
if users_response['success']:
    users = users_response['data']['data']
    print(f"Found {len(users)} users on page 1")

# Search for users
search_results = user_manager.search_users('maria', role='teacher')

# Get all teachers
teachers = user_manager.get_users_by_role('teacher')

# Get users with specific preferences
arabic_students = user_manager.get_users({
    'role': 'student',
    'preferred_language': 'ar',
    'notify_whatsapp': True
})

# Get complete user list
all_users = user_manager.get_all_users_paginated()
print(f"Total users in system: {len(all_users)}")

# Get user statistics
stats = user_manager.get_user_statistics()
print(f"User breakdown by role: {stats.get('by_role', {})}")
```

---

#### POST /admin/users

**Description**: Create a new user account with specified role and preferences

**Authentication**: Required (Admin role)

**Purpose**:

-   Create new user accounts for students, teachers, or administrators
-   Set initial user preferences and notification settings
-   Assign primary role and configure account settings
-   Generate secure password hash and initial access

**Request Body Parameters**:

| Field                | Type    | Required | Description                      | Validation Rules                         |
| -------------------- | ------- | -------- | -------------------------------- | ---------------------------------------- |
| `name`               | string  | Yes      | Full name of the user            | 2-255 characters                         |
| `email`              | string  | Yes      | Email address (must be unique)   | Valid email, unique in system            |
| `password`           | string  | Yes      | User password                    | Min 8 characters, mixed case recommended |
| `phone`              | string  | No       | Phone number                     | Valid international format               |
| `role`               | string  | Yes      | Primary user role                | One of: admin, teacher, student          |
| `preferred_language` | string  | No       | User's preferred language        | One of: ar, en, es (default: en)         |
| `preferred_locale`   | string  | No       | User's preferred locale          | One of: ar, en, es (default: en)         |
| `notify_email`       | boolean | No       | Email notification preference    | Default: true                            |
| `notify_whatsapp`    | boolean | No       | WhatsApp notification preference | Default: false                           |

**Request Body Example**:

```json
{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "SecurePass123!",
    "phone": "+1234567891",
    "role": "teacher",
    "preferred_language": "es",
    "preferred_locale": "es",
    "notify_email": true,
    "notify_whatsapp": true
}
```

**Success Response (201)**:

```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "phone": "+1234567891",
        "role": "teacher",
        "preferred_language": "es",
        "preferred_locale": "es",
        "notify_email": true,
        "notify_whatsapp": true,
        "image_path": null,
        "roles": ["teacher"],
        "permissions": [],
        "created_at": "2024-01-15T11:00:00.000000Z",
        "updated_at": "2024-01-15T11:00:00.000000Z",
        "deleted_at": null
    }
}
```

**Error Responses**:

**Validation Error (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email has already been taken."],
        "password": ["The password must be at least 8 characters."],
        "role": ["The selected role is invalid."],
        "phone": ["The phone format is invalid."]
    }
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to create user",
    "error": "Database connection error"
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "SecurePass123!",
    "phone": "+1234567891",
    "role": "teacher",
    "preferred_language": "es",
    "preferred_locale": "es",
    "notify_email": true,
    "notify_whatsapp": true
  }'
```

---

#### GET /admin/users/{user}

**Description**: Retrieve detailed information for a specific user including roles and permissions

**Authentication**: Required (Admin role)

**Purpose**:

-   View complete user profile and account details
-   Check user roles and permission assignments
-   Review user activity and account status
-   Prepare data for user updates or role management

**Parameters**:

-   **Path**: `user` (integer, required) - User ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1234567890",
        "role": "student",
        "preferred_language": "en",
        "preferred_locale": "en",
        "notify_email": true,
        "notify_whatsapp": false,
        "image_path": "/storage/profiles/john-doe.jpg",
        "roles": [
            {
                "id": 3,
                "name": "student",
                "guard_name": "api",
                "created_at": "2024-01-01T00:00:00.000000Z",
                "updated_at": "2024-01-01T00:00:00.000000Z",
                "pivot": {
                    "model_type": "App\\Models\\User",
                    "model_id": 1,
                    "role_id": 3
                }
            }
        ],
        "permissions": [
            {
                "id": 15,
                "name": "take-quizzes",
                "guard_name": "api",
                "created_at": "2024-01-01T00:00:00.000000Z",
                "updated_at": "2024-01-01T00:00:00.000000Z",
                "pivot": {
                    "model_type": "App\\Models\\User",
                    "model_id": 1,
                    "permission_id": 15
                }
            }
        ],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "deleted_at": null,
        "email_verified_at": "2024-01-15T10:35:00.000000Z"
    }
}
```

**Error Responses**:

**User Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve user",
    "error": "Database query failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/users/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### PUT /admin/users/{user}

**Description**: Update user information, preferences, and account settings

**Authentication**: Required (Admin role)

**Purpose**:

-   Modify user profile information and contact details
-   Update user preferences and notification settings
-   Change user roles and access levels
-   Maintain user account information

**Parameters**:

-   **Path**: `user` (integer, required) - User ID

**Request Body Parameters**:

| Field                | Type    | Required | Description                      | Validation Rules                        |
| -------------------- | ------- | -------- | -------------------------------- | --------------------------------------- |
| `name`               | string  | No       | Full name of the user            | 2-255 characters                        |
| `email`              | string  | No       | Email address (must be unique)   | Valid email, unique except current user |
| `password`           | string  | No       | New password                     | Min 8 characters if provided            |
| `phone`              | string  | No       | Phone number                     | Valid international format              |
| `role`               | string  | No       | Primary user role                | One of: admin, teacher, student         |
| `preferred_language` | string  | No       | User's preferred language        | One of: ar, en, es                      |
| `preferred_locale`   | string  | No       | User's preferred locale          | One of: ar, en, es                      |
| `notify_email`       | boolean | No       | Email notification preference    | true/false                              |
| `notify_whatsapp`    | boolean | No       | WhatsApp notification preference | true/false                              |

**Request Body Example**:

```json
{
    "name": "John Updated",
    "email": "john.updated@example.com",
    "phone": "+1234567899",
    "role": "teacher",
    "preferred_language": "es",
    "preferred_locale": "es",
    "notify_email": false,
    "notify_whatsapp": true
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "User updated successfully",
    "data": {
        "id": 1,
        "name": "John Updated",
        "email": "john.updated@example.com",
        "phone": "+1234567899",
        "role": "teacher",
        "preferred_language": "es",
        "preferred_locale": "es",
        "notify_email": false,
        "notify_whatsapp": true,
        "image_path": "/storage/profiles/john-doe.jpg",
        "roles": ["teacher"],
        "permissions": [],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T12:00:00.000000Z",
        "deleted_at": null
    }
}
```

**Error Responses**:

**Validation Error (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password must be at least 8 characters."],
        "role": ["The selected role is invalid."],
        "phone": ["The phone format is invalid."]
    }
}
```

**User Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to update user",
    "error": "Database update failed"
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/admin/users/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated",
    "email": "john.updated@example.com",
    "phone": "+1234567899",
    "role": "teacher",
    "preferred_language": "es",
    "notify_email": false,
    "notify_whatsapp": true
  }'
```

---

#### DELETE /admin/users/{user}

**Description**: Soft delete a user account (marks as deleted but preserves data)

**Authentication**: Required (Admin role)

**Purpose**:

-   Remove user access while preserving historical data
-   Maintain referential integrity for enrollments and quiz attempts
-   Allow for potential account recovery
-   Comply with data retention policies

**Parameters**:

-   **Path**: `user` (integer, required) - User ID

**Important Notes**:

-   This performs a soft delete (sets `deleted_at` timestamp)
-   User data is preserved for historical records
-   User cannot log in after deletion
-   Related data (enrollments, quiz attempts) remains intact
-   Admin users cannot delete themselves
-   Cannot delete users with active enrollments (must revoke access first)

**Success Response (200)**:

```json
{
    "success": true,
    "message": "User deleted successfully",
    "data": {
        "deleted_user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "deleted_at": "2024-01-15T13:00:00.000000Z"
        }
    }
}
```

**Error Responses**:

**User Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**Cannot Delete Self (400)**:

```json
{
    "success": false,
    "message": "Cannot delete your own account"
}
```

**User Has Active Enrollments (400)**:

```json
{
    "success": false,
    "message": "Cannot delete user with active enrollments. Please revoke access first.",
    "data": {
        "active_enrollments_count": 3,
        "active_programs": [
            "Spanish Beginner",
            "English Intermediate",
            "Arabic Advanced"
        ]
    }
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to delete user",
    "error": "Database operation failed"
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/admin/users/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

### User Role Management

The system uses Spatie Laravel Permission package for role and permission management. Users can have multiple roles and permissions assigned.

#### Available Roles

| Role      | Description          | Capabilities                                              |
| --------- | -------------------- | --------------------------------------------------------- |
| `admin`   | System Administrator | Full system access, user management, system configuration |
| `teacher` | Teacher/Instructor   | Quiz creation, meeting management, student interaction    |
| `student` | Student/Learner      | Quiz taking, enrollment viewing, meeting participation    |

#### Available Permissions

| Permission            | Description                       | Applicable Roles |
| --------------------- | --------------------------------- | ---------------- |
| `manage-users`        | Create, update, delete users      | admin            |
| `manage-programs`     | Program administration            | admin, teacher   |
| `manage-quizzes`      | Quiz creation and management      | admin, teacher   |
| `take-quizzes`        | Take quizzes and submit attempts  | student          |
| `manage-meetings`     | Meeting scheduling and management | admin, teacher   |
| `view-analytics`      | Access system analytics           | admin            |
| `manage-translations` | Translation management            | admin            |
| `manage-settings`     | System settings configuration     | admin            |

#### Role Assignment Examples

**Assign Role to User**:

```bash
# This would be handled through Laravel's role assignment
# The API endpoints above handle role changes through the 'role' field
curl -X PUT "https://your-domain.com/api/admin/users/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "teacher"
  }'
```

**Multiple Role Assignment**:
Users can have multiple roles through the Spatie package. The primary `role` field determines the main role, while the `roles` array shows all assigned roles.

---

### User Management Workflows

#### Complete User Creation Workflow

**Step 1: Create User Account**

```bash
curl -X POST "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Teacher",
    "email": "teacher@example.com",
    "password": "SecurePass123!",
    "phone": "+1234567890",
    "role": "teacher",
    "preferred_language": "en",
    "notify_email": true,
    "notify_whatsapp": false
  }'
```

**Step 2: Verify User Creation**

```bash
curl -X GET "https://your-domain.com/api/admin/users/3" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Assign to Languages (for teachers)**

```bash
curl -X POST "https://your-domain.com/api/admin/assign-teacher-to-language" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 3,
    "language_id": 1
  }'
```

#### User Search and Filter Workflow

**Search by Name or Email**:

```bash
curl -X GET "https://your-domain.com/api/admin/users?search=john&per_page=10" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Filter by Role and Language**:

```bash
curl -X GET "https://your-domain.com/api/admin/users?role=teacher&preferred_language=es" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Sort and Paginate Results**:

```bash
curl -X GET "https://your-domain.com/api/admin/users?sort_by=created_at&sort_order=desc&page=2&per_page=25" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### Bulk User Operations

While individual user operations are handled through the endpoints above, bulk operations can be performed by iterating through user lists:

**Example: Update Multiple Users' Notification Preferences**

```bash
# First, get users to update
curl -X GET "https://your-domain.com/api/admin/users?role=student&notify_email=false" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Then update each user (this would typically be done programmatically)
curl -X PUT "https://your-domain.com/api/admin/users/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"notify_email": true}'
```

---

### User Management Best Practices

#### Security Considerations

1. **Password Requirements**: Enforce strong passwords (minimum 8 characters, mixed case, numbers, symbols)
2. **Email Verification**: Verify email addresses before account activation
3. **Role Validation**: Always validate role assignments and permissions
4. **Audit Logging**: Log all user management operations for security auditing
5. **Access Control**: Ensure only admins can perform user management operations

#### Data Integrity

1. **Unique Constraints**: Enforce email uniqueness across the system
2. **Soft Deletes**: Use soft deletes to preserve historical data
3. **Referential Integrity**: Check for related data before deletion
4. **Validation**: Validate all input data before processing
5. **Transaction Safety**: Use database transactions for complex operations

#### Performance Optimization

1. **Pagination**: Always use pagination for user lists
2. **Indexing**: Ensure proper database indexing on searchable fields
3. **Eager Loading**: Load related data (roles, permissions) efficiently
4. **Caching**: Cache frequently accessed user data
5. **Query Optimization**: Optimize database queries for large user sets

---

### Enrollment Management

The Enrollment Management system provides comprehensive functionality for managing student enrollments in programs. This includes pending enrollment retrieval, approval workflows, bulk operations, and enrollment statistics. The system supports both individual and bulk enrollment operations with detailed tracking and reporting capabilities.

#### Enrollment Data Schema

**Enrollment Object Structure**:

| Field               | Type     | Description                   | Required       | Validation Rules            |
| ------------------- | -------- | ----------------------------- | -------------- | --------------------------- |
| `id`                | integer  | Unique enrollment identifier  | Auto-generated | Primary key                 |
| `student`           | object   | Student information           | Auto-populated | User object                 |
| `program`           | object   | Program information           | Auto-populated | Program object              |
| `assigned_at`       | datetime | Initial enrollment timestamp  | Auto-generated | ISO 8601 format             |
| `access_granted_at` | datetime | Approval timestamp            | Admin action   | ISO 8601 format (nullable)  |
| `approved_by`       | integer  | Admin who approved enrollment | Admin action   | User ID (nullable)          |
| `has_access`        | boolean  | Current access status         | Computed       | Based on access_granted_at  |
| `status`            | string   | Enrollment status             | Computed       | 'pending' or 'approved'     |
| `days_pending`      | integer  | Days since enrollment         | Computed       | Calculated from assigned_at |

---

#### GET /admin/pending-enrollments

**Description**: Retrieve all pending enrollments across the system for administrative review and approval

**Authentication**: Required (Admin role)

**Purpose**:

-   View all students awaiting program access approval
-   Monitor enrollment queue and processing times
-   Identify enrollments requiring immediate attention
-   Prepare data for bulk approval operations

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "pending_enrollments": [
            {
                "id": 15,
                "student": {
                    "id": 42,
                    "name": "Ahmed Hassan",
                    "email": "ahmed.hassan@example.com",
                    "phone": "+201234567890"
                },
                "program": {
                    "id": 8,
                    "title": "Arabic for Beginners",
                    "language": "Arabic",
                    "level": "Beginner"
                },
                "assigned_at": "2024-01-10T14:30:00.000000Z"
            },
            {
                "id": 16,
                "student": {
                    "id": 43,
                    "name": "Maria Rodriguez",
                    "email": "maria.rodriguez@example.com",
                    "phone": "+34612345678"
                },
                "program": {
                    "id": 12,
                    "title": "Spanish Intermediate",
                    "language": "Spanish",
                    "level": "Intermediate"
                },
                "assigned_at": "2024-01-11T09:15:00.000000Z"
            }
        ]
    }
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve pending enrollments",
    "error": "Database connection timeout"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/pending-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/all-pending-enrollments

**Description**: Retrieve all pending enrollments with advanced filtering options for detailed analysis and management

**Authentication**: Required (Admin role)

**Purpose**:

-   Filter pending enrollments by specific criteria
-   Analyze enrollment patterns and bottlenecks
-   Generate reports on pending enrollment statistics
-   Support targeted approval workflows

**Query Parameters**:

| Parameter      | Type    | Required | Description                    | Validation                |
| -------------- | ------- | -------- | ------------------------------ | ------------------------- |
| `language_id`  | integer | No       | Filter by program language     | Exists in languages table |
| `level_id`     | integer | No       | Filter by program level        | Exists in levels table    |
| `program_id`   | integer | No       | Filter by specific program     | Exists in programs table  |
| `days_pending` | integer | No       | Filter by minimum days pending | Min: 0                    |

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "pending_enrollments": [
            {
                "id": 15,
                "student": {
                    "id": 42,
                    "name": "Ahmed Hassan",
                    "email": "ahmed.hassan@example.com",
                    "phone": "+201234567890"
                },
                "program": {
                    "id": 8,
                    "title": "Arabic for Beginners",
                    "language": "Arabic",
                    "level": "Beginner"
                },
                "assigned_at": "2024-01-10T14:30:00.000000Z",
                "days_pending": 5
            }
        ],
        "total": 23,
        "filters_applied": {
            "language_id": 2,
            "days_pending": 3
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "Invalid filter parameters",
    "errors": {
        "language_id": ["The selected language id is invalid."],
        "days_pending": ["The days pending must be at least 0."]
    }
}
```

**cURL Examples**:

```bash
# Get all pending enrollments
curl -X GET "https://your-domain.com/api/admin/all-pending-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Filter by language and minimum days pending
curl -X GET "https://your-domain.com/api/admin/all-pending-enrollments?language_id=2&days_pending=7" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Filter by specific program
curl -X GET "https://your-domain.com/api/admin/all-pending-enrollments?program_id=8" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/enrollment-statistics

**Description**: Retrieve comprehensive enrollment statistics across all programs for system monitoring and reporting

**Authentication**: Required (Admin role)

**Purpose**:

-   Monitor overall system enrollment health
-   Generate administrative reports and dashboards
-   Track enrollment trends and patterns
-   Support capacity planning and resource allocation

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "overview": {
            "total_enrollments": 1247,
            "pending_enrollments": 89,
            "approved_enrollments": 1158,
            "approval_rate": 92.86
        },
        "by_language": [
            {
                "language": "English",
                "language_id": 1,
                "total_enrollments": 542,
                "pending_enrollments": 34,
                "approved_enrollments": 508,
                "approval_rate": 93.73
            },
            {
                "language": "Arabic",
                "language_id": 2,
                "total_enrollments": 398,
                "pending_enrollments": 28,
                "approved_enrollments": 370,
                "approval_rate": 92.96
            },
            {
                "language": "Spanish",
                "language_id": 3,
                "total_enrollments": 307,
                "pending_enrollments": 27,
                "approved_enrollments": 280,
                "approval_rate": 91.21
            }
        ],
        "by_level": [
            {
                "level": "Beginner",
                "level_id": 1,
                "total_enrollments": 687,
                "pending_enrollments": 45,
                "approved_enrollments": 642,
                "approval_rate": 93.45
            },
            {
                "level": "Intermediate",
                "level_id": 2,
                "total_enrollments": 398,
                "pending_enrollments": 32,
                "approved_enrollments": 366,
                "approval_rate": 91.96
            },
            {
                "level": "Advanced",
                "level_id": 3,
                "total_enrollments": 162,
                "pending_enrollments": 12,
                "approved_enrollments": 150,
                "approval_rate": 92.59
            }
        ],
        "recent_activity": {
            "enrollments_last_7_days": 47,
            "approvals_last_7_days": 52,
            "pending_over_7_days": 23,
            "pending_over_30_days": 8
        },
        "top_programs": [
            {
                "program_id": 15,
                "program_title": "English for Business",
                "total_enrollments": 89,
                "pending_enrollments": 7,
                "approved_enrollments": 82
            },
            {
                "program_id": 8,
                "program_title": "Arabic for Beginners",
                "total_enrollments": 76,
                "pending_enrollments": 5,
                "approved_enrollments": 71
            }
        ]
    }
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve enrollment statistics",
    "error": "Statistics calculation failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/enrollment-statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### POST /admin/grant-access

**Description**: Grant program access to a specific student by approving their enrollment

**Authentication**: Required (Admin role)

**Purpose**:

-   Approve individual student enrollment requests
-   Grant immediate access to program content
-   Track approval actions and administrators
-   Maintain enrollment audit trail

**Request Body Parameters**:

| Field        | Type    | Required | Description                      | Validation                   |
| ------------ | ------- | -------- | -------------------------------- | ---------------------------- |
| `student_id` | integer | Yes      | ID of student to grant access    | Must exist in users table    |
| `program_id` | integer | Yes      | ID of program to grant access to | Must exist in programs table |

**Request Body Example**:

```json
{
    "student_id": 42,
    "program_id": 8
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Access granted successfully",
    "data": {
        "student_name": "Ahmed Hassan",
        "program_title": "Arabic for Beginners"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "student_id": ["The selected student id is invalid."],
        "program_id": ["The selected program id is invalid."]
    }
}
```

**Error Response - Enrollment Not Found (404)**:

```json
{
    "success": false,
    "message": "Failed to grant access. Enrollment not found."
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to grant access",
    "error": "Database transaction failed"
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/grant-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 42,
    "program_id": 8
  }'
```

---

#### POST /admin/bulk-grant-access

**Description**: Grant program access to multiple students simultaneously for efficient enrollment management

**Authentication**: Required (Admin role)

**Purpose**:

-   Approve multiple enrollment requests in a single operation
-   Improve administrative efficiency for large enrollment batches
-   Maintain consistent approval tracking across bulk operations
-   Reduce processing time for enrollment approvals

**Request Body Parameters**:

| Field           | Type    | Required | Description                          | Validation                        |
| --------------- | ------- | -------- | ------------------------------------ | --------------------------------- |
| `student_ids`   | array   | Yes      | Array of student IDs to grant access | Each ID must exist in users table |
| `student_ids.*` | integer | Yes      | Individual student ID                | Must be valid user ID             |
| `program_id`    | integer | Yes      | ID of program to grant access to     | Must exist in programs table      |

**Request Body Example**:

```json
{
    "student_ids": [42, 43, 44, 45],
    "program_id": 8
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Access granted to 4 students",
    "data": {
        "approved_count": 4,
        "program_title": "Arabic for Beginners"
    }
}
```

**Partial Success Response (200)**:

```json
{
    "success": true,
    "message": "Access granted to 3 students",
    "data": {
        "approved_count": 3,
        "program_title": "Arabic for Beginners"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "student_ids": ["The student ids field is required."],
        "student_ids.0": ["The selected student ids.0 is invalid."],
        "program_id": ["The selected program id is invalid."]
    }
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to bulk grant access",
    "error": "Bulk operation transaction failed"
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/bulk-grant-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_ids": [42, 43, 44, 45],
    "program_id": 8
  }'
```

---

#### POST /admin/bulk-approve-enrollments

**Description**: Approve multiple enrollments by their enrollment IDs for targeted bulk approval operations

**Authentication**: Required (Admin role)

**Purpose**:

-   Approve specific enrollment requests by enrollment ID
-   Support selective approval from filtered enrollment lists
-   Maintain precise control over which enrollments are approved
-   Enable approval workflows based on enrollment criteria

**Request Body Parameters**:

| Field              | Type    | Required | Description                        | Validation                              |
| ------------------ | ------- | -------- | ---------------------------------- | --------------------------------------- |
| `enrollment_ids`   | array   | Yes      | Array of enrollment IDs to approve | Each ID must exist in enrollments table |
| `enrollment_ids.*` | integer | Yes      | Individual enrollment ID           | Must be valid enrollment ID             |

**Request Body Example**:

```json
{
    "enrollment_ids": [15, 16, 17, 18, 19]
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Successfully approved 5 enrollments",
    "data": {
        "approved_count": 5,
        "total_requested": 5
    }
}
```

**Partial Success Response (200)**:

```json
{
    "success": true,
    "message": "Successfully approved 4 enrollments",
    "data": {
        "approved_count": 4,
        "total_requested": 5
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "enrollment_ids": ["The enrollment ids field is required."],
        "enrollment_ids.0": ["The selected enrollment ids.0 is invalid."]
    }
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to bulk approve enrollments",
    "error": "Bulk approval transaction failed"
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/bulk-approve-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "enrollment_ids": [15, 16, 17, 18, 19]
  }'
```

---

#### POST /admin/revoke-access

**Description**: Revoke program access from a student by removing their enrollment approval

**Authentication**: Required (Admin role)

**Purpose**:

-   Remove student access to program content
-   Handle disciplinary actions or policy violations
-   Manage enrollment corrections and adjustments
-   Maintain access control integrity

**Request Body Parameters**:

| Field        | Type    | Required | Description                         | Validation                   |
| ------------ | ------- | -------- | ----------------------------------- | ---------------------------- |
| `student_id` | integer | Yes      | ID of student to revoke access from | Must exist in users table    |
| `program_id` | integer | Yes      | ID of program to revoke access from | Must exist in programs table |

**Request Body Example**:

```json
{
    "student_id": 42,
    "program_id": 8
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Access revoked successfully",
    "data": {
        "student_name": "Ahmed Hassan",
        "program_title": "Arabic for Beginners"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "student_id": ["The selected student id is invalid."],
        "program_id": ["The selected program id is invalid."]
    }
}
```

**Error Response - Enrollment Not Found (404)**:

```json
{
    "success": false,
    "message": "Failed to revoke access. Enrollment not found."
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to revoke access",
    "error": "Database transaction failed"
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/revoke-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 42,
    "program_id": 8
  }'
```

---

#### GET /admin/programs/{program}/students

**Description**: Retrieve all students enrolled in a specific program with detailed enrollment information and statistics

**Authentication**: Required (Admin role)

**Purpose**:

-   View all students associated with a specific program
-   Monitor program enrollment status and approval rates
-   Generate program-specific enrollment reports
-   Support program-level administrative decisions

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "program": {
            "id": 8,
            "title": "Arabic for Beginners",
            "language": "Arabic",
            "level": "Beginner"
        },
        "students": [
            {
                "enrollment_id": 15,
                "student": {
                    "id": 42,
                    "name": "Ahmed Hassan",
                    "email": "ahmed.hassan@example.com",
                    "phone": "+201234567890"
                },
                "assigned_at": "2024-01-10T14:30:00.000000Z",
                "access_granted_at": "2024-01-12T09:15:00.000000Z",
                "has_access": true,
                "approved_by": 1
            },
            {
                "enrollment_id": 16,
                "student": {
                    "id": 43,
                    "name": "Fatima Al-Zahra",
                    "email": "fatima.alzahra@example.com",
                    "phone": "+201234567891"
                },
                "assigned_at": "2024-01-11T16:45:00.000000Z",
                "access_granted_at": null,
                "has_access": false,
                "approved_by": null
            }
        ],
        "statistics": {
            "total_students": 76,
            "approved_students": 71,
            "pending_students": 5
        }
    }
}
```

**Error Response - Program Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve program students",
    "error": "Database query failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/programs/8/students" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /programs/{program}/pending-enrollments

**Description**: Retrieve pending enrollments for a specific program (Teacher/Admin access)

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   View students awaiting approval for a specific program
-   Support program-level enrollment management
-   Enable teachers to monitor their program enrollment requests
-   Facilitate targeted approval workflows

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "program": {
            "id": 8,
            "title": "Arabic for Beginners",
            "language": "Arabic",
            "level": "Beginner"
        },
        "pending_enrollments": [
            {
                "id": 16,
                "student": {
                    "id": 43,
                    "name": "Fatima Al-Zahra",
                    "email": "fatima.alzahra@example.com",
                    "phone": "+201234567891"
                },
                "assigned_at": "2024-01-11T16:45:00.000000Z",
                "days_pending": 4
            }
        ],
        "total": 5
    }
}
```

**Error Response - Program Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Authorization Error Response (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: teacher, admin"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/programs/8/pending-enrollments" \
  -H "Authorization: Bearer {teacher_or_admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /programs/{program}/approved-enrollments

**Description**: Retrieve approved enrollments for a specific program (Teacher/Admin access)

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   View students with approved access to a specific program
-   Monitor program enrollment success and completion
-   Support program-level student management
-   Generate program enrollment reports

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "program": {
            "id": 8,
            "title": "Arabic for Beginners",
            "language": "Arabic",
            "level": "Beginner"
        },
        "approved_enrollments": [
            {
                "id": 15,
                "student": {
                    "id": 42,
                    "name": "Ahmed Hassan",
                    "email": "ahmed.hassan@example.com",
                    "phone": "+201234567890"
                },
                "assigned_at": "2024-01-10T14:30:00.000000Z",
                "access_granted_at": "2024-01-12T09:15:00.000000Z",
                "approved_by": {
                    "id": 1,
                    "name": "Admin User",
                    "email": "admin@example.com"
                }
            }
        ],
        "total": 71
    }
}
```

**Error Response - Program Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Authorization Error Response (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: teacher, admin"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/programs/8/approved-enrollments" \
  -H "Authorization: Bearer {teacher_or_admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /student/enrollments

**Description**: Retrieve current user's enrollment status and program access information

**Authentication**: Required (Student or Admin role)

**Purpose**:

-   View personal enrollment status across all programs
-   Check program access approval status
-   Monitor enrollment history and timeline
-   Access program information for approved enrollments

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "enrollments": [
            {
                "id": 15,
                "program": {
                    "id": 8,
                    "title": "Arabic for Beginners",
                    "description": "Learn Arabic from the basics with native speakers",
                    "language": "Arabic",
                    "level": "Beginner"
                },
                "assigned_at": "2024-01-10T14:30:00.000000Z",
                "access_granted_at": "2024-01-12T09:15:00.000000Z",
                "has_access": true,
                "status": "approved"
            },
            {
                "id": 23,
                "program": {
                    "id": 12,
                    "title": "Spanish Intermediate",
                    "description": "Advance your Spanish skills with interactive lessons",
                    "language": "Spanish",
                    "level": "Intermediate"
                },
                "assigned_at": "2024-01-14T11:20:00.000000Z",
                "access_granted_at": null,
                "has_access": false,
                "status": "pending"
            }
        ]
    }
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve enrollments",
    "error": "Database query failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

---

### Enrollment Management Workflows

#### Complete Enrollment Approval Workflow

**Step 1: Review Pending Enrollments**

```bash
# Get all pending enrollments with filtering
curl -X GET "https://your-domain.com/api/admin/all-pending-enrollments?days_pending=7" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Review Enrollment Statistics**

```bash
# Get system-wide enrollment statistics
curl -X GET "https://your-domain.com/api/admin/enrollment-statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Approve Individual Enrollment**

```bash
# Grant access to specific student
curl -X POST "https://your-domain.com/api/admin/grant-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 42,
    "program_id": 8
  }'
```

**Step 4: Bulk Approve Multiple Enrollments**

```bash
# Approve multiple enrollments by ID
curl -X POST "https://your-domain.com/api/admin/bulk-approve-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "enrollment_ids": [15, 16, 17, 18, 19]
  }'
```

#### Program-Specific Enrollment Management

**Step 1: Review Program Enrollments**

```bash
# Get all students for a specific program
curl -X GET "https://your-domain.com/api/admin/programs/8/students" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Check Pending Enrollments for Program**

```bash
# Get pending enrollments for specific program
curl -X GET "https://your-domain.com/api/programs/8/pending-enrollments" \
  -H "Authorization: Bearer {teacher_or_admin_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Bulk Grant Access for Program**

```bash
# Grant access to multiple students for same program
curl -X POST "https://your-domain.com/api/admin/bulk-grant-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_ids": [42, 43, 44],
    "program_id": 8
  }'
```

#### Student Enrollment Monitoring

**Step 1: Student Checks Own Enrollments**

```bash
# Student views their enrollment status
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Admin Reviews Student's Enrollments**

```bash
# Admin can also access student enrollments for support
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

### Enrollment Management Best Practices

#### Administrative Efficiency

1. **Batch Processing**: Use bulk operations for multiple enrollment approvals
2. **Filtering**: Apply filters to focus on specific enrollment criteria
3. **Regular Monitoring**: Check enrollment statistics regularly for system health
4. **Timely Approvals**: Process pending enrollments within reasonable timeframes
5. **Documentation**: Maintain clear records of approval decisions and rationale

#### Data Integrity

1. **Validation**: Always validate student and program IDs before operations
2. **Transaction Safety**: Use database transactions for bulk operations
3. **Audit Trail**: Maintain complete records of enrollment actions and administrators
4. **Referential Integrity**: Ensure enrollment relationships remain consistent
5. **Error Handling**: Implement robust error handling for failed operations

#### Performance Optimization

1. **Pagination**: Use pagination for large enrollment lists
2. **Indexing**: Ensure proper database indexing on enrollment queries
3. **Caching**: Cache frequently accessed enrollment statistics
4. **Bulk Operations**: Prefer bulk operations over individual requests
5. **Query Optimization**: Optimize database queries for enrollment reporting

#### Security Considerations

1. **Role Validation**: Ensure only authorized users can manage enrollments
2. **Access Control**: Validate program access permissions before operations
3. **Input Sanitization**: Sanitize all input parameters for security
4. **Rate Limiting**: Implement rate limiting for bulk operations
5. **Audit Logging**: Log all enrollment management actions for security review

---

### Program Management

The Program Management system provides comprehensive functionality for managing educational programs within the Learn Academy platform. This includes program CRUD operations, student enrollment tracking, program statistics, and detailed program information retrieval. The system supports filtering, detailed analytics, and enrollment management at the program level.

All program management endpoints require admin authentication and provide detailed information about programs, their associated students, enrollment statistics, and related educational content.

---

#### GET /admin/programs

**Description**: Retrieve a list of all programs with optional filtering and statistics

**Authentication**: Required (Admin role)

**Query Parameters**:

| Parameter     | Type    | Required | Description                     |
| ------------- | ------- | -------- | ------------------------------- |
| `language_id` | integer | No       | Filter programs by language ID  |
| `level_id`    | integer | No       | Filter programs by level ID     |
| `active`      | boolean | No       | Filter by program active status |

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "programs": [
            {
                "id": 1,
                "title": "Beginner English Conversation",
                "description": "Introduction to basic English conversation skills for new learners. Covers greetings, introductions, basic questions, and everyday vocabulary.",
                "active": true,
                "language": {
                    "id": 1,
                    "name": "English",
                    "code": "en",
                    "native_name": "English"
                },
                "level": {
                    "id": 1,
                    "name": "Beginner",
                    "description": "Basic level for new learners",
                    "order": 1
                },
                "teacher": {
                    "id": 5,
                    "name": "Sarah Johnson",
                    "email": "sarah.johnson@example.com",
                    "profile_image": "/storage/teachers/sarah-johnson.jpg",
                    "specializations": ["Conversation", "Grammar Basics"]
                },
                "statistics": {
                    "enrollment": {
                        "total_students": 25,
                        "approved_students": 20,
                        "pending_students": 5,
                        "enrollment_rate": 80.0
                    },
                    "content": {
                        "total_quizzes": 8,
                        "active_quizzes": 6,
                        "total_meetings": 12,
                        "upcoming_meetings": 3,
                        "completed_meetings": 9
                    },
                    "performance": {
                        "average_quiz_score": 78.5,
                        "completion_rate": 85.0,
                        "student_satisfaction": 4.2
                    }
                },
                "recent_activity": {
                    "last_enrollment": "2024-01-20T14:30:00.000000Z",
                    "last_quiz_attempt": "2024-01-20T16:45:00.000000Z",
                    "last_meeting": "2024-01-19T10:00:00.000000Z"
                },
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-20T14:45:00.000000Z"
            },
            {
                "id": 2,
                "title": "Advanced Spanish Literature",
                "description": "Deep dive into Spanish literary works and analysis. Study classic and contemporary authors, literary movements, and critical analysis techniques.",
                "active": true,
                "language": {
                    "id": 2,
                    "name": "Spanish",
                    "code": "es",
                    "native_name": "Español"
                },
                "level": {
                    "id": 3,
                    "name": "Advanced",
                    "description": "Advanced level for experienced learners",
                    "order": 3
                },
                "teacher": {
                    "id": 7,
                    "name": "Carlos Rodriguez",
                    "email": "carlos.rodriguez@example.com",
                    "profile_image": "/storage/teachers/carlos-rodriguez.jpg",
                    "specializations": ["Literature", "Advanced Grammar", "Cultural Studies"]
                },
                "statistics": {
                    "enrollment": {
                        "total_students": 15,
                        "approved_students": 15,
                        "pending_students": 0,
                        "enrollment_rate": 100.0
                    },
                    "content": {
                        "total_quizzes": 12,
                        "active_quizzes": 10,
                        "total_meetings": 20,
                        "upcoming_meetings": 5,
                        "completed_meetings": 15
                    },
                    "performance": {
                        "average_quiz_score": 88.2,
                        "completion_rate": 92.0,
                        "student_satisfaction": 4.7
                    }
                },
                "recent_activity": {
                    "last_enrollment": "2024-01-18T11:20:00.000000Z",
                    "last_quiz_attempt": "2024-01-20T15:30:00.000000Z",
                    "last_meeting": "2024-01-20T14:00:00.000000Z"
                },
                "created_at": "2024-01-10T09:15:00.000000Z",
                "updated_at": "2024-01-18T16:20:00.000000Z"
            },
            {
                "id": 3,
                "title": "Arabic Grammar Fundamentals",
                "description": "Comprehensive introduction to Arabic grammar rules, sentence structure, and writing systems. Perfect for intermediate learners.",
                "active": true,
                "language": {
                    "id": 3,
                    "name": "Arabic",
                    "code": "ar",
                    "native_name": "العربية"
                },
                "level": {
                    "id": 2,
                    "name": "Intermediate",
                    "description": "For learners with basic knowledge",
                    "order": 2
                },
                "teacher": {
                    "id": 12,
                    "name": "Fatima Al-Zahra",
                    "email": "fatima.alzahra@example.com",
                    "profile_image": "/storage/teachers/fatima-alzahra.jpg",
                    "specializations": ["Grammar", "Classical Arabic", "Modern Standard Arabic"]
                },
                "statistics": {
                    "enrollment": {
                        "total_students": 18,
                        "approved_students": 16,
                        "pending_students": 2,
                        "enrollment_rate": 88.9
                    },
                    "content": {
                        "total_quizzes": 10,
                        "active_quizzes": 8,
                        "total_meetings": 15,
                        "upcoming_meetings": 4,
                        "completed_meetings": 11
                    },
                    "performance": {
                        "average_quiz_score": 82.1,
                        "completion_rate": 87.5,
                        "student_satisfaction": 4.4
                    }
                },
                "recent_activity": {
                    "last_enrollment": "2024-01-19T09:45:00.000000Z",
                    "last_quiz_attempt": "2024-01-20T13:15:00.000000Z",
                    "last_meeting": "2024-01-18T16:00:00.000000Z"
                },
                "created_at": "2024-01-12T14:20:00.000000Z",
                "updated_at": "2024-01-19T11:30:00.000000Z"
            }
        ],
        "total": 3,
        "summary": {
            "total_programs": 3,
            "active_programs": 3,
            "inactive_programs": 0,
            "total_enrolled_students": 58,
            "total_approved_students": 51,
            "total_pending_students": 7,
            "average_enrollment_rate": 89.6,
            "average_quiz_score": 82.9,
            "average_completion_rate": 88.2,
            "by_language": {
                "English": 1,
                "Spanish": 1,
                "Arabic": 1
            },
            "by_level": {
                "Beginner": 1,
                "Intermediate": 1,
                "Advanced": 1
            }
        }
    }
}
```

**Filtered Response Examples**:

**Filter by Language** (`?language_id=1`)
{
    "success": false,
    "message": "Failed to retrieve programs",
    "error": "Database connection error"
}
```

**cURL Examples**:

```bash
# Get all programs
curl -X GET "https://your-domain.com/api/admin/programs" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Filter programs by language
curl -X GET "https://your-domain.com/api/admin/programs?language_id=1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Filter active programs by level
curl -X GET "https://your-domain.com/api/admin/programs?level_id=2&active=true" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/programs/{program}

**Description**: Retrieve detailed information for a specific program including comprehensive statistics and relationships

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Beginner English Conversation",
        "description": "Introduction to basic English conversation skills for new learners. This program covers fundamental vocabulary, basic grammar structures, and practical conversation scenarios.",
        "active": true,
        "language": {
            "id": 1,
            "name": "English",
            "code": "en",
            "native_name": "English"
        },
        "level": {
            "id": 1,
            "name": "Beginner",
            "description": "Basic level for new learners",
            "order": 1
        },
        "teacher": {
            "id": 5,
            "name": "Sarah Johnson",
            "email": "sarah.johnson@example.com",
            "phone": "+1-555-0123",
            "profile_image": "https://your-domain.com/storage/teachers/sarah-johnson.jpg"
        },
        "enrollment_statistics": {
            "total_enrollments": 25,
            "approved_enrollments": 20,
            "pending_enrollments": 5,
            "recent_enrollments": 3,
            "enrollment_trend": "increasing"
        },
        "content_statistics": {
            "total_quizzes": 8,
            "active_quizzes": 6,
            "inactive_quizzes": 2,
            "total_quiz_attempts": 156,
            "average_quiz_score": 78.5,
            "total_meetings": 12,
            "upcoming_meetings": 3,
            "completed_meetings": 9
        },
        "recent_activity": {
            "last_enrollment": "2024-01-20T14:30:00.000000Z",
            "last_quiz_attempt": "2024-01-20T16:45:00.000000Z",
            "last_meeting": "2024-01-19T10:00:00.000000Z",
            "next_meeting": "2024-01-22T14:00:00.000000Z"
        },
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-20T14:45:00.000000Z"
    }
}
```

**Error Response - Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve program details",
    "error": "Database query failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### PUT /admin/programs/{program}

**Description**: Update program information including title, description, and active status

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Request Body Parameters**:

| Field         | Type    | Required | Description                               |
| ------------- | ------- | -------- | ----------------------------------------- |
| `title`       | string  | No       | Program title (max 255 characters)        |
| `description` | string  | No       | Program description (max 1000 characters) |
| `active`      | boolean | No       | Program active status                     |

**Request Body Example**:

```json
{
    "title": "Advanced English Conversation",
    "description": "Enhanced English conversation skills with focus on business communication and advanced vocabulary",
    "active": true
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Program updated successfully",
    "data": {
        "program": {
            "id": 1,
            "title": "Advanced English Conversation",
            "description": "Enhanced English conversation skills with focus on business communication and advanced vocabulary",
            "active": true,
            "updated_at": "2024-01-20T15:30:00.000000Z"
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": ["The title may not be greater than 255 characters."],
        "description": [
            "The description may not be greater than 1000 characters."
        ]
    }
}
```

**Error Response - Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to update program",
    "error": "Database update failed"
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/admin/programs/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Advanced English Conversation",
    "description": "Enhanced English conversation skills with focus on business communication and advanced vocabulary",
    "active": true
  }'
```

---

#### DELETE /admin/programs/{program}

**Description**: Delete a program if it meets deletion criteria (no active enrollments or dependencies)

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Program 'Beginner English Conversation' deleted successfully"
}
```

**Error Response - Cannot Delete (422)**:

```json
{
    "success": false,
    "message": "Cannot delete program",
    "reasons": [
        "Program has active enrollments",
        "Program has associated quizzes with attempts",
        "Program has scheduled meetings"
    ]
}
```

**Error Response - Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to delete program",
    "error": "Database deletion failed"
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/admin/programs/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/programs/{program}/students

**Description**: Retrieve all students enrolled in a specific program with enrollment details and statistics

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "program": {
            "id": 1,
            "title": "Beginner English Conversation",
            "language": "English",
            "level": "Beginner"
        },
        "students": [
            {
                "id": 10,
                "name": "Ahmed Hassan",
                "email": "ahmed.hassan@example.com",
                "phone": "+20-123-456-789",
                "preferred_language": "ar",
                "enrollment": {
                    "enrolled_at": "2024-01-15T10:30:00.000000Z",
                    "has_access": true,
                    "access_granted_at": "2024-01-16T09:15:00.000000Z",
                    "approved_by": {
                        "id": 1,
                        "name": "Admin User",
                        "email": "admin@example.com"
                    }
                },
                "progress": {
                    "quizzes_completed": 5,
                    "total_quizzes": 8,
                    "average_score": 85.2,
                    "meetings_attended": 7,
                    "total_meetings": 9,
                    "last_activity": "2024-01-20T14:30:00.000000Z"
                }
            },
            {
                "id": 11,
                "name": "Maria Garcia",
                "email": "maria.garcia@example.com",
                "phone": "+34-987-654-321",
                "preferred_language": "es",
                "enrollment": {
                    "enrolled_at": "2024-01-18T14:20:00.000000Z",
                    "has_access": false,
                    "access_granted_at": null,
                    "approved_by": null
                },
                "progress": {
                    "quizzes_completed": 0,
                    "total_quizzes": 8,
                    "average_score": null,
                    "meetings_attended": 0,
                    "total_meetings": 9,
                    "last_activity": null
                }
            }
        ],
        "statistics": {
            "total_students": 2,
            "approved_students": 1,
            "pending_students": 1
        }
    }
}
```

**Error Response - Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve program students",
    "error": "Database query failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1/students" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/programs/{program}/statistics

**Description**: Retrieve comprehensive statistics for a specific program including enrollment trends, performance metrics, and activity data

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "program": {
            "id": 1,
            "title": "Beginner English Conversation",
            "language": "English",
            "level": "Beginner",
            "active": true
        },
        "enrollment_statistics": {
            "total_enrollments": 25,
            "approved_enrollments": 20,
            "pending_enrollments": 5,
            "enrollment_rate": 80.0,
            "recent_enrollments": {
                "last_7_days": 3,
                "last_30_days": 8,
                "last_90_days": 15
            },
            "enrollment_trend": "increasing"
        },
        "performance_statistics": {
            "quiz_statistics": {
                "total_quizzes": 8,
                "active_quizzes": 6,
                "total_attempts": 156,
                "average_score": 78.5,
                "completion_rate": 87.5,
                "top_performing_students": [
                    {
                        "student_id": 10,
                        "name": "Ahmed Hassan",
                        "average_score": 92.3
                    },
                    {
                        "student_id": 15,
                        "name": "Lisa Chen",
                        "average_score": 89.7
                    }
                ]
            },
            "meeting_statistics": {
                "total_meetings": 12,
                "completed_meetings": 9,
                "upcoming_meetings": 3,
                "average_attendance": 85.2,
                "attendance_trend": "stable"
            }
        },
        "activity_statistics": {
            "daily_active_students": 12,
            "weekly_active_students": 18,
            "monthly_active_students": 22,
            "last_activity": "2024-01-20T16:45:00.000000Z",
            "most_active_day": "Tuesday",
            "peak_activity_hours": ["14:00", "15:00", "16:00"]
        },
        "content_engagement": {
            "most_popular_quiz": {
                "id": 5,
                "title": "Basic Vocabulary Test",
                "attempts": 45
            },
            "most_attended_meeting": {
                "id": 8,
                "title": "Conversation Practice Session",
                "attendance": 18
            }
        },
        "generated_at": "2024-01-20T17:00:00.000000Z"
    }
}
```

**Error Response - Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve program statistics",
    "error": "Statistics calculation failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1/statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/programs/{program}/pending-enrollments

**Description**: Retrieve all pending enrollments for a specific program awaiting approval

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "program": {
            "id": 1,
            "title": "Beginner English Conversation",
            "language": "English",
            "level": "Beginner"
        },
        "pending_enrollments": [
            {
                "id": 25,
                "student": {
                    "id": 11,
                    "name": "Maria Garcia",
                    "email": "maria.garcia@example.com",
                    "phone": "+34-987-654-321",
                    "preferred_language": "es"
                },
                "enrollment_details": {
                    "enrolled_at": "2024-01-18T14:20:00.000000Z",
                    "days_pending": 2,
                    "enrollment_source": "web_registration",
                    "notes": "Student requested immediate access for upcoming course start"
                }
            },
            {
                "id": 26,
                "student": {
                    "id": 12,
                    "name": "John Smith",
                    "email": "john.smith@example.com",
                    "phone": "+1-555-0199",
                    "preferred_language": "en"
                },
                "enrollment_details": {
                    "enrolled_at": "2024-01-19T09:45:00.000000Z",
                    "days_pending": 1,
                    "enrollment_source": "admin_assignment",
                    "notes": "Transferred from intermediate level program"
                }
            }
        ],
        "total": 2
    }
}
```

**Error Response - Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve pending enrollments",
    "error": "Database query failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1/pending-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/programs/{program}/approved-enrollments

**Description**: Retrieve all approved enrollments for a specific program with access details

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `program` (integer, required) - Program ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "program": {
            "id": 1,
            "title": "Beginner English Conversation",
            "language": "English",
            "level": "Beginner"
        },
        "approved_enrollments": [
            {
                "id": 20,
                "student": {
                    "id": 10,
                    "name": "Ahmed Hassan",
                    "email": "ahmed.hassan@example.com",
                    "phone": "+20-123-456-789",
                    "preferred_language": "ar"
                },
                "enrollment_details": {
                    "enrolled_at": "2024-01-15T10:30:00.000000Z",
                    "access_granted_at": "2024-01-16T09:15:00.000000Z",
                    "approved_by": {
                        "id": 1,
                        "name": "Admin User",
                        "email": "admin@example.com"
                    },
                    "days_since_approval": 4,
                    "enrollment_source": "web_registration"
                },
                "activity_summary": {
                    "quizzes_completed": 5,
                    "meetings_attended": 7,
                    "last_activity": "2024-01-20T14:30:00.000000Z",
                    "engagement_level": "high"
                }
            },
            {
                "id": 21,
                "student": {
                    "id": 13,
                    "name": "Lisa Chen",
                    "email": "lisa.chen@example.com",
                    "phone": "+86-138-0013-8000",
                    "preferred_language": "en"
                },
                "enrollment_details": {
                    "enrolled_at": "2024-01-12T16:20:00.000000Z",
                    "access_granted_at": "2024-01-13T10:00:00.000000Z",
                    "approved_by": {
                        "id": 2,
                        "name": "Sarah Johnson",
                        "email": "sarah.johnson@example.com"
                    },
                    "days_since_approval": 7,
                    "enrollment_source": "teacher_recommendation"
                },
                "activity_summary": {
                    "quizzes_completed": 6,
                    "meetings_attended": 8,
                    "last_activity": "2024-01-20T16:15:00.000000Z",
                    "engagement_level": "high"
                }
            }
        ],
        "total": 2
    }
}
```

**Error Response - Not Found (404)**:

```json
{
    "success": false,
    "message": "Program not found"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve approved enrollments",
    "error": "Database query failed"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1/approved-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

### Program Management Workflows

#### Complete Program Analysis Workflow

**Step 1: Get Program Overview**

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Analyze Program Statistics**

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1/statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Review Student Enrollments**

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1/students" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 4: Check Pending Enrollments**

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1/pending-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### Program Filtering and Search Workflow

**Step 1: Filter by Language**

```bash
curl -X GET "https://your-domain.com/api/admin/programs?language_id=1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Filter by Level and Status**

```bash
curl -X GET "https://your-domain.com/api/admin/programs?level_id=2&active=true" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Combine Multiple Filters**

```bash
curl -X GET "https://your-domain.com/api/admin/programs?language_id=1&level_id=1&active=true" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### Program Update and Management Workflow

**Step 1: Get Current Program Details**

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Update Program Information**

```bash
curl -X PUT "https://your-domain.com/api/admin/programs/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Advanced English Conversation",
    "description": "Enhanced program with business communication focus",
    "active": true
  }'
```

**Step 3: Verify Updates**

```bash
curl -X GET "https://your-domain.com/api/admin/programs/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

### Program Management Best Practices

#### Administrative Efficiency

1. **Regular Monitoring**: Review program statistics and enrollment trends regularly
2. **Proactive Management**: Address pending enrollments promptly to maintain student engagement
3. **Data-Driven Decisions**: Use program statistics to inform curriculum and resource allocation decisions
4. **Performance Tracking**: Monitor quiz completion rates and meeting attendance for program effectiveness
5. **Student Engagement**: Track activity levels and engagement metrics to identify at-risk students

#### Program Optimization

1. **Content Analysis**: Use quiz and meeting statistics to identify popular and effective content
2. **Enrollment Patterns**: Analyze enrollment trends to optimize program scheduling and capacity
3. **Performance Metrics**: Monitor average scores and completion rates to assess program difficulty
4. **Resource Allocation**: Use student distribution data to allocate teachers and resources effectively
5. **Continuous Improvement**: Regular program updates based on student feedback and performance data

#### Data Management

1. **Filtering Efficiency**: Use appropriate filters to focus on relevant program subsets
2. **Statistical Analysis**: Leverage comprehensive statistics for informed decision-making
3. **Enrollment Tracking**: Maintain accurate records of enrollment status and approval workflows
4. **Activity Monitoring**: Track student activity patterns to optimize program delivery
5. **Performance Reporting**: Generate regular reports on program effectiveness and student outcomes

#### Security and Access Control

1. **Admin Authorization**: Ensure only authorized administrators can access program management functions
2. **Data Privacy**: Protect student information while providing necessary program insights
3. **Audit Trails**: Maintain records of program modifications and administrative actions
4. **Access Validation**: Verify program access permissions before granting student enrollment
5. **Secure Operations**: Implement proper validation and error handling for all program operations

---

### Language Management

The Language Management system provides comprehensive functionality for managing languages, their associated levels and programs, and teacher-language assignments within the Learn Academy platform. This includes language CRUD operations, teacher assignment management, language statistics, and program association tracking.

All language management endpoints require admin authentication and provide detailed information about languages, their associated teachers, programs, enrollment statistics, and educational content organization.

---

#### Language Resource Management

These endpoints provide full CRUD operations for managing language resources in the system.

##### GET /admin/languages

**Description**: Retrieve all languages with comprehensive statistics including levels, programs, and teacher assignments

**Authentication**: Required (Admin role)

**Purpose**: Get complete overview of all languages in the system with associated counts and metadata for administrative management

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "languages": [
            {
                "id": 1,
                "code": "en",
                "name": "English",
                "active": true,
                "levels_count": 3,
                "programs_count": 12,
                "teachers_count": 5,
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-20T14:45:00.000000Z"
            },
            {
                "id": 2,
                "code": "es",
                "name": "Spanish",
                "active": true,
                "levels_count": 4,
                "programs_count": 8,
                "teachers_count": 3,
                "created_at": "2024-01-16T09:15:00.000000Z",
                "updated_at": "2024-01-18T16:20:00.000000Z"
            },
            {
                "id": 3,
                "code": "ar",
                "name": "Arabic",
                "active": false,
                "levels_count": 2,
                "programs_count": 4,
                "teachers_count": 1,
                "created_at": "2024-01-17T11:00:00.000000Z",
                "updated_at": "2024-01-19T13:30:00.000000Z"
            }
        ],
        "total": 3
    }
}
```

**Response Details**:

-   **languages**: Array of language objects with comprehensive statistics
-   **id**: Unique language identifier
-   **code**: Language code (ISO 639-1 format, e.g., "en", "es", "ar")
-   **name**: Human-readable language name
-   **active**: Boolean indicating if language is currently active
-   **levels_count**: Number of levels associated with this language
-   **programs_count**: Number of programs using this language
-   **teachers_count**: Number of teachers assigned to this language
-   **total**: Total number of languages in the system

**Error Response (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve languages",
    "error": "Database connection error"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

##### POST /admin/languages

**Description**: Create a new language with auto-generated levels and programs

**Authentication**: Required (Admin role)

**Request Body Parameters**:

| Field            | Type    | Required | Description                            |
| ---------------- | ------- | -------- | -------------------------------------- |
| `code`           | string  | Yes      | Language code (max 10 chars, unique)   |
| `name`           | string  | Yes      | Language name (max 255 chars)          |
| `active`         | boolean | No       | Language active status (default: true) |
| `levels`         | array   | Yes      | Array of level objects to create       |
| `levels.*.name`  | string  | Yes      | Level name (max 255 chars)             |
| `levels.*.order` | integer | Yes      | Level order (min: 1)                   |

**Request Body Example**:

```json
{
    "code": "fr",
    "name": "French",
    "active": true,
    "levels": [
        {
            "name": "Beginner",
            "order": 1
        },
        {
            "name": "Intermediate",
            "order": 2
        },
        {
            "name": "Advanced",
            "order": 3
        }
    ]
}
```

**Success Response (201)**:

```json
{
    "success": true,
    "message": "Language created successfully with levels and programs",
    "data": {
        "language": {
            "id": 4,
            "code": "fr",
            "name": "French",
            "active": true,
            "levels": [
                {
                    "id": 10,
                    "name": "Beginner",
                    "order": 1,
                    "programs": [
                        {
                            "id": 25,
                            "title": "French Beginner Program",
                            "description": "Introduction to French language",
                            "active": true
                        }
                    ]
                },
                {
                    "id": 11,
                    "name": "Intermediate",
                    "order": 2,
                    "programs": [
                        {
                            "id": 26,
                            "title": "French Intermediate Program",
                            "description": "Intermediate French language skills",
                            "active": true
                        }
                    ]
                },
                {
                    "id": 12,
                    "name": "Advanced",
                    "order": 3,
                    "programs": [
                        {
                            "id": 27,
                            "title": "French Advanced Program",
                            "description": "Advanced French language mastery",
                            "active": true
                        }
                    ]
                }
            ]
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "code": ["The code field is required."],
        "name": ["The name field is required."],
        "levels": ["The levels field must have at least 1 items."],
        "levels.0.name": ["The levels.0.name field is required."],
        "levels.0.order": ["The levels.0.order field is required."]
    }
}
```

**Duplicate Code Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "code": ["The code has already been taken."]
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "fr",
    "name": "French",
    "active": true,
    "levels": [
      {
        "name": "Beginner",
        "order": 1
      },
      {
        "name": "Intermediate",
        "order": 2
      },
      {
        "name": "Advanced",
        "order": 3
      }
    ]
  }'
```

---

##### GET /admin/languages/{language}

**Description**: Retrieve detailed information about a specific language including levels, programs, and assigned teachers

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `language` (integer, required) - Language ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "language": {
            "id": 1,
            "code": "en",
            "name": "English",
            "active": true,
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-20T14:45:00.000000Z",
            "levels": [
                {
                    "id": 1,
                    "name": "Beginner",
                    "order": 1,
                    "programs": [
                        {
                            "id": 1,
                            "title": "English Beginner Program",
                            "description": "Introduction to English language",
                            "active": true,
                            "enrollment_count": 25,
                            "approved_enrollment_count": 20
                        },
                        {
                            "id": 2,
                            "title": "English Beginner Conversation",
                            "description": "Basic English conversation skills",
                            "active": true,
                            "enrollment_count": 18,
                            "approved_enrollment_count": 15
                        }
                    ]
                },
                {
                    "id": 2,
                    "name": "Intermediate",
                    "order": 2,
                    "programs": [
                        {
                            "id": 3,
                            "title": "English Intermediate Program",
                            "description": "Intermediate English language skills",
                            "active": true,
                            "enrollment_count": 30,
                            "approved_enrollment_count": 28
                        }
                    ]
                }
            ],
            "teachers": [
                {
                    "id": 5,
                    "name": "John Smith",
                    "email": "john.smith@example.com",
                    "image_path": "/storage/teachers/john_smith.jpg",
                    "assigned_at": "2024-01-15T10:30:00.000000Z"
                },
                {
                    "id": 8,
                    "name": "Sarah Johnson",
                    "email": "sarah.johnson@example.com",
                    "image_path": "/storage/teachers/sarah_johnson.jpg",
                    "assigned_at": "2024-01-18T14:20:00.000000Z"
                }
            ]
        }
    }
}
```

**Error Response - Language Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/languages/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

##### PUT /admin/languages/{language}

**Description**: Update an existing language's basic information

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `language` (integer, required) - Language ID

**Request Body Parameters**:

| Field    | Type    | Required | Description                          |
| -------- | ------- | -------- | ------------------------------------ |
| `code`   | string  | No       | Language code (max 10 chars, unique) |
| `name`   | string  | No       | Language name (max 255 chars)        |
| `active` | boolean | No       | Language active status               |

**Request Body Example**:

```json
{
    "name": "English (US)",
    "active": true
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Language updated successfully",
    "data": {
        "language": {
            "id": 1,
            "code": "en",
            "name": "English (US)",
            "active": true,
            "updated_at": "2024-01-20T15:30:00.000000Z"
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "code": ["The code has already been taken."],
        "name": ["The name may not be greater than 255 characters."]
    }
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/admin/languages/1" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "English (US)",
    "active": true
  }'
```

---

##### DELETE /admin/languages/{language}

**Description**: Delete a language from the system (only if no enrollments exist)

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `language` (integer, required) - Language ID

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Language 'French' deleted successfully"
}
```

**Error Response - Has Enrollments (422)**:

```json
{
    "success": false,
    "message": "Cannot delete language with existing enrollments"
}
```

**Error Response - Language Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/admin/languages/4" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### Teacher-Language Assignment Management

These endpoints manage the relationships between teachers and languages, controlling which teachers can access and teach specific languages.

##### POST /admin/assign-teacher-language

**Description**: Assign a teacher to a specific language for teaching access

**Authentication**: Required (Admin role)

**Request Body Parameters**:

| Field         | Type    | Required | Description              |
| ------------- | ------- | -------- | ------------------------ |
| `teacher_id`  | integer | Yes      | ID of teacher to assign  |
| `language_id` | integer | Yes      | ID of language to assign |

**Request Body Example**:

```json
{
    "teacher_id": 5,
    "language_id": 1
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Teacher assigned to language successfully",
    "data": {
        "assignment": {
            "id": 12,
            "teacher": {
                "id": 5,
                "name": "John Smith",
                "email": "john.smith@example.com"
            },
            "language": {
                "id": 1,
                "name": "English",
                "code": "en"
            },
            "assigned_at": "2024-01-20T15:45:00.000000Z",
            "assigned_by": "Admin User"
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "teacher_id": ["The selected teacher id is invalid."],
        "language_id": ["The selected language id is invalid."]
    }
}
```

**Error Response - Already Assigned (500)**:

```json
{
    "success": false,
    "message": "Failed to assign teacher to language",
    "error": "Teacher is already assigned to this language"
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/assign-teacher-language" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 5,
    "language_id": 1
  }'
```

---

##### POST /admin/assign-teacher-multiple-languages

**Description**: Assign a teacher to multiple languages simultaneously

**Authentication**: Required (Admin role)

**Request Body Parameters**:

| Field            | Type    | Required | Description                     |
| ---------------- | ------- | -------- | ------------------------------- |
| `teacher_id`     | integer | Yes      | ID of teacher to assign         |
| `language_ids`   | array   | Yes      | Array of language IDs to assign |
| `language_ids.*` | integer | Yes      | Individual language ID          |

**Request Body Example**:

```json
{
    "teacher_id": 5,
    "language_ids": [1, 2, 3]
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Teacher assigned to 3 languages successfully",
    "data": {
        "teacher": {
            "id": 5,
            "name": "John Smith",
            "email": "john.smith@example.com"
        },
        "assignments_count": 3,
        "assignments": [
            {
                "id": 12,
                "language": {
                    "id": 1,
                    "name": "English",
                    "code": "en"
                },
                "assigned_at": "2024-01-20T15:45:00.000000Z"
            },
            {
                "id": 13,
                "language": {
                    "id": 2,
                    "name": "Spanish",
                    "code": "es"
                },
                "assigned_at": "2024-01-20T15:45:00.000000Z"
            },
            {
                "id": 14,
                "language": {
                    "id": 3,
                    "name": "Arabic",
                    "code": "ar"
                },
                "assigned_at": "2024-01-20T15:45:00.000000Z"
            }
        ]
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "teacher_id": ["The selected teacher id is invalid."],
        "language_ids": ["The language ids field is required."],
        "language_ids.0": ["The selected language ids.0 is invalid."]
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/assign-teacher-multiple-languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 5,
    "language_ids": [1, 2, 3]
  }'
```

---

##### DELETE /admin/remove-teacher-language

**Description**: Remove a teacher's assignment from a specific language

**Authentication**: Required (Admin role)

**Request Body Parameters**:

| Field         | Type    | Required | Description                   |
| ------------- | ------- | -------- | ----------------------------- |
| `teacher_id`  | integer | Yes      | ID of teacher to remove       |
| `language_id` | integer | Yes      | ID of language to remove from |

**Request Body Example**:

```json
{
    "teacher_id": 5,
    "language_id": 1
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Teacher removed from language successfully",
    "data": {
        "teacher": {
            "id": 5,
            "name": "John Smith"
        },
        "language": {
            "id": 1,
            "name": "English",
            "code": "en"
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "teacher_id": ["The selected teacher id is invalid."],
        "language_id": ["The selected language id is invalid."]
    }
}
```

**Error Response - Assignment Not Found (500)**:

```json
{
    "success": false,
    "message": "Failed to remove teacher from language"
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/admin/remove-teacher-language" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 5,
    "language_id": 1
  }'
```

---

##### GET /admin/languages/{language}/teachers

**Description**: Get all teachers assigned to a specific language

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `language` (integer, required) - Language ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "language": {
            "id": 1,
            "name": "English",
            "code": "en"
        },
        "teachers": [
            {
                "id": 5,
                "name": "John Smith",
                "email": "john.smith@example.com",
                "phone": "+1234567890",
                "image_path": "/storage/teachers/john_smith.jpg",
                "assigned_at": "2024-01-15T10:30:00.000000Z",
                "assigned_by": "Admin User"
            },
            {
                "id": 8,
                "name": "Sarah Johnson",
                "email": "sarah.johnson@example.com",
                "phone": "+1234567891",
                "image_path": "/storage/teachers/sarah_johnson.jpg",
                "assigned_at": "2024-01-18T14:20:00.000000Z",
                "assigned_by": "Admin User"
            }
        ],
        "total": 2
    }
}
```

**Error Response - Language Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/languages/1/teachers" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

##### GET /admin/teacher-language-assignments

**Description**: Get all teacher-language assignments in the system

**Authentication**: Required (Admin role)

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "assignments": [
            {
                "id": 12,
                "teacher": {
                    "id": 5,
                    "name": "John Smith",
                    "email": "john.smith@example.com",
                    "phone": "+1234567890",
                    "image_path": "/storage/teachers/john_smith.jpg"
                },
                "language": {
                    "id": 1,
                    "name": "English",
                    "code": "en",
                    "active": true
                },
                "assigned_at": "2024-01-15T10:30:00.000000Z",
                "assigned_by": "Admin User"
            },
            {
                "id": 13,
                "teacher": {
                    "id": 5,
                    "name": "John Smith",
                    "email": "john.smith@example.com",
                    "phone": "+1234567890",
                    "image_path": "/storage/teachers/john_smith.jpg"
                },
                "language": {
                    "id": 2,
                    "name": "Spanish",
                    "code": "es",
                    "active": true
                },
                "assigned_at": "2024-01-18T14:20:00.000000Z",
                "assigned_by": "Admin User"
            }
        ],
        "total": 2
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/teacher-language-assignments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

##### GET /admin/teachers/{teacher}/available-languages

**Description**: Get languages that are available for assignment to a specific teacher (not yet assigned)

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `teacher` (integer, required) - Teacher ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "teacher": {
            "id": 5,
            "name": "John Smith",
            "email": "john.smith@example.com"
        },
        "available_languages": [
            {
                "id": 3,
                "code": "ar",
                "name": "Arabic",
                "active": true,
                "levels_count": 2,
                "programs_count": 4,
                "created_at": "2024-01-17T11:00:00.000000Z",
                "updated_at": "2024-01-19T13:30:00.000000Z"
            },
            {
                "id": 4,
                "code": "fr",
                "name": "French",
                "active": true,
                "levels_count": 3,
                "programs_count": 6,
                "created_at": "2024-01-20T09:15:00.000000Z",
                "updated_at": "2024-01-20T09:15:00.000000Z"
            }
        ],
        "total": 2
    }
}
```

**Error Response - Teacher Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/teachers/5/available-languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### Language Statistics and Program Association

These endpoints provide detailed statistics and program information for languages.

##### GET /admin/languages/{language}/statistics

**Description**: Get comprehensive program statistics for a specific language including enrollment data

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `language` (integer, required) - Language ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "language": {
            "id": 1,
            "name": "English",
            "code": "en"
        },
        "total_statistics": {
            "total_programs": 12,
            "active_programs": 10,
            "total_enrollments": 245,
            "total_approved_enrollments": 198
        },
        "program_statistics": [
            {
                "program": {
                    "id": 1,
                    "title": "English Beginner Program",
                    "description": "Introduction to English language",
                    "active": true,
                    "level": {
                        "id": 1,
                        "name": "Beginner",
                        "order": 1
                    }
                },
                "statistics": {
                    "total_enrollments": 25,
                    "approved_enrollments": 20,
                    "pending_enrollments": 5,
                    "enrollment_rate": 80.0
                }
            },
            {
                "program": {
                    "id": 2,
                    "title": "English Intermediate Program",
                    "description": "Intermediate English language skills",
                    "active": true,
                    "level": {
                        "id": 2,
                        "name": "Intermediate",
                        "order": 2
                    }
                },
                "statistics": {
                    "total_enrollments": 30,
                    "approved_enrollments": 28,
                    "pending_enrollments": 2,
                    "enrollment_rate": 93.33
                }
            }
        ]
    }
}
```

**Response Details**:

-   **total_statistics**: Aggregated statistics for the entire language
-   **program_statistics**: Detailed statistics for each program within the language
-   **enrollment_rate**: Percentage of approved enrollments vs total enrollments

**Error Response - Language Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/languages/1/statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

##### GET /admin/languages/{language}/programs

**Description**: Get all programs associated with a specific language including enrollment counts

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `language` (integer, required) - Language ID

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "language": {
            "id": 1,
            "name": "English",
            "code": "en"
        },
        "programs": [
            {
                "id": 1,
                "title": "English Beginner Program",
                "description": "Introduction to English language",
                "active": true,
                "level": {
                    "id": 1,
                    "name": "Beginner",
                    "order": 1
                },
                "enrollment_count": 25,
                "approved_enrollment_count": 20,
                "pending_enrollment_count": 5,
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-20T14:45:00.000000Z"
            },
            {
                "id": 2,
                "title": "English Beginner Conversation",
                "description": "Basic English conversation skills",
                "active": true,
                "level": {
                    "id": 1,
                    "name": "Beginner",
                    "order": 1
                },
                "enrollment_count": 18,
                "approved_enrollment_count": 15,
                "pending_enrollment_count": 3,
                "created_at": "2024-01-16T09:15:00.000000Z",
                "updated_at": "2024-01-19T16:20:00.000000Z"
            },
            {
                "id": 3,
                "title": "English Intermediate Program",
                "description": "Intermediate English language skills",
                "active": true,
                "level": {
                    "id": 2,
                    "name": "Intermediate",
                    "order": 2
                },
                "enrollment_count": 30,
                "approved_enrollment_count": 28,
                "pending_enrollment_count": 2,
                "created_at": "2024-01-17T11:00:00.000000Z",
                "updated_at": "2024-01-18T13:30:00.000000Z"
            }
        ],
        "total": 3
    }
}
```

**Response Details**:

-   **programs**: Array of all programs associated with the language
-   **level**: Level information for each program showing hierarchical organization
-   **enrollment_count**: Total number of enrollments for each program
-   **approved_enrollment_count**: Number of approved enrollments
-   **pending_enrollment_count**: Number of pending enrollments awaiting approval

**Error Response - Language Not Found (404)**:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/languages/1/programs" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### Language Management Workflows

##### Complete Language Setup Workflow

**Step 1: Create New Language with Levels**

```bash
curl -X POST "https://your-domain.com/api/admin/languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "de",
    "name": "German",
    "active": true,
    "levels": [
      {"name": "A1 - Beginner", "order": 1},
      {"name": "A2 - Elementary", "order": 2},
      {"name": "B1 - Intermediate", "order": 3},
      {"name": "B2 - Upper Intermediate", "order": 4},
      {"name": "C1 - Advanced", "order": 5}
    ]
  }'
```

**Step 2: Assign Teachers to Language**

```bash
curl -X POST "https://your-domain.com/api/admin/assign-teacher-multiple-languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 7,
    "language_ids": [4]
  }'
```

**Step 3: Verify Language Setup**

```bash
curl -X GET "https://your-domain.com/api/admin/languages/4" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 4: Monitor Language Statistics**

```bash
curl -X GET "https://your-domain.com/api/admin/languages/4/statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

##### Teacher Assignment Management Workflow

**Step 1: Check Available Languages for Teacher**

```bash
curl -X GET "https://your-domain.com/api/admin/teachers/5/available-languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Assign Teacher to Multiple Languages**

```bash
curl -X POST "https://your-domain.com/api/admin/assign-teacher-multiple-languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 5,
    "language_ids": [1, 2, 3]
  }'
```

**Step 3: Verify Assignments**

```bash
curl -X GET "https://your-domain.com/api/admin/teacher-language-assignments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 4: Remove Assignment if Needed**

```bash
curl -X DELETE "https://your-domain.com/api/admin/remove-teacher-language" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 5,
    "language_id": 3
  }'
```

##### Language Analytics and Reporting Workflow

**Step 1: Get Overall Language Overview**

```bash
curl -X GET "https://your-domain.com/api/admin/languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Analyze Specific Language Performance**

```bash
curl -X GET "https://your-domain.com/api/admin/languages/1/statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Review Program Distribution**

```bash
curl -X GET "https://your-domain.com/api/admin/languages/1/programs" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 4: Check Teacher Assignments**

```bash
curl -X GET "https://your-domain.com/api/admin/languages/1/teachers" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### Language Management Best Practices

##### Administrative Efficiency

**Language Organization**:

-   Use standardized language codes (ISO 639-1) for consistency
-   Create meaningful level names that reflect proficiency standards
-   Maintain active status to control language availability
-   Regular review of language statistics for performance monitoring

**Teacher Assignment Strategy**:

-   Assign teachers based on language expertise and availability
-   Use bulk assignment operations for efficiency
-   Monitor teacher workload across multiple languages
-   Regular review of teacher-language assignments

**Program Management**:

-   Ensure balanced program distribution across levels
-   Monitor enrollment patterns and approval rates
-   Use statistics endpoints for data-driven decisions
-   Regular cleanup of inactive or underperforming programs

##### Security and Access Control

**Role-Based Access**:

-   All language management operations require admin role
-   Teacher assignments control content access permissions
-   Regular audit of teacher-language assignments
-   Proper validation of all input parameters

**Data Integrity**:

-   Validate language codes for uniqueness and format
-   Prevent deletion of languages with active enrollments
-   Maintain referential integrity in teacher assignments
-   Regular backup of language configuration data

##### Performance Optimization

**Efficient Queries**:

-   Use bulk operations for multiple teacher assignments
-   Leverage statistics endpoints for reporting
-   Cache frequently accessed language data
-   Monitor query performance for large datasets

**Scalability Considerations**:

-   Plan for growth in number of languages and teachers
-   Consider pagination for large result sets
-   Optimize database indexes for language queries
-   Regular performance monitoring and optimization

---

### Settings Management

The Settings Management system provides administrators with comprehensive control over system configuration, including guest access permissions, feature toggles, and system-wide preferences. All settings endpoints require admin authentication and provide robust validation, caching, and error handling to ensure system stability and security.

#### System Settings Overview

The Learn Academy API uses a flexible key-value settings system that supports multiple data types including strings, booleans, integers, floats, arrays, and JSON objects. Settings are cached for optimal performance and automatically invalidated when updates occur.

**Supported Setting Types**:

-   `string` - Text values and configuration strings
-   `boolean` - True/false flags for feature toggles
-   `integer` - Numeric values for limits and counts
-   `float` - Decimal values for calculations and percentages
-   `array` - Lists of values stored as JSON
-   `json` - Complex objects and nested data structures

**Caching Strategy**:

-   Guest access settings cached for 1 hour (3600 seconds)
-   Automatic cache invalidation on setting updates
-   Cache keys organized by setting category for efficient management

---

#### GET /admin/settings/guest-access

**Description**: Retrieve all guest access configuration settings with available feature descriptions

**Authentication**: Required (Admin role)

**Purpose**: Get current guest access permissions and available features for system configuration

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "settings": {
            "allow_guest_languages": false,
            "allow_guest_teachers": true,
            "allow_guest_quizzes": false
        },
        "available_features": {
            "languages": "Allow guests to view available languages",
            "teachers": "Allow guests to view teacher profiles",
            "quizzes": "Allow guests to attempt quizzes anonymously"
        }
    }
}
```

**Response Details**:

-   **settings**: Current boolean values for each guest access feature
-   **available_features**: Descriptions of what each feature enables for guest users

**Error Response - Unauthorized (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "error": {
        "code": "SETTINGS_RETRIEVAL_FAILED",
        "message": "Failed to retrieve guest access settings",
        "details": "Database connection error"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
fetch("https://your-domain.com/api/admin/settings/guest-access", {
    method: "GET",
    headers: {
        Authorization: "Bearer " + adminToken,
        "Content-Type": "application/json",
    },
})
    .then((response) => response.json())
    .then((data) => {
        console.log("Guest Access Settings:", data.data.settings);
        console.log("Available Features:", data.data.available_features);
    });
```

---

#### PUT /admin/settings/guest-access

**Description**: Update guest access configuration settings with validation and automatic cache invalidation

**Authentication**: Required (Admin role)

**Request Body Parameters**:

| Field                   | Type    | Required | Description                              |
| ----------------------- | ------- | -------- | ---------------------------------------- |
| `allow_guest_languages` | boolean | No       | Enable guest access to language listings |
| `allow_guest_teachers`  | boolean | No       | Enable guest access to teacher profiles  |
| `allow_guest_quizzes`   | boolean | No       | Enable guest access to quiz attempts     |

**Request Body Example**:

```json
{
    "allow_guest_languages": true,
    "allow_guest_teachers": true,
    "allow_guest_quizzes": false
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Guest access settings updated successfully",
    "data": {
        "settings": {
            "allow_guest_languages": true,
            "allow_guest_teachers": true,
            "allow_guest_quizzes": false
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid settings data provided",
        "details": {
            "allow_guest_languages": [
                "The allow guest languages field must be true or false."
            ],
            "allow_guest_teachers": [
                "The allow guest teachers field must be true or false."
            ]
        }
    }
}
```

**Error Response - Update Failed (500)**:

```json
{
    "success": false,
    "error": {
        "code": "SETTINGS_UPDATE_FAILED",
        "message": "Failed to update guest access settings",
        "details": "Database write error"
    }
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "allow_guest_languages": true,
    "allow_guest_teachers": true,
    "allow_guest_quizzes": false
  }'
```

**JavaScript Example**:

```javascript
const updateGuestSettings = async (settings) => {
    try {
        const response = await fetch(
            "https://your-domain.com/api/admin/settings/guest-access",
            {
                method: "PUT",
                headers: {
                    Authorization: "Bearer " + adminToken,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(settings),
            }
        );

        const data = await response.json();

        if (data.success) {
            console.log("Settings updated:", data.data.settings);
        } else {
            console.error("Update failed:", data.error);
        }
    } catch (error) {
        console.error("Request failed:", error);
    }
};

// Update settings
updateGuestSettings({
    allow_guest_languages: true,
    allow_guest_teachers: true,
    allow_guest_quizzes: false,
});
```

---

#### GET /admin/settings/{key}

**Description**: Retrieve a specific setting value by key with type casting and default value handling

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `key` (string, required) - Setting key to retrieve

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "key": "allow_guest_languages",
        "value": true
    }
}
```

**Success Response - String Setting (200)**:

```json
{
    "success": true,
    "data": {
        "key": "system_name",
        "value": "Learn Academy Platform"
    }
}
```

**Success Response - Numeric Setting (200)**:

```json
{
    "success": true,
    "data": {
        "key": "max_quiz_attempts",
        "value": 3
    }
}
```

**Success Response - Array Setting (200)**:

```json
{
    "success": true,
    "data": {
        "key": "supported_languages",
        "value": ["en", "ar", "es"]
    }
}
```

**Error Response - Setting Not Found (404)**:

```json
{
    "success": false,
    "error": {
        "code": "SETTING_NOT_FOUND",
        "message": "Setting 'invalid_key' not found"
    }
}
```

**Error Response - Retrieval Failed (500)**:

```json
{
    "success": false,
    "error": {
        "code": "SETTING_RETRIEVAL_FAILED",
        "message": "Failed to retrieve setting",
        "details": "Database connection error"
    }
}
```

**cURL Examples**:

```bash
# Get boolean setting
curl -X GET "https://your-domain.com/api/admin/settings/allow_guest_languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Get string setting
curl -X GET "https://your-domain.com/api/admin/settings/system_name" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Get numeric setting
curl -X GET "https://your-domain.com/api/admin/settings/max_quiz_attempts" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### PUT /admin/settings/{key}

**Description**: Set or update a specific setting value with type validation and optional description

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `key` (string, required) - Setting key to update

**Request Body Parameters**:

| Field         | Type   | Required | Description                                             |
| ------------- | ------ | -------- | ------------------------------------------------------- |
| `value`       | mixed  | Yes      | Setting value (type depends on setting type)            |
| `type`        | string | No       | Data type: string, boolean, integer, float, array, json |
| `description` | string | No       | Human-readable description (max 255 characters)         |

**Request Body Examples**:

**Boolean Setting**:

```json
{
    "value": true,
    "type": "boolean",
    "description": "Enable guest access to language listings"
}
```

**String Setting**:

```json
{
    "value": "Learn Academy Platform v2.0",
    "type": "string",
    "description": "System display name"
}
```

**Integer Setting**:

```json
{
    "value": 5,
    "type": "integer",
    "description": "Maximum quiz attempts per user"
}
```

**Array Setting**:

```json
{
    "value": ["en", "ar", "es", "fr"],
    "type": "array",
    "description": "Supported system languages"
}
```

**JSON Setting**:

```json
{
    "value": {
        "email": {
            "enabled": true,
            "smtp_host": "smtp.example.com"
        },
        "sms": {
            "enabled": false,
            "provider": "twilio"
        }
    },
    "type": "json",
    "description": "Notification system configuration"
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Setting updated successfully",
    "data": {
        "setting": {
            "key": "allow_guest_languages",
            "value": true,
            "type": "boolean",
            "description": "Enable guest access to language listings"
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid setting data provided",
        "details": {
            "value": ["The value field is required."],
            "type": ["The selected type is invalid."],
            "description": [
                "The description may not be greater than 255 characters."
            ]
        }
    }
}
```

**Error Response - Update Failed (500)**:

```json
{
    "success": false,
    "error": {
        "code": "SETTING_UPDATE_FAILED",
        "message": "Failed to update setting",
        "details": "Database write error"
    }
}
```

**cURL Examples**:

```bash
# Set boolean setting
curl -X PUT "https://your-domain.com/api/admin/settings/allow_guest_languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "value": true,
    "type": "boolean",
    "description": "Enable guest access to language listings"
  }'

# Set string setting
curl -X PUT "https://your-domain.com/api/admin/settings/system_name" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "value": "Learn Academy Platform v2.0",
    "type": "string",
    "description": "System display name"
  }'

# Set integer setting
curl -X PUT "https://your-domain.com/api/admin/settings/max_quiz_attempts" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "value": 5,
    "type": "integer",
    "description": "Maximum quiz attempts per user"
  }'

# Set array setting
curl -X PUT "https://your-domain.com/api/admin/settings/supported_languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "value": ["en", "ar", "es", "fr"],
    "type": "array",
    "description": "Supported system languages"
  }'
```

---

#### POST /admin/settings/initialize-defaults

**Description**: Initialize default system settings with predefined values for guest access and core system configuration

**Authentication**: Required (Admin role)

**Purpose**: Set up default settings for new installations or reset settings to system defaults

**Request Body**: None required (empty JSON object)

**Request Body Example**:

```json
{}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Default settings initialized successfully",
    "data": {
        "settings": {
            "allow_guest_languages": false,
            "allow_guest_teachers": false,
            "allow_guest_quizzes": false
        }
    }
}
```

**Error Response - Initialization Failed (500)**:

```json
{
    "success": false,
    "error": {
        "code": "INITIALIZATION_FAILED",
        "message": "Failed to initialize default settings",
        "details": "Database write error during initialization"
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/admin/settings/initialize-defaults" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**JavaScript Example**:

```javascript
const initializeDefaults = async () => {
    try {
        const response = await fetch(
            "https://your-domain.com/api/admin/settings/initialize-defaults",
            {
                method: "POST",
                headers: {
                    Authorization: "Bearer " + adminToken,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({}),
            }
        );

        const data = await response.json();

        if (data.success) {
            console.log("Default settings initialized:", data.data.settings);
        } else {
            console.error("Initialization failed:", data.error);
        }
    } catch (error) {
        console.error("Request failed:", error);
    }
};

initializeDefaults();
```

**Default Settings Initialized**:

-   `allow_guest_languages`: false - Guest access to language listings disabled
-   `allow_guest_teachers`: false - Guest access to teacher profiles disabled
-   `allow_guest_quizzes`: false - Guest access to quiz attempts disabled

**Initialization Behavior**:

-   Only creates settings that don't already exist
-   Preserves existing setting values and customizations
-   Clears settings cache after initialization
-   Safe to run multiple times without data loss

---

### Settings Management Workflows

#### Complete Guest Access Configuration Workflow

**Step 1: Check Current Settings**

```bash
curl -X GET "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Update Guest Access Settings**

```bash
curl -X PUT "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "allow_guest_languages": true,
    "allow_guest_teachers": true,
    "allow_guest_quizzes": false
  }'
```

**Step 3: Verify Individual Settings**

```bash
# Check language access setting
curl -X GET "https://your-domain.com/api/admin/settings/allow_guest_languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Check teacher access setting
curl -X GET "https://your-domain.com/api/admin/settings/allow_guest_teachers" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### System Configuration Setup Workflow

**Step 1: Initialize Default Settings**

```bash
curl -X POST "https://your-domain.com/api/admin/settings/initialize-defaults" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Step 2: Configure System Name**

```bash
curl -X PUT "https://your-domain.com/api/admin/settings/system_name" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "value": "My Learning Academy",
    "type": "string",
    "description": "Custom system display name"
  }'
```

**Step 3: Set Quiz Limits**

```bash
curl -X PUT "https://your-domain.com/api/admin/settings/max_quiz_attempts" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "value": 3,
    "type": "integer",
    "description": "Maximum quiz attempts per user"
  }'
```

**Step 4: Configure Notification Settings**

```bash
curl -X PUT "https://your-domain.com/api/admin/settings/notification_config" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "value": {
      "email_enabled": true,
      "sms_enabled": false,
      "push_enabled": true
    },
    "type": "json",
    "description": "Notification system configuration"
  }'
```

#### Bulk Settings Management Example

**JavaScript Implementation**:

```javascript
class SettingsManager {
    constructor(apiBase, adminToken) {
        this.apiBase = apiBase;
        this.token = adminToken;
    }

    async getSetting(key) {
        const response = await fetch(`${this.apiBase}/admin/settings/${key}`, {
            headers: {
                Authorization: `Bearer ${this.token}`,
                "Content-Type": "application/json",
            },
        });
        return response.json();
    }

    async setSetting(key, value, type = "string", description = null) {
        const response = await fetch(`${this.apiBase}/admin/settings/${key}`, {
            method: "PUT",
            headers: {
                Authorization: `Bearer ${this.token}`,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ value, type, description }),
        });
        return response.json();
    }

    async updateGuestAccess(settings) {
        const response = await fetch(
            `${this.apiBase}/admin/settings/guest-access`,
            {
                method: "PUT",
                headers: {
                    Authorization: `Bearer ${this.token}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(settings),
            }
        );
        return response.json();
    }

    async initializeDefaults() {
        const response = await fetch(
            `${this.apiBase}/admin/settings/initialize-defaults`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${this.token}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({}),
            }
        );
        return response.json();
    }
}

// Usage example
const settings = new SettingsManager("https://your-domain.com/api", adminToken);

// Configure system settings
await settings.initializeDefaults();
await settings.setSetting("system_name", "My Academy", "string", "System name");
await settings.setSetting("max_attempts", 5, "integer", "Max quiz attempts");
await settings.updateGuestAccess({
    allow_guest_languages: true,
    allow_guest_teachers: true,
    allow_guest_quizzes: false,
});
```

### Settings Security and Performance

#### Security Considerations

**Access Control**:

-   All settings endpoints require admin authentication
-   Settings keys are validated to prevent injection attacks
-   Sensitive settings should be encrypted at the application level
-   Regular audit logs for settings changes recommended

**Data Validation**:

-   Type validation ensures data integrity
-   Description length limits prevent database overflow
-   JSON validation for complex settings structures
-   Boolean conversion handles various input formats

**Cache Security**:

-   Cache keys are predictable but require admin access
-   Cache invalidation prevents stale data exposure
-   TTL limits reduce exposure window for cached sensitive data

#### Performance Optimization

**Caching Strategy**:

-   Guest access settings cached for 1 hour (3600 seconds)
-   Automatic cache invalidation on updates
-   Selective cache clearing for related settings only
-   Memory-efficient cache key organization

**Database Optimization**:

-   Indexed setting keys for fast retrieval
-   Minimal database queries through caching
-   Batch operations for multiple setting updates
-   Connection pooling for high-traffic scenarios

**Monitoring Recommendations**:

-   Track cache hit/miss ratios for optimization
-   Monitor setting update frequency for abuse detection
-   Log setting changes for audit trails
-   Alert on critical setting modifications

---

## Teacher Operations

The Teacher Operations section provides comprehensive functionality for teachers to manage their educational content, interact with students, and access language-specific resources. All teacher endpoints require authentication with either teacher or admin role, ensuring secure access to educational management tools.

### Quiz Management

Teacher quiz management endpoints provide complete CRUD operations for quiz creation, question management, and student progress tracking. Teachers can create, update, and delete quizzes, manage quiz questions with full control over ordering and content, and access detailed statistics about student performance and quiz attempts.

#### GET /teacher/quizzes

**Description**: Retrieve all quizzes created by the authenticated teacher with pagination and filtering options

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   List all quizzes created by the teacher for management and overview
-   Support pagination for teachers with large numbers of quizzes
-   Enable filtering and searching for specific quizzes
-   Provide quick access to quiz statistics and status information

**Query Parameters**:

| Parameter    | Type    | Required | Description                            | Default | Validation       |
| ------------ | ------- | -------- | -------------------------------------- | ------- | ---------------- |
| `page`       | integer | No       | Page number for pagination             | 1       | Min: 1           |
| `per_page`   | integer | No       | Items per page                         | 15      | Min: 1, Max: 100 |
| `search`     | string  | No       | Search term for quiz title/description | -       | Max: 255 chars   |
| `type`       | string  | No       | Filter by quiz type                    | -       | Valid quiz type  |
| `active`     | boolean | No       | Filter by active status                | -       | true/false       |
| `program_id` | integer | No       | Filter by program ID                   | -       | Valid program ID |

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "title": "Spanish Grammar Basics",
                "description": "Introduction to Spanish grammar fundamentals",
                "type": "multiple_choice",
                "active": true,
                "program_id": 5,
                "teacher_id": 3,
                "questions_count": 15,
                "attempts_count": 42,
                "average_score": 78.5,
                "program": {
                    "id": 5,
                    "title": "Spanish Level 1",
                    "language": {
                        "id": 2,
                        "name": "Spanish",
                        "code": "es"
                    }
                },
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-20T14:15:00.000000Z"
            },
            {
                "id": 2,
                "title": "Vocabulary Test - Animals",
                "description": "Test your knowledge of animal names in Spanish",
                "type": "fill_in_blank",
                "active": true,
                "program_id": 5,
                "teacher_id": 3,
                "questions_count": 20,
                "attempts_count": 38,
                "average_score": 82.3,
                "program": {
                    "id": 5,
                    "title": "Spanish Level 1",
                    "language": {
                        "id": 2,
                        "name": "Spanish",
                        "code": "es"
                    }
                },
                "created_at": "2024-01-18T09:45:00.000000Z",
                "updated_at": "2024-01-22T16:30:00.000000Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 8,
        "last_page": 1,
        "from": 1,
        "to": 8
    }
}
```

**Response Details**:

-   **id**: Unique quiz identifier
-   **title**: Quiz title/name
-   **description**: Optional quiz description
-   **type**: Quiz type (multiple_choice, fill_in_blank, true_false, etc.)
-   **active**: Whether quiz is active and available to students
-   **program_id**: Associated program ID
-   **teacher_id**: Quiz creator's ID
-   **questions_count**: Total number of questions in the quiz
-   **attempts_count**: Total number of student attempts
-   **average_score**: Average score across all attempts (percentage)
-   **program**: Associated program information with language details

**cURL Example**:

```bash
# Get all teacher's quizzes
curl -X GET "https://your-domain.com/api/teacher/quizzes" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Get quizzes with search and filtering
curl -X GET "https://your-domain.com/api/teacher/quizzes?search=grammar&active=true&per_page=10" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### POST /teacher/quizzes

**Description**: Create a new quiz with basic information and configuration

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   Create new quizzes for student assessment
-   Set quiz configuration and basic properties
-   Associate quiz with specific programs and languages
-   Initialize quiz structure for question addition

**Request Body Parameters**:

| Field                 | Type    | Required | Description                      | Validation                                                |
| --------------------- | ------- | -------- | -------------------------------- | --------------------------------------------------------- |
| `title`               | string  | Yes      | Quiz title/name                  | Max: 255 chars, unique per teacher                        |
| `description`         | string  | No       | Quiz description                 | Max: 1000 chars                                           |
| `type`                | string  | Yes      | Quiz type                        | One of: multiple_choice, fill_in_blank, true_false, mixed |
| `active`              | boolean | No       | Quiz active status               | Default: true                                             |
| `program_id`          | integer | Yes      | Associated program ID            | Must exist and be accessible to teacher                   |
| `time_limit`          | integer | No       | Time limit in minutes            | Min: 1, Max: 300                                          |
| `max_attempts`        | integer | No       | Maximum attempts per student     | Min: 1, Max: 10                                           |
| `passing_score`       | integer | No       | Minimum passing score percentage | Min: 0, Max: 100                                          |
| `show_results`        | boolean | No       | Show results to students         | Default: true                                             |
| `randomize_questions` | boolean | No       | Randomize question order         | Default: false                                            |

**Request Body Examples**:

**Multiple Choice Quiz**:
```json
{
    "title": "Spanish Verb Conjugation Quiz",
    "description": "Test your understanding of present tense verb conjugations in Spanish. This quiz covers regular and irregular verbs in the present tense.",
    "type": "multiple_choice",
    "active": true,
    "program_id": 5,
    "time_limit": 30,
    "max_attempts": 3,
    "passing_score": 70,
    "show_results": true,
    "randomize_questions": false
}
```

**Fill in the Blank Quiz**:
```json
{
    "title": "Arabic Grammar Completion",
    "description": "Complete the sentences with the correct Arabic grammar forms. Focus on verb conjugations and noun declensions.",
    "type": "fill_in_blank",
    "active": true,
    "program_id": 12,
    "time_limit": 45,
    "max_attempts": 2,
    "passing_score": 80,
    "show_results": false,
    "randomize_questions": true
}
```

**Mixed Type Quiz**:
```json
{
    "title": "English Comprehensive Assessment",
    "description": "Comprehensive English assessment covering vocabulary, grammar, and reading comprehension using various question types.",
    "type": "mixed",
    "active": true,
    "program_id": 8,
    "time_limit": 60,
    "max_attempts": 1,
    "passing_score": 75,
    "show_results": true,
    "randomize_questions": true
}
```

**Quick Assessment Quiz**:
```json
{
    "title": "Daily Vocabulary Check",
    "description": "Quick daily vocabulary assessment for intermediate learners.",
    "type": "true_false",
    "active": true,
    "program_id": 3,
    "time_limit": 10,
    "max_attempts": 5,
    "passing_score": 60,
    "show_results": true,
    "randomize_questions": true
}
```

**Success Response Examples**:

**Multiple Choice Quiz Created (201)**:
```json
{
    "success": true,
    "message": "Quiz created successfully",
    "data": {
        "id": 15,
        "title": "Spanish Verb Conjugation Quiz",
        "description": "Test your understanding of present tense verb conjugations in Spanish. This quiz covers regular and irregular verbs in the present tense.",
        "type": "multiple_choice",
        "active": true,
        "program_id": 5,
        "teacher_id": 3,
        "time_limit": 30,
        "max_attempts": 3,
        "passing_score": 70,
        "show_results": true,
        "randomize_questions": false,
        "questions_count": 0,
        "attempts_count": 0,
        "average_score": null,
        "program": {
            "id": 5,
            "title": "Spanish Level 1 - Beginner Conversation",
            "description": "Introduction to Spanish conversation and basic grammar",
            "language": {
                "id": 2,
                "name": "Spanish",
                "code": "es",
                "native_name": "Español"
            },
            "level": {
                "id": 1,
                "name": "Beginner",
                "description": "Basic level for new learners"
            }
        },
        "teacher": {
            "id": 3,
            "name": "Maria Rodriguez",
            "email": "maria.rodriguez@example.com"
        },
        "settings": {
            "time_limit": 30,
            "max_attempts": 3,
            "passing_score": 70,
            "show_results": true,
            "randomize_questions": false,
            "allow_review": true,
            "show_correct_answers": true
        },
        "created_at": "2024-01-25T11:30:00.000000Z",
        "updated_at": "2024-01-25T11:30:00.000000Z"
    }
}
```

**Fill in the Blank Quiz Created (201)**:
```json
{
    "success": true,
    "message": "Quiz created successfully",
    "data": {
        "id": 23,
        "title": "Arabic Grammar Completion",
        "description": "Complete the sentences with the correct Arabic grammar forms. Focus on verb conjugations and noun declensions.",
        "type": "fill_in_blank",
        "active": true,
        "program_id": 12,
        "teacher_id": 7,
        "time_limit": 45,
        "max_attempts": 2,
        "passing_score": 80,
        "show_results": false,
        "randomize_questions": true,
        "questions_count": 0,
        "attempts_count": 0,
        "average_score": null,
        "program": {
            "id": 12,
            "title": "Arabic Grammar Fundamentals",
            "language": {
                "id": 3,
                "name": "Arabic",
                "code": "ar",
                "native_name": "العربية"
            },
            "level": {
                "id": 2,
                "name": "Intermediate",
                "description": "For learners with basic knowledge"
            }
        },
        "teacher": {
            "id": 7,
            "name": "Ahmed Al-Mansouri",
            "email": "ahmed.almansouri@example.com"
        },
        "created_at": "2024-01-25T14:15:00.000000Z",
        "updated_at": "2024-01-25T14:15:00.000000Z"
    }
}
```

**Validation Error Response Examples**:

**Multiple Field Validation Errors (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": [
            "The title field is required.",
            "The title may not be greater than 255 characters."
        ],
        "program_id": [
            "The selected program id is invalid.",
            "You do not have access to this program."
        ],
        "type": ["The selected type is invalid."],
        "time_limit": [
            "The time limit must be at least 1 minute.",
            "The time limit may not be greater than 300 minutes."
        ],
        "passing_score": [
            "The passing score must be between 0 and 100."
        ]
    }
}
```

**Duplicate Title Error (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": [
            "You already have a quiz with this title. Quiz titles must be unique."
        ]
    }
}
```

**Program Access Error (403)**:
```json
{
    "success": false,
    "message": "Unauthorized access to program",
    "errors": {
        "program_id": [
            "You do not have permission to create quizzes for this program.",
            "This program is not assigned to your teaching languages."
        ]
    }
}
```

**Invalid Quiz Configuration (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "max_attempts": ["The max attempts must be between 1 and 10."],
        "time_limit": ["The time limit must be a positive integer."],
        "passing_score": ["The passing score must be between 0 and 100."]
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/teacher/quizzes" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Spanish Verb Conjugation Quiz",
    "description": "Test your understanding of present tense verb conjugations",
    "type": "multiple_choice",
    "active": true,
    "program_id": 5,
    "time_limit": 30,
    "max_attempts": 3,
    "passing_score": 70,
    "show_results": true,
    "randomize_questions": false
  }'
```

---

#### GET /teacher/quizzes/{quiz}

**Description**: Retrieve detailed information about a specific quiz including questions and configuration

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   View complete quiz details including all questions
-   Access quiz configuration and settings
-   Review quiz statistics and performance data
-   Prepare for quiz editing or question management

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 15,
        "title": "Spanish Verb Conjugation Quiz",
        "description": "Test your understanding of present tense verb conjugations",
        "type": "multiple_choice",
        "active": true,
        "program_id": 5,
        "teacher_id": 3,
        "time_limit": 30,
        "max_attempts": 3,
        "passing_score": 70,
        "show_results": true,
        "randomize_questions": false,
        "questions_count": 5,
        "attempts_count": 12,
        "average_score": 75.8,
        "program": {
            "id": 5,
            "title": "Spanish Level 1",
            "language": {
                "id": 2,
                "name": "Spanish",
                "code": "es"
            }
        },
        "questions": [
            {
                "id": 45,
                "question_text": "What is the correct conjugation of 'hablar' for 'yo'?",
                "question_type": "multiple_choice",
                "points": 2,
                "order": 1,
                "options": [
                    {
                        "id": 180,
                        "option_text": "hablo",
                        "is_correct": true,
                        "order": 1
                    },
                    {
                        "id": 181,
                        "option_text": "hablas",
                        "is_correct": false,
                        "order": 2
                    },
                    {
                        "id": 182,
                        "option_text": "habla",
                        "is_correct": false,
                        "order": 3
                    }
                ]
            }
        ],
        "created_at": "2024-01-25T11:30:00.000000Z",
        "updated_at": "2024-01-25T14:45:00.000000Z"
    }
}
```

**Error Response - Quiz Not Found (404)**:

```json
{
    "success": false,
    "message": "Quiz not found or access denied"
}
```

**Error Response - Access Denied (403)**:

```json
{
    "success": false,
    "message": "You do not have permission to access this quiz"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/teacher/quizzes/15" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### PUT /teacher/quizzes/{quiz}

**Description**: Update quiz information, configuration, and settings

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   Modify quiz title, description, and configuration
-   Update quiz settings like time limits and attempt restrictions
-   Change quiz active status and visibility
-   Adjust scoring and result display options

**Request Body Parameters**:

| Field                 | Type    | Required | Description                      | Validation                                                |
| --------------------- | ------- | -------- | -------------------------------- | --------------------------------------------------------- |
| `title`               | string  | No       | Quiz title/name                  | Max: 255 chars                                            |
| `description`         | string  | No       | Quiz description                 | Max: 1000 chars                                           |
| `type`                | string  | No       | Quiz type                        | One of: multiple_choice, fill_in_blank, true_false, mixed |
| `active`              | boolean | No       | Quiz active status               | true/false                                                |
| `time_limit`          | integer | No       | Time limit in minutes            | Min: 1, Max: 300                                          |
| `max_attempts`        | integer | No       | Maximum attempts per student     | Min: 1, Max: 10                                           |
| `passing_score`       | integer | No       | Minimum passing score percentage | Min: 0, Max: 100                                          |
| `show_results`        | boolean | No       | Show results to students         | true/false                                                |
| `randomize_questions` | boolean | No       | Randomize question order         | true/false                                                |

**Request Body Example**:

```json
{
    "title": "Spanish Verb Conjugation - Updated",
    "description": "Comprehensive test of present tense verb conjugations with examples",
    "active": true,
    "time_limit": 45,
    "max_attempts": 5,
    "passing_score": 75,
    "show_results": true,
    "randomize_questions": true
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Quiz updated successfully",
    "data": {
        "id": 15,
        "title": "Spanish Verb Conjugation - Updated",
        "description": "Comprehensive test of present tense verb conjugations with examples",
        "type": "multiple_choice",
        "active": true,
        "program_id": 5,
        "teacher_id": 3,
        "time_limit": 45,
        "max_attempts": 5,
        "passing_score": 75,
        "show_results": true,
        "randomize_questions": true,
        "questions_count": 5,
        "attempts_count": 12,
        "average_score": 75.8,
        "program": {
            "id": 5,
            "title": "Spanish Level 1",
            "language": {
                "id": 2,
                "name": "Spanish",
                "code": "es"
            }
        },
        "created_at": "2024-01-25T11:30:00.000000Z",
        "updated_at": "2024-01-25T16:20:00.000000Z"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "time_limit": ["The time limit must be at least 1 minute."],
        "passing_score": ["The passing score must be between 0 and 100."]
    }
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/teacher/quizzes/15" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Spanish Verb Conjugation - Updated",
    "description": "Comprehensive test of present tense verb conjugations with examples",
    "time_limit": 45,
    "max_attempts": 5,
    "passing_score": 75,
    "randomize_questions": true
  }'
```

---

#### DELETE /teacher/quizzes/{quiz}

**Description**: Delete a quiz and all associated questions and student attempts

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   Remove quiz from the system permanently
-   Clean up associated questions and student data
-   Free up resources and maintain data integrity
-   Provide confirmation of successful deletion

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Quiz deleted successfully"
}
```

**Error Response - Quiz Not Found (404)**:

```json
{
    "success": false,
    "message": "Quiz not found or access denied"
}
```

**Error Response - Cannot Delete (400)**:

```json
{
    "success": false,
    "message": "Cannot delete quiz with active student attempts"
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/teacher/quizzes/15" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### POST /teacher/quizzes/{quiz}/questions

**Description**: Add a new question to an existing quiz with answer options

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   Add questions to quizzes for student assessment
-   Define question types and scoring
-   Set up answer options with correct answers
-   Control question ordering within the quiz

**Request Body Parameters**:

| Field                   | Type    | Required | Description               | Validation                                         |
| ----------------------- | ------- | -------- | ------------------------- | -------------------------------------------------- |
| `question_text`         | string  | Yes      | The question text         | Max: 1000 chars                                    |
| `question_type`         | string  | Yes      | Type of question          | One of: multiple_choice, fill_in_blank, true_false |
| `points`                | integer | No       | Points for correct answer | Min: 1, Max: 10, Default: 1                        |
| `order`                 | integer | No       | Question order in quiz    | Auto-assigned if not provided                      |
| `options`               | array   | Yes\*    | Answer options            | Required for multiple_choice and true_false        |
| `options.*.option_text` | string  | Yes      | Option text               | Max: 500 chars                                     |
| `options.*.is_correct`  | boolean | Yes      | Whether option is correct | At least one must be true                          |
| `options.*.order`       | integer | No       | Option order              | Auto-assigned if not provided                      |

**Request Body Example (Multiple Choice)**:

```json
{
    "question_text": "What is the correct conjugation of 'comer' for 'tú'?",
    "question_type": "multiple_choice",
    "points": 2,
    "order": 6,
    "options": [
        {
            "option_text": "como",
            "is_correct": false,
            "order": 1
        },
        {
            "option_text": "comes",
            "is_correct": true,
            "order": 2
        },
        {
            "option_text": "come",
            "is_correct": false,
            "order": 3
        },
        {
            "option_text": "comemos",
            "is_correct": false,
            "order": 4
        }
    ]
}
```

**Request Body Example (Fill in Blank)**:

```json
{
    "question_text": "Complete the sentence: Yo _____ español todos los días.",
    "question_type": "fill_in_blank",
    "points": 1,
    "options": [
        {
            "option_text": "estudio",
            "is_correct": true
        },
        {
            "option_text": "estudío",
            "is_correct": true
        }
    ]
}
```

**Success Response (201)**:

```json
{
    "success": true,
    "message": "Question added successfully",
    "data": {
        "id": 46,
        "quiz_id": 15,
        "question_text": "What is the correct conjugation of 'comer' for 'tú'?",
        "question_type": "multiple_choice",
        "points": 2,
        "order": 6,
        "options": [
            {
                "id": 183,
                "option_text": "como",
                "is_correct": false,
                "order": 1
            },
            {
                "id": 184,
                "option_text": "comes",
                "is_correct": true,
                "order": 2
            },
            {
                "id": 185,
                "option_text": "come",
                "is_correct": false,
                "order": 3
            },
            {
                "id": 186,
                "option_text": "comemos",
                "is_correct": false,
                "order": 4
            }
        ],
        "created_at": "2024-01-25T17:30:00.000000Z",
        "updated_at": "2024-01-25T17:30:00.000000Z"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "question_text": ["The question text field is required."],
        "options": ["At least one correct answer must be provided."],
        "options.0.option_text": ["The option text field is required."]
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/teacher/quizzes/15/questions" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is the correct conjugation of '\''comer'\'' for '\''tú'\''?",
    "question_type": "multiple_choice",
    "points": 2,
    "options": [
      {
        "option_text": "como",
        "is_correct": false
      },
      {
        "option_text": "comes",
        "is_correct": true
      },
      {
        "option_text": "come",
        "is_correct": false
      }
    ]
  }'
```

---

#### PUT /teacher/quizzes/{quiz}/questions/{question}

**Description**: Update an existing quiz question and its answer options

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID
-   **Path**: `question` (integer, required) - Question ID

**Purpose**:

-   Modify question text and configuration
-   Update answer options and correct answers
-   Adjust question scoring and ordering
-   Maintain quiz content accuracy and relevance

**Request Body Parameters**:

| Field                   | Type    | Required | Description               | Validation                                         |
| ----------------------- | ------- | -------- | ------------------------- | -------------------------------------------------- |
| `question_text`         | string  | No       | The question text         | Max: 1000 chars                                    |
| `question_type`         | string  | No       | Type of question          | One of: multiple_choice, fill_in_blank, true_false |
| `points`                | integer | No       | Points for correct answer | Min: 1, Max: 10                                    |
| `order`                 | integer | No       | Question order in quiz    | Positive integer                                   |
| `options`               | array   | No       | Answer options            | Required if updating options                       |
| `options.*.id`          | integer | No       | Option ID (for updates)   | Existing option ID                                 |
| `options.*.option_text` | string  | Yes      | Option text               | Max: 500 chars                                     |
| `options.*.is_correct`  | boolean | Yes      | Whether option is correct | At least one must be true                          |
| `options.*.order`       | integer | No       | Option order              | Positive integer                                   |

**Request Body Example**:

```json
{
    "question_text": "What is the correct conjugation of 'comer' for 'tú' in present tense?",
    "points": 3,
    "options": [
        {
            "id": 183,
            "option_text": "como",
            "is_correct": false,
            "order": 1
        },
        {
            "id": 184,
            "option_text": "comes",
            "is_correct": true,
            "order": 2
        },
        {
            "id": 185,
            "option_text": "come",
            "is_correct": false,
            "order": 3
        },
        {
            "option_text": "comen",
            "is_correct": false,
            "order": 4
        }
    ]
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Question updated successfully",
    "data": {
        "id": 46,
        "quiz_id": 15,
        "question_text": "What is the correct conjugation of 'comer' for 'tú' in present tense?",
        "question_type": "multiple_choice",
        "points": 3,
        "order": 6,
        "options": [
            {
                "id": 183,
                "option_text": "como",
                "is_correct": false,
                "order": 1
            },
            {
                "id": 184,
                "option_text": "comes",
                "is_correct": true,
                "order": 2
            },
            {
                "id": 185,
                "option_text": "come",
                "is_correct": false,
                "order": 3
            },
            {
                "id": 187,
                "option_text": "comen",
                "is_correct": false,
                "order": 4
            }
        ],
        "created_at": "2024-01-25T17:30:00.000000Z",
        "updated_at": "2024-01-25T18:15:00.000000Z"
    }
}
```

**Error Response - Question Not Found (404)**:

```json
{
    "success": false,
    "message": "Question not found or access denied"
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/teacher/quizzes/15/questions/46" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question_text": "What is the correct conjugation of '\''comer'\'' for '\''tú'\'' in present tense?",
    "points": 3,
    "options": [
      {
        "id": 183,
        "option_text": "como",
        "is_correct": false
      },
      {
        "id": 184,
        "option_text": "comes",
        "is_correct": true
      }
    ]
  }'
```

---

#### DELETE /teacher/quizzes/{quiz}/questions/{question}

**Description**: Delete a specific question from a quiz

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID
-   **Path**: `question` (integer, required) - Question ID

**Purpose**:

-   Remove questions from quizzes
-   Clean up quiz content and maintain relevance
-   Adjust quiz structure and question count
-   Maintain data integrity after question removal

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Question deleted successfully"
}
```

**Error Response - Question Not Found (404)**:

```json
{
    "success": false,
    "message": "Question not found or access denied"
}
```

**Error Response - Cannot Delete (400)**:

```json
{
    "success": false,
    "message": "Cannot delete question from quiz with active attempts"
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/teacher/quizzes/15/questions/46" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### POST /teacher/quizzes/{quiz}/questions/reorder

**Description**: Reorder questions within a quiz by updating their order values

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   Change the order of questions in a quiz
-   Organize questions logically for better flow
-   Adjust quiz structure for improved learning experience
-   Maintain question sequence consistency

**Request Body Parameters**:

| Field               | Type    | Required | Description                     | Validation                           |
| ------------------- | ------- | -------- | ------------------------------- | ------------------------------------ |
| `questions`         | array   | Yes      | Array of question order objects | Must contain all quiz questions      |
| `questions.*.id`    | integer | Yes      | Question ID                     | Must exist in the quiz               |
| `questions.*.order` | integer | Yes      | New order position              | Positive integer, unique within quiz |

**Request Body Example**:

```json
{
    "questions": [
        {
            "id": 45,
            "order": 1
        },
        {
            "id": 47,
            "order": 2
        },
        {
            "id": 46,
            "order": 3
        },
        {
            "id": 48,
            "order": 4
        }
    ]
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Questions reordered successfully",
    "data": {
        "quiz_id": 15,
        "questions_reordered": 4,
        "new_order": [
            {
                "id": 45,
                "order": 1,
                "question_text": "What is the correct conjugation of 'hablar' for 'yo'?"
            },
            {
                "id": 47,
                "order": 2,
                "question_text": "Choose the correct article for 'mesa'."
            },
            {
                "id": 46,
                "order": 3,
                "question_text": "What is the correct conjugation of 'comer' for 'tú' in present tense?"
            },
            {
                "id": 48,
                "order": 4,
                "question_text": "Complete the sentence: Ella _____ en la biblioteca."
            }
        ]
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "questions": [
            "All quiz questions must be included in the reorder request."
        ],
        "questions.0.order": ["The order values must be unique."]
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/teacher/quizzes/15/questions/reorder" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "questions": [
      {
        "id": 45,
        "order": 1
      },
      {
        "id": 47,
        "order": 2
      },
      {
        "id": 46,
        "order": 3
      }
    ]
  }'
```

---

#### GET /teacher/quizzes/{quiz}/statistics

**Description**: Retrieve comprehensive statistics and analytics for a specific quiz

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   Monitor quiz performance and student engagement
-   Analyze question difficulty and effectiveness
-   Track completion rates and scoring patterns
-   Generate insights for quiz improvement

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "quiz_id": 15,
        "quiz_title": "Spanish Verb Conjugation Quiz",
        "total_attempts": 45,
        "unique_students": 28,
        "completion_rate": 88.9,
        "average_score": 76.4,
        "median_score": 78.0,
        "highest_score": 95.0,
        "lowest_score": 42.0,
        "passing_rate": 82.2,
        "average_time_spent": 18.5,
        "score_distribution": {
            "90-100": 8,
            "80-89": 12,
            "70-79": 11,
            "60-69": 7,
            "50-59": 4,
            "0-49": 3
        },
        "question_statistics": [
            {
                "question_id": 45,
                "question_text": "What is the correct conjugation of 'hablar' for 'yo'?",
                "correct_answers": 42,
                "incorrect_answers": 3,
                "accuracy_rate": 93.3,
                "average_time": 12.8,
                "difficulty_level": "easy"
            },
            {
                "question_id": 46,
                "question_text": "What is the correct conjugation of 'comer' for 'tú' in present tense?",
                "correct_answers": 31,
                "incorrect_answers": 14,
                "accuracy_rate": 68.9,
                "average_time": 18.2,
                "difficulty_level": "medium"
            }
        ],
        "recent_attempts": [
            {
                "student_name": "Maria Garcia",
                "score": 85.0,
                "completed_at": "2024-01-25T16:30:00.000000Z",
                "time_spent": 22
            },
            {
                "student_name": "Carlos Rodriguez",
                "score": 92.0,
                "completed_at": "2024-01-25T15:45:00.000000Z",
                "time_spent": 16
            }
        ],
        "performance_trends": {
            "last_7_days": {
                "attempts": 12,
                "average_score": 78.2
            },
            "last_30_days": {
                "attempts": 45,
                "average_score": 76.4
            }
        }
    }
}
```

**Response Details**:

-   **total_attempts**: Total number of quiz attempts
-   **unique_students**: Number of different students who attempted
-   **completion_rate**: Percentage of attempts that were completed
-   **score_distribution**: Breakdown of scores by grade ranges
-   **question_statistics**: Performance data for each question
-   **recent_attempts**: Latest quiz attempts with student info
-   **performance_trends**: Performance data over time periods

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/teacher/quizzes/15/statistics" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /teacher/quizzes/{quiz}/attempts

**Description**: Retrieve all student attempts for a specific quiz with filtering and pagination

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Query Parameters**:

| Parameter    | Type    | Required | Description                | Default | Validation                        |
| ------------ | ------- | -------- | -------------------------- | ------- | --------------------------------- |
| `page`       | integer | No       | Page number for pagination | 1       | Min: 1                            |
| `per_page`   | integer | No       | Items per page             | 15      | Min: 1, Max: 100                  |
| `student_id` | integer | No       | Filter by specific student | -       | Valid student ID                  |
| `status`     | string  | No       | Filter by attempt status   | -       | completed, in_progress, abandoned |
| `min_score`  | integer | No       | Minimum score filter       | -       | 0-100                             |
| `max_score`  | integer | No       | Maximum score filter       | -       | 0-100                             |
| `date_from`  | string  | No       | Start date filter          | -       | YYYY-MM-DD format                 |
| `date_to`    | string  | No       | End date filter            | -       | YYYY-MM-DD format                 |

**Purpose**:

-   Review all student attempts for assessment
-   Monitor student progress and performance
-   Identify students who need additional support
-   Track quiz completion patterns and timing

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 123,
                "quiz_id": 15,
                "student_id": 45,
                "student": {
                    "id": 45,
                    "name": "Maria Garcia",
                    "email": "maria.garcia@example.com"
                },
                "score": 85.0,
                "max_score": 100.0,
                "percentage": 85.0,
                "status": "completed",
                "time_spent": 22,
                "started_at": "2024-01-25T16:08:00.000000Z",
                "completed_at": "2024-01-25T16:30:00.000000Z",
                "attempt_number": 2,
                "answers_count": 5,
                "correct_answers": 4,
                "incorrect_answers": 1
            },
            {
                "id": 124,
                "quiz_id": 15,
                "student_id": 47,
                "student": {
                    "id": 47,
                    "name": "Carlos Rodriguez",
                    "email": "carlos.rodriguez@example.com"
                },
                "score": 92.0,
                "max_score": 100.0,
                "percentage": 92.0,
                "status": "completed",
                "time_spent": 16,
                "started_at": "2024-01-25T15:30:00.000000Z",
                "completed_at": "2024-01-25T15:46:00.000000Z",
                "attempt_number": 1,
                "answers_count": 5,
                "correct_answers": 5,
                "incorrect_answers": 0
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 45,
        "last_page": 3,
        "from": 1,
        "to": 15
    }
}
```

**cURL Example**:

```bash
# Get all attempts
curl -X GET "https://your-domain.com/api/teacher/quizzes/15/attempts" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Get attempts with filtering
curl -X GET "https://your-domain.com/api/teacher/quizzes/15/attempts?status=completed&min_score=80&per_page=10" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /teacher/quizzes/{quiz}/attempts/{attempt}/results

**Description**: Retrieve detailed results for a specific quiz attempt including all answers

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID
-   **Path**: `attempt` (integer, required) - Attempt ID

**Purpose**:

-   Review detailed student responses and performance
-   Analyze specific answer choices and reasoning
-   Provide detailed feedback to students
-   Identify common mistakes and learning gaps

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "attempt_id": 123,
        "quiz": {
            "id": 15,
            "title": "Spanish Verb Conjugation Quiz",
            "total_questions": 5,
            "max_score": 100
        },
        "student": {
            "id": 45,
            "name": "Maria Garcia",
            "email": "maria.garcia@example.com"
        },
        "summary": {
            "score": 85.0,
            "percentage": 85.0,
            "status": "completed",
            "time_spent": 22,
            "started_at": "2024-01-25T16:08:00.000000Z",
            "completed_at": "2024-01-25T16:30:00.000000Z",
            "attempt_number": 2,
            "correct_answers": 4,
            "incorrect_answers": 1,
            "passed": true
        },
        "answers": [
            {
                "question_id": 45,
                "question": {
                    "question_text": "What is the correct conjugation of 'hablar' for 'yo'?",
                    "question_type": "multiple_choice",
                    "points": 20
                },
                "student_answer": {
                    "option_id": 180,
                    "option_text": "hablo",
                    "is_correct": true
                },
                "correct_answer": {
                    "option_id": 180,
                    "option_text": "hablo"
                },
                "is_correct": true,
                "points_earned": 20,
                "time_spent": 8
            },
            {
                "question_id": 46,
                "question": {
                    "question_text": "What is the correct conjugation of 'comer' for 'tú' in present tense?",
                    "question_type": "multiple_choice",
                    "points": 20
                },
                "student_answer": {
                    "option_id": 183,
                    "option_text": "como",
                    "is_correct": false
                },
                "correct_answer": {
                    "option_id": 184,
                    "option_text": "comes"
                },
                "is_correct": false,
                "points_earned": 0,
                "time_spent": 12
            }
        ],
        "feedback": {
            "strengths": [
                "Excellent understanding of first person singular conjugations",
                "Good time management throughout the quiz"
            ],
            "areas_for_improvement": [
                "Review second person singular verb conjugations",
                "Practice more with 'comer' verb family"
            ],
            "recommended_resources": [
                "Spanish Verb Conjugation Practice - Level 2",
                "Interactive Conjugation Exercises"
            ]
        }
    }
}
```

**Error Response - Attempt Not Found (404)**:

```json
{
    "success": false,
    "message": "Quiz attempt not found or access denied"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/teacher/quizzes/15/attempts/123/results" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

### Profile Management

Teacher profile management endpoints provide comprehensive functionality for teachers to manage their personal information, profile images, and notification preferences. These endpoints support image upload and removal, profile updates, and access to teacher-specific information including assigned languages and teaching credentials.

#### GET /teacher/profile

**Description**: Retrieve the authenticated teacher's complete profile information including personal details, assigned languages, and notification preferences

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   Access complete teacher profile information for display and editing
-   Retrieve assigned languages and teaching credentials
-   Get current notification preferences and settings
-   Provide profile data for teacher dashboard and management interfaces

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 15,
        "name": "Maria Rodriguez",
        "email": "maria.rodriguez@example.com",
        "phone": "+1-555-0123",
        "role": "teacher",
        "preferred_language": "es",
        "image_path": "teachers/profiles/15/profile-image.jpg",
        "image_url": "https://your-domain.com/storage/teachers/profiles/15/profile-image.jpg",
        "languages": [
            {
                "id": 2,
                "name": "Spanish",
                "code": "es"
            },
            {
                "id": 1,
                "name": "English",
                "code": "en"
            }
        ],
        "notify_email": true,
        "notify_whatsapp": false,
        "created_at": "2024-01-10T08:30:00.000000Z",
        "updated_at": "2024-01-25T14:20:00.000000Z"
    }
}
```

**Response Details**:

-   **id**: Unique teacher identifier
-   **name**: Teacher's full name
-   **email**: Teacher's email address (unique)
-   **phone**: Teacher's phone number (unique, nullable)
-   **role**: User role (always "teacher" for this endpoint)
-   **preferred_language**: Teacher's preferred interface language (ISO 639-1 code)
-   **image_path**: Relative path to profile image file (nullable)
-   **image_url**: Full URL to profile image (null if no image)
-   **languages**: Array of languages assigned to the teacher for teaching
-   **notify_email**: Email notification preference (boolean)
-   **notify_whatsapp**: WhatsApp notification preference (boolean)
-   **created_at**: Account creation timestamp
-   **updated_at**: Last profile update timestamp

**Error Response - Unauthorized (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**Error Response - Forbidden (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: teacher, admin"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
fetch("https://your-domain.com/api/teacher/profile", {
    method: "GET",
    headers: {
        Authorization: "Bearer " + teacherToken,
        "Content-Type": "application/json",
    },
})
    .then((response) => response.json())
    .then((data) => {
        console.log("Teacher Profile:", data.data);
        console.log("Assigned Languages:", data.data.languages);
        console.log("Profile Image:", data.data.image_url);
    });
```

---

#### PUT /teacher/profile

**Description**: Update the authenticated teacher's profile information including personal details, profile image, and notification preferences

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   Update teacher personal information and contact details
-   Upload or update profile image with automatic processing
-   Modify notification preferences for email and WhatsApp
-   Maintain current profile information for teaching activities

**Request Body Parameters**:

| Field             | Type    | Required | Description                      | Validation                                      |
| ----------------- | ------- | -------- | -------------------------------- | ----------------------------------------------- |
| `name`            | string  | No       | Teacher's full name              | Max: 255 chars                                  |
| `email`           | string  | No       | Email address                    | Valid email, unique, max: 255 chars             |
| `phone`           | string  | No       | Phone number                     | Unique, max: 20 chars                           |
| `image`           | file    | No       | Profile image file               | Image file: jpeg, jpg, png, gif, webp, max: 5MB |
| `notify_email`    | boolean | No       | Email notification preference    | true/false                                      |
| `notify_whatsapp` | boolean | No       | WhatsApp notification preference | true/false                                      |

**Request Examples**:

**JSON Request (No Image Upload)**:
```json
{
    "name": "Maria Elena Rodriguez",
    "email": "maria.elena@example.com",
    "phone": "+1-555-0124",
    "notify_email": true,
    "notify_whatsapp": true
}
```

**Multipart Form Data Request (With Image Upload)**:
```bash
curl -X PUT "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "name=Maria Elena Rodriguez" \
  -F "email=maria.elena@example.com" \
  -F "phone=+1-555-0124" \
  -F "notify_email=true" \
  -F "notify_whatsapp=true" \
  -F "image=@/path/to/profile-image.jpg"
```

**JavaScript FormData Example**:
```javascript
const formData = new FormData();
formData.append('name', 'Maria Elena Rodriguez');
formData.append('email', 'maria.elena@example.com');
formData.append('phone', '+1-555-0124');
formData.append('notify_email', 'true');
formData.append('notify_whatsapp', 'true');
formData.append('image', fileInput.files[0]); // File from input element

fetch('https://your-domain.com/api/teacher/profile', {
    method: 'PUT',
    headers: {
        'Authorization': 'Bearer ' + teacherToken,
        // Don't set Content-Type header - browser will set it with boundary
    },
    body: formData
})
.then(response => response.json())
.then(data => console.log('Profile updated:', data));
```

**Python Requests Example**:
```python
import requests

url = 'https://your-domain.com/api/teacher/profile'
headers = {
    'Authorization': 'Bearer ' + teacher_token
}

# With image file
files = {
    'image': ('profile.jpg', open('/path/to/profile.jpg', 'rb'), 'image/jpeg')
}

data = {
    'name': 'Maria Elena Rodriguez',
    'email': 'maria.elena@example.com',
    'phone': '+1-555-0124',
    'notify_email': 'true',
    'notify_whatsapp': 'true'
}

response = requests.put(url, headers=headers, files=files, data=data)
print(response.json())
```

**Success Response Examples**:

**Profile Updated with New Image (200)**:
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 15,
        "name": "Maria Elena Rodriguez",
        "email": "maria.elena@example.com",
        "phone": "+1-555-0124",
        "role": "teacher",
        "preferred_language": "es",
        "image_path": "teachers/profiles/15/profile-image-20240126-101500.jpg",
        "image_url": "https://your-domain.com/storage/teachers/profiles/15/profile-image-20240126-101500.jpg",
        "previous_image_url": "https://your-domain.com/storage/teachers/profiles/15/profile-image-old.jpg",
        "languages": [
            {
                "id": 2,
                "name": "Spanish",
                "code": "es",
                "native_name": "Español"
            },
            {
                "id": 1,
                "name": "English",
                "code": "en",
                "native_name": "English"
            }
        ],
        "notify_email": true,
        "notify_whatsapp": true,
        "image_metadata": {
            "original_name": "profile-photo.jpg",
            "size": 2048576,
            "mime_type": "image/jpeg",
            "dimensions": {
                "width": 800,
                "height": 800
            },
            "uploaded_at": "2024-01-26T10:15:00.000000Z"
        },
        "created_at": "2024-01-10T08:30:00.000000Z",
        "updated_at": "2024-01-26T10:15:00.000000Z"
    }
}
```

**Profile Updated without Image (200)**:
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 15,
        "name": "Maria Elena Rodriguez",
        "email": "maria.elena@example.com",
        "phone": "+1-555-0124",
        "role": "teacher",
        "preferred_language": "es",
        "image_path": "teachers/profiles/15/existing-image.jpg",
        "image_url": "https://your-domain.com/storage/teachers/profiles/15/existing-image.jpg",
        "languages": [
            {
                "id": 2,
                "name": "Spanish",
                "code": "es"
            },
            {
                "id": 1,
                "name": "English",
                "code": "en"
            }
        ],
        "notify_email": true,
        "notify_whatsapp": true,
        "created_at": "2024-01-10T08:30:00.000000Z",
        "updated_at": "2024-01-26T10:15:00.000000Z"
    }
}
```

**Validation Error Response Examples**:

**Multiple Field Validation Errors (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name may not be greater than 255 characters."],
        "email": [
            "The email has already been taken.",
            "The email must be a valid email address."
        ],
        "phone": [
            "The phone number has already been taken.",
            "The phone format is invalid."
        ],
        "image": [
            "The image must be a file of type: jpeg, jpg, png, gif, webp.",
            "The image may not be greater than 5MB."
        ]
    }
}
```

**Image Validation Errors (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "image": [
            "The image must be a file of type: jpeg, jpg, png, gif, webp.",
            "The image may not be greater than 5MB.",
            "The image dimensions must be at least 100x100 pixels.",
            "The image dimensions may not be greater than 2048x2048 pixels."
        ]
    }
}
```

**File Upload Errors (422)**:
```json
{
    "success": false,
    "message": "File upload failed",
    "errors": {
        "image": [
            "The uploaded file is corrupted.",
            "The file upload was interrupted.",
            "Insufficient storage space for file upload."
        ]
    }
}
```

**Duplicate Information Errors (422)**:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken by another user."],
        "phone": ["The phone number has already been taken by another user."]
    }
}
```

**cURL Examples**:

```bash
# Update profile information only
curl -X PUT "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Maria Elena Rodriguez",
    "email": "maria.elena@example.com",
    "notify_email": true,
    "notify_whatsapp": true
  }'

# Update profile with image upload
curl -X PUT "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "name=Maria Elena Rodriguez" \
  -F "email=maria.elena@example.com" \
  -F "image=@profile-photo.jpg"
```

---

#### DELETE /teacher/profile/image

**Description**: Remove the authenticated teacher's profile image while keeping all other profile information intact

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   Remove current profile image from teacher's profile
-   Clean up associated image files from storage
-   Reset profile to default image state
-   Maintain profile data while removing image reference

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 15,
        "name": "Maria Elena Rodriguez",
        "email": "maria.elena@example.com",
        "phone": "+1-555-0124",
        "role": "teacher",
        "preferred_language": "es",
        "image_path": null,
        "image_url": null,
        "languages": [
            {
                "id": 2,
                "name": "Spanish",
                "code": "es"
            },
            {
                "id": 1,
                "name": "English",
                "code": "en"
            }
        ],
        "notify_email": true,
        "notify_whatsapp": true,
        "created_at": "2024-01-10T08:30:00.000000Z",
        "updated_at": "2024-01-26T11:30:00.000000Z"
    },
    "message": "Profile image removed successfully"
}
```

**Error Response - No Image to Remove (400)**:

```json
{
    "success": false,
    "message": "No profile image to remove"
}
```

**Error Response - Image Removal Failed (500)**:

```json
{
    "success": false,
    "message": "Failed to remove profile image"
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/teacher/profile/image" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /admin/teachers/{teacherId}/image

**Description**: Retrieve a specific teacher's profile image (Admin only endpoint for teacher management)

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `teacherId` (integer, required) - Teacher's user ID

**Purpose**:

-   Admin access to teacher profile images for management interfaces
-   Support teacher profile management from admin dashboard
-   Enable teacher profile verification and moderation
-   Provide image access for administrative reporting

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "teacher_id": 15,
        "teacher_name": "Maria Elena Rodriguez",
        "image_path": "teachers/profiles/15/profile-image.jpg",
        "image_url": "https://your-domain.com/storage/teachers/profiles/15/profile-image.jpg",
        "image_size": 245760,
        "image_type": "image/jpeg",
        "uploaded_at": "2024-01-25T14:20:00.000000Z"
    }
}
```

**Error Response - Teacher Not Found (404)**:

```json
{
    "success": false,
    "message": "Teacher not found"
}
```

**Error Response - No Image Available (404)**:

```json
{
    "success": false,
    "message": "No profile image available for this teacher"
}
```

**Error Response - Unauthorized (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/admin/teachers/15/image" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

### Language Access

Teacher language access endpoints provide functionality for teachers to validate their access to specific languages and retrieve language-specific content. These endpoints ensure teachers can only access and manage content for languages they are authorized to teach.

#### GET /teacher/my-languages

**Description**: Retrieve all languages assigned to the authenticated teacher for teaching purposes

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   Get list of languages the teacher is authorized to teach
-   Validate teacher's language access permissions
-   Support language-specific content filtering in teacher interfaces
-   Enable language-based navigation and content organization

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "teacher_id": 15,
        "teacher_name": "Maria Elena Rodriguez",
        "assigned_languages": [
            {
                "id": 2,
                "name": "Spanish",
                "code": "es",
                "programs_count": 8,
                "active_quizzes_count": 12,
                "students_count": 45,
                "assigned_at": "2024-01-10T08:30:00.000000Z"
            },
            {
                "id": 1,
                "name": "English",
                "code": "en",
                "programs_count": 3,
                "active_quizzes_count": 7,
                "students_count": 28,
                "assigned_at": "2024-01-15T10:15:00.000000Z"
            }
        ],
        "total_languages": 2
    }
}
```

**Response Details**:

-   **teacher_id**: Teacher's unique identifier
-   **teacher_name**: Teacher's full name
-   **assigned_languages**: Array of languages assigned to the teacher
    -   **id**: Language unique identifier
    -   **name**: Language display name
    -   **code**: ISO 639-1 language code
    -   **programs_count**: Number of programs in this language
    -   **active_quizzes_count**: Number of active quizzes created by teacher in this language
    -   **students_count**: Number of students enrolled in teacher's programs for this language
    -   **assigned_at**: When the language was assigned to the teacher
-   **total_languages**: Total count of assigned languages

**Error Response - No Languages Assigned (200)**:

```json
{
    "success": true,
    "data": {
        "teacher_id": 15,
        "teacher_name": "Maria Elena Rodriguez",
        "assigned_languages": [],
        "total_languages": 0
    },
    "message": "No languages currently assigned to this teacher"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/teacher/my-languages" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /teacher/languages/{language}/access

**Description**: Validate teacher's access to a specific language and retrieve language-specific teaching information

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `language` (string, required) - Language code (e.g., 'es', 'en', 'ar') or language ID

**Purpose**:

-   Validate teacher has permission to access specific language content
-   Retrieve language-specific teaching statistics and information
-   Support language-based content filtering and access control
-   Enable language-specific dashboard and management features

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "language": {
            "id": 2,
            "name": "Spanish",
            "code": "es"
        },
        "access_granted": true,
        "teacher_stats": {
            "programs_count": 8,
            "active_programs_count": 6,
            "total_quizzes_count": 12,
            "active_quizzes_count": 10,
            "total_students_count": 45,
            "active_students_count": 38,
            "meetings_count": 15,
            "upcoming_meetings_count": 3
        },
        "recent_activity": {
            "last_quiz_created": "2024-01-24T16:30:00.000000Z",
            "last_meeting_scheduled": "2024-01-25T09:15:00.000000Z",
            "last_student_interaction": "2024-01-25T14:45:00.000000Z"
        },
        "assigned_at": "2024-01-10T08:30:00.000000Z"
    }
}
```

**Error Response - Access Denied (403)**:

```json
{
    "success": false,
    "data": {
        "language": {
            "id": 2,
            "name": "Spanish",
            "code": "es"
        },
        "access_granted": false,
        "message": "Teacher does not have access to this language"
    }
}
```

**Error Response - Language Not Found (404)**:

```json
{
    "success": false,
    "message": "Language not found"
}
```

**cURL Examples**:

```bash
# Check access by language code
curl -X GET "https://your-domain.com/api/teacher/languages/es/access" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Check access by language ID
curl -X GET "https://your-domain.com/api/teacher/languages/2/access" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

### Meeting Management

Teacher meeting management endpoints provide comprehensive CRUD operations for scheduling, managing, and tracking educational meetings. Teachers can create meetings for their assigned programs, update meeting details, manage schedules, and track student participation. The system supports timezone handling, meeting status management, and integration with notification systems.

#### GET /teacher/meetings

**Description**: Retrieve all meetings created by the authenticated teacher with filtering, pagination, and detailed meeting information

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   List all meetings created by the teacher for management and overview
-   Support filtering by program, status, and date ranges
-   Enable meeting schedule management and planning
-   Provide meeting statistics and participation tracking

**Query Parameters**:

| Parameter    | Type    | Required | Description                | Default | Validation            |
| ------------ | ------- | -------- | -------------------------- | ------- | --------------------- |
| `page`       | integer | No       | Page number for pagination | 1       | Min: 1                |
| `per_page`   | integer | No       | Items per page             | 15      | Min: 1, Max: 100      |
| `program_id` | integer | No       | Filter by program ID       | -       | Valid program ID      |
| `status`     | string  | No       | Filter by meeting status   | -       | upcoming, past, today |
| `active`     | boolean | No       | Filter by active status    | -       | true/false            |

**Success Response (200)**:

```json
{
    "success": true,
    "data": [
        {
            "id": 45,
            "title": "Spanish Conversation Practice - Week 3",
            "description": "Interactive conversation practice focusing on daily activities and routines",
            "meeting_link": "https://zoom.us/j/123456789?pwd=abcdef123456",
            "start_time": "2024-01-28T15:00:00.000000Z",
            "timezone": "America/New_York",
            "status": "upcoming",
            "active": true,
            "program": {
                "id": 12,
                "title": "Spanish Intermediate Conversation",
                "language": "Spanish",
                "level": "Intermediate"
            },
            "teacher": {
                "id": 15,
                "name": "Maria Elena Rodriguez"
            },
            "created_at": "2024-01-25T10:30:00.000000Z",
            "updated_at": "2024-01-26T14:20:00.000000Z"
        },
        {
            "id": 44,
            "title": "Grammar Review Session",
            "description": "Review of past tense conjugations and common irregular verbs",
            "meeting_link": "https://zoom.us/j/987654321?pwd=fedcba654321",
            "start_time": "2024-01-26T16:30:00.000000Z",
            "timezone": "America/New_York",
            "status": "past",
            "active": true,
            "program": {
                "id": 12,
                "title": "Spanish Intermediate Conversation",
                "language": "Spanish",
                "level": "Intermediate"
            },
            "teacher": {
                "id": 15,
                "name": "Maria Elena Rodriguez"
            },
            "created_at": "2024-01-22T09:15:00.000000Z",
            "updated_at": "2024-01-22T09:15:00.000000Z"
        }
    ]
}
```

**Response Details**:

-   **id**: Unique meeting identifier
-   **title**: Meeting title/name
-   **description**: Optional meeting description
-   **meeting_link**: URL for joining the meeting (Zoom, Teams, etc.)
-   **start_time**: Meeting start time in UTC (ISO 8601 format)
-   **timezone**: Original timezone for the meeting
-   **status**: Meeting status (upcoming, past, today)
-   **active**: Whether meeting is active and visible to students
-   **program**: Associated program information
-   **teacher**: Meeting creator information
-   **created_at**: Meeting creation timestamp
-   **updated_at**: Last update timestamp

**cURL Examples**:

```bash
# Get all teacher's meetings
curl -X GET "https://your-domain.com/api/teacher/meetings" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Get upcoming meetings for specific program
curl -X GET "https://your-domain.com/api/teacher/meetings?program_id=12&status=upcoming" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### POST /teacher/meetings

**Description**: Create a new meeting with scheduling, program association, and automatic student notifications

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   Schedule new meetings for assigned programs
-   Set meeting details, timing, and access information
-   Automatically notify enrolled students about new meetings
-   Integrate with teacher's program and language assignments

**Request Body Parameters**:

| Field          | Type    | Required | Description                     | Validation                                  |
| -------------- | ------- | -------- | ------------------------------- | ------------------------------------------- |
| `program_id`   | integer | Yes      | Associated program ID           | Must exist and be accessible to teacher     |
| `title`        | string  | Yes      | Meeting title/name              | Max: 255 chars                              |
| `description`  | string  | No       | Meeting description             | Max: 1000 chars                             |
| `meeting_link` | string  | Yes      | Meeting URL (Zoom, Teams, etc.) | Valid URL, max: 500 chars                   |
| `start_time`   | string  | Yes      | Meeting start time              | Format: YYYY-MM-DD HH:MM:SS, must be future |
| `timezone`     | string  | Yes      | Meeting timezone                | Valid timezone identifier                   |
| `active`       | boolean | No       | Meeting active status           | Default: true                               |

**Supported Timezones**:

-   `UTC`, `America/New_York`, `America/Chicago`, `America/Denver`, `America/Los_Angeles`
-   `Europe/London`, `Europe/Paris`, `Europe/Berlin`
-   `Asia/Tokyo`, `Asia/Shanghai`, `Asia/Dubai`, `Asia/Riyadh`
-   `Australia/Sydney`

**Request Example**:

```json
{
    "program_id": 12,
    "title": "Spanish Conversation Practice - Week 4",
    "description": "Advanced conversation practice with focus on subjunctive mood and complex sentence structures",
    "meeting_link": "https://zoom.us/j/555666777?pwd=newmeeting123",
    "start_time": "2024-02-02 15:00:00",
    "timezone": "America/New_York",
    "active": true
}
```

**Success Response (201)**:

```json
{
    "success": true,
    "data": {
        "id": 46,
        "title": "Spanish Conversation Practice - Week 4",
        "description": "Advanced conversation practice with focus on subjunctive mood and complex sentence structures",
        "meeting_link": "https://zoom.us/j/555666777?pwd=newmeeting123",
        "start_time": "2024-02-02T20:00:00.000000Z",
        "timezone": "America/New_York",
        "status": "upcoming",
        "active": true,
        "program": {
            "id": 12,
            "title": "Spanish Intermediate Conversation",
            "language": "Spanish",
            "level": "Intermediate"
        },
        "teacher": {
            "id": 15,
            "name": "Maria Elena Rodriguez"
        },
        "created_at": "2024-01-26T16:45:00.000000Z",
        "updated_at": "2024-01-26T16:45:00.000000Z"
    },
    "message": "Meeting created successfully"
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "program_id": ["The selected program id is invalid."],
        "title": ["The title field is required."],
        "meeting_link": ["The meeting link must be a valid URL."],
        "start_time": ["The start time must be a date after now."],
        "timezone": ["The selected timezone is invalid."]
    }
}
```

**Error Response - Language Access Denied (403)**:

```json
{
    "success": false,
    "error": {
        "code": "LANGUAGE_ACCESS_DENIED",
        "message": "Teacher does not have access to this program's language"
    }
}
```

**Error Response - Meeting Time Validation (400)**:

```json
{
    "success": false,
    "error": {
        "code": "INVALID_MEETING_TIME",
        "message": "Meeting must be scheduled at least 30 minutes in advance"
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/teacher/meetings" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "program_id": 12,
    "title": "Spanish Conversation Practice - Week 4",
    "description": "Advanced conversation practice with focus on subjunctive mood",
    "meeting_link": "https://zoom.us/j/555666777?pwd=newmeeting123",
    "start_time": "2024-02-02 15:00:00",
    "timezone": "America/New_York",
    "active": true
  }'
```

---

#### GET /teacher/meetings/{meeting}

**Description**: Retrieve detailed information for a specific meeting including program details and access validation

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `meeting` (integer, required) - Meeting ID

**Purpose**:

-   Get complete meeting details for editing and management
-   Validate teacher access to specific meetings
-   Provide meeting information for teacher interfaces
-   Support meeting detail views and modification forms

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 45,
        "title": "Spanish Conversation Practice - Week 3",
        "description": "Interactive conversation practice focusing on daily activities and routines",
        "meeting_link": "https://zoom.us/j/123456789?pwd=abcdef123456",
        "start_time": "2024-01-28T20:00:00.000000Z",
        "timezone": "America/New_York",
        "status": "upcoming",
        "active": true,
        "program": {
            "id": 12,
            "title": "Spanish Intermediate Conversation",
            "language": "Spanish",
            "level": "Intermediate"
        },
        "teacher": {
            "id": 15,
            "name": "Maria Elena Rodriguez"
        },
        "created_at": "2024-01-25T10:30:00.000000Z",
        "updated_at": "2024-01-26T14:20:00.000000Z"
    }
}
```

**Error Response - Meeting Not Found (404)**:

```json
{
    "success": false,
    "message": "Meeting not found"
}
```

**Error Response - Access Denied (403)**:

```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "You do not have access to this meeting"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/teacher/meetings/45" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### PUT /teacher/meetings/{meeting}

**Description**: Update an existing meeting's details, schedule, or configuration with automatic student notifications

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `meeting` (integer, required) - Meeting ID

**Purpose**:

-   Modify meeting details, timing, and configuration
-   Update meeting links and access information
-   Reschedule meetings with proper validation
-   Notify enrolled students about meeting changes

**Request Body Parameters**:

| Field          | Type    | Required | Description           | Validation                                  |
| -------------- | ------- | -------- | --------------------- | ------------------------------------------- |
| `program_id`   | integer | No       | Associated program ID | Must exist and be accessible to teacher     |
| `title`        | string  | No       | Meeting title/name    | Max: 255 chars                              |
| `description`  | string  | No       | Meeting description   | Max: 1000 chars                             |
| `meeting_link` | string  | No       | Meeting URL           | Valid URL, max: 500 chars                   |
| `start_time`   | string  | No       | Meeting start time    | Format: YYYY-MM-DD HH:MM:SS, must be future |
| `timezone`     | string  | No       | Meeting timezone      | Valid timezone identifier                   |
| `active`       | boolean | No       | Meeting active status | true/false                                  |

**Request Example**:

```json
{
    "title": "Spanish Conversation Practice - Week 3 (Updated)",
    "description": "Interactive conversation practice focusing on daily activities, routines, and weekend plans",
    "start_time": "2024-01-28 16:00:00",
    "timezone": "America/New_York",
    "active": true
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 45,
        "title": "Spanish Conversation Practice - Week 3 (Updated)",
        "description": "Interactive conversation practice focusing on daily activities, routines, and weekend plans",
        "meeting_link": "https://zoom.us/j/123456789?pwd=abcdef123456",
        "start_time": "2024-01-28T21:00:00.000000Z",
        "timezone": "America/New_York",
        "status": "upcoming",
        "active": true,
        "program": {
            "id": 12,
            "title": "Spanish Intermediate Conversation",
            "language": "Spanish",
            "level": "Intermediate"
        },
        "teacher": {
            "id": 15,
            "name": "Maria Elena Rodriguez"
        },
        "created_at": "2024-01-25T10:30:00.000000Z",
        "updated_at": "2024-01-26T17:15:00.000000Z"
    },
    "message": "Meeting updated successfully"
}
```

**Error Response - Unauthorized Update (403)**:

```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "You can only update your own meetings"
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "start_time": ["The start time must be a date after now."],
            "meeting_link": ["The meeting link must be a valid URL."]
        }
    }
}
```

**cURL Example**:

```bash
curl -X PUT "https://your-domain.com/api/teacher/meetings/45" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Spanish Conversation Practice - Week 3 (Updated)",
    "description": "Interactive conversation practice focusing on daily activities, routines, and weekend plans",
    "start_time": "2024-01-28 16:00:00",
    "timezone": "America/New_York"
  }'
```

---

#### DELETE /teacher/meetings/{meeting}

**Description**: Delete a meeting and send cancellation notifications to enrolled students

**Authentication**: Required (Teacher or Admin role)

**Parameters**:

-   **Path**: `meeting` (integer, required) - Meeting ID

**Purpose**:

-   Remove meetings that are no longer needed
-   Cancel scheduled meetings with proper student notification
-   Clean up meeting schedules and prevent student confusion
-   Maintain meeting history while removing future access

**Authorization Rules**:

-   Teachers can only delete meetings they created
-   Admins can delete any meeting
-   Meeting deletion triggers automatic cancellation notifications

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Meeting deleted successfully"
}
```

**Error Response - Unauthorized Deletion (403)**:

```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "You can only delete your own meetings"
    }
}
```

**Error Response - Meeting Not Found (404)**:

```json
{
    "success": false,
    "message": "Meeting not found"
}
```

**Error Response - Deletion Failed (500)**:

```json
{
    "success": false,
    "error": {
        "code": "MEETING_DELETE_ERROR",
        "message": "Failed to delete meeting"
    }
}
```

**cURL Example**:

```bash
curl -X DELETE "https://your-domain.com/api/teacher/meetings/45" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /teacher/meetings/upcoming

**Description**: Retrieve all upcoming meetings for the authenticated teacher with program filtering

**Authentication**: Required (Teacher or Admin role)

**Purpose**:

-   Get teacher's upcoming meeting schedule for planning
-   Support teacher dashboard with next meetings display
-   Enable meeting preparation and schedule management
-   Provide quick access to imminent teaching commitments

**Query Parameters**:

| Parameter    | Type    | Required | Description          | Default | Validation       |
| ------------ | ------- | -------- | -------------------- | ------- | ---------------- |
| `program_id` | integer | No       | Filter by program ID | -       | Valid program ID |

**Success Response (200)**:

```json
{
    "success": true,
    "data": [
        {
            "id": 46,
            "title": "Spanish Conversation Practice - Week 4",
            "description": "Advanced conversation practice with focus on subjunctive mood",
            "meeting_link": "https://zoom.us/j/555666777?pwd=newmeeting123",
            "start_time": "2024-02-02T20:00:00.000000Z",
            "timezone": "America/New_York",
            "status": "upcoming",
            "program": {
                "id": 12,
                "title": "Spanish Intermediate Conversation",
                "language": "Spanish",
                "level": "Intermediate"
            },
            "teacher": {
                "id": 15,
                "name": "Maria Elena Rodriguez"
            }
        },
        {
            "id": 47,
            "title": "English Grammar Workshop",
            "description": "Focus on conditional sentences and modal verbs",
            "meeting_link": "https://zoom.us/j/888999000?pwd=englishworkshop",
            "start_time": "2024-02-05T18:00:00.000000Z",
            "timezone": "America/New_York",
            "status": "upcoming",
            "program": {
                "id": 8,
                "title": "English Advanced Grammar",
                "language": "English",
                "level": "Advanced"
            },
            "teacher": {
                "id": 15,
                "name": "Maria Elena Rodriguez"
            }
        }
    ]
}
```

**cURL Examples**:

```bash
# Get all upcoming meetings
curl -X GET "https://your-domain.com/api/teacher/meetings/upcoming" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Get upcoming meetings for specific program
curl -X GET "https://your-domain.com/api/teacher/meetings/upcoming?program_id=12" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

---

## Stud

ent Operations

The Student Operations section provides comprehensive functionality for students to interact with the learning management system. Students can access quizzes from their enrolled programs, submit quiz attempts, view their attempt history, and retrieve detailed results. All student endpoints require authentication and automatically filter content based on the student's approved program enrollments.

### Student Authentication & Access Control

All student endpoints require:

-   **Authentication**: Valid Bearer token from student login
-   **Role Verification**: User must have `student` role
-   **Enrollment Validation**: Access is automatically filtered to approved program enrollments only
-   **Resource Ownership**: Students can only access their own attempts and results

### Student Data Access Patterns

**Enrollment-Based Filtering**: All quiz access is automatically filtered based on:

-   Student must be enrolled in the quiz's program
-   Enrollment must have `access_granted_at` timestamp (approved enrollment)
-   Only active quizzes are accessible to students

**Attempt Ownership**: Students can only:

-   View their own quiz attempts and results
-   Submit attempts for quizzes they have access to
-   Access detailed results for their own attempts only

---

### Quiz Taking

The Quiz Taking system allows students to discover available quizzes, view quiz details, submit attempts, and track their learning progress. All quiz access is automatically filtered based on the student's approved program enrollments.

#### Student Quiz Data Schema

**Quiz Object (Student View)**:

| Field             | Type         | Description                    | Notes                       |
| ----------------- | ------------ | ------------------------------ | --------------------------- |
| `id`              | integer      | Unique quiz identifier         | Primary key                 |
| `title`           | string       | Quiz title                     | Display name                |
| `description`     | string\|null | Quiz description               | Optional details            |
| `type`            | string       | Quiz type                      | "inline" or "file"          |
| `active`          | boolean      | Quiz availability status       | Only active quizzes shown   |
| `program`         | object       | Associated program information | Includes language and level |
| `total_questions` | integer      | Number of questions            | For inline quizzes          |
| `quiz_questions`  | array        | Quiz questions (inline only)   | Excludes correct answers    |
| `created_at`      | datetime     | Quiz creation timestamp        | ISO 8601 format             |
| `updated_at`      | datetime     | Last modification timestamp    | ISO 8601 format             |

**Quiz Attempt Object**:

| Field              | Type     | Description                 | Notes                          |
| ------------------ | -------- | --------------------------- | ------------------------------ |
| `id`               | integer  | Unique attempt identifier   | Primary key                    |
| `quiz_id`          | integer  | Associated quiz ID          | Foreign key                    |
| `student_id`       | integer  | Student user ID             | Foreign key                    |
| `answers`          | array    | Student's submitted answers | Question ID -> Answer mapping  |
| `score`            | integer  | Percentage score achieved   | 0-100                          |
| `percentage_score` | float    | Calculated percentage       | Same as score field            |
| `passed`           | boolean  | Whether attempt passed      | Based on quiz pass_score       |
| `submitted_at`     | datetime | Submission timestamp        | ISO 8601 format                |
| `quiz`             | object   | Quiz information            | Included in detailed responses |

---

#### GET /student/quizzes

**Description**: Get all available quizzes for the authenticated student based on approved program enrollments

**Authentication**: Required (Student role)

**Purpose**:

-   Display available quizzes to students in their learning dashboard
-   Filter quizzes based on approved program enrollments automatically
-   Provide quiz overview information for student decision-making
-   Support quiz discovery and learning path navigation

**Query Parameters**:

| Parameter  | Type    | Required | Description                | Default | Validation      |
| ---------- | ------- | -------- | -------------------------- | ------- | --------------- |
| `page`     | integer | No       | Page number for pagination | 1       | Min: 1          |
| `per_page` | integer | No       | Items per page             | 15      | Min: 1, Max: 50 |

**Access Control**:

-   Automatically filters quizzes to only show those from programs where the student has approved enrollment
-   Only shows active quizzes (`active = true`)
-   Includes program, language, and level information for context

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 15,
                "title": "Spanish Beginner Vocabulary Quiz",
                "description": "Test your knowledge of basic Spanish vocabulary including greetings, numbers, and common phrases",
                "type": "inline",
                "active": true,
                "program": {
                    "id": 8,
                    "title": "Spanish for Beginners",
                    "language": {
                        "id": 2,
                        "name": "Spanish",
                        "code": "es"
                    },
                    "level": {
                        "id": 1,
                        "name": "Beginner",
                        "description": "Basic level for new learners"
                    }
                },
                "total_questions": 20,
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-20T14:15:00.000000Z"
            },
            {
                "id": 23,
                "title": "English Grammar - Present Tense",
                "description": "Practice quiz covering present simple and present continuous tenses",
                "type": "inline",
                "active": true,
                "program": {
                    "id": 12,
                    "title": "English Grammar Fundamentals",
                    "language": {
                        "id": 1,
                        "name": "English",
                        "code": "en"
                    },
                    "level": {
                        "id": 2,
                        "name": "Intermediate",
                        "description": "For learners with basic knowledge"
                    }
                },
                "total_questions": 15,
                "created_at": "2024-01-18T09:45:00.000000Z",
                "updated_at": "2024-01-22T16:30:00.000000Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 8,
        "last_page": 1,
        "from": 1,
        "to": 8
    }
}
```

**Empty Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [],
        "current_page": 1,
        "per_page": 15,
        "total": 0,
        "last_page": 1,
        "from": null,
        "to": null
    }
}
```

**Error Response - Authentication Required (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**Error Response - Insufficient Role (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: student"
}
```

**cURL Examples**:

```bash
# Get all available quizzes
curl -X GET "https://your-domain.com/api/student/quizzes" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Get quizzes with pagination
curl -X GET "https://your-domain.com/api/student/quizzes?page=1&per_page=10" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
// Fetch available quizzes for student dashboard
fetch("https://your-domain.com/api/student/quizzes", {
    method: "GET",
    headers: {
        Authorization: `Bearer ${studentToken}`,
        "Content-Type": "application/json",
    },
})
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            console.log("Available quizzes:", data.data.data);
            console.log("Total quizzes:", data.data.total);
        }
    })
    .catch((error) => console.error("Error fetching quizzes:", error));
```

---

#### GET /student/quizzes/{quiz}

**Description**: Get detailed information about a specific quiz for taking, including questions for inline quizzes

**Authentication**: Required (Student role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   Provide complete quiz information for student quiz-taking interface
-   Include quiz questions for inline quizzes (excluding correct answers)
-   Verify student access to the quiz before displaying content
-   Support quiz preparation and question preview

**Access Control**:

-   Verifies student has approved enrollment in the quiz's program
-   Returns 403 error if student doesn't have access to the quiz
-   Automatically excludes correct answers from quiz questions
-   Only shows active quizzes

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 15,
        "title": "Spanish Beginner Vocabulary Quiz",
        "description": "Test your knowledge of basic Spanish vocabulary including greetings, numbers, and common phrases",
        "type": "inline",
        "active": true,
        "program": {
            "id": 8,
            "title": "Spanish for Beginners",
            "language": {
                "id": 2,
                "name": "Spanish",
                "code": "es"
            },
            "level": {
                "id": 1,
                "name": "Beginner",
                "description": "Basic level for new learners"
            }
        },
        "quiz_questions": [
            {
                "id": 45,
                "quiz_id": 15,
                "question": "What is the Spanish word for 'hello'?",
                "choices": ["Hola", "Adiós", "Gracias", "Por favor"],
                "order": 1
            },
            {
                "id": 46,
                "quiz_id": 15,
                "question": "How do you say 'thank you' in Spanish?",
                "choices": ["De nada", "Gracias", "Perdón", "Disculpe"],
                "order": 2
            }
        ],
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-20T14:15:00.000000Z"
    }
}
```

**Error Response - Access Denied (403)**:

```json
{
    "success": false,
    "message": "You do not have access to this quiz."
}
```

**Error Response - Quiz Not Found (404)**:

```json
{
    "success": false,
    "message": "Quiz not found"
}
```

**Error Response - Authentication Required (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/15" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
// Load quiz for taking
function loadQuizForTaking(quizId) {
    fetch(`https://your-domain.com/api/student/quizzes/${quizId}`, {
        method: "GET",
        headers: {
            Authorization: `Bearer ${studentToken}`,
            "Content-Type": "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log("Quiz loaded:", data.data.title);
                console.log("Questions:", data.data.quiz_questions.length);
                // Render quiz interface
                renderQuizInterface(data.data);
            } else {
                console.error("Access denied:", data.message);
            }
        })
        .catch((error) => console.error("Error loading quiz:", error));
}
```

---

#### POST /student/quizzes/{quiz}/attempt

**Description**: Submit a quiz attempt with answers and receive immediate scoring results

**Authentication**: Required (Student role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Request Body Parameters**:

| Field       | Type   | Required | Description                         | Validation                 |
| ----------- | ------ | -------- | ----------------------------------- | -------------------------- |
| `answers`   | array  | Yes      | Student's answers to quiz questions | Must be array of strings   |
| `answers.*` | string | Yes      | Answer for each question            | Required for each question |

**Purpose**:

-   Submit student's quiz attempt for scoring and record-keeping
-   Provide immediate feedback with score and pass/fail status
-   Track student progress and learning outcomes
-   Support learning analytics and progress monitoring

**Access Control**:

-   Verifies student has approved enrollment in the quiz's program
-   Validates all required questions are answered
-   Prevents duplicate submissions (handled by business logic)
-   Records attempt with timestamp for tracking

**Request Body Examples**:

**Basic Quiz Attempt** (5 questions):

```json
{
    "answers": [
        "Hola",
        "Gracias", 
        "Buenos días",
        "De nada",
        "Por favor"
    ]
}
```

**Multiple Choice Quiz Attempt** (10 questions):

```json
{
    "answers": [
        "A",
        "C",
        "B", 
        "D",
        "A",
        "B",
        "C",
        "A",
        "D",
        "B"
    ]
}
```

**Mixed Format Quiz Attempt** (text and multiple choice):

```json
{
    "answers": [
        "Hello",
        "B",
        "Thank you",
        "A", 
        "Good morning",
        "C",
        "Excuse me",
        "D"
    ]
}
```

**Success Response Examples**:

**Passing Score (201)**:

```json
{
    "success": true,
    "message": "Quiz attempt submitted successfully. Congratulations, you passed!",
    "data": {
        "attempt_id": 127,
        "quiz_id": 15,
        "student_id": 45,
        "score": 17,
        "total_questions": 20,
        "percentage": 85.0,
        "passed": true,
        "pass_threshold": 70.0,
        "submitted_at": "2024-01-25T14:30:00.000000Z",
        "quiz": {
            "id": 15,
            "title": "Spanish Beginner Vocabulary Quiz",
            "type": "inline",
            "pass_score": 70
        },
        "performance": {
            "correct_answers": 17,
            "incorrect_answers": 3,
            "accuracy_rate": 85.0,
            "improvement_from_last": 15.0
        },
        "next_steps": {
            "can_retake": false,
            "next_quiz_available": true,
            "recommended_study_areas": [
                "Greetings and farewells",
                "Common expressions"
            ]
        }
    }
}
```

**Failing Score (201)**:

```json
{
    "success": true,
    "message": "Quiz attempt submitted successfully. Keep practicing!",
    "data": {
        "attempt_id": 128,
        "quiz_id": 15,
        "student_id": 45,
        "score": 12,
        "total_questions": 20,
        "percentage": 60.0,
        "passed": false,
        "pass_threshold": 70.0,
        "submitted_at": "2024-01-25T15:45:00.000000Z",
        "quiz": {
            "id": 15,
            "title": "Spanish Beginner Vocabulary Quiz",
            "type": "inline",
            "pass_score": 70
        },
        "performance": {
            "correct_answers": 12,
            "incorrect_answers": 8,
            "accuracy_rate": 60.0,
            "improvement_from_last": -25.0
        },
        "next_steps": {
            "can_retake": true,
            "retake_available_at": "2024-01-26T15:45:00.000000Z",
            "recommended_study_areas": [
                "Basic vocabulary",
                "Common phrases",
                "Numbers and colors"
            ],
            "study_resources": [
                {
                    "type": "lesson",
                    "title": "Spanish Vocabulary Basics",
                    "url": "/lessons/spanish-vocabulary-basics"
                }
            ]
        }
    }
}
```

**Perfect Score (201)**:

```json
{
    "success": true,
    "message": "Perfect score! Excellent work!",
    "data": {
        "attempt_id": 129,
        "quiz_id": 15,
        "student_id": 45,
        "score": 20,
        "total_questions": 20,
        "percentage": 100.0,
        "passed": true,
        "pass_threshold": 70.0,
        "submitted_at": "2024-01-25T16:30:00.000000Z",
        "quiz": {
            "id": 15,
            "title": "Spanish Beginner Vocabulary Quiz",
            "type": "inline",
            "pass_score": 70
        },
        "performance": {
            "correct_answers": 20,
            "incorrect_answers": 0,
            "accuracy_rate": 100.0,
            "improvement_from_last": 40.0,
            "achievement": "Perfect Score"
        },
        "next_steps": {
            "can_retake": false,
            "next_quiz_available": true,
            "advancement_eligible": true,
            "next_level_quiz": {
                "id": 18,
                "title": "Spanish Intermediate Grammar",
                "level": "Intermediate"
            }
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "answers": ["Quiz answers are required."],
        "answers.0": ["All questions must be answered."]
    }
}
```

**Error Response - Access Denied (403)**:

```json
{
    "success": false,
    "message": "You do not have access to this quiz."
}
```

**Error Response - Quiz Not Found (404)**:

```json
{
    "success": false,
    "message": "Quiz not found"
}
```

**Error Response - Submission Failed (500)**:

```json
{
    "success": false,
    "message": "Failed to submit quiz attempt.",
    "error": "Internal server error details"
}
```

**cURL Examples**:

```bash
# Basic quiz attempt submission
curl -X POST "https://your-domain.com/api/student/quizzes/15/attempt" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": [
      "Hola",
      "Gracias", 
      "Buenos días",
      "De nada",
      "Por favor"
    ]
  }'

# Multiple choice quiz attempt
curl -X POST "https://your-domain.com/api/student/quizzes/22/attempt" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": [
      "A", "C", "B", "D", "A",
      "B", "C", "A", "D", "B"
    ]
  }'

# Mixed format quiz (text and multiple choice)
curl -X POST "https://your-domain.com/api/student/quizzes/8/attempt" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": [
      "Hello",
      "B",
      "Thank you", 
      "A",
      "Good morning",
      "C"
    ]
  }'
```

**JavaScript Integration Examples**:

```javascript
// Quiz attempt submission class
class QuizAttemptManager {
    constructor(apiBaseUrl, authToken) {
        this.apiBaseUrl = apiBaseUrl;
        this.authToken = authToken;
    }

    async submitAttempt(quizId, answers) {
        try {
            const response = await fetch(
                `${this.apiBaseUrl}/student/quizzes/${quizId}/attempt`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.authToken}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ answers })
                }
            );

            const data = await response.json();
            
            if (data.success) {
                this.handleSuccessfulSubmission(data.data);
            } else {
                this.handleSubmissionError(data);
            }
            
            return data;
        } catch (error) {
            console.error('Quiz submission failed:', error);
            throw error;
        }
    }

    handleSuccessfulSubmission(attemptData) {
        const { percentage, passed, performance, next_steps } = attemptData;
        
        console.log(`Quiz completed! Score: ${percentage}%`);
        
        if (passed) {
            console.log('🎉 Congratulations! You passed!');
            
            if (performance.achievement) {
                console.log(`🏆 Achievement: ${performance.achievement}`);
            }
            
            if (next_steps.advancement_eligible) {
                console.log('🚀 You\'re eligible for the next level!');
            }
        } else {
            console.log('📚 Keep studying! You can do better next time.');
            
            if (next_steps.can_retake) {
                console.log(`🔄 Retake available at: ${next_steps.retake_available_at}`);
            }
            
            if (next_steps.recommended_study_areas) {
                console.log('📖 Recommended study areas:');
                next_steps.recommended_study_areas.forEach(area => {
                    console.log(`  - ${area}`);
                });
            }
        }
    }

    handleSubmissionError(errorData) {
        console.error('Submission failed:', errorData.message);
        
        if (errorData.errors) {
            console.error('Validation errors:', errorData.errors);
        }
    }

    async validateAnswers(answers, expectedCount) {
        if (!Array.isArray(answers)) {
            throw new Error('Answers must be an array');
        }
        
        if (answers.length !== expectedCount) {
            throw new Error(`Expected ${expectedCount} answers, got ${answers.length}`);
        }
        
        // Check for empty answers
        const emptyAnswers = answers.findIndex(answer => !answer || answer.trim() === '');
        if (emptyAnswers !== -1) {
            throw new Error(`Answer ${emptyAnswers + 1} is empty`);
        }
        
        return true;
    }
}

// Usage examples
const quizManager = new QuizAttemptManager('https://your-domain.com/api', studentToken);

// Submit a basic quiz attempt
async function takeQuiz() {
    const quizId = 15;
    const answers = [
        'Hola',
        'Gracias',
        'Buenos días', 
        'De nada',
        'Por favor'
    ];
    
    try {
        // Validate answers before submission
        await quizManager.validateAnswers(answers, 5);
        
        // Submit the attempt
        const result = await quizManager.submitAttempt(quizId, answers);
        
        // Handle the result
        if (result.success) {
            displayQuizResults(result.data);
        }
    } catch (error) {
        console.error('Quiz submission error:', error.message);
        displayErrorMessage(error.message);
    }
}

// Interactive quiz taking function
async function interactiveQuizTaking(quizId, questions) {
    const answers = [];
    
    // Collect answers from user interface
    for (let i = 0; i < questions.length; i++) {
        const question = questions[i];
        console.log(`Question ${i + 1}: ${question.text}`);
        
        if (question.type === 'multiple_choice') {
            console.log('Options:', question.choices);
        }
        
        // In a real app, this would be user input
        const answer = await getUserAnswer(question);
        answers.push(answer);
    }
    
    // Submit all answers
    return quizManager.submitAttempt(quizId, answers);
}

// Progress tracking after quiz submission
function displayQuizResults(attemptData) {
    const resultsContainer = document.getElementById('quiz-results');
    
    resultsContainer.innerHTML = `
        <div class="quiz-results">
            <h3>Quiz Results</h3>
            <div class="score ${attemptData.passed ? 'passed' : 'failed'}">
                <span class="percentage">${attemptData.percentage}%</span>
                <span class="status">${attemptData.passed ? 'PASSED' : 'FAILED'}</span>
            </div>
            
            <div class="performance">
                <p>Correct: ${attemptData.performance.correct_answers}/${attemptData.total_questions}</p>
                <p>Accuracy: ${attemptData.performance.accuracy_rate}%</p>
                ${attemptData.performance.improvement_from_last !== undefined ? 
                    `<p>Improvement: ${attemptData.performance.improvement_from_last > 0 ? '+' : ''}${attemptData.performance.improvement_from_last}%</p>` : ''
                }
            </div>
            
            ${attemptData.next_steps.recommended_study_areas ? `
                <div class="recommendations">
                    <h4>Study Recommendations:</h4>
                    <ul>
                        ${attemptData.next_steps.recommended_study_areas.map(area => 
                            `<li>${area}</li>`
                        ).join('')}
                    </ul>
                </div>
            ` : ''}
            
            ${attemptData.next_steps.can_retake ? `
                <button onclick="retakeQuiz(${attemptData.quiz_id})" class="retake-btn">
                    Retake Quiz
                </button>
            ` : ''}
        </div>
    `;
}
```

**Python Integration Examples**:

```python
import requests
from typing import List, Dict, Any
from datetime import datetime

class QuizAttemptManager:
    def __init__(self, api_base_url: str, auth_token: str):
        self.api_base_url = api_base_url
        self.auth_token = auth_token
        self.headers = {
            'Authorization': f'Bearer {auth_token}',
            'Content-Type': 'application/json'
        }

    def submit_attempt(self, quiz_id: int, answers: List[str]) -> Dict[str, Any]:
        """Submit a quiz attempt with answers"""
        url = f"{self.api_base_url}/student/quizzes/{quiz_id}/attempt"
        payload = {'answers': answers}
        
        try:
            response = requests.post(url, json=payload, headers=self.headers)
            data = response.json()
            
            if data.get('success'):
                self._handle_successful_submission(data['data'])
            else:
                self._handle_submission_error(data)
            
            return data
        except requests.exceptions.RequestException as e:
            print(f"Quiz submission failed: {e}")
            raise

    def _handle_successful_submission(self, attempt_data: Dict[str, Any]):
        """Handle successful quiz submission"""
        percentage = attempt_data['percentage']
        passed = attempt_data['passed']
        performance = attempt_data.get('performance', {})
        next_steps = attempt_data.get('next_steps', {})
        
        print(f"Quiz completed! Score: {percentage}%")
        
        if passed:
            print("🎉 Congratulations! You passed!")
            
            if performance.get('achievement'):
                print(f"🏆 Achievement: {performance['achievement']}")
            
            if next_steps.get('advancement_eligible'):
                print("🚀 You're eligible for the next level!")
        else:
            print("📚 Keep studying! You can do better next time.")
            
            if next_steps.get('can_retake'):
                retake_time = next_steps.get('retake_available_at')
                print(f"🔄 Retake available at: {retake_time}")
            
            study_areas = next_steps.get('recommended_study_areas', [])
            if study_areas:
                print("📖 Recommended study areas:")
                for area in study_areas:
                    print(f"  - {area}")

    def _handle_submission_error(self, error_data: Dict[str, Any]):
        """Handle submission errors"""
        print(f"Submission failed: {error_data.get('message', 'Unknown error')}")
        
        if 'errors' in error_data:
            print("Validation errors:")
            for field, messages in error_data['errors'].items():
                for message in messages:
                    print(f"  - {field}: {message}")

    def validate_answers(self, answers: List[str], expected_count: int) -> bool:
        """Validate answers before submission"""
        if not isinstance(answers, list):
            raise ValueError("Answers must be a list")
        
        if len(answers) != expected_count:
            raise ValueError(f"Expected {expected_count} answers, got {len(answers)}")
        
        # Check for empty answers
        for i, answer in enumerate(answers):
            if not answer or not answer.strip():
                raise ValueError(f"Answer {i + 1} is empty")
        
        return True

    def submit_multiple_choice_quiz(self, quiz_id: int, selected_options: List[str]) -> Dict[str, Any]:
        """Submit a multiple choice quiz with selected options"""
        return self.submit_attempt(quiz_id, selected_options)

    def submit_text_quiz(self, quiz_id: int, text_answers: List[str]) -> Dict[str, Any]:
        """Submit a text-based quiz with written answers"""
        # Clean and validate text answers
        cleaned_answers = [answer.strip() for answer in text_answers]
        return self.submit_attempt(quiz_id, cleaned_answers)

---

## Practical Usage Examples

This section provides comprehensive practical examples, complete user journey workflows, and real-world integration patterns for the Learn Academy API. Each example includes cURL commands, JavaScript implementations, and complete workflow demonstrations.

### Authentication Workflows

#### Complete User Registration and Login Flow

**Step 1: User Registration**

```bash
# Register a new student
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sarah Johnson",
    "email": "sarah.johnson@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "phone": "+1-555-123-4567",
    "role": "student",
    "preferred_language": "en",
    "notify_email": true,
    "notify_whatsapp": false
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 156,
      "name": "Sarah Johnson",
      "email": "sarah.johnson@example.com",
      "role": "student",
      "preferred_language": "en"
    },
    "token": "1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz"
  }
}
```

**Step 2: Login (Alternative to Registration)**

```bash
# Login existing user
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "sarah.johnson@example.com",
    "password": "SecurePass123!"
  }'
```

**Step 3: Access Protected Resources**

```bash
# Get user profile
curl -X GET "https://your-domain.com/api/auth/profile" \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz" \
  -H "Content-Type: application/json"
```

**Step 4: Logout**

```bash
# Logout and invalidate token
curl -X POST "https://your-domain.com/api/auth/logout" \
  -H "Authorization: Bearer 1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz" \
  -H "Content-Type: application/json"
```

#### JavaScript Authentication Manager

```javascript
class AuthManager {
  constructor(apiBaseUrl) {
    this.apiBaseUrl = apiBaseUrl;
    this.token = localStorage.getItem('auth_token');
  }

  async register(userData) {
    try {
      const response = await fetch(`${this.apiBaseUrl}/auth/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
      });

      const data = await response.json();
      
      if (data.success) {
        this.token = data.data.token;
        localStorage.setItem('auth_token', this.token);
        return { success: true, user: data.data.user };
      } else {
        return { success: false, errors: data.errors };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  async login(email, password) {
    try {
      const response = await fetch(`${this.apiBaseUrl}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();
      
      if (data.success) {
        this.token = data.data.token;
        localStorage.setItem('auth_token', this.token);
        return { success: true, user: data.data.user };
      } else {
        return { success: false, message: data.message };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  async logout() {
    try {
      await fetch(`${this.apiBaseUrl}/auth/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        }
      });
    } finally {
      this.token = null;
      localStorage.removeItem('auth_token');
    }
  }

  getAuthHeaders() {
    return {
      'Authorization': `Bearer ${this.token}`,
      'Content-Type': 'application/json'
    };
  }

  isAuthenticated() {
    return !!this.token;
  }
}

// Usage example
const auth = new AuthManager('https://your-domain.com/api');

// Register new user
async function registerUser() {
  const result = await auth.register({
    name: "Sarah Johnson",
    email: "sarah.johnson@example.com",
    password: "SecurePass123!",
    password_confirmation: "SecurePass123!",
    phone: "+1-555-123-4567",
    role: "student",
    preferred_language: "en",
    notify_email: true,
    notify_whatsapp: false
  });

  if (result.success) {
    console.log('Registration successful:', result.user);
  } else {
    console.error('Registration failed:', result.errors);
  }
}
```

### Complete Student Learning Journey

#### End-to-End Student Experience Workflow

**Step 1: Student Registration and Login**

```bash
# Register as student
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Alex Chen",
    "email": "alex.chen@example.com",
    "password": "StudentPass123!",
    "password_confirmation": "StudentPass123!",
    "role": "student",
    "preferred_language": "en"
  }'
```

**Step 2: View Available Programs (After Enrollment Approval)**

```bash
# Get approved programs
curl -X GET "https://your-domain.com/api/student/programs" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Check Enrollment Status**

```bash
# View all enrollments (pending and approved)
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**Step 4: Discover Available Quizzes**

```bash
# Get quizzes for enrolled programs
curl -X GET "https://your-domain.com/api/student/quizzes" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**Step 5: Take a Quiz**

```bash
# Load quiz details
curl -X GET "https://your-domain.com/api/student/quizzes/15" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Submit quiz attempt
curl -X POST "https://your-domain.com/api/student/quizzes/15/attempt" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": ["Hola", "Gracias", "Buenos días", "De nada", "Por favor"]
  }'
```

**Step 6: View Results and Progress**

```bash
# Get detailed results
curl -X GET "https://your-domain.com/api/student/quizzes/15/attempts/127/results" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# View all attempt history
curl -X GET "https://your-domain.com/api/student/quizzes/attempts/my" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**Step 7: Access Meetings**

```bash
# View available meetings
curl -X GET "https://your-domain.com/api/student/meetings" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Get upcoming meetings
curl -X GET "https://your-domain.com/api/student/meetings/upcoming" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

#### Complete Student Learning App Implementation

```javascript
class StudentLearningApp {
  constructor(apiBaseUrl) {
    this.apiBaseUrl = apiBaseUrl;
    this.auth = new AuthManager(apiBaseUrl);
  }

  async initialize() {
    if (!this.auth.isAuthenticated()) {
      throw new Error('User must be authenticated');
    }

    // Load initial data
    const [programs, enrollments, quizzes] = await Promise.all([
      this.getPrograms(),
      this.getEnrollments(),
      this.getQuizzes()
    ]);

    return {
      programs: programs.data,
      enrollments: enrollments.data,
      quizzes: quizzes.data
    };
  }

  async getPrograms() {
    const response = await fetch(`${this.apiBaseUrl}/student/programs`, {
      headers: this.auth.getAuthHeaders()
    });
    return response.json();
  }

  async getEnrollments() {
    const response = await fetch(`${this.apiBaseUrl}/student/enrollments`, {
      headers: this.auth.getAuthHeaders()
    });
    return response.json();
  }

  async getQuizzes() {
    const response = await fetch(`${this.apiBaseUrl}/student/quizzes`, {
      headers: this.auth.getAuthHeaders()
    });
    return response.json();
  }

  async takeQuiz(quizId) {
    // Load quiz
    const quizResponse = await fetch(`${this.apiBaseUrl}/student/quizzes/${quizId}`, {
      headers: this.auth.getAuthHeaders()
    });
    const quizData = await quizResponse.json();

    if (!quizData.success) {
      throw new Error(quizData.message);
    }

    // Present quiz to user and collect answers
    const answers = await this.presentQuizToUser(quizData.data);

    // Submit attempt
    const attemptResponse = await fetch(`${this.apiBaseUrl}/student/quizzes/${quizId}/attempt`, {
      method: 'POST',
      headers: this.auth.getAuthHeaders(),
      body: JSON.stringify({ answers })
    });

    const attemptResult = await attemptResponse.json();

    if (attemptResult.success) {
      // Load detailed results
      const resultsResponse = await fetch(
        `${this.apiBaseUrl}/student/quizzes/${quizId}/attempts/${attemptResult.data.attempt_id}/results`,
        { headers: this.auth.getAuthHeaders() }
      );
      const detailedResults = await resultsResponse.json();

      return {
        attempt: attemptResult.data,
        results: detailedResults.data
      };
    } else {
      throw new Error(attemptResult.message);
    }
  }

  async presentQuizToUser(quiz) {
    // This would integrate with your UI framework
    console.log(`Taking quiz: ${quiz.title}`);
    console.log(`Questions: ${quiz.questions.length}`);

    const answers = [];
    for (let i = 0; i < quiz.questions.length; i++) {
      const question = quiz.questions[i];
      console.log(`Question ${i + 1}: ${question.question}`);
      
      // In a real app, this would be user input
      const answer = prompt(`Answer for question ${i + 1}:`);
      answers.push(answer);
    }

    return answers;
  }

  async getProgressSummary() {
    const attemptsResponse = await fetch(`${this.apiBaseUrl}/student/quizzes/attempts/my`, {
      headers: this.auth.getAuthHeaders()
    });
    const attempts = await attemptsResponse.json();

    if (attempts.success) {
      const summary = {
        totalAttempts: attempts.data.total,
        averageScore: 0,
        passedQuizzes: 0,
        recentAttempts: attempts.data.data.slice(0, 5)
      };

      if (attempts.data.data.length > 0) {
        const totalScore = attempts.data.data.reduce((sum, attempt) => sum + attempt.percentage_score, 0);
        summary.averageScore = totalScore / attempts.data.data.length;
        summary.passedQuizzes = attempts.data.data.filter(attempt => attempt.passed).length;
      }

      return summary;
    }

    throw new Error('Failed to load progress summary');
  }
}

// Usage example
async function runStudentApp() {
  const app = new StudentLearningApp('https://your-domain.com/api');
  
  try {
    // Initialize app
    const initialData = await app.initialize();
    console.log('Available programs:', initialData.programs.length);
    console.log('Available quizzes:', initialData.quizzes.length);

    // Take a quiz
    if (initialData.quizzes.length > 0) {
      const firstQuiz = initialData.quizzes[0];
      const quizResult = await app.takeQuiz(firstQuiz.id);
      console.log(`Quiz completed! Score: ${quizResult.attempt.score}%`);
    }

    // Get progress summary
    const progress = await app.getProgressSummary();
    console.log(`Total attempts: ${progress.totalAttempts}`);
    console.log(`Average score: ${progress.averageScore.toFixed(1)}%`);
    console.log(`Passed quizzes: ${progress.passedQuizzes}`);

  } catch (error) {
    console.error('App error:', error.message);
  }
}
```

### Teacher Quiz Management Workflow

#### Complete Quiz Creation and Management

**Step 1: Teacher Login**

```bash
# Login as teacher
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "maria.rodriguez@example.com",
    "password": "TeacherPass123!"
  }'
```

**Step 2: Check Available Languages**

```bash
# Get teacher's assigned languages
curl -X GET "https://your-domain.com/api/teacher/my-languages" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Create a New Quiz**

```bash
# Create quiz with questions
curl -X POST "https://your-domain.com/api/teacher/quizzes" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Spanish Vocabulary - Greetings",
    "description": "Test your knowledge of Spanish greeting words and phrases",
    "type": "inline",
    "program_id": 8,
    "questions": [
      {
        "question": "What is the Spanish word for \"hello\"?",
        "choices": ["Hola", "Adiós", "Gracias", "Por favor"],
        "correct_answer": "Hola"
      },
      {
        "question": "How do you say \"good morning\" in Spanish?",
        "choices": ["Buenas noches", "Buenos días", "Buenas tardes", "Hasta luego"],
        "correct_answer": "Buenos días"
      },
      {
        "question": "What does \"¿Cómo estás?\" mean in English?",
        "choices": ["What is your name?", "How are you?", "Where are you from?", "How old are you?"],
        "correct_answer": "How are you?"
      }
    ]
  }'
```

**Step 4: Update Quiz Questions**

```bash
# Add a new question
curl -X POST "https://your-domain.com/api/teacher/quizzes/25/questions" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question": "What is the Spanish word for \"goodbye\"?",
    "choices": ["Hola", "Adiós", "Gracias", "De nada"],
    "correct_answer": "Adiós"
  }'

# Update existing question
curl -X PUT "https://your-domain.com/api/teacher/quizzes/25/questions/45" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question": "What is the Spanish word for \"hello\" (updated)?",
    "choices": ["Hola", "Adiós", "Gracias", "Por favor"],
    "correct_answer": "Hola"
  }'
```

**Step 5: Monitor Quiz Performance**

```bash
# Get quiz statistics
curl -X GET "https://your-domain.com/api/teacher/quizzes/25/statistics" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Get all attempts for the quiz
curl -X GET "https://your-domain.com/api/teacher/quizzes/25/attempts" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Get detailed results for specific attempt
curl -X GET "https://your-domain.com/api/teacher/quizzes/25/attempts/127/results" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

#### Teacher Quiz Management Class

```javascript
class TeacherQuizManager {
  constructor(apiBaseUrl, authToken) {
    this.apiBaseUrl = apiBaseUrl;
    this.authToken = authToken;
  }

  getAuthHeaders() {
    return {
      'Authorization': `Bearer ${this.authToken}`,
      'Content-Type': 'application/json'
    };
  }

  async createQuiz(quizData) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: JSON.stringify(quizData)
    });
    return response.json();
  }

  async getMyQuizzes() {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes`, {
      headers: this.getAuthHeaders()
    });
    return response.json();
  }

  async updateQuiz(quizId, updates) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes/${quizId}`, {
      method: 'PUT',
      headers: this.getAuthHeaders(),
      body: JSON.stringify(updates)
    });
    return response.json();
  }

  async addQuestion(quizId, questionData) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes/${quizId}/questions`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: JSON.stringify(questionData)
    });
    return response.json();
  }

  async updateQuestion(quizId, questionId, questionData) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes/${quizId}/questions/${questionId}`, {
      method: 'PUT',
      headers: this.getAuthHeaders(),
      body: JSON.stringify(questionData)
    });
    return response.json();
  }

  async deleteQuestion(quizId, questionId) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes/${quizId}/questions/${questionId}`, {
      method: 'DELETE',
      headers: this.getAuthHeaders()
    });
    return response.json();
  }

  async reorderQuestions(quizId, questionOrder) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes/${quizId}/questions/reorder`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: JSON.stringify({ question_order: questionOrder })
    });
    return response.json();
  }

  async getQuizStatistics(quizId) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes/${quizId}/statistics`, {
      headers: this.getAuthHeaders()
    });
    return response.json();
  }

  async getQuizAttempts(quizId, page = 1) {
    const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes/${quizId}/attempts?page=${page}`, {
      headers: this.getAuthHeaders()
    });
    return response.json();
  }
}

// Usage example
const quizManager = new TeacherQuizManager('https://your-domain.com/api', teacherToken);

async function createCompleteQuiz() {
  try {
    // Create quiz with initial questions
    const quizData = {
      title: "Spanish Vocabulary - Greetings",
      description: "Test your knowledge of Spanish greeting words and phrases",
      type: "inline",
      program_id: 8,
      questions: [
        {
          question: "What is the Spanish word for \"hello\"?",
          choices: ["Hola", "Adiós", "Gracias", "Por favor"],
          correct_answer: "Hola"
        },
        {
          question: "How do you say \"good morning\" in Spanish?",
          choices: ["Buenas noches", "Buenos días", "Buenas tardes", "Hasta luego"],
          correct_answer: "Buenos días"
        }
      ]
    };

    const createResult = await quizManager.createQuiz(quizData);
    
    if (createResult.success) {
      const quizId = createResult.data.id;
      console.log(`Quiz created with ID: ${quizId}`);

      // Add additional question
      const additionalQuestion = {
        question: "What does \"¿Cómo estás?\" mean in English?",
        choices: ["What is your name?", "How are you?", "Where are you from?", "How old are you?"],
        correct_answer: "How are you?"
      };

      const addResult = await quizManager.addQuestion(quizId, additionalQuestion);
      
      if (addResult.success) {
        console.log('Additional question added successfully');
        
        // Get quiz statistics
        const stats = await quizManager.getQuizStatistics(quizId);
        console.log('Quiz statistics:', stats.data);
      }
    }
  } catch (error) {
    console.error('Error creating quiz:', error);
  }
}
```

### File Upload Examples

#### Teacher Profile Image Upload

**cURL Example with Multipart Form Data:**

```bash
# Upload teacher profile image
curl -X PUT "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "name=Maria Rodriguez" \
  -F "phone=+34-987-654-321" \
  -F "preferred_language=es" \
  -F "image=@/path/to/profile-image.jpg"
```

**JavaScript File Upload Example:**

```javascript
class FileUploadManager {
  constructor(apiBaseUrl, authToken) {
    this.apiBaseUrl = apiBaseUrl;
    this.authToken = authToken;
  }

  async uploadTeacherProfileImage(profileData, imageFile) {
    const formData = new FormData();
    
    // Add profile data
    Object.keys(profileData).forEach(key => {
      formData.append(key, profileData[key]);
    });
    
    // Add image file
    if (imageFile) {
      formData.append('image', imageFile);
    }

    try {
      const response = await fetch(`${this.apiBaseUrl}/teacher/profile`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${this.authToken}`
          // Note: Don't set Content-Type for FormData, browser will set it automatically
        },
        body: formData
      });

      return await response.json();
    } catch (error) {
      throw new Error(`Upload failed: ${error.message}`);
    }
  }

  async uploadQuizFile(quizData, file) {
    const formData = new FormData();
    
    // Add quiz data
    formData.append('title', quizData.title);
    formData.append('description', quizData.description || '');
    formData.append('type', 'file');
    formData.append('program_id', quizData.program_id);
    
    // Add file
    formData.append('file', file);

    try {
      const response = await fetch(`${this.apiBaseUrl}/teacher/quizzes`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.authToken}`
        },
        body: formData
      });

      return await response.json();
    } catch (error) {
      throw new Error(`Quiz file upload failed: ${error.message}`);
    }
  }
}

// HTML form example
function createFileUploadForm() {
  return `
    <form id="profileUploadForm" enctype="multipart/form-data">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="tel" name="phone" placeholder="Phone Number">
      <select name="preferred_language">
        <option value="en">English</option>
        <option value="es">Spanish</option>
        <option value="ar">Arabic</option>
      </select>
      <input type="file" name="image" accept="image/*">
      <button type="submit">Update Profile</button>
    </form>
  `;
}

// Form submission handler
document.getElementById('profileUploadForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  const imageFile = formData.get('image');
  
  const profileData = {
    name: formData.get('name'),
    phone: formData.get('phone'),
    preferred_language: formData.get('preferred_language')
  };

  const uploadManager = new FileUploadManager('https://your-domain.com/api', teacherToken);
  
  try {
    const result = await uploadManager.uploadTeacherProfileImage(profileData, imageFile);
    
    if (result.success) {
      console.log('Profile updated successfully:', result.data);
      // Update UI with new profile data
    } else {
      console.error('Upload failed:', result.message);
      if (result.errors) {
        console.error('Validation errors:', result.errors);
      }
    }
  } catch (error) {
    console.error('Upload error:', error.message);
  }
});
```

#### File Upload Validation Examples

**Image Upload with Validation:**

```javascript
class ImageUploadValidator {
  static validateImage(file) {
    const errors = [];
    
    // Check file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml'];
    if (!allowedTypes.includes(file.type)) {
      errors.push('File must be JPEG, PNG, JPG, GIF, or SVG format');
    }
    
    // Check file size (2MB limit)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
      errors.push('File size must be less than 2MB');
    }
    
    // Check dimensions (optional)
    return new Promise((resolve) => {
      if (errors.length > 0) {
        resolve({ valid: false, errors });
        return;
      }
      
      const img = new Image();
      img.onload = function() {
        if (this.width > 2000 || this.height > 2000) {
          errors.push('Image dimensions must be less than 2000x2000 pixels');
        }
        resolve({ valid: errors.length === 0, errors });
      };
      img.onerror = function() {
        errors.push('Invalid image file');
        resolve({ valid: false, errors });
      };
      img.src = URL.createObjectURL(file);
    });
  }
}

// Usage in file upload
async function handleImageUpload(file) {
  const validation = await ImageUploadValidator.validateImage(file);
  
  if (!validation.valid) {
    console.error('Validation errors:', validation.errors);
    return;
  }
  
  // Proceed with upload
  const uploadManager = new FileUploadManager('https://your-domain.com/api', token);
  const result = await uploadManager.uploadTeacherProfileImage(profileData, file);
}
```

### Admin Management Workflows

#### Complete User Management Workflow

**Step 1: Admin Login**

```bash
# Login as admin
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@learnacademy.com",
    "password": "AdminPass123!"
  }'
```

**Step 2: User Management Operations**

```bash
# Get all users with pagination
curl -X GET "https://your-domain.com/api/admin/users?page=1&per_page=20" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Create new teacher
curl -X POST "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Ahmed Hassan",
    "email": "ahmed.hassan@example.com",
    "password": "TeacherPass123!",
    "password_confirmation": "TeacherPass123!",
    "phone": "+20-123-456-789",
    "role": "teacher",
    "preferred_language": "ar"
  }'

# Update user information
curl -X PUT "https://your-domain.com/api/admin/users/156" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Ahmed Hassan (Updated)",
    "phone": "+20-123-456-790",
    "preferred_language": "en"
  }'
```

**Step 3: Enrollment Management**

```bash
# Get pending enrollments
curl -X GET "https://your-domain.com/api/admin/pending-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Approve individual enrollment
curl -X POST "https://your-domain.com/api/admin/grant-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 123,
    "program_id": 8
  }'

# Bulk approve enrollments
curl -X POST "https://your-domain.com/api/admin/bulk-approve-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "enrollment_ids": [45, 52, 67, 89, 91]
  }'
```

**Step 4: System Configuration**

```bash
# Update guest access settings
curl -X PUT "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "guest_access_enabled": true,
    "guest_languages_enabled": true,
    "guest_teachers_enabled": true,
    "guest_quizzes_enabled": false
  }'

# Update translations
curl -X PUT "https://your-domain.com/api/translations/es" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "welcome_message": "Bienvenido a Learn Academy",
    "login_button": "Iniciar Sesión",
    "register_button": "Registrarse"
  }'
```

#### Admin Dashboard Implementation

```javascript
class AdminDashboard {
  constructor(apiBaseUrl, authToken) {
    this.apiBaseUrl = apiBaseUrl;
    this.authToken = authToken;
  }

  getAuthHeaders() {
    return {
      'Authorization': `Bearer ${this.authToken}`,
      'Content-Type': 'application/json'
    };
  }

  async getDashboardData() {
    try {
      const [users, enrollments, statistics] = await Promise.all([
        this.getUsers(1, 10),
        this.getPendingEnrollments(),
        this.getEnrollmentStatistics()
      ]);

      return {
        users: users.data,
        pendingEnrollments: enrollments.data,
        statistics: statistics.data
      };
    } catch (error) {
      throw new Error(`Failed to load dashboard data: ${error.message}`);
    }
  }

  async getUsers(page = 1, perPage = 15) {
    const response = await fetch(`${this.apiBaseUrl}/admin/users?page=${page}&per_page=${perPage}`, {
      headers: this.getAuthHeaders()
    });
    return response.json();
  }

  async createUser(userData) {
    const response = await fetch(`${this.apiBaseUrl}/admin/users`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: JSON.stringify(userData)
    });
    return response.json();
  }

  async updateUser(userId, updates) {
    const response = await fetch(`${this.apiBaseUrl}/admin/users/${userId}`, {
      method: 'PUT',
      headers: this.getAuthHeaders(),
      body: JSON.stringify(updates)
    });
    return response.json();
  }

  async deleteUser(userId) {
    const response = await fetch(`${this.apiBaseUrl}/admin/users/${userId}`, {
      method: 'DELETE',
      headers: this.getAuthHeaders()
    });
    return response.json();
  }

  async getPendingEnrollments() {
    const response = await fetch(`${this.apiBaseUrl}/admin/pending-enrollments`, {
      headers: this.getAuthHeaders()
    });
    return response.json();
  }

  async approveEnrollment(studentId, programId) {
    const response = await fetch(`${this.apiBaseUrl}/admin/grant-access`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: JSON.stringify({ student_id: studentId, program_id: programId })
    });
    return response.json();
  }

  async bulkApproveEnrollments(enrollmentIds) {
    const response = await fetch(`${this.apiBaseUrl}/admin/bulk-approve-enrollments`, {
      method: 'POST',
      headers: this.getAuthHeaders(),
      body: JSON.stringify({ enrollment_ids: enrollmentIds })
    });
    return response.json();
  }

  async getEnrollmentStatistics() {
    const response = await fetch(`${this.apiBaseUrl}/admin/enrollment-statistics`, {
      headers: this.getAuthHeaders()
    });
    return response.json();
  }

  async updateGuestSettings(settings) {
    const response = await fetch(`${this.apiBaseUrl}/admin/settings/guest-access`, {
      method: 'PUT',
      headers: this.getAuthHeaders(),
      body: JSON.stringify(settings)
    });
    return response.json();
  }
}

// Usage example
const adminDashboard = new AdminDashboard('https://your-domain.com/api', adminToken);

async function initializeAdminDashboard() {
  try {
    const dashboardData = await adminDashboard.getDashboardData();
    
    console.log('Dashboard loaded:');
    console.log(`- Total users: ${dashboardData.users.total}`);
    console.log(`- Pending enrollments: ${dashboardData.pendingEnrollments.length}`);
    console.log(`- Statistics:`, dashboardData.statistics);

    // Render dashboard UI
    renderUserTable(dashboardData.users);
    renderPendingEnrollments(dashboardData.pendingEnrollments);
    renderStatistics(dashboardData.statistics);

  } catch (error) {
    console.error('Dashboard initialization failed:', error.message);
  }
}

// Bulk enrollment approval example
async function processPendingEnrollments() {
  try {
    const pendingEnrollments = await adminDashboard.getPendingEnrollments();
    
    if (pendingEnrollments.success && pendingEnrollments.data.length > 0) {
      const enrollmentIds = pendingEnrollments.data.map(enrollment => enrollment.id);
      
      const result = await adminDashboard.bulkApproveEnrollments(enrollmentIds);
      
      if (result.success) {
        console.log(`Approved ${result.data.approved_count} enrollments`);
        // Refresh dashboard
        await initializeAdminDashboard();
      }
    }
  } catch (error) {
    console.error('Bulk approval failed:', error.message);
  }
}
```

### Guest Access Workflows

#### Public Content Access (No Authentication)

**Step 1: Check Guest Access Settings**

```bash
# Check if guest access is enabled (public endpoint)
curl -X GET "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Content-Type: application/json"
```

**Step 2: Access Public Content**

```bash
# Get public languages (if enabled)
curl -X GET "https://your-domain.com/api/guest/languages" \
  -H "Content-Type: application/json"

# Get public teachers (if enabled)
curl -X GET "https://your-domain.com/api/guest/teachers" \
  -H "Content-Type: application/json"

# Get public quizzes (if enabled)
curl -X GET "https://your-domain.com/api/guest/quizzes" \
  -H "Content-Type: application/json"
```

**Step 3: Take Public Quiz**

```bash
# Get quiz details
curl -X GET "https://your-domain.com/api/guest/quizzes/15" \
  -H "Content-Type: application/json"

# Submit anonymous quiz attempt
curl -X POST "https://your-domain.com/api/guest/quizzes/15/attempt" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": ["Hola", "Gracias", "Buenos días", "De nada", "Por favor"]
  }'
```

#### Guest Access Implementation

```javascript
class GuestAccessManager {
  constructor(apiBaseUrl) {
    this.apiBaseUrl = apiBaseUrl;
  }

  async checkGuestAccess() {
    try {
      const response = await fetch(`${this.apiBaseUrl}/admin/settings/guest-access`);
      const result = await response.json();
      
      if (result.success) {
        return result.data;
      }
      
      // Default to no access if settings can't be retrieved
      return {
        guest_access_enabled: false,
        guest_languages_enabled: false,
        guest_teachers_enabled: false,
        guest_quizzes_enabled: false
      };
    } catch (error) {
      console.error('Failed to check guest access:', error);
      return { guest_access_enabled: false };
    }
  }

  async getPublicLanguages() {
    const response = await fetch(`${this.apiBaseUrl}/guest/languages`);
    return response.json();
  }

  async getPublicTeachers() {
    const response = await fetch(`${this.apiBaseUrl}/guest/teachers`);
    return response.json();
  }

  async getPublicQuizzes() {
    const response = await fetch(`${this.apiBaseUrl}/guest/quizzes`);
    return response.json();
  }

  async getPublicQuiz(quizId) {
    const response = await fetch(`${this.apiBaseUrl}/guest/quizzes/${quizId}`);
    return response.json();
  }

  async submitGuestQuizAttempt(quizId, answers) {
    const response = await fetch(`${this.apiBaseUrl}/guest/quizzes/${quizId}/attempt`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ answers })
    });
    return response.json();
  }
}

// Public learning portal implementation
class PublicLearningPortal {
  constructor(apiBaseUrl) {
    this.guestManager = new GuestAccessManager(apiBaseUrl);
    this.accessSettings = null;
  }

  async initialize() {
    this.accessSettings = await this.guestManager.checkGuestAccess();
    
    if (!this.accessSettings.guest_access_enabled) {
      throw new Error('Guest access is not enabled');
    }

    const availableContent = {};

    if (this.accessSettings.guest_languages_enabled) {
      availableContent.languages = await this.guestManager.getPublicLanguages();
    }

    if (this.accessSettings.guest_teachers_enabled) {
      availableContent.teachers = await this.guestManager.getPublicTeachers();
    }

    if (this.accessSettings.guest_quizzes_enabled) {
      availableContent.quizzes = await this.guestManager.getPublicQuizzes();
    }

    return availableContent;
  }

  async takePublicQuiz(quizId) {
    if (!this.accessSettings.guest_quizzes_enabled) {
      throw new Error('Guest quiz access is not enabled');
    }

    // Load quiz
    const quizData = await this.guestManager.getPublicQuiz(quizId);
    
    if (!quizData.success) {
      throw new Error(quizData.message);
    }

    // Present quiz to user (implementation depends on UI framework)
    const answers = await this.presentQuizToUser(quizData.data);

    // Submit attempt
    const result = await this.guestManager.submitGuestQuizAttempt(quizId, answers);
    
    return result;
  }

  async presentQuizToUser(quiz) {
    // This would integrate with your UI framework
    console.log(`Taking public quiz: ${quiz.title}`);
    
    const answers = [];
    for (let i = 0; i < quiz.questions.length; i++) {
      const question = quiz.questions[i];
      console.log(`Question ${i + 1}: ${question.question}`);
      
      // In a real app, this would be user input
      const answer = prompt(`Answer for question ${i + 1}:`);
      answers.push(answer);
    }

    return answers;
  }
}

// Usage example
async function runPublicPortal() {
  const portal = new PublicLearningPortal('https://your-domain.com/api');
  
  try {
    const content = await portal.initialize();
    
    console.log('Available public content:');
    if (content.languages) {
      console.log(`- Languages: ${content.languages.data.length}`);
    }
    if (content.teachers) {
      console.log(`- Teachers: ${content.teachers.data.length}`);
    }
    if (content.quizzes) {
      console.log(`- Quizzes: ${content.quizzes.data.length}`);
      
      // Take first available quiz
      if (content.quizzes.data.length > 0) {
        const firstQuiz = content.quizzes.data[0];
        const result = await portal.takePublicQuiz(firstQuiz.id);
        
        if (result.success) {
          console.log(`Quiz completed! Score: ${result.data.score}%`);
        }
      }
    }
  } catch (error) {
    console.error('Public portal error:', error.message);
  }
}
```

### Error Handling and Retry Patterns

#### Comprehensive Error Handling

```javascript
class APIErrorHandler {
  static async handleResponse(response) {
    const data = await response.json();
    
    if (!response.ok) {
      switch (response.status) {
        case 401:
          throw new AuthenticationError(data.message);
        case 403:
          throw new AuthorizationError(data.message);
        case 404:
          throw new NotFoundError(data.message);
        case 422:
          throw new ValidationError(data.message, data.errors);
        case 429:
          throw new RateLimitError(data.message);
        case 500:
          throw new ServerError(data.message);
        default:
          throw new APIError(data.message, response.status);
      }
    }
    
    return data;
  }
}

class APIError extends Error {
  constructor(message, status) {
    super(message);
    this.name = 'APIError';
    this.status = status;
  }
}

class AuthenticationError extends APIError {
  constructor(message) {
    super(message, 401);
    this.name = 'AuthenticationError';
  }
}

class ValidationError extends APIError {
  constructor(message, errors) {
    super(message, 422);
    this.name = 'ValidationError';
    this.errors = errors;
  }
}

class RateLimitError extends APIError {
  constructor(message) {
    super(message, 429);
    this.name = 'RateLimitError';
  }
}

// Retry mechanism with exponential backoff
class RetryManager {
  static async withRetry(operation, maxRetries = 3, baseDelay = 1000) {
    let lastError;
    
    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      try {
        return await operation();
      } catch (error) {
        lastError = error;
        
        // Don't retry on certain errors
        if (error instanceof AuthenticationError || 
            error instanceof AuthorizationError || 
            error instanceof ValidationError) {
          throw error;
        }
        
        if (attempt === maxRetries) {
          break;
        }
        
        // Exponential backoff
        const delay = baseDelay * Math.pow(2, attempt);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
    
    throw lastError;
  }
}

// Enhanced API client with error handling and retries
class EnhancedAPIClient {
  constructor(apiBaseUrl, authToken) {
    this.apiBaseUrl = apiBaseUrl;
    this.authToken = authToken;
  }

  async makeRequest(endpoint, options = {}) {
    const url = `${this.apiBaseUrl}${endpoint}`;
    const config = {
      headers: {
        'Content-Type': 'application/json',
        ...(this.authToken && { 'Authorization': `Bearer ${this.authToken}` }),
        ...options.headers
      },
      ...options
    };

    const operation = async () => {
      const response = await fetch(url, config);
      return APIErrorHandler.handleResponse(response);
    };

    return RetryManager.withRetry(operation);
  }

  async get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;
    return this.makeRequest(url);
  }

  async post(endpoint, data) {
    return this.makeRequest(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async put(endpoint, data) {
    return this.makeRequest(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async delete(endpoint) {
    return this.makeRequest(endpoint, {
      method: 'DELETE'
    });
  }
}

// Usage with comprehensive error handling
async function robustQuizSubmission(quizId, answers) {
  const client = new EnhancedAPIClient('https://your-domain.com/api', studentToken);
  
  try {
    const result = await client.post(`/student/quizzes/${quizId}/attempt`, { answers });
    
    console.log('Quiz submitted successfully:', result.data);
    return result;
    
  } catch (error) {
    if (error instanceof ValidationError) {
      console.error('Validation errors:', error.errors);
      // Handle validation errors (show to user)
      
    } else if (error instanceof AuthenticationError) {
      console.error('Authentication failed, redirecting to login');
      // Redirect to login page
      
    } else if (error instanceof RateLimitError) {
      console.error('Rate limit exceeded, please try again later');
      // Show rate limit message to user
      
    } else {
      console.error('Unexpected error:', error.message);
      // Show generic error message
    }
    
    throw error;
  }
}
```

### Complete User Journey Examples

#### New Student Onboarding Journey

This complete workflow demonstrates a new student's journey from registration to taking their first quiz.

**Step 1: Student Registration**

```bash
# Register new student account
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Emma Thompson",
    "email": "emma.thompson@example.com",
    "password": "StudentPass123!",
    "password_confirmation": "StudentPass123!",
    "phone": "+1-555-987-6543",
    "role": "student",
    "preferred_language": "en",
    "notify_email": true,
    "notify_whatsapp": false
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 234,
      "name": "Emma Thompson",
      "email": "emma.thompson@example.com",
      "role": "student",
      "preferred_language": "en"
    },
    "token": "1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx"
  }
}
```

**Step 2: Check Enrollment Status**

```bash
# Check current enrollments (likely empty for new student)
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer 1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx" \
  -H "Content-Type: application/json"
```

**Step 3: View Available Programs (After Admin Approval)**

```bash
# View approved programs
curl -X GET "https://your-domain.com/api/student/programs" \
  -H "Authorization: Bearer 1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx" \
  -H "Content-Type: application/json"
```

**Step 4: Discover Available Quizzes**

```bash
# Get quizzes for enrolled programs
curl -X GET "https://your-domain.com/api/student/quizzes" \
  -H "Authorization: Bearer 1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx" \
  -H "Content-Type: application/json"
```

**Step 5: Take First Quiz**

```bash
# Load quiz details
curl -X GET "https://your-domain.com/api/student/quizzes/18" \
  -H "Authorization: Bearer 1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx" \
  -H "Content-Type: application/json"

# Submit quiz attempt
curl -X POST "https://your-domain.com/api/student/quizzes/18/attempt" \
  -H "Authorization: Bearer 1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": ["Hello", "Good morning", "Thank you", "You are welcome", "Excuse me"]
  }'
```

**Step 6: View Results and Progress**

```bash
# Get detailed results
curl -X GET "https://your-domain.com/api/student/quizzes/18/attempts/145/results" \
  -H "Authorization: Bearer 1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx" \
  -H "Content-Type: application/json"

# Check overall progress
curl -X GET "https://your-domain.com/api/student/quizzes/attempts/my" \
  -H "Authorization: Bearer 1|xyz789abc456def123ghi890jkl567mno234pqr901stu678vwx" \
  -H "Content-Type: application/json"
```

#### Teacher Content Creation Journey

This workflow shows a teacher creating and managing educational content.

**Step 1: Teacher Login**

```bash
# Login as teacher
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "carlos.mendez@example.com",
    "password": "TeacherPass456!"
  }'
```

**Step 2: Update Profile with Image**

```bash
# Update profile with image upload
curl -X PUT "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "name=Carlos Mendez" \
  -F "phone=+52-555-123-4567" \
  -F "preferred_language=es" \
  -F "image=@/path/to/teacher-photo.jpg"
```

**Step 3: Check Language Access**

```bash
# Get assigned languages
curl -X GET "https://your-domain.com/api/teacher/my-languages" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Verify access to specific language
curl -X GET "https://your-domain.com/api/teacher/languages/es/access" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

**Step 4: Create Comprehensive Quiz**

```bash
# Create quiz with multiple question types
curl -X POST "https://your-domain.com/api/teacher/quizzes" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Spanish Intermediate - Past Tense",
    "description": "Test your understanding of Spanish past tense conjugations and usage",
    "type": "inline",
    "program_id": 15,
    "questions": [
      {
        "question": "What is the correct past tense form of \"hablar\" (to speak) for \"yo\" (I)?",
        "choices": ["hablo", "hablé", "hablaba", "hablaré"],
        "correct_answer": "hablé"
      },
      {
        "question": "Choose the correct sentence using the imperfect tense:",
        "choices": [
          "Ayer comí pizza",
          "Cuando era niño, comía pizza todos los viernes",
          "Mañana comeré pizza",
          "Estoy comiendo pizza"
        ],
        "correct_answer": "Cuando era niño, comía pizza todos los viernes"
      },
      {
        "question": "What is the difference between \"fui\" and \"era\"?",
        "choices": [
          "Both mean the same thing",
          "Fui is preterite (completed action), era is imperfect (ongoing/habitual)",
          "Era is preterite, fui is imperfect",
          "They are future tense forms"
        ],
        "correct_answer": "Fui is preterite (completed action), era is imperfect (ongoing/habitual)"
      }
    ]
  }'
```

**Step 5: Schedule Meeting**

```bash
# Create meeting for students
curl -X POST "https://your-domain.com/api/teacher/meetings" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Spanish Past Tense Review Session",
    "description": "Interactive session to practice past tense conjugations and usage",
    "meeting_date": "2024-02-15",
    "meeting_time": "14:00:00",
    "duration_minutes": 60,
    "meeting_link": "https://zoom.us/j/123456789",
    "program_id": 15
  }'
```

**Step 6: Monitor Student Progress**

```bash
# Get quiz statistics
curl -X GET "https://your-domain.com/api/teacher/quizzes/28/statistics" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Review student attempts
curl -X GET "https://your-domain.com/api/teacher/quizzes/28/attempts" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"

# Get detailed results for specific attempt
curl -X GET "https://your-domain.com/api/teacher/quizzes/28/attempts/156/results" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

#### Admin System Management Journey

This workflow demonstrates comprehensive admin system management tasks.

**Step 1: Admin Login and Dashboard Overview**

```bash
# Login as admin
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@learnacademy.com",
    "password": "AdminSecure789!"
  }'

# Get system health check
curl -X GET "https://your-domain.com/api/health" \
  -H "Content-Type: application/json"

# Get enrollment statistics
curl -X GET "https://your-domain.com/api/admin/enrollment-statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: User Management Operations**

```bash
# Create new teacher with full details
curl -X POST "https://your-domain.com/api/admin/users" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Fatima Al-Zahra",
    "email": "fatima.alzahra@example.com",
    "password": "TeacherSecure123!",
    "password_confirmation": "TeacherSecure123!",
    "phone": "+971-50-123-4567",
    "role": "teacher",
    "preferred_language": "ar",
    "notify_email": true,
    "notify_whatsapp": true
  }'

# Assign teacher to multiple languages
curl -X POST "https://your-domain.com/api/admin/assign-teacher-multiple-languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": 67,
    "language_ids": [1, 3]
  }'

# Update user information
curl -X PUT "https://your-domain.com/api/admin/users/67" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Fatima Al-Zahra (Senior Instructor)",
    "phone": "+971-50-123-4568"
  }'
```

**Step 3: Bulk Enrollment Management**

```bash
# Get all pending enrollments
curl -X GET "https://your-domain.com/api/admin/all-pending-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Bulk approve multiple enrollments
curl -X POST "https://your-domain.com/api/admin/bulk-approve-enrollments" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "enrollment_ids": [78, 82, 85, 91, 94, 97]
  }'

# Grant access to specific program
curl -X POST "https://your-domain.com/api/admin/grant-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 234,
    "program_id": 15
  }'
```

**Step 4: System Configuration**

```bash
# Update guest access settings
curl -X PUT "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "guest_access_enabled": true,
    "guest_languages_enabled": true,
    "guest_teachers_enabled": true,
    "guest_quizzes_enabled": false
  }'

# Update system translations
curl -X PUT "https://your-domain.com/api/translations/ar" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "welcome_message": "مرحباً بكم في أكاديمية التعلم",
    "login_button": "تسجيل الدخول",
    "register_button": "إنشاء حساب جديد",
    "quiz_title": "اختبار",
    "submit_button": "إرسال"
  }'

# Clear translation cache
curl -X POST "https://your-domain.com/api/translations/cache/clear" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

### cURL Command Examples

#### Authentication Examples

**User Registration with All Options:**

```bash
# Complete student registration
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Ahmed Hassan",
    "email": "ahmed.hassan@example.com",
    "password": "SecurePassword123!",
    "password_confirmation": "SecurePassword123!",
    "phone": "+20-100-123-4567",
    "role": "student",
    "preferred_language": "ar",
    "notify_email": true,
    "notify_whatsapp": true
  }'

# Teacher registration
curl -X POST "https://your-domain.com/api/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Sarah Johnson",
    "email": "sarah.johnson@example.com",
    "password": "TeacherPass456!",
    "password_confirmation": "TeacherPass456!",
    "phone": "+1-555-234-5678",
    "role": "teacher",
    "preferred_language": "en",
    "notify_email": true,
    "notify_whatsapp": false
  }'
```

**Login with Error Handling:**

```bash
# Successful login
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "HTTP Status: %{http_code}\n" \
  -d '{
    "email": "ahmed.hassan@example.com",
    "password": "SecurePassword123!"
  }'

# Login with invalid credentials (will return 401)
curl -X POST "https://your-domain.com/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "HTTP Status: %{http_code}\n" \
  -d '{
    "email": "ahmed.hassan@example.com",
    "password": "wrongpassword"
  }'
```

#### Quiz Management Examples

**Complete Quiz CRUD Operations:**

```bash
# Create quiz with file upload
curl -X POST "https://your-domain.com/api/teacher/quizzes" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "title=Advanced English Grammar Test" \
  -F "description=Comprehensive test covering advanced grammar topics" \
  -F "type=file" \
  -F "program_id=22" \
  -F "file=@/path/to/quiz-questions.pdf"

# Update quiz information
curl -X PUT "https://your-domain.com/api/teacher/quizzes/35" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Advanced English Grammar Test (Updated)",
    "description": "Updated comprehensive test with additional exercises",
    "active": true
  }'

# Add question to existing quiz
curl -X POST "https://your-domain.com/api/teacher/quizzes/35/questions" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question": "Which sentence uses the subjunctive mood correctly?",
    "choices": [
      "I wish I was taller",
      "I wish I were taller", 
      "I wish I am taller",
      "I wish I will be taller"
    ],
    "correct_answer": "I wish I were taller"
  }'

# Reorder quiz questions
curl -X POST "https://your-domain.com/api/teacher/quizzes/35/questions/reorder" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "question_order": [67, 65, 66, 68, 69]
  }'

# Delete quiz
curl -X DELETE "https://your-domain.com/api/teacher/quizzes/35" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

#### Admin Operations Examples

**User Management with Pagination:**

```bash
# Get users with search and filtering
curl -X GET "https://your-domain.com/api/admin/users?page=2&per_page=25&search=ahmed&role=student" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Get specific user details
curl -X GET "https://your-domain.com/api/admin/users/234" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Delete user account
curl -X DELETE "https://your-domain.com/api/admin/users/234" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -w "HTTP Status: %{http_code}\n"
```

**Language and Program Management:**

```bash
# Create new language
curl -X POST "https://your-domain.com/api/admin/languages" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "French",
    "code": "fr",
    "description": "French language courses and materials"
  }'

# Create new program
curl -X POST "https://your-domain.com/api/admin/programs" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "French for Beginners",
    "description": "Comprehensive beginner French course",
    "language_id": 4,
    "level_id": 1,
    "teacher_id": 67
  }'

# Get program statistics
curl -X GET "https://your-domain.com/api/admin/programs/28/statistics" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### Guest Access Examples

**Public Content Access (No Authentication):**

```bash
# Get public languages (if guest access enabled)
curl -X GET "https://your-domain.com/api/guest/languages" \
  -H "Content-Type: application/json"

# Get public teachers
curl -X GET "https://your-domain.com/api/guest/teachers" \
  -H "Content-Type: application/json"

# Get teachers by language
curl -X GET "https://your-domain.com/api/guest/languages/es/teachers" \
  -H "Content-Type: application/json"

# Get public quizzes
curl -X GET "https://your-domain.com/api/guest/quizzes" \
  -H "Content-Type: application/json"

# Submit anonymous quiz attempt
curl -X POST "https://your-domain.com/api/guest/quizzes/18/attempt" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": ["Bonjour", "Merci", "Au revoir", "S'\''il vous plaît", "Excusez-moi"]
  }'
```

### File Upload Examples

#### Teacher Profile Image Upload

**Complete Profile Update with Image:**

```bash
# Upload profile image with form data
curl -X PUT "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "name=Dr. Maria Rodriguez" \
  -F "phone=+34-91-123-4567" \
  -F "preferred_language=es" \
  -F "notify_email=true" \
  -F "notify_whatsapp=false" \
  -F "image=@/Users/maria/Documents/profile-photo.jpg;type=image/jpeg"
```

**Image Upload with Size and Type Validation:**

```bash
# Upload with specific image constraints
curl -X PUT "https://your-domain.com/api/teacher/profile" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "name=Dr. Maria Rodriguez" \
  -F "image=@/path/to/high-quality-photo.png;type=image/png" \
  --form-string "Content-Type=multipart/form-data" \
  -w "Upload Status: %{http_code}\nUpload Time: %{time_total}s\n"
```

**Remove Profile Image:**

```bash
# Delete current profile image
curl -X DELETE "https://your-domain.com/api/teacher/profile/image" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Content-Type: application/json"
```

#### Quiz File Upload

**Upload Quiz as PDF File:**

```bash
# Create file-based quiz
curl -X POST "https://your-domain.com/api/teacher/quizzes" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "title=Spanish Literature Analysis" \
  -F "description=Comprehensive analysis of Spanish literary works" \
  -F "type=file" \
  -F "program_id=15" \
  -F "file=@/path/to/literature-quiz.pdf;type=application/pdf"
```

**Download Quiz File:**

```bash
# Download quiz file
curl -X GET "https://your-domain.com/api/teacher/quizzes/42/download" \
  -H "Authorization: Bearer {teacher_token}" \
  -H "Accept: application/pdf" \
  -o "downloaded-quiz.pdf" \
  -w "Download completed: %{filename_effective}\nFile size: %{size_download} bytes\n"
```

#### Multiple File Upload Example

**Upload Multiple Files (Hypothetical Endpoint):**

```bash
# Upload multiple course materials
curl -X POST "https://your-domain.com/api/teacher/materials" \
  -H "Authorization: Bearer {teacher_token}" \
  -F "course_id=15" \
  -F "materials[]=@/path/to/lesson1.pdf;type=application/pdf" \
  -F "materials[]=@/path/to/lesson2.pdf;type=application/pdf" \
  -F "materials[]=@/path/to/audio-exercise.mp3;type=audio/mpeg" \
  -F "description=Course materials for Spanish Beginners Week 1"
```

### Common Workflow Patterns

#### Error Handling and Retry Pattern

**Robust API Call with Retry Logic:**

```bash
#!/bin/bash

# Function to make API call with retry
make_api_call() {
    local url=$1
    local method=$2
    local data=$3
    local token=$4
    local max_retries=3
    local retry_count=0
    
    while [ $retry_count -lt $max_retries ]; do
        echo "Attempt $((retry_count + 1)) of $max_retries"
        
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" \
            -X "$method" \
            -H "Authorization: Bearer $token" \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$url")
        
        http_code=$(echo $response | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
        body=$(echo $response | sed -e 's/HTTPSTATUS\:.*//g')
        
        if [ "$http_code" -eq 200 ] || [ "$http_code" -eq 201 ]; then
            echo "Success: $body"
            return 0
        elif [ "$http_code" -eq 429 ]; then
            echo "Rate limited, waiting 60 seconds..."
            sleep 60
        elif [ "$http_code" -eq 500 ] || [ "$http_code" -eq 502 ] || [ "$http_code" -eq 503 ]; then
            echo "Server error ($http_code), retrying in 30 seconds..."
            sleep 30
        else
            echo "Client error ($http_code): $body"
            return 1
        fi
        
        retry_count=$((retry_count + 1))
    done
    
    echo "Max retries exceeded"
    return 1
}

# Usage example
make_api_call \
    "https://your-domain.com/api/student/quizzes/18/attempt" \
    "POST" \
    '{"answers": ["Hello", "Good morning", "Thank you"]}' \
    "$STUDENT_TOKEN"
```

#### Batch Operations Pattern

**Bulk User Creation:**

```bash
#!/bin/bash

# Bulk create students from CSV
create_students_from_csv() {
    local csv_file=$1
    local admin_token=$2
    
    # Skip header line and process each student
    tail -n +2 "$csv_file" | while IFS=',' read -r name email phone language; do
        echo "Creating student: $name"
        
        curl -X POST "https://your-domain.com/api/admin/users" \
            -H "Authorization: Bearer $admin_token" \
            -H "Content-Type: application/json" \
            -d "{
                \"name\": \"$name\",
                \"email\": \"$email\",
                \"password\": \"TempPass123!\",
                \"password_confirmation\": \"TempPass123!\",
                \"phone\": \"$phone\",
                \"role\": \"student\",
                \"preferred_language\": \"$language\"
            }" \
            -w "Status: %{http_code}\n" \
            -o /dev/null -s
        
        # Small delay to avoid rate limiting
        sleep 1
    done
}

# Usage: create_students_from_csv students.csv $ADMIN_TOKEN
```

#### Pagination Handling Pattern

**Complete Data Retrieval with Pagination:**

```bash
#!/bin/bash

# Function to get all pages of data
get_all_pages() {
    local base_url=$1
    local token=$2
    local page=1
    local all_data=""
    
    while true; do
        echo "Fetching page $page..."
        
        response=$(curl -s \
            -H "Authorization: Bearer $token" \
            -H "Content-Type: application/json" \
            "$base_url?page=$page&per_page=50")
        
        # Extract data and pagination info
        current_page=$(echo "$response" | jq -r '.data.current_page')
        last_page=$(echo "$response" | jq -r '.data.last_page')
        page_data=$(echo "$response" | jq -r '.data.data[]')
        
        # Append current page data
        all_data="$all_data$page_data"
        
        # Check if we've reached the last page
        if [ "$current_page" -eq "$last_page" ]; then
            break
        fi
        
        page=$((page + 1))
    done
    
    echo "$all_data"
}

# Usage example
all_users=$(get_all_pages "https://your-domain.com/api/admin/users" "$ADMIN_TOKEN")
echo "Retrieved all users: $all_users"
```

#### Health Check and Monitoring Pattern

**System Health Monitoring:**

```bash
#!/bin/bash

# Comprehensive health check
check_system_health() {
    local base_url=$1
    local admin_token=$2
    
    echo "=== Learn Academy System Health Check ==="
    echo "Timestamp: $(date)"
    echo
    
    # Basic health check
    echo "1. API Health Check:"
    health_response=$(curl -s -w "Status: %{http_code}" "$base_url/health")
    echo "$health_response"
    echo
    
    # Authentication test
    echo "2. Authentication Test:"
    auth_response=$(curl -s -w "Status: %{http_code}" \
        -H "Authorization: Bearer $admin_token" \
        "$base_url/auth/profile")
    echo "$auth_response"
    echo
    
    # Database connectivity (via user count)
    echo "3. Database Connectivity:"
    db_response=$(curl -s -w "Status: %{http_code}" \
        -H "Authorization: Bearer $admin_token" \
        "$base_url/admin/users?per_page=1")
    echo "$db_response"
    echo
    
    # Translation system
    echo "4. Translation System:"
    trans_response=$(curl -s -w "Status: %{http_code}" \
        "$base_url/translations/en")
    echo "$trans_response"
    echo
    
    echo "=== Health Check Complete ==="
}

# Usage
check_system_health "https://your-domain.com/api" "$ADMIN_TOKEN"
```

This comprehensive practical usage examples section provides:

1. **Complete User Journey Examples** - End-to-end workflows for students, teachers, and admins
2. **Comprehensive cURL Examples** - Detailed command-line examples for all major operations
3. **File Upload Examples** - Multipart form data handling for images and documents
4. **Common Workflow Patterns** - Reusable patterns for error handling, batch operations, and monitoring
5. **Real-world Integration Examples** - Practical scripts and automation patterns
6. **Error Handling Strategies** - Robust error handling and retry mechanisms
7. **Performance Optimization** - Pagination handling and efficient data retrieval

Each example includes proper authentication, error handling, and follows API best practices that developers can immediately implement in their applications.
```
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            answers: answers,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log("Quiz submitted successfully!");
                console.log(`Score: ${data.data.score}%`);
                console.log(
                    `Status: ${data.data.passed ? "PASSED" : "FAILED"}`
                );
                // Show results to student
                showQuizResults(data.data);
            } else {
                console.error("Submission failed:", data.message);
                if (data.errors) {
                    console.error("Validation errors:", data.errors);
                }
            }
        })
        .catch((error) => console.error("Error submitting quiz:", error));
}
```

---

#### GET /student/quizzes/attempts/my

**Description**: Get all quiz attempts made by the authenticated student across all accessible quizzes

**Authentication**: Required (Student role)

**Purpose**:

-   Provide comprehensive attempt history for student progress tracking
-   Support learning analytics and progress visualization
-   Enable students to review their learning journey
-   Display attempt statistics and improvement trends

**Query Parameters**:

| Parameter  | Type    | Required | Description                | Default | Validation      |
| ---------- | ------- | -------- | -------------------------- | ------- | --------------- |
| `page`     | integer | No       | Page number for pagination | 1       | Min: 1          |
| `per_page` | integer | No       | Items per page             | 15      | Min: 1, Max: 50 |

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 127,
                "quiz_id": 15,
                "student_id": 45,
                "score": 85,
                "percentage_score": 85.0,
                "passed": true,
                "submitted_at": "2024-01-25T14:30:00.000000Z",
                "quiz": {
                    "id": 15,
                    "title": "Spanish Beginner Vocabulary Quiz",
                    "type": "inline",
                    "program": {
                        "id": 8,
                        "title": "Spanish for Beginners",
                        "language": {
                            "name": "Spanish",
                            "code": "es"
                        }
                    }
                }
            },
            {
                "id": 124,
                "quiz_id": 23,
                "student_id": 45,
                "score": 72,
                "percentage_score": 72.0,
                "passed": true,
                "submitted_at": "2024-01-22T16:45:00.000000Z",
                "quiz": {
                    "id": 23,
                    "title": "English Grammar - Present Tense",
                    "type": "inline",
                    "program": {
                        "id": 12,
                        "title": "English Grammar Fundamentals",
                        "language": {
                            "name": "English",
                            "code": "en"
                        }
                    }
                }
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 12,
        "last_page": 1,
        "from": 1,
        "to": 12
    }
}
```

**Empty Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [],
        "current_page": 1,
        "per_page": 15,
        "total": 0,
        "last_page": 1,
        "from": null,
        "to": null
    }
}
```

**Error Response - Server Error (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve quiz attempts.",
    "error": "Internal server error details"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/attempts/my" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
// Load student's attempt history
function loadAttemptHistory() {
    fetch("https://your-domain.com/api/student/quizzes/attempts/my", {
        method: "GET",
        headers: {
            Authorization: `Bearer ${studentToken}`,
            "Content-Type": "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                console.log("Total attempts:", data.data.total);
                data.data.data.forEach((attempt) => {
                    console.log(
                        `${attempt.quiz.title}: ${attempt.score}% (${
                            attempt.passed ? "PASSED" : "FAILED"
                        })`
                    );
                });
                // Render attempt history
                renderAttemptHistory(data.data.data);
            }
        })
        .catch((error) => console.error("Error loading attempts:", error));
}
```

---

#### GET /student/quizzes/{quiz}/attempts/my

**Description**: Get all attempts made by the authenticated student for a specific quiz

**Authentication**: Required (Student role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID

**Purpose**:

-   Show student's attempt history for a specific quiz
-   Enable comparison of multiple attempts on the same quiz
-   Support retry analysis and improvement tracking
-   Provide quiz-specific progress information

**Access Control**:

-   Automatically filters to show only the authenticated student's attempts
-   Verifies student has access to the specified quiz
-   Returns attempts in chronological order (most recent first)

**Success Response (200)**:

```json
{
    "success": true,
    "data": [
        {
            "id": 127,
            "quiz_id": 15,
            "student_id": 45,
            "score": 85,
            "percentage_score": 85.0,
            "passed": true,
            "submitted_at": "2024-01-25T14:30:00.000000Z",
            "quiz": {
                "id": 15,
                "title": "Spanish Beginner Vocabulary Quiz",
                "type": "inline"
            }
        },
        {
            "id": 119,
            "quiz_id": 15,
            "student_id": 45,
            "score": 65,
            "percentage_score": 65.0,
            "passed": false,
            "submitted_at": "2024-01-20T10:15:00.000000Z",
            "quiz": {
                "id": 15,
                "title": "Spanish Beginner Vocabulary Quiz",
                "type": "inline"
            }
        }
    ]
}
```

**Empty Response (200)**:

```json
{
    "success": true,
    "data": []
}
```

**Error Response - Access Denied (403)**:

```json
{
    "success": false,
    "message": "You do not have access to this quiz."
}
```

**Error Response - Quiz Not Found (404)**:

```json
{
    "success": false,
    "message": "Quiz not found"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/15/attempts/my" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /student/quizzes/{quiz}/attempts/{attempt}/results

**Description**: Get detailed results for a specific quiz attempt, including question-by-question breakdown

**Authentication**: Required (Student role)

**Parameters**:

-   **Path**: `quiz` (integer, required) - Quiz ID
-   **Path**: `attempt` (integer, required) - Quiz attempt ID

**Purpose**:

-   Provide detailed feedback on quiz performance
-   Show correct/incorrect answers for learning purposes
-   Enable students to understand their mistakes and learn from them
-   Support detailed progress analysis and improvement planning

**Access Control**:

-   Verifies the attempt belongs to the authenticated student
-   Verifies the attempt belongs to the specified quiz
-   Returns 403 error if student tries to access another student's results
-   Validates quiz access permissions

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "attempt": {
            "id": 127,
            "quiz_id": 15,
            "student_id": 45,
            "score": 85,
            "percentage_score": 85.0,
            "passed": true,
            "submitted_at": "2024-01-25T14:30:00.000000Z"
        },
        "quiz": {
            "id": 15,
            "title": "Spanish Beginner Vocabulary Quiz",
            "description": "Test your knowledge of basic Spanish vocabulary",
            "type": "inline",
            "pass_score": 70
        },
        "results": [
            {
                "question_id": 45,
                "question": "What is the Spanish word for 'hello'?",
                "choices": ["Hola", "Adiós", "Gracias", "Por favor"],
                "student_answer": "Hola",
                "correct_answer": "Hola",
                "is_correct": true,
                "points": 1
            },
            {
                "question_id": 46,
                "question": "How do you say 'thank you' in Spanish?",
                "choices": ["De nada", "Gracias", "Perdón", "Disculpe"],
                "student_answer": "De nada",
                "correct_answer": "Gracias",
                "is_correct": false,
                "points": 0
            }
        ],
        "summary": {
            "total_questions": 20,
            "correct_answers": 17,
            "incorrect_answers": 3,
            "score_percentage": 85.0,
            "pass_threshold": 70,
            "result": "passed"
        }
    }
}
```

**Error Response - Unauthorized Access (403)**:

```json
{
    "success": false,
    "message": "You can only view your own quiz results."
}
```

**Error Response - Attempt Mismatch (400)**:

```json
{
    "success": false,
    "message": "Attempt does not belong to this quiz."
}
```

**Error Response - Attempt Not Found (404)**:

```json
{
    "success": false,
    "message": "Quiz attempt not found"
}
```

**Error Response - Results Retrieval Failed (500)**:

```json
{
    "success": false,
    "message": "Failed to retrieve attempt results.",
    "error": "Internal server error details"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/15/attempts/127/results" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
// Load detailed quiz results
function loadQuizResults(quizId, attemptId) {
    fetch(
        `https://your-domain.com/api/student/quizzes/${quizId}/attempts/${attemptId}/results`,
        {
            method: "GET",
            headers: {
                Authorization: `Bearer ${studentToken}`,
                "Content-Type": "application/json",
            },
        }
    )
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const { attempt, quiz, results, summary } = data.data;

                console.log(`Quiz: ${quiz.title}`);
                console.log(
                    `Score: ${summary.score_percentage}% (${summary.result})`
                );
                console.log(
                    `Correct: ${summary.correct_answers}/${summary.total_questions}`
                );

                // Show question-by-question results
                results.forEach((result, index) => {
                    const status = result.is_correct ? "✓" : "✗";
                    console.log(`${index + 1}. ${status} ${result.question}`);
                    if (!result.is_correct) {
                        console.log(`   Your answer: ${result.student_answer}`);
                        console.log(
                            `   Correct answer: ${result.correct_answer}`
                        );
                    }
                });

                // Render detailed results interface
                renderDetailedResults(data.data);
            }
        })
        .catch((error) => console.error("Error loading results:", error));
}
```

---

### Student Quiz Workflows

#### Complete Quiz Taking Workflow

**Step 1: Discover Available Quizzes**

```bash
curl -X GET "https://your-domain.com/api/student/quizzes" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Load Quiz for Taking**

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/15" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Submit Quiz Attempt**

```bash
curl -X POST "https://your-domain.com/api/student/quizzes/15/attempt" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": ["Hola", "Gracias", "Buenos días", "De nada", "Por favor"]
  }'
```

**Step 4: View Detailed Results**

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/15/attempts/127/results" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

#### Progress Tracking Workflow

**View All Attempts Across Quizzes**:

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/attempts/my" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

**View Attempts for Specific Quiz**:

```bash
curl -X GET "https://your-domain.com/api/student/quizzes/15/attempts/my" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

#### JavaScript Complete Integration Example

```javascript
class StudentQuizManager {
    constructor(apiBaseUrl, authToken) {
        this.apiBaseUrl = apiBaseUrl;
        this.authToken = authToken;
    }

    async getAvailableQuizzes() {
        const response = await fetch(`${this.apiBaseUrl}/student/quizzes`, {
            headers: {
                Authorization: `Bearer ${this.authToken}`,
                "Content-Type": "application/json",
            },
        });
        return response.json();
    }

    async loadQuiz(quizId) {
        const response = await fetch(
            `${this.apiBaseUrl}/student/quizzes/${quizId}`,
            {
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
            }
        );
        return response.json();
    }

    async submitAttempt(quizId, answers) {
        const response = await fetch(
            `${this.apiBaseUrl}/student/quizzes/${quizId}/attempt`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ answers }),
            }
        );
        return response.json();
    }

    async getAttemptHistory() {
        const response = await fetch(
            `${this.apiBaseUrl}/student/quizzes/attempts/my`,
            {
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
            }
        );
        return response.json();
    }

    async getAttemptResults(quizId, attemptId) {
        const response = await fetch(
            `${this.apiBaseUrl}/student/quizzes/${quizId}/attempts/${attemptId}/results`,
            {
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
            }
        );
        return response.json();
    }
}

// Usage example
const quizManager = new StudentQuizManager(
    "https://your-domain.com/api",
    studentToken
);

// Load and take a quiz
async function takeQuiz(quizId) {
    try {
        // Load quiz details
        const quizData = await quizManager.loadQuiz(quizId);
        if (!quizData.success) {
            throw new Error(quizData.message);
        }

        // Display quiz to student and collect answers
        const answers = await collectStudentAnswers(quizData.data);

        // Submit attempt
        const attemptResult = await quizManager.submitAttempt(quizId, answers);
        if (attemptResult.success) {
            console.log(`Quiz completed! Score: ${attemptResult.data.score}%`);

            // Load detailed results
            const detailedResults = await quizManager.getAttemptResults(
                quizId,
                attemptResult.data.attempt_id
            );
            displayResults(detailedResults.data);
        }
    } catch (error) {
        console.error("Quiz taking failed:", error);
    }
}
```

---

### Enrollment & Programs

The Enrollment & Programs system allows students to view their enrollment status and access approved programs. Students can see which programs they are enrolled in, check their enrollment approval status, and access program-specific content based on their approved enrollments.

#### Student Enrollment Data Schema

**Enrollment Object**:

| Field               | Type           | Description                  | Notes                               |
| ------------------- | -------------- | ---------------------------- | ----------------------------------- |
| `id`                | integer        | Unique enrollment identifier | Primary key                         |
| `student_id`        | integer        | Student user ID              | Foreign key                         |
| `program_id`        | integer        | Program ID                   | Foreign key                         |
| `access_granted_at` | datetime\|null | Approval timestamp           | null if pending                     |
| `created_at`        | datetime       | Enrollment request timestamp | ISO 8601 format                     |
| `updated_at`        | datetime       | Last modification timestamp  | ISO 8601 format                     |
| `program`           | object         | Program information          | Includes language and level details |

**Program Object (Student View)**:

| Field            | Type         | Description                 | Notes                     |
| ---------------- | ------------ | --------------------------- | ------------------------- |
| `id`             | integer      | Unique program identifier   | Primary key               |
| `title`          | string       | Program title               | Display name              |
| `description`    | string\|null | Program description         | Optional details          |
| `language`       | object       | Language information        | Name and code             |
| `level`          | object       | Level information           | Name and description      |
| `teacher`        | object       | Teacher information         | Name and profile          |
| `students_count` | integer      | Total enrolled students     | Approved enrollments only |
| `created_at`     | datetime     | Program creation timestamp  | ISO 8601 format           |
| `updated_at`     | datetime     | Last modification timestamp | ISO 8601 format           |

---

#### GET /student/enrollments

**Description**: Get all enrollments for the authenticated student, including both pending and approved enrollments

**Authentication**: Required (Student role)

**Purpose**:

-   Display student's enrollment status across all programs
-   Show both pending (awaiting approval) and approved enrollments
-   Provide enrollment timeline and approval information
-   Support enrollment management and status tracking

**Query Parameters**:

| Parameter  | Type    | Required | Description                 | Default | Validation                   |
| ---------- | ------- | -------- | --------------------------- | ------- | ---------------------------- |
| `page`     | integer | No       | Page number for pagination  | 1       | Min: 1                       |
| `per_page` | integer | No       | Items per page              | 15      | Min: 1, Max: 50              |
| `status`   | string  | No       | Filter by enrollment status | all     | "pending", "approved", "all" |

**Access Control**:

-   Students can only view their own enrollments
-   Includes both pending and approved enrollment records
-   Provides program details for context and navigation

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 45,
                "student_id": 123,
                "program_id": 8,
                "access_granted_at": "2024-01-20T14:30:00.000000Z",
                "created_at": "2024-01-15T10:00:00.000000Z",
                "updated_at": "2024-01-20T14:30:00.000000Z",
                "program": {
                    "id": 8,
                    "title": "Spanish for Beginners",
                    "description": "Comprehensive beginner Spanish course covering basic vocabulary, grammar, and conversation skills",
                    "language": {
                        "id": 2,
                        "name": "Spanish",
                        "code": "es"
                    },
                    "level": {
                        "id": 1,
                        "name": "Beginner",
                        "description": "Basic level for new learners"
                    },
                    "teacher": {
                        "id": 15,
                        "name": "Maria Rodriguez",
                        "email": "maria.rodriguez@example.com"
                    },
                    "students_count": 24,
                    "created_at": "2024-01-10T09:00:00.000000Z",
                    "updated_at": "2024-01-18T16:45:00.000000Z"
                }
            },
            {
                "id": 52,
                "student_id": 123,
                "program_id": 12,
                "access_granted_at": null,
                "created_at": "2024-01-22T11:15:00.000000Z",
                "updated_at": "2024-01-22T11:15:00.000000Z",
                "program": {
                    "id": 12,
                    "title": "English Grammar Fundamentals",
                    "description": "Essential English grammar concepts for intermediate learners",
                    "language": {
                        "id": 1,
                        "name": "English",
                        "code": "en"
                    },
                    "level": {
                        "id": 2,
                        "name": "Intermediate",
                        "description": "For learners with basic knowledge"
                    },
                    "teacher": {
                        "id": 8,
                        "name": "John Smith",
                        "email": "john.smith@example.com"
                    },
                    "students_count": 18,
                    "created_at": "2024-01-12T14:20:00.000000Z",
                    "updated_at": "2024-01-20T10:30:00.000000Z"
                }
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 2,
        "last_page": 1,
        "from": 1,
        "to": 2
    }
}
```

**Response Details**:

-   **access_granted_at**: `null` indicates pending enrollment, timestamp indicates approved enrollment
-   **program**: Complete program information including language, level, and teacher details
-   **students_count**: Number of students with approved enrollment in the program

**Filtered Response Examples**:

**Pending Enrollments Only** (`?status=pending`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 52,
                "student_id": 123,
                "program_id": 12,
                "access_granted_at": null,
                "created_at": "2024-01-22T11:15:00.000000Z",
                "updated_at": "2024-01-22T11:15:00.000000Z",
                "program": {
                    "id": 12,
                    "title": "English Grammar Fundamentals",
                    "description": "Essential English grammar concepts for intermediate learners"
                }
            }
        ]
    }
}
```

**Approved Enrollments Only** (`?status=approved`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 45,
                "student_id": 123,
                "program_id": 8,
                "access_granted_at": "2024-01-20T14:30:00.000000Z",
                "created_at": "2024-01-15T10:00:00.000000Z",
                "updated_at": "2024-01-20T14:30:00.000000Z",
                "program": {
                    "id": 8,
                    "title": "Spanish for Beginners",
                    "description": "Comprehensive beginner Spanish course"
                }
            }
        ]
    }
}
```

**Error Responses**:

**Authentication Error (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: student"
}
```

**Validation Error (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "status": ["The status field must be one of: pending, approved, all."],
        "per_page": ["The per page field must be between 1 and 50."]
    }
}
```

**cURL Examples**:

```bash
# Get all enrollments
curl -X GET "https://your-domain.com/api/student/enrollments" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Get only pending enrollments
curl -X GET "https://your-domain.com/api/student/enrollments?status=pending" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Get approved enrollments with pagination
curl -X GET "https://your-domain.com/api/student/enrollments?status=approved&page=1&per_page=10" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /student/programs

**Description**: Get all approved programs for the authenticated student (programs with granted access only)

**Authentication**: Required (Student role)

**Purpose**:

-   Display programs the student has access to for learning activities
-   Show only approved programs where access has been granted
-   Provide program details for navigation to quizzes and meetings
-   Support learning dashboard and program selection

**Query Parameters**:

| Parameter  | Type    | Required | Description                | Default | Validation          |
| ---------- | ------- | -------- | -------------------------- | ------- | ------------------- |
| `page`     | integer | No       | Page number for pagination | 1       | Min: 1              |
| `per_page` | integer | No       | Items per page             | 15      | Min: 1, Max: 50     |
| `language` | string  | No       | Filter by language code    | all     | Valid language code |
| `level`    | integer | No       | Filter by level ID         | all     | Valid level ID      |

**Access Control**:

-   Students can only view programs they have approved enrollment for
-   Only shows programs where `access_granted_at` is not null
-   Automatically filters based on student's approved enrollments

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 8,
                "title": "Spanish for Beginners",
                "description": "Comprehensive beginner Spanish course covering basic vocabulary, grammar, and conversation skills. Perfect for students starting their Spanish learning journey.",
                "language": {
                    "id": 2,
                    "name": "Spanish",
                    "code": "es"
                },
                "level": {
                    "id": 1,
                    "name": "Beginner",
                    "description": "Basic level for new learners"
                },
                "teacher": {
                    "id": 15,
                    "name": "Maria Rodriguez",
                    "email": "maria.rodriguez@example.com",
                    "profile_image": "https://your-domain.com/storage/teachers/maria-rodriguez.jpg"
                },
                "students_count": 24,
                "enrollment": {
                    "id": 45,
                    "access_granted_at": "2024-01-20T14:30:00.000000Z",
                    "created_at": "2024-01-15T10:00:00.000000Z"
                },
                "created_at": "2024-01-10T09:00:00.000000Z",
                "updated_at": "2024-01-18T16:45:00.000000Z"
            },
            {
                "id": 15,
                "title": "French Conversation Practice",
                "description": "Interactive French conversation sessions focusing on practical communication skills and pronunciation improvement.",
                "language": {
                    "id": 3,
                    "name": "French",
                    "code": "fr"
                },
                "level": {
                    "id": 2,
                    "name": "Intermediate",
                    "description": "For learners with basic knowledge"
                },
                "teacher": {
                    "id": 22,
                    "name": "Pierre Dubois",
                    "email": "pierre.dubois@example.com",
                    "profile_image": null
                },
                "students_count": 16,
                "enrollment": {
                    "id": 67,
                    "access_granted_at": "2024-01-18T09:15:00.000000Z",
                    "created_at": "2024-01-16T14:20:00.000000Z"
                },
                "created_at": "2024-01-08T11:30:00.000000Z",
                "updated_at": "2024-01-19T13:45:00.000000Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 2,
        "last_page": 1,
        "from": 1,
        "to": 2
    }
}
```

**Response Details**:

-   **enrollment**: Student's enrollment information including approval timestamp
-   **teacher**: Complete teacher information including profile image if available
-   **students_count**: Total number of approved students in the program
-   Only programs with approved enrollment are included

**Filtered Response Examples**:

**Filter by Language** (`?language=es`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 8,
                "title": "Spanish for Beginners",
                "language": {
                    "id": 2,
                    "name": "Spanish",
                    "code": "es"
                }
            }
        ]
    }
}
```

**Filter by Level** (`?level=1`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 8,
                "title": "Spanish for Beginners",
                "level": {
                    "id": 1,
                    "name": "Beginner",
                    "description": "Basic level for new learners"
                }
            }
        ]
    }
}
```

**Empty Response (No Approved Programs)**:

```json
{
    "success": true,
    "data": {
        "data": [],
        "current_page": 1,
        "per_page": 15,
        "total": 0,
        "last_page": 1,
        "from": null,
        "to": null
    }
}
```

**Error Responses**:

**Authentication Error (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: student"
}
```

**Validation Error (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "language": ["The selected language is invalid."],
        "level": ["The selected level is invalid."],
        "per_page": ["The per page field must be between 1 and 50."]
    }
}
```

**cURL Examples**:

```bash
# Get all approved programs
curl -X GET "https://your-domain.com/api/student/programs" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Filter by Spanish language
curl -X GET "https://your-domain.com/api/student/programs?language=es" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Filter by beginner level with pagination
curl -X GET "https://your-domain.com/api/student/programs?level=1&page=1&per_page=5" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

---

### Meeting Access

The Meeting Access system allows students to view and participate in meetings for their approved programs. Students can see upcoming meetings, view meeting details, and access meetings they are enrolled in through their approved program enrollments.

#### Student Meeting Data Schema

**Meeting Object (Student View)**:

| Field              | Type         | Description                    | Notes                       |
| ------------------ | ------------ | ------------------------------ | --------------------------- |
| `id`               | integer      | Unique meeting identifier      | Primary key                 |
| `title`            | string       | Meeting title                  | Display name                |
| `description`      | string\|null | Meeting description            | Optional details            |
| `meeting_date`     | datetime     | Scheduled meeting date/time    | ISO 8601 format             |
| `duration_minutes` | integer      | Meeting duration in minutes    | Estimated duration          |
| `meeting_link`     | string\|null | Meeting URL or link            | Zoom, Teams, etc.           |
| `program`          | object       | Associated program information | Includes language and level |
| `teacher`          | object       | Meeting host teacher           | Name and contact info       |
| `created_at`       | datetime     | Meeting creation timestamp     | ISO 8601 format             |
| `updated_at`       | datetime     | Last modification timestamp    | ISO 8601 format             |

---

#### GET /student/meetings

**Description**: Get all meetings for the authenticated student based on approved program enrollments

**Authentication**: Required (Student role)

**Purpose**:

-   Display meetings the student can attend based on their approved program enrollments
-   Show meeting schedule and details for planning and participation
-   Provide meeting access links and teacher information
-   Support meeting calendar and attendance tracking

**Query Parameters**:

| Parameter    | Type    | Required | Description                      | Default | Validation       |
| ------------ | ------- | -------- | -------------------------------- | ------- | ---------------- |
| `page`       | integer | No       | Page number for pagination       | 1       | Min: 1           |
| `per_page`   | integer | No       | Items per page                   | 15      | Min: 1, Max: 50  |
| `upcoming`   | boolean | No       | Filter to upcoming meetings only | false   | true/false       |
| `program_id` | integer | No       | Filter by specific program       | all     | Valid program ID |

**Access Control**:

-   Students can only view meetings for programs they have approved enrollment in
-   Automatically filters meetings based on student's approved program enrollments
-   Only shows meetings the student is eligible to attend

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 34,
                "title": "Spanish Conversation Practice - Week 3",
                "description": "Interactive conversation session focusing on daily activities vocabulary and present tense usage. Bring questions about homework exercises.",
                "meeting_date": "2024-01-25T15:00:00.000000Z",
                "duration_minutes": 60,
                "meeting_link": "https://zoom.us/j/123456789?pwd=abcdef123456",
                "program": {
                    "id": 8,
                    "title": "Spanish for Beginners",
                    "language": {
                        "id": 2,
                        "name": "Spanish",
                        "code": "es"
                    },
                    "level": {
                        "id": 1,
                        "name": "Beginner",
                        "description": "Basic level for new learners"
                    }
                },
                "teacher": {
                    "id": 15,
                    "name": "Maria Rodriguez",
                    "email": "maria.rodriguez@example.com",
                    "profile_image": "https://your-domain.com/storage/teachers/maria-rodriguez.jpg"
                },
                "created_at": "2024-01-20T10:30:00.000000Z",
                "updated_at": "2024-01-22T14:15:00.000000Z"
            },
            {
                "id": 41,
                "title": "French Grammar Review Session",
                "description": "Review of past tense constructions and irregular verbs. We'll practice with real-world examples and common expressions.",
                "meeting_date": "2024-01-28T14:30:00.000000Z",
                "duration_minutes": 45,
                "meeting_link": "https://teams.microsoft.com/l/meetup-join/19%3ameeting_abc123",
                "program": {
                    "id": 15,
                    "title": "French Conversation Practice",
                    "language": {
                        "id": 3,
                        "name": "French",
                        "code": "fr"
                    },
                    "level": {
                        "id": 2,
                        "name": "Intermediate",
                        "description": "For learners with basic knowledge"
                    }
                },
                "teacher": {
                    "id": 22,
                    "name": "Pierre Dubois",
                    "email": "pierre.dubois@example.com",
                    "profile_image": null
                },
                "created_at": "2024-01-21T09:45:00.000000Z",
                "updated_at": "2024-01-23T11:20:00.000000Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 2,
        "last_page": 1,
        "from": 1,
        "to": 2
    }
}
```

**Response Details**:

-   **meeting_link**: Direct link to join the meeting (Zoom, Teams, etc.)
-   **program**: Program information to provide context for the meeting
-   **teacher**: Meeting host information for contact and identification
-   **meeting_date**: UTC timestamp for meeting scheduling

**Filtered Response Examples**:

**Upcoming Meetings Only** (`?upcoming=true`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 34,
                "title": "Spanish Conversation Practice - Week 3",
                "meeting_date": "2024-01-25T15:00:00.000000Z",
                "duration_minutes": 60,
                "meeting_link": "https://zoom.us/j/123456789?pwd=abcdef123456"
            }
        ]
    }
}
```

**Filter by Program** (`?program_id=8`):

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 34,
                "title": "Spanish Conversation Practice - Week 3",
                "program": {
                    "id": 8,
                    "title": "Spanish for Beginners"
                }
            }
        ]
    }
}
```

**Empty Response (No Accessible Meetings)**:

```json
{
    "success": true,
    "data": {
        "data": [],
        "current_page": 1,
        "per_page": 15,
        "total": 0,
        "last_page": 1,
        "from": null,
        "to": null
    }
}
```

**Error Responses**:

**Authentication Error (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: student"
}
```

**Validation Error (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "program_id": ["The selected program id is invalid."],
        "per_page": ["The per page field must be between 1 and 50."],
        "upcoming": ["The upcoming field must be true or false."]
    }
}
```

**cURL Examples**:

```bash
# Get all accessible meetings
curl -X GET "https://your-domain.com/api/student/meetings" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Get upcoming meetings only
curl -X GET "https://your-domain.com/api/student/meetings?upcoming=true" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Filter by specific program
curl -X GET "https://your-domain.com/api/student/meetings?program_id=8" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /student/meetings/upcoming

**Description**: Get upcoming meetings for the authenticated student (meetings scheduled for future dates)

**Authentication**: Required (Student role)

**Purpose**:

-   Quick access to upcoming meetings for dashboard display
-   Meeting reminders and schedule planning
-   Simplified endpoint for calendar integration
-   Focus on immediate meeting needs

**Query Parameters**:

| Parameter    | Type    | Required | Description                          | Default | Validation      |
| ------------ | ------- | -------- | ------------------------------------ | ------- | --------------- |
| `limit`      | integer | No       | Maximum number of meetings to return | 10      | Min: 1, Max: 50 |
| `days_ahead` | integer | No       | Number of days to look ahead         | 7       | Min: 1, Max: 30 |

**Access Control**:

-   Students can only view upcoming meetings for programs they have approved enrollment in
-   Automatically filters to meetings with `meeting_date` greater than current timestamp
-   Orders meetings by date (earliest first)

**Success Response (200)**:

```json
{
    "success": true,
    "data": [
        {
            "id": 34,
            "title": "Spanish Conversation Practice - Week 3",
            "description": "Interactive conversation session focusing on daily activities vocabulary and present tense usage.",
            "meeting_date": "2024-01-25T15:00:00.000000Z",
            "duration_minutes": 60,
            "meeting_link": "https://zoom.us/j/123456789?pwd=abcdef123456",
            "program": {
                "id": 8,
                "title": "Spanish for Beginners",
                "language": {
                    "name": "Spanish",
                    "code": "es"
                }
            },
            "teacher": {
                "id": 15,
                "name": "Maria Rodriguez",
                "email": "maria.rodriguez@example.com"
            },
            "time_until_meeting": "2 days, 3 hours",
            "is_today": false,
            "is_soon": false
        },
        {
            "id": 41,
            "title": "French Grammar Review Session",
            "description": "Review of past tense constructions and irregular verbs.",
            "meeting_date": "2024-01-28T14:30:00.000000Z",
            "duration_minutes": 45,
            "meeting_link": "https://teams.microsoft.com/l/meetup-join/19%3ameeting_abc123",
            "program": {
                "id": 15,
                "title": "French Conversation Practice",
                "language": {
                    "name": "French",
                    "code": "fr"
                }
            },
            "teacher": {
                "id": 22,
                "name": "Pierre Dubois",
                "email": "pierre.dubois@example.com"
            },
            "time_until_meeting": "5 days, 2 hours",
            "is_today": false,
            "is_soon": false
        }
    ]
}
```

**Response Details**:

-   **time_until_meeting**: Human-readable time until meeting starts
-   **is_today**: Boolean indicating if meeting is scheduled for today
-   **is_soon**: Boolean indicating if meeting starts within 1 hour
-   Meetings are ordered by `meeting_date` (earliest first)

**Limited Response Example** (`?limit=1`):

```json
{
    "success": true,
    "data": [
        {
            "id": 34,
            "title": "Spanish Conversation Practice - Week 3",
            "meeting_date": "2024-01-25T15:00:00.000000Z",
            "time_until_meeting": "2 days, 3 hours",
            "is_today": false,
            "is_soon": false
        }
    ]
}
```

**Empty Response (No Upcoming Meetings)**:

```json
{
    "success": true,
    "data": []
}
```

**cURL Examples**:

```bash
# Get next 5 upcoming meetings
curl -X GET "https://your-domain.com/api/student/meetings/upcoming?limit=5" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"

# Get meetings for next 3 days
curl -X GET "https://your-domain.com/api/student/meetings/upcoming?days_ahead=3" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /student/meetings/{meeting}

**Description**: Get detailed information about a specific meeting the student has access to

**Authentication**: Required (Student role)

**Parameters**:

-   **Path**: `meeting` (integer, required) - Meeting ID

**Purpose**:

-   View detailed meeting information before joining
-   Access meeting materials and preparation instructions
-   Verify meeting details and access permissions
-   Support meeting participation workflow

**Access Control**:

-   Students can only view meetings for programs they have approved enrollment in
-   Meeting must be associated with a program the student is enrolled in
-   Returns 404 if meeting doesn't exist or student doesn't have access

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "id": 34,
        "title": "Spanish Conversation Practice - Week 3",
        "description": "Interactive conversation session focusing on daily activities vocabulary and present tense usage. Please review Chapter 5 vocabulary before attending. Bring questions about homework exercises from pages 45-50.",
        "meeting_date": "2024-01-25T15:00:00.000000Z",
        "duration_minutes": 60,
        "meeting_link": "https://zoom.us/j/123456789?pwd=abcdef123456",
        "meeting_password": "Spanish123",
        "preparation_notes": "Review vocabulary from Chapter 5, complete exercises 1-3 on page 47, prepare 3 questions about daily routines",
        "program": {
            "id": 8,
            "title": "Spanish for Beginners",
            "description": "Comprehensive beginner Spanish course covering basic vocabulary, grammar, and conversation skills",
            "language": {
                "id": 2,
                "name": "Spanish",
                "code": "es"
            },
            "level": {
                "id": 1,
                "name": "Beginner",
                "description": "Basic level for new learners"
            }
        },
        "teacher": {
            "id": 15,
            "name": "Maria Rodriguez",
            "email": "maria.rodriguez@example.com",
            "profile_image": "https://your-domain.com/storage/teachers/maria-rodriguez.jpg"
        },
        "enrollment": {
            "id": 45,
            "access_granted_at": "2024-01-20T14:30:00.000000Z"
        },
        "time_until_meeting": "2 days, 3 hours",
        "is_today": false,
        "is_soon": false,
        "can_join": true,
        "created_at": "2024-01-20T10:30:00.000000Z",
        "updated_at": "2024-01-22T14:15:00.000000Z"
    }
}
```

**Response Details**:

-   **meeting_password**: Meeting password if required for joining
-   **preparation_notes**: Teacher's notes on meeting preparation
-   **enrollment**: Student's enrollment information for the program
-   **can_join**: Boolean indicating if student can join the meeting
-   **time_until_meeting**: Human-readable time until meeting starts

**Error Responses**:

**Authentication Error (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: student"
}
```

**Meeting Not Found or No Access (404)**:

```json
{
    "success": false,
    "message": "Meeting not found or you don't have access to this meeting."
}
```

**cURL Example**:

```bash
# Get specific meeting details
curl -X GET "https://your-domain.com/api/student/meetings/34" \
  -H "Authorization: Bearer {student_token}" \
  -H "Content-Type: application/json"
```

---

### Student Access Control & Data Filtering

#### Enrollment-Based Access Control

All student endpoints implement automatic filtering based on approved program enrollments:

**Enrollment Validation Process**:

1. **Authentication Check**: Verify student has valid Bearer token
2. **Role Verification**: Confirm user has `student` role
3. **Enrollment Lookup**: Query student's enrollments with `access_granted_at` not null
4. **Resource Filtering**: Filter quizzes, meetings, and programs based on approved enrollments
5. **Access Decision**: Grant or deny access based on enrollment status

**Access Control Examples**:

```php
// Pseudo-code for enrollment-based filtering
$approvedProgramIds = $student->enrollments()
    ->whereNotNull('access_granted_at')
    ->pluck('program_id');

$accessibleQuizzes = Quiz::whereIn('program_id', $approvedProgramIds)
    ->where('active', true)
    ->get();

$accessibleMeetings = Meeting::whereIn('program_id', $approvedProgramIds)
    ->get();
```

#### Data Privacy & Security

**Student Data Isolation**:

-   Students can only access their own enrollment records
-   Quiz attempts are filtered by student ownership
-   Meeting access is limited to enrolled programs only
-   Personal information is protected from other students

**Resource Ownership Validation**:

-   Quiz attempts: `student_id` must match authenticated user
-   Enrollments: `student_id` must match authenticated user
-   Meeting access: Must have approved enrollment in meeting's program

**Security Measures**:

-   All endpoints require authentication via Bearer token
-   Role-based access control prevents privilege escalation
-   Automatic filtering prevents data leakage between students
-   Input validation prevents injection attacks

#### Error Handling for Access Control

**Common Access Control Errors**:

**No Approved Enrollments**:

```json
{
    "success": true,
    "data": {
        "data": [],
        "message": "No approved program enrollments found. Please contact an administrator to approve your enrollment requests."
    }
}
```

**Enrollment Pending Approval**:

```json
{
    "success": false,
    "message": "Your enrollment is pending approval. You will receive access once an administrator approves your enrollment request."
}
```

**Program Access Revoked**:

```json
{
    "success": false,
    "message": "Your access to this program has been revoked. Please contact an administrator for more information."
}
```

---

## Notification System

The Learn Academy API provides a comprehensive notification system that manages email and WhatsApp notifications for users. The system tracks notification history, provides statistics, and allows users to manage their notification preferences. Administrators have additional capabilities for system-wide notification management and cleanup operations.

### Notification Types & Channels

The notification system supports multiple notification types and delivery channels:

**Notification Types**:

-   `enrollment_approved` - When a student's enrollment is approved
-   `meeting_scheduled` - When a new meeting is scheduled
-   `meeting_updated` - When meeting details are changed
-   `meeting_cancelled` - When a meeting is cancelled
-   `quiz_assigned` - When a new quiz is assigned to a student
-   `quiz_reminder` - Reminder notifications for pending quizzes
-   `system_announcement` - System-wide announcements

**Delivery Channels**:

-   `email` - Email notifications via configured mail service
-   `whatsapp` - WhatsApp notifications via configured service
-   `database` - In-app notifications stored in database

**Notification Status**:

-   `pending` - Notification queued for delivery
-   `sent` - Successfully delivered
-   `failed` - Delivery failed (with error details)
-   `retrying` - Currently being retried after failure

---

### User Notification Endpoints

These endpoints allow authenticated users to manage their notification history and preferences.

#### GET /notifications/history

**Description**: Retrieve the authenticated user's notification history with pagination support

**Authentication**: Required (Any authenticated user)

**Purpose**:

-   View personal notification history for audit and tracking
-   Monitor delivery status of sent notifications
-   Access notification details and timestamps
-   Support pagination for large notification histories

**Query Parameters**:

| Parameter | Type    | Required | Description                      | Default |
| --------- | ------- | -------- | -------------------------------- | ------- |
| `limit`   | integer | No       | Number of notifications per page | 50      |
| `page`    | integer | No       | Page number for pagination       | 1       |

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "notifications": [
            {
                "id": 123,
                "type": "enrollment_approved",
                "channel": "email",
                "status": "sent",
                "payload": {
                    "program_name": "Advanced English Course",
                    "message": "Your enrollment has been approved"
                },
                "sent_at": "2024-01-15T10:30:00.000000Z",
                "created_at": "2024-01-15T10:25:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            },
            {
                "id": 124,
                "type": "meeting_scheduled",
                "channel": "whatsapp",
                "status": "failed",
                "payload": {
                    "meeting_title": "Grammar Review Session",
                    "scheduled_at": "2024-01-20T14:00:00.000000Z"
                },
                "sent_at": null,
                "error_message": "Invalid phone number format",
                "created_at": "2024-01-15T11:00:00.000000Z",
                "updated_at": "2024-01-15T11:05:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 50,
            "total": 127,
            "has_more_pages": true
        }
    }
}
```

**Response Details**:

-   **notifications**: Array of notification objects with delivery details
-   **id**: Unique notification identifier
-   **type**: Notification category (enrollment_approved, meeting_scheduled, etc.)
-   **channel**: Delivery method (email, whatsapp, database)
-   **status**: Current delivery status (pending, sent, failed, retrying)
-   **payload**: Notification content and metadata (visible to notification owner)
-   **sent_at**: Timestamp when notification was successfully delivered (null if failed)
-   **error_message**: Error details for failed notifications (visible to notification owner)
-   **pagination**: Standard pagination metadata for navigation

**Authentication Error (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**cURL Example**:

```bash
# Get notification history with default pagination
curl -X GET "https://your-domain.com/api/notifications/history" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json"

# Get specific page with custom limit
curl -X GET "https://your-domain.com/api/notifications/history?page=2&limit=25" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /notifications/stats

**Description**: Get notification statistics for the authenticated user including delivery success rates and channel preferences

**Authentication**: Required (Any authenticated user)

**Purpose**:

-   Monitor personal notification delivery performance
-   View notification preferences and usage patterns
-   Track notification volume and success rates
-   Identify potential delivery issues

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "total_notifications": 127,
        "by_status": {
            "sent": 118,
            "failed": 6,
            "pending": 2,
            "retrying": 1
        },
        "by_channel": {
            "email": 89,
            "whatsapp": 32,
            "database": 6
        },
        "by_type": {
            "enrollment_approved": 3,
            "meeting_scheduled": 45,
            "meeting_updated": 12,
            "meeting_cancelled": 8,
            "quiz_assigned": 34,
            "quiz_reminder": 18,
            "system_announcement": 7
        },
        "success_rate": {
            "overall": 92.9,
            "email": 96.6,
            "whatsapp": 84.4
        },
        "preferences": {
            "notify_email": true,
            "notify_whatsapp": false
        },
        "recent_activity": {
            "last_notification": "2024-01-15T11:00:00.000000Z",
            "notifications_last_7_days": 12,
            "notifications_last_30_days": 45
        }
    }
}
```

**Response Details**:

-   **total_notifications**: Total number of notifications sent to user
-   **by_status**: Breakdown of notifications by delivery status
-   **by_channel**: Distribution across delivery channels
-   **by_type**: Categorization by notification type
-   **success_rate**: Delivery success percentages (overall and by channel)
-   **preferences**: Current user notification preferences
-   **recent_activity**: Recent notification activity metrics

**Authentication Error (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/notifications/stats" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json"
```

---

#### PUT /notifications/preferences

**Description**: Update the authenticated user's notification preferences for email and WhatsApp channels

**Authentication**: Required (Any authenticated user)

**Purpose**:

-   Allow users to control their notification preferences
-   Enable or disable specific notification channels
-   Provide granular control over notification delivery
-   Support user privacy and communication preferences

**Request Body Parameters**:

| Field             | Type    | Required | Description                           |
| ----------------- | ------- | -------- | ------------------------------------- |
| `notify_email`    | boolean | No       | Enable/disable email notifications    |
| `notify_whatsapp` | boolean | No       | Enable/disable WhatsApp notifications |

**Request Body Example**:

```json
{
    "notify_email": true,
    "notify_whatsapp": false
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Notification preferences updated successfully",
    "data": {
        "notify_email": true,
        "notify_whatsapp": false
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "notify_email": [
            "Email notification preference must be true or false."
        ],
        "notify_whatsapp": [
            "WhatsApp notification preference must be true or false."
        ]
    }
}
```

**Authentication Error (401)**:

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

**cURL Examples**:

```bash
# Enable email notifications, disable WhatsApp
curl -X PUT "https://your-domain.com/api/notifications/preferences" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "notify_email": true,
    "notify_whatsapp": false
  }'

# Update only email preference
curl -X PUT "https://your-domain.com/api/notifications/preferences" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "notify_email": false
  }'
```

---

### Administrative Notification Management

These endpoints provide administrators with system-wide notification management capabilities including statistics, user notification history, and maintenance operations.

#### GET /notifications/system/stats

**Description**: Get comprehensive system-wide notification statistics and performance metrics

**Authentication**: Required (Admin role)

**Purpose**:

-   Monitor overall notification system performance
-   Track delivery success rates across all users
-   Identify system-wide notification issues
-   Generate reports for system health monitoring

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "total_notifications": 15847,
        "total_users": 342,
        "by_status": {
            "sent": 14523,
            "failed": 892,
            "pending": 287,
            "retrying": 145
        },
        "by_channel": {
            "email": 11234,
            "whatsapp": 3876,
            "database": 737
        },
        "by_type": {
            "enrollment_approved": 1247,
            "meeting_scheduled": 5634,
            "meeting_updated": 2341,
            "meeting_cancelled": 876,
            "quiz_assigned": 3456,
            "quiz_reminder": 1823,
            "system_announcement": 470
        },
        "success_rate": {
            "overall": 91.7,
            "email": 94.2,
            "whatsapp": 86.8,
            "database": 99.1
        },
        "performance_metrics": {
            "average_delivery_time": "2.3 seconds",
            "failed_notifications_last_24h": 23,
            "retry_queue_size": 145,
            "notifications_last_hour": 67
        },
        "user_preferences": {
            "email_enabled": 298,
            "whatsapp_enabled": 156,
            "both_enabled": 134,
            "both_disabled": 18
        }
    }
}
```

**Response Details**:

-   **total_notifications**: System-wide notification count
-   **total_users**: Number of users in the system
-   **by_status**: Global status distribution
-   **by_channel**: Channel usage statistics
-   **by_type**: Notification type breakdown
-   **success_rate**: System-wide delivery success rates
-   **performance_metrics**: System performance indicators
-   **user_preferences**: User preference distribution

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/notifications/system/stats" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### GET /notifications/users/{user}/history

**Description**: Retrieve notification history for a specific user (admin access)

**Authentication**: Required (Admin role)

**Parameters**:

-   **Path**: `user` (integer, required) - User ID to retrieve history for

**Query Parameters**:

| Parameter | Type    | Required | Description                      | Default |
| --------- | ------- | -------- | -------------------------------- | ------- |
| `limit`   | integer | No       | Number of notifications per page | 50      |
| `page`    | integer | No       | Page number for pagination       | 1       |

**Purpose**:

-   Administrative access to user notification history
-   Troubleshoot notification delivery issues for specific users
-   Audit notification activity for compliance
-   Support user inquiries about notification problems

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 123,
            "name": "John Doe",
            "email": "john.doe@example.com"
        },
        "notifications": [
            {
                "id": 456,
                "type": "enrollment_approved",
                "channel": "email",
                "status": "sent",
                "payload": {
                    "program_name": "Advanced English Course",
                    "message": "Your enrollment has been approved"
                },
                "sent_at": "2024-01-15T10:30:00.000000Z",
                "created_at": "2024-01-15T10:25:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 2,
            "per_page": 50,
            "total": 87,
            "has_more_pages": true
        }
    }
}
```

**User Not Found Error (404)**:

```json
{
    "success": false,
    "message": "User not found"
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**cURL Example**:

```bash
# Get notification history for user ID 123
curl -X GET "https://your-domain.com/api/notifications/users/123/history" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"

# Get specific page with custom limit
curl -X GET "https://your-domain.com/api/notifications/users/123/history?page=2&limit=25" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

---

#### POST /notifications/retry-failed

**Description**: Retry failed notifications by dispatching them back to the notification queue

**Authentication**: Required (Admin role)

**Purpose**:

-   Recover from temporary notification delivery failures
-   Retry notifications after fixing system configuration issues
-   Manage notification queue and reduce failed notification backlog
-   Maintain notification delivery reliability

**Request Body Parameters**:

| Field   | Type    | Required | Description                              | Default |
| ------- | ------- | -------- | ---------------------------------------- | ------- |
| `limit` | integer | No       | Maximum number of notifications to retry | 10      |

**Request Body Example**:

```json
{
    "limit": 25
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Dispatched retry jobs for 25 failed notifications",
    "data": {
        "retry_count": 25
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "limit": ["The limit must be between 1 and 100."]
    }
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**No Failed Notifications Response (200)**:

```json
{
    "success": true,
    "message": "Dispatched retry jobs for 0 failed notifications",
    "data": {
        "retry_count": 0
    }
}
```

**cURL Examples**:

```bash
# Retry failed notifications with default limit
curl -X POST "https://your-domain.com/api/notifications/retry-failed" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{}'

# Retry up to 50 failed notifications
curl -X POST "https://your-domain.com/api/notifications/retry-failed" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "limit": 50
  }'
```

---

#### POST /notifications/cleanup

**Description**: Clean up old notification logs to maintain database performance and manage storage

**Authentication**: Required (Admin role)

**Purpose**:

-   Remove old notification logs to optimize database performance
-   Manage storage space by cleaning up historical data
-   Maintain system performance with regular cleanup operations
-   Comply with data retention policies

**Request Body Parameters**:

| Field  | Type    | Required | Description                           | Default |
| ------ | ------- | -------- | ------------------------------------- | ------- |
| `days` | integer | No       | Number of days to keep (delete older) | 90      |

**Request Body Example**:

```json
{
    "days": 180
}
```

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Deleted 1,247 old notification logs",
    "data": {
        "deleted_count": 1247,
        "days_kept": 180
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "days": ["The days must be between 1 and 365."]
    }
}
```

**Authorization Error (403)**:

```json
{
    "success": false,
    "message": "Unauthorized. Required roles: admin"
}
```

**No Records to Delete Response (200)**:

```json
{
    "success": true,
    "message": "Deleted 0 old notification logs",
    "data": {
        "deleted_count": 0,
        "days_kept": 90
    }
}
```

**cURL Examples**:

```bash
# Clean up notifications older than 90 days (default)
curl -X POST "https://your-domain.com/api/notifications/cleanup" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{}'

# Clean up notifications older than 30 days
curl -X POST "https://your-domain.com/api/notifications/cleanup" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "days": 30
  }'
```

---

### Notification System Workflows

#### Complete User Notification Management Workflow

**Step 1: Check Current Notification Statistics**

```bash
curl -X GET "https://your-domain.com/api/notifications/stats" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Review Notification History**

```bash
curl -X GET "https://your-domain.com/api/notifications/history?limit=25" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Update Notification Preferences**

```bash
curl -X PUT "https://your-domain.com/api/notifications/preferences" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "notify_email": true,
    "notify_whatsapp": false
  }'
```

#### Administrative Notification System Management Workflow

**Step 1: Monitor System-Wide Statistics**

```bash
curl -X GET "https://your-domain.com/api/notifications/system/stats" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 2: Investigate User-Specific Issues**

```bash
curl -X GET "https://your-domain.com/api/notifications/users/123/history" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

**Step 3: Retry Failed Notifications**

```bash
curl -X POST "https://your-domain.com/api/notifications/retry-failed" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "limit": 50
  }'
```

**Step 4: Perform Regular Cleanup**

```bash
curl -X POST "https://your-domain.com/api/notifications/cleanup" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "days": 90
  }'
```

### Notification System Integration

#### Webhook Integration for Real-Time Updates

For applications requiring real-time notification updates, consider implementing webhook endpoints or WebSocket connections to receive immediate notification status updates.

#### Monitoring and Alerting

**Recommended Monitoring Metrics**:

-   Overall notification success rate (should be > 95%)
-   Failed notification count (monitor for spikes)
-   Retry queue size (should remain manageable)
-   Average delivery time (should be < 5 seconds)

**Alert Thresholds**:

-   Success rate drops below 90%
-   More than 100 failed notifications in 1 hour
-   Retry queue exceeds 500 notifications
-   Average delivery time exceeds 10 seconds

#### Best Practices

**For Users**:

-   Regularly review notification preferences to ensure relevant communications
-   Check notification history if expected notifications are not received
-   Update contact information to ensure delivery success

**For Administrators**:

-   Monitor system statistics daily for performance trends
-   Perform weekly cleanup of old notification logs
-   Retry failed notifications during maintenance windows
-   Investigate patterns in failed notifications to identify system issues

---

## Guest Access

The Learn Academy API provides conditional guest access to public content based on system settings. Guest access allows unauthenticated users to interact with specific features when enabled by administrators. All guest endpoints are subject to system-wide guest access settings and individual resource-level permissions.

### Guest Access Overview

Guest access is controlled through three main settings:

-   **Languages Access**: Allow guests to view available languages and levels
-   **Teachers Access**: Allow guests to view teacher profiles and language assignments
-   **Quizzes Access**: Allow guests to view and take public quizzes anonymously

**Key Features**:

-   **Conditional Access**: All guest endpoints require corresponding settings to be enabled
-   **Anonymous Interactions**: No authentication required for guest-accessible content
-   **Limited Functionality**: Read-only access with basic interaction capabilities
-   **Settings-Based Control**: Administrators can enable/disable guest features independently

**Access Control**:

-   Guest access is controlled by the `guest.access` middleware
-   Each feature (languages, teachers, quizzes) has independent access control
-   Individual resources may have additional guest access restrictions
-   Failed access attempts return appropriate error messages with feature information

---

### Guest Language Access

Guest language endpoints provide access to language information and structure when enabled by system settings.

#### GET /guest/languages

**Description**: Get all available languages and their levels for guest users

**Authentication**: None required (Public endpoint with conditional access)

**Middleware**: `guest.access:languages` - Requires guest language access to be enabled

**Access Control**:

-   Requires `allow_guest_languages` setting to be enabled
-   Returns only active languages
-   Includes language levels ordered by sequence

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "languages": [
            {
                "id": 1,
                "code": "en",
                "name": "English",
                "active": true,
                "levels": [
                    {
                        "id": 1,
                        "name": "Beginner",
                        "order": 1
                    },
                    {
                        "id": 2,
                        "name": "Intermediate",
                        "order": 2
                    },
                    {
                        "id": 3,
                        "name": "Advanced",
                        "order": 3
                    }
                ]
            },
            {
                "id": 2,
                "code": "es",
                "name": "Spanish",
                "active": true,
                "levels": [
                    {
                        "id": 4,
                        "name": "Beginner",
                        "order": 1
                    },
                    {
                        "id": 5,
                        "name": "Intermediate",
                        "order": 2
                    }
                ]
            }
        ],
        "total": 2
    }
}
```

**Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "GUEST_ACCESS_DENIED",
        "message": "Guest access to languages is not allowed. Please log in to access this resource.",
        "feature": "languages"
    }
}
```

**Server Error Response (500)**:

```json
{
    "success": false,
    "error": {
        "code": "LANGUAGES_RETRIEVAL_FAILED",
        "message": "Failed to retrieve languages",
        "details": "Error details (only in debug mode)"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/guest/languages" \
  -H "Content-Type: application/json"
```

**JavaScript Example**:

```javascript
fetch("https://your-domain.com/api/guest/languages")
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            console.log("Available languages:", data.data.languages);
            data.data.languages.forEach((language) => {
                console.log(
                    `${language.name} (${language.code}): ${language.levels.length} levels`
                );
            });
        } else {
            console.error("Access denied:", data.error.message);
        }
    })
    .catch((error) => console.error("Request failed:", error));
```

---

### Guest Teacher Access

Guest teacher endpoints provide access to teacher profiles and language assignments when enabled by system settings.

#### GET /guest/teachers

**Description**: Get all teachers with their language assignments for guest users

**Authentication**: None required (Public endpoint with conditional access)

**Middleware**: `guest.access:teachers` - Requires guest teacher access to be enabled

**Access Control**:

-   Requires `allow_guest_teachers` setting to be enabled
-   Returns only users with teacher role
-   Includes teacher language assignments and profile information

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "teachers": [
            {
                "id": 5,
                "name": "Sarah Johnson",
                "email": "sarah.johnson@example.com",
                "profile_image": "https://your-domain.com/storage/profiles/sarah.jpg",
                "languages": [
                    {
                        "id": 1,
                        "code": "en",
                        "name": "English"
                    },
                    {
                        "id": 2,
                        "code": "es",
                        "name": "Spanish"
                    }
                ]
            },
            {
                "id": 8,
                "name": "Ahmed Hassan",
                "email": "ahmed.hassan@example.com",
                "profile_image": null,
                "languages": [
                    {
                        "id": 3,
                        "code": "ar",
                        "name": "Arabic"
                    }
                ]
            }
        ],
        "total": 2
    }
}
```

**Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "GUEST_ACCESS_DENIED",
        "message": "Guest access to teachers is not allowed. Please log in to access this resource.",
        "feature": "teachers"
    }
}
```

**Server Error Response (500)**:

```json
{
    "success": false,
    "error": {
        "code": "TEACHERS_RETRIEVAL_FAILED",
        "message": "Failed to retrieve teachers",
        "details": "Error details (only in debug mode)"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/guest/teachers" \
  -H "Content-Type: application/json"
```

---

#### GET /guest/languages/{language}/teachers

**Description**: Get teachers assigned to a specific language for guest users

**Authentication**: None required (Public endpoint with conditional access)

**Middleware**: `guest.access:teachers` - Requires guest teacher access to be enabled

**Parameters**:

-   **Path**: `language` (integer, required) - Language ID to filter teachers

**Access Control**:

-   Requires `allow_guest_teachers` setting to be enabled
-   Validates language exists and is accessible
-   Returns only teachers assigned to the specified language

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "language": {
            "id": 1,
            "code": "en",
            "name": "English"
        },
        "teachers": [
            {
                "id": 5,
                "name": "Sarah Johnson",
                "email": "sarah.johnson@example.com",
                "profile_image": "https://your-domain.com/storage/profiles/sarah.jpg"
            },
            {
                "id": 12,
                "name": "Michael Brown",
                "email": "michael.brown@example.com",
                "profile_image": null
            }
        ],
        "total": 2
    }
}
```

**Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "GUEST_ACCESS_DENIED",
        "message": "Guest access to teachers is not allowed. Please log in to access this resource.",
        "feature": "teachers"
    }
}
```

**Language Not Found Response (404)**:

```json
{
    "success": false,
    "error": {
        "code": "RESOURCE_NOT_FOUND",
        "message": "Language not found"
    }
}
```

**Server Error Response (500)**:

```json
{
    "success": false,
    "error": {
        "code": "TEACHERS_BY_LANGUAGE_RETRIEVAL_FAILED",
        "message": "Failed to retrieve teachers for language",
        "details": "Error details (only in debug mode)"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/guest/languages/1/teachers" \
  -H "Content-Type: application/json"
```

---

### Guest Quiz Access

Guest quiz endpoints provide access to public quizzes and anonymous quiz-taking functionality when enabled by system settings.

#### GET /guest/quizzes

**Description**: Get all available quizzes for guest users with anonymous access

**Authentication**: None required (Public endpoint with conditional access)

**Middleware**: `guest.access:quizzes` - Requires guest quiz access to be enabled

**Access Control**:

-   Requires `allow_guest_quizzes` setting to be enabled
-   Returns only active quizzes with guest access enabled
-   Includes program and language information for context

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "quizzes": [
            {
                "id": 15,
                "title": "English Grammar Basics",
                "description": "Test your understanding of basic English grammar rules",
                "type": "multiple_choice",
                "pass_score": 70,
                "time_limit": 30,
                "program": {
                    "id": 3,
                    "language": {
                        "id": 1,
                        "code": "en",
                        "name": "English"
                    },
                    "level": {
                        "id": 1,
                        "name": "Beginner",
                        "order": 1
                    }
                },
                "questions_count": 10
            },
            {
                "id": 22,
                "title": "Spanish Vocabulary Challenge",
                "description": "Basic Spanish vocabulary for beginners",
                "type": "multiple_choice",
                "pass_score": 60,
                "time_limit": 20,
                "program": {
                    "id": 7,
                    "language": {
                        "id": 2,
                        "code": "es",
                        "name": "Spanish"
                    },
                    "level": {
                        "id": 4,
                        "name": "Beginner",
                        "order": 1
                    }
                },
                "questions_count": 15
            }
        ],
        "total": 2
    }
}
```

**Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "GUEST_ACCESS_DENIED",
        "message": "Guest access to quizzes is not allowed. Please log in to access this resource.",
        "feature": "quizzes"
    }
}
```

**Server Error Response (500)**:

```json
{
    "success": false,
    "error": {
        "code": "QUIZZES_RETRIEVAL_FAILED",
        "message": "Failed to retrieve quizzes",
        "details": "Error details (only in debug mode)"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/guest/quizzes" \
  -H "Content-Type: application/json"
```

---

#### GET /guest/quizzes/{quizId}

**Description**: Get detailed information about a specific quiz for guest users including questions

**Authentication**: None required (Public endpoint with conditional access)

**Middleware**: `guest.access:quizzes` - Requires guest quiz access to be enabled

**Parameters**:

-   **Path**: `quizId` (integer, required) - Quiz ID to retrieve

**Access Control**:

-   Requires `allow_guest_quizzes` setting to be enabled
-   Quiz must be active and allow guest access
-   Questions are included but correct answers are hidden

**Success Response (200)**:

```json
{
    "success": true,
    "data": {
        "quiz": {
            "id": 15,
            "title": "English Grammar Basics",
            "description": "Test your understanding of basic English grammar rules",
            "type": "multiple_choice",
            "pass_score": 70,
            "time_limit": 30,
            "program": {
                "id": 3,
                "language": {
                    "id": 1,
                    "code": "en",
                    "name": "English"
                },
                "level": {
                    "id": 1,
                    "name": "Beginner",
                    "order": 1
                }
            },
            "questions": [
                {
                    "id": 45,
                    "question": "Which of the following is a noun?",
                    "type": "multiple_choice",
                    "choices": ["run", "quickly", "book", "beautiful"],
                    "order": 1
                },
                {
                    "id": 46,
                    "question": "What is the past tense of 'go'?",
                    "type": "multiple_choice",
                    "choices": ["goed", "went", "gone", "going"],
                    "order": 2
                }
            ]
        }
    }
}
```

**Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "GUEST_ACCESS_DENIED",
        "message": "Guest access to quizzes is not allowed. Please log in to access this resource.",
        "feature": "quizzes"
    }
}
```

**Quiz Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "QUIZ_ACCESS_DENIED",
        "message": "This quiz does not allow guest access."
    }
}
```

**Quiz Not Found Response (404)**:

```json
{
    "success": false,
    "error": {
        "code": "RESOURCE_NOT_FOUND",
        "message": "Quiz not found"
    }
}
```

**Server Error Response (500)**:

```json
{
    "success": false,
    "error": {
        "code": "QUIZ_RETRIEVAL_FAILED",
        "message": "Failed to retrieve quiz",
        "details": "Error details (only in debug mode)"
    }
}
```

**cURL Example**:

```bash
curl -X GET "https://your-domain.com/api/guest/quizzes/15" \
  -H "Content-Type: application/json"
```

---

#### POST /guest/quizzes/{quizId}/attempt

**Description**: Submit an anonymous quiz attempt as a guest user

**Authentication**: None required (Public endpoint with conditional access)

**Middleware**: `guest.access:quizzes` - Requires guest quiz access to be enabled

**Parameters**:

-   **Path**: `quizId` (integer, required) - Quiz ID to submit attempt for

**Request Body Parameters**:

| Field       | Type   | Required | Description                                   |
| ----------- | ------ | -------- | --------------------------------------------- |
| `answers`   | array  | Yes      | Array of answers for quiz questions           |
| `answers.*` | string | Yes      | Answer for each question (question ID as key) |

**Request Body Example**:

```json
{
    "answers": {
        "45": "book",
        "46": "went",
        "47": "is",
        "48": "they"
    }
}
```

**Access Control**:

-   Requires `allow_guest_quizzes` setting to be enabled
-   Quiz must be active and allow guest access
-   Answers are validated against quiz questions
-   Attempt is recorded anonymously (no user association)

**Success Response (201)**:

```json
{
    "success": true,
    "message": "Quiz attempt submitted successfully.",
    "data": {
        "score": 85,
        "passed": true,
        "total_questions": 10,
        "correct_answers": 8,
        "attempt": {
            "id": 156,
            "score": 85,
            "passed": true,
            "submitted_at": "2024-01-15T14:30:00.000000Z",
            "quiz": {
                "id": 15,
                "title": "English Grammar Basics",
                "pass_score": 70
            }
        }
    }
}
```

**Validation Error Response (422)**:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "answers": ["Quiz answers are required."],
        "answers.45": ["All questions must be answered."]
    }
}
```

**Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "GUEST_ACCESS_DENIED",
        "message": "Guest access to quizzes is not allowed. Please log in to access this resource.",
        "feature": "quizzes"
    }
}
```

**Quiz Access Denied Response (403)**:

```json
{
    "success": false,
    "error": {
        "code": "QUIZ_ACCESS_DENIED",
        "message": "This quiz does not allow guest access."
    }
}
```

**Server Error Response (500)**:

```json
{
    "success": false,
    "error": {
        "code": "QUIZ_ATTEMPT_FAILED",
        "message": "Failed to submit quiz attempt",
        "details": "Error details (only in debug mode)"
    }
}
```

**cURL Example**:

```bash
curl -X POST "https://your-domain.com/api/guest/quizzes/15/attempt" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {
      "45": "book",
      "46": "went",
      "47": "is",
      "48": "they"
    }
  }'
```

---

### Legacy Guest Quiz Endpoints

For backward compatibility, the system maintains legacy guest quiz endpoints with the same functionality but different URL structure.

#### GET /guest/quizzes (Legacy)

**Description**: Legacy endpoint for getting available quizzes for guests

**Authentication**: None required (Public endpoint with conditional access)

**URL**: `/api/guest/quizzes/` (note the trailing slash)

**Functionality**: Identical to `GET /guest/quizzes` but with legacy URL structure

**Response Format**: Same as the main guest quizzes endpoint

---

#### GET /guest/quizzes/{quiz} (Legacy)

**Description**: Legacy endpoint for getting a specific quiz for guests

**Authentication**: None required (Public endpoint with conditional access)

**URL**: `/api/guest/quizzes/{quiz}` (uses quiz model binding)

**Functionality**: Identical to `GET /guest/quizzes/{quizId}` but with legacy URL structure

**Response Format**: Same as the main guest quiz detail endpoint

---

#### POST /guest/quizzes/{quiz}/attempt (Legacy)

**Description**: Legacy endpoint for submitting guest quiz attempts

**Authentication**: None required (Public endpoint with conditional access)

**URL**: `/api/guest/quizzes/{quiz}/attempt` (uses quiz model binding)

**Functionality**: Identical to `POST /guest/quizzes/{quizId}/attempt` but with legacy URL structure

**Response Format**: Same as the main guest quiz attempt endpoint

---

### Guest Access Configuration

Guest access is controlled through system settings that administrators can manage through the settings endpoints.

#### Guest Access Settings Structure

```json
{
    "allow_guest_languages": false,
    "allow_guest_teachers": false,
    "allow_guest_quizzes": false
}
```

**Setting Descriptions**:

-   **allow_guest_languages**: Enable/disable guest access to language information
-   **allow_guest_teachers**: Enable/disable guest access to teacher profiles
-   **allow_guest_quizzes**: Enable/disable guest access to quiz functionality

#### Checking Guest Access Status

Administrators can check current guest access settings using:

```bash
curl -X GET "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json"
```

#### Updating Guest Access Settings

Administrators can update guest access settings using:

```bash
curl -X PUT "https://your-domain.com/api/admin/settings/guest-access" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "allow_guest_languages": true,
    "allow_guest_teachers": true,
    "allow_guest_quizzes": false
  }'
```

---

### Guest Access Workflows

#### Complete Guest User Journey

**Step 1: Check Available Languages**

```bash
curl -X GET "https://your-domain.com/api/guest/languages" \
  -H "Content-Type: application/json"
```

**Step 2: Browse Teachers for a Language**

```bash
curl -X GET "https://your-domain.com/api/guest/languages/1/teachers" \
  -H "Content-Type: application/json"
```

**Step 3: View Available Quizzes**

```bash
curl -X GET "https://your-domain.com/api/guest/quizzes" \
  -H "Content-Type: application/json"
```

**Step 4: Get Quiz Details**

```bash
curl -X GET "https://your-domain.com/api/guest/quizzes/15" \
  -H "Content-Type: application/json"
```

**Step 5: Submit Quiz Attempt**

```bash
curl -X POST "https://your-domain.com/api/guest/quizzes/15/attempt" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {
      "45": "book",
      "46": "went"
    }
  }'
```

#### Error Handling for Guest Access

**Access Denied Handling**:

```javascript
fetch("https://your-domain.com/api/guest/quizzes")
    .then((response) => response.json())
    .then((data) => {
        if (!data.success && data.error?.code === "GUEST_ACCESS_DENIED") {
            // Redirect to login or show registration prompt
            console.log("Guest access disabled:", data.error.message);
            showLoginPrompt(data.error.feature);
        } else if (data.success) {
            displayQuizzes(data.data.quizzes);
        }
    })
    .catch((error) => console.error("Request failed:", error));
```

#### Guest Access Monitoring

**Recommended Monitoring**:

-   Track guest endpoint usage to understand public interest
-   Monitor guest quiz completion rates
-   Analyze which languages/teachers are most viewed by guests
-   Track conversion from guest to registered user

**Analytics Integration**:

```javascript
// Track guest interactions for analytics
function trackGuestInteraction(endpoint, success) {
    analytics.track("Guest API Access", {
        endpoint: endpoint,
        success: success,
        timestamp: new Date().toISOString(),
    });
}
```

---
## Appendices

### Data Models Reference

#### User Model Structure
```json
{
    "id": "integer",
    "name": "string",
    "email": "string",
    "phone": "string|null",
    "role": "string (admin|teacher|student)",
    "preferred_language": "string",
    "notify_email": "boolean",
    "notify_whatsapp": "boolean",
    "roles": "array of strings",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

#### Quiz Model Structure
```json
{
    "id": "integer",
    "title": "string",
    "description": "string|null",
    "type": "string",
    "active": "boolean",
    "program_id": "integer",
    "teacher_id": "integer",
    "questions_count": "integer",
    "attempts_count": "integer",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

#### Program Model Structure
```json
{
    "id": "integer",
    "title": "string",
    "description": "string|null",
    "language": "object",
    "level": "object",
    "teacher": "object",
    "students_count": "integer",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Error Codes Reference

| Error Code | HTTP Status | Description | Resolution |
|------------|-------------|-------------|------------|
| `MISSING_TOKEN` | 401 | Authorization header missing | Include Bearer token |
| `INVALID_TOKEN` | 401 | Token malformed or expired | Re-authenticate |
| `INSUFFICIENT_ROLE` | 403 | User lacks required role | Contact administrator |
| `RESOURCE_NOT_FOUND` | 404 | Requested resource doesn't exist | Verify resource ID |
| `VALIDATION_FAILED` | 422 | Request data validation failed | Check field requirements |
| `GUEST_ACCESS_DENIED` | 403 | Guest access disabled | Enable guest access or authenticate |

### Rate Limiting

The API implements rate limiting to ensure fair usage and system stability:

- **Authenticated Users**: 1000 requests per hour
- **Guest Users**: 100 requests per hour
- **Admin Operations**: 2000 requests per hour

Rate limit headers are included in all responses:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

When rate limits are exceeded, the API returns a `429 Too Many Requests` status with retry information.

---

**API Documentation Version**: 1.0.0  
**Last Updated**: January 2024  
**Documentation Status**: Complete and Validated ✅