# Requirements Document

## Introduction

Learn Academy is a comprehensive language learning platform that requires a robust RESTful backend built with Laravel 12. The system manages a multi-role environment with students, teachers, and administrators, supporting language programs with different levels, quizzes, meetings, and notifications. The platform includes guest access controls, teacher profile management, and dynamic translation support for Arabic, English, and Spanish locales.

## Requirements

### Requirement 1: User Registration and Authentication

**User Story:** As a student, I want to register with my preferred language and level so that I can be automatically enrolled in relevant programs and start learning immediately.

#### Acceptance Criteria

1. WHEN a student registers THEN the system SHALL require name, email, phone, password, preferred language, and level selection
2. WHEN a student completes registration THEN the system SHALL automatically assign them to all programs matching their selected language and level
3. WHEN a student is enrolled THEN the system SHALL send notifications via email and WhatsApp (if enabled in user preferences)
4. WHEN a user authenticates THEN the system SHALL use Laravel Sanctum for API token management
5. WHEN a user has a role THEN the system SHALL enforce role-based permissions using spatie/laravel-permission

### Requirement 2: Teacher Profile and Language Management

**User Story:** As a teacher, I want to manage my profile with an image and be assigned to specific languages so that students can identify me and understand what I teach.

#### Acceptance Criteria

1. WHEN a teacher uploads a profile image THEN the system SHALL store it using Laravel Filesystem with S3/local storage
2. WHEN an admin assigns a teacher to languages THEN the system SHALL create TeacherLanguage relationships
3. WHEN a teacher is linked to languages THEN the system SHALL allow them to create content for those languages only
4. WHEN displaying teachers THEN the system SHALL show their profile image, name, and associated languages

### Requirement 3: Quiz Management and Attempts

**User Story:** As a teacher, I want to create quizzes either by uploading files or adding questions inline so that I can assess student progress in my programs.

#### Acceptance Criteria

1. WHEN a teacher creates a quiz THEN the system SHALL support both file upload and inline question creation
2. WHEN creating inline quizzes THEN the system SHALL allow multiple choice questions with JSON-stored choices and correct answers
3. WHEN a student attempts a quiz THEN the system SHALL calculate score, determine pass/fail based on pass_score, and store answers as JSON
4. WHEN guest access is enabled for quizzes THEN the system SHALL allow anonymous attempts with student_id as null
5. WHEN a quiz attempt is completed THEN the system SHALL store submission timestamp and results

### Requirement 4: Meeting Scheduling and Notifications

**User Story:** As a teacher, I want to schedule meetings with links and notify all students in my programs so that they can attend live sessions.

#### Acceptance Criteria

1. WHEN a teacher schedules a meeting THEN the system SHALL require title, link, start time, timezone, and optional description
2. WHEN a meeting is created THEN the system SHALL notify all enrolled students in the program via email and WhatsApp
3. WHEN displaying meetings THEN the system SHALL show upcoming meetings for student's enrolled programs
4. WHEN an admin creates meetings THEN the system SHALL allow targeting specific programs

### Requirement 5: Administrative Program Management and Student Access Control

**User Story:** As an admin, I want to create languages with auto-generated levels and programs and control when students can start learning so that I can manage the learning process effectively.

#### Acceptance Criteria

1. WHEN an admin creates a language THEN the system SHALL auto-generate associated levels and programs
2. WHEN viewing programs THEN the system SHALL show enrollment counts and student lists for each program
3. WHEN a new student registers THEN the system SHALL notify admins via email and WhatsApp
4. WHEN managing teachers THEN the system SHALL allow assignment to programs and language associations
5. WHEN creating teachers THEN the system SHALL support profile image upload during creation
6. WHEN a student is enrolled THEN the system SHALL require admin approval before the student can access program content
7. WHEN admin grants access THEN the student SHALL be able to view and attempt quizzes and access meeting links
8. WHEN admin has not granted access THEN the student SHALL see enrollment status but cannot access program materials

### Requirement 6: Guest Access Control System

**User Story:** As an admin, I want to control what guests can access without registration so that I can manage public visibility of our content.

#### Acceptance Criteria

1. WHEN allow_guest_languages is enabled THEN the system SHALL allow unauthenticated users to view available languages
2. WHEN allow_guest_teachers is enabled THEN the system SHALL allow guests to see teacher profiles and their associated languages
3. WHEN allow_guest_quizzes is enabled THEN the system SHALL allow anonymous quiz attempts
4. WHEN guest settings are disabled THEN the system SHALL require authentication for respective endpoints
5. WHEN updating guest settings THEN the system SHALL immediately apply access controls

### Requirement 7: Notification System

**User Story:** As a user, I want to receive notifications via email and WhatsApp based on my preferences so that I stay informed about important updates.

#### Acceptance Criteria

1. WHEN notifications are triggered THEN the system SHALL check user preferences for notify_email and notify_whatsapp
2. WHEN sending notifications THEN the system SHALL use Laravel Notifications with email and WhatsApp channels
3. WHEN notifications are sent THEN the system SHALL log them in NotificationLog with type, payload, and timestamp
4. WHEN using queues THEN the system SHALL process notifications asynchronously using Redis and Horizon
5. WHEN notification fails THEN the system SHALL handle errors gracefully and log failure details

### Requirement 8: Dynamic Translation System

**User Story:** As a frontend developer, I want to fetch translations dynamically via API so that I can support Arabic, English, and Spanish locales seamlessly.

#### Acceptance Criteria

1. WHEN requesting translations THEN the system SHALL provide endpoint GET /api/translations/{locale}
2. WHEN locale is ar, en, or es THEN the system SHALL return appropriate translation keys and values
3. WHEN translation keys are missing THEN the system SHALL handle gracefully with fallback values
4. WHEN translations are updated THEN the system SHALL reflect changes immediately via API
5. WHEN caching translations THEN the system SHALL optimize performance for frequent requests

### Requirement 9: Role-Based Access Control

**User Story:** As a system user, I want my access to be controlled based on my role so that I can only perform actions appropriate to my permissions.

#### Acceptance Criteria

1. WHEN a student accesses the system THEN they SHALL only see their enrolled programs, quizzes, and meetings
2. WHEN a teacher accesses the system THEN they SHALL only manage content for their assigned languages and programs
3. WHEN an admin accesses the system THEN they SHALL have full access to all management functions
4. WHEN unauthorized access is attempted THEN the system SHALL return appropriate HTTP error codes
5. WHEN roles are assigned THEN the system SHALL enforce permissions consistently across all endpoints

### Requirement 10: Data Integrity and Relationships

**User Story:** As a system administrator, I want data relationships to be properly maintained so that the system remains consistent and reliable.

#### Acceptance Criteria

1. WHEN programs are created THEN they SHALL be properly linked to languages and levels
2. WHEN enrollments are made THEN they SHALL maintain referential integrity between users and programs
3. WHEN quiz attempts are recorded THEN they SHALL properly reference quizzes and users (or be null for guests)
4. WHEN teachers are assigned THEN the TeacherLanguage pivot table SHALL maintain proper relationships
5. WHEN data is deleted THEN the system SHALL handle cascading relationships appropriately