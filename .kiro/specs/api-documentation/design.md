# API Documentation Design

## Overview

The Learn Academy API documentation will be a comprehensive reference guide that covers all endpoints in the Laravel-based learning management system. The documentation will be structured as a detailed markdown file that serves as both a reference and implementation guide for developers working with the API.

The API follows RESTful conventions with role-based access control, supporting four main user types: administrators, teachers, students, and guests. The system uses Laravel Sanctum for authentication and implements middleware for role and permission-based access control.

## Architecture

### Documentation Structure

The API documentation will be organized into the following main sections:

1. **Authentication & Authorization** - Login, registration, token management
2. **Health & System** - Health checks and system status
3. **Translation Management** - Multi-language support endpoints
4. **Administrative Functions** - User management, enrollment control, system settings
5. **Teacher Operations** - Quiz management, meeting scheduling, profile management
6. **Student Operations** - Quiz taking, enrollment viewing, meeting access
7. **Guest Access** - Public endpoints for unauthenticated users
8. **Notification System** - Notification management and preferences

### Response Format Standards

All API responses follow a consistent JSON structure:

```json
{
  "success": boolean,
  "message": string,
  "data": object|array,
  "errors": object (only on validation failures),
  "meta": object (for paginated responses)
}
```

### Authentication Patterns

- **Public Endpoints**: No authentication required
- **Protected Endpoints**: Require `Authorization: Bearer {token}` header
- **Role-Based Access**: Additional role validation (admin, teacher, student)
- **Permission-Based Access**: Specific permission requirements
- **Middleware Validation**: Custom middleware for language access and guest settings

## Components and Interfaces

### Endpoint Documentation Template

Each endpoint will be documented with the following structure:

```markdown
### [HTTP_METHOD] /api/endpoint-path

**Description**: Brief description of endpoint functionality

**Authentication**: Required/Optional + Role requirements

**Parameters**:
- Path Parameters: {param}: type - description
- Query Parameters: param: type - description  
- Request Body: JSON schema with field descriptions

**Request Example**:
```json
{
  "field": "example_value"
}
```

**Response Examples**:

Success (200):
```json
{
  "success": true,
  "data": {}
}
```

Error (400/401/403/422):
```json
{
  "success": false,
  "message": "Error description",
  "errors": {}
}
```
```

### Authentication Section Design

The authentication section will include:

- Registration flow with validation rules
- Login process with token generation
- Profile management endpoints
- Logout and token invalidation
- Role-based access examples

### Administrative Functions Design

Admin endpoints will be grouped by functionality:

- **User Management**: CRUD operations for users
- **Enrollment Management**: Approval workflows and bulk operations
- **Program Management**: Program administration and statistics
- **Language Management**: Language configuration and teacher assignments
- **Quiz Administration**: Admin-level quiz management
- **System Settings**: Configuration management

### Teacher Operations Design

Teacher endpoints will cover:

- **Profile Management**: Teacher profile and image handling
- **Quiz Management**: Full CRUD operations with question management
- **Meeting Management**: Meeting scheduling and management
- **Language Access**: Language-specific content access
- **Statistics**: Quiz and teaching analytics

### Student Operations Design

Student endpoints will include:

- **Enrollment Viewing**: Access to approved programs
- **Quiz Taking**: Quiz access and attempt submission
- **Meeting Access**: Meeting viewing and participation
- **Progress Tracking**: Attempt history and results

### Guest Access Design

Guest endpoints will be documented with:

- **Conditional Access**: Settings-based availability
- **Public Content**: Languages, teachers, and quizzes
- **Anonymous Quiz Taking**: Guest quiz attempts

## Data Models

### Core Entity Schemas

**User Model**:
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

**Quiz Model**:
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

**Program Model**:
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

### Request/Response Patterns

**Pagination Response**:
```json
{
  "success": true,
  "data": {
    "data": "array of items",
    "current_page": "integer",
    "per_page": "integer",
    "total": "integer",
    "last_page": "integer",
    "from": "integer",
    "to": "integer"
  }
}
```

**Validation Error Response**:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

## Error Handling

### HTTP Status Codes

- **200 OK**: Successful GET, PUT, PATCH requests
- **201 Created**: Successful POST requests
- **204 No Content**: Successful DELETE requests
- **400 Bad Request**: Invalid request format
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server errors

### Error Response Format

All error responses will include:
- Consistent error message format
- Detailed validation error breakdown
- Appropriate HTTP status codes
- Helpful error descriptions for debugging

### Common Error Scenarios

- Authentication failures
- Authorization/permission errors
- Validation failures with field-specific messages
- Resource not found errors
- Server errors with appropriate logging

## Testing Strategy

### Documentation Validation

1. **Endpoint Coverage**: Verify all routes from api.php are documented
2. **Response Accuracy**: Validate response examples match actual API responses
3. **Authentication Testing**: Verify auth requirements are correctly documented
4. **Parameter Validation**: Ensure all required/optional parameters are documented
5. **Error Scenario Coverage**: Document common error responses

### Example Generation

1. **Realistic Data**: Use meaningful example data that reflects actual usage
2. **Complete Workflows**: Provide end-to-end examples for common user journeys
3. **cURL Examples**: Include practical command-line examples
4. **Multiple Scenarios**: Show both success and error cases

### Maintenance Strategy

1. **Version Control**: Track documentation changes with code changes
2. **Automated Validation**: Consider tools to validate documentation against actual API
3. **Regular Updates**: Establish process for keeping documentation current
4. **Feedback Integration**: Mechanism for developers to report documentation issues

The documentation will be created as a single comprehensive markdown file that can be easily maintained, version-controlled, and integrated into development workflows.