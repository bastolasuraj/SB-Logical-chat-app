# SB Logical Chat App

A real-time chat application built with Laravel, Vue.js, TypeScript, and WebSocket technology.

## 🚀 Features

- **Real-time Messaging** - Instant message delivery using Laravel Reverb WebSocket
- **User Authentication** - Secure registration and login with email verification
- **Friend Management** - Add friends and manage friend requests
- **Online Status** - See who's online in real-time
- **Modern UI** - Built with Vue.js 3, TypeScript, and Tailwind CSS
- **Email Integration** - SMTP2GO for reliable email delivery

## 🛠 Tech Stack

### Backend
- **Laravel 11** - PHP framework
- **MySQL** - Database
- **Laravel Reverb** - WebSocket server
- **SMTP2GO** - Email service

### Frontend
- **Vue.js 3** - JavaScript framework
- **TypeScript** - Type safety
- **Tailwind CSS 4.0** - Styling
- **Vite** - Build tool
- **Pinia** - State management

## 📋 Development Progress

### ✅ Completed Tasks

- **Task 1**: Project foundation setup
  - Laravel + Vue.js + TypeScript integration
  - Tailwind CSS configuration
  - Laravel Reverb WebSocket setup
  - SMTP2GO email configuration
  - Development environment setup

- **Task 2.1**: User model and authentication database migrations
  - User model with email verification support
  - Password hashing and security
  - Avatar system with Gravatar fallback
  - Online status tracking
  - Comprehensive unit tests (12 tests passing)

- **Task 2.2**: Registration API with email verification
  - RegisterController with comprehensive validation
  - EmailVerificationService with dual verification methods
  - 6-digit code AND verification link support
  - Rate limiting and security measures
  - Queued email notifications via SMTP2GO
  - Comprehensive test suite (12 tests passing)
  - API endpoints: `/api/auth/register`, `/api/auth/verify-email`, `/api/auth/resend-verification`

- **Task 2.3**: Login/logout API endpoints
  - LoginController with secure authentication logic
  - Laravel Sanctum API token authentication
  - Rate limiting for login attempts (5 attempts per email/IP)
  - Email verification requirement for login
  - Session management and token handling
  - Multiple device logout support
  - User profile endpoint with online status
  - EnsureEmailIsVerified middleware for protected routes
  - Comprehensive test suite (20 tests passing)
  - API endpoints: `/api/auth/login`, `/api/auth/logout`, `/api/auth/logout-all`, `/api/auth/me`

- **Task 3.1**: Create authentication views and forms
  - LoginView component with form validation
  - RegisterView component with TypeScript interfaces
  - EmailVerificationView for code/link verification
  - ForgotPasswordView for password recovery
  - Form validation with error message display
  - Responsive design with Tailwind CSS
  - Router integration with navigation guards

- **Task 3.2**: Implement authentication state management ✅ **COMPLETED**
  - **Pinia Store Implementation**: Complete authentication state management using Pinia
    - User state management with reactive properties
    - Token management with localStorage persistence
    - Loading states and error handling
    - Computed properties for authentication status and email verification
  - **API Service Layer**: Comprehensive API integration
    - Axios-based service with interceptors
    - Automatic token attachment to requests
    - Error handling and response transformation
    - Support for all authentication endpoints
  - **Route Guards**: Navigation protection and flow control
    - Protected routes requiring authentication
    - Guest-only routes for unauthenticated users
    - Email verification enforcement
    - Automatic redirects based on authentication state
  - **State Persistence**: Seamless user experience
    - Token persistence in localStorage
    - Automatic authentication restoration on app load
    - Graceful handling of invalid/expired tokens
  - **Comprehensive Testing**: Full test coverage
    - Unit tests for Pinia store (9 tests)
    - Integration tests for authentication flow (6 tests)
    - Route guard tests (7 tests)
    - Component tests for authentication views (5 tests)
    - Validation composable tests (7 tests)
    - **Total: 34 tests passing**

- **Task 4.1**: Database schema for chat system ✅ **COMPLETED**
  - **Chats Table**: Core chat entity with support for private and group chats
    - Primary key, type (private/group), optional name for groups
    - Last message timestamp for sorting and performance
    - Proper indexes on type and last_message_at fields
  - **Messages Table**: Message storage with full relationship support
    - Foreign keys to chats and users with cascade delete
    - Content storage with message type support (text, image, file)
    - Read status tracking with timestamp
    - Performance indexes on chat_id, user_id, and created_at
  - **Chat Participants Table**: Many-to-many relationship between users and chats
    - Unique constraint preventing duplicate participants
    - Join timestamp tracking for audit purposes
    - Cascade delete for data integrity
  - **Friendships Table**: User relationship management
    - Requester/addressee relationship with status tracking
    - Support for pending, accepted, and declined states
    - Unique constraint preventing duplicate friend requests
    - Comprehensive indexing for friend lookup performance

- **Task 4.2**: Eloquent models with relationships ✅ **COMPLETED**
  - **Chat Model**: Complete chat entity with comprehensive relationship management
    - HasMany relationship to messages with proper ordering
    - BelongsToMany relationship to participants through pivot table
    - HasOne relationship to lastMessage for efficient queries
    - Helper methods for private/group chat detection
    - Unread message counting and marking as read functionality
    - Scopes for user-specific queries and ordering by activity
  - **Message Model**: Full-featured message entity with relationships and utilities
    - BelongsTo relationships to both chat and user entities
    - Read status tracking with automatic timestamp management
    - Message type support (text, image, file) with helper methods
    - Formatted content attribute for different message types
    - Comprehensive scopes for filtering and querying messages
    - Automatic chat last_message_at updating on message creation
  - **Friendship Model**: Complete user relationship management system
    - BelongsTo relationships to requester and addressee users
    - Status management (pending, accepted, declined) with helper methods
    - Bidirectional friendship detection and management
    - Static methods for checking and retrieving friendships between users
    - Comprehensive scopes for filtering by status and user involvement
    - Helper methods for getting the other user in a friendship
  - **Model Factories**: Complete factory implementations for all models
    - ChatFactory with states for private/group chats and activity levels
    - MessageFactory with states for read/unread and different message types
    - FriendshipFactory with states for different friendship statuses
    - All factories support relationship creation and realistic test data
  - **Comprehensive Test Suite**: 40 unit tests covering all model functionality
    - Chat model tests: 12 tests covering relationships and business logic
    - Message model tests: 12 tests covering relationships and message handling
    - Friendship model tests: 16 tests covering relationship management
    - All tests passing with proper assertions and edge case coverage

### 🔄 In Progress
- **Task 5**: Friend management system
- **Task 6**: Core messaging API
- **Task 7**: WebSocket infrastructure
- **Task 8**: Main chat interface
- **Task 9**: Real-time messaging integration
- **Task 10**: Performance optimization
- **Task 11**: Error handling
- **Task 12**: Testing suite
- **Task 13**: Final integration

## 🚦 Getting Started

### Prerequisites
- PHP 8.1+
- Node.js 18+
- MySQL 8.0+
- Composer
- NPM

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/bastolasuraj/SB-Logical-Chat-App.git
   cd SB-Logical-Chat-App
   ```

2. **Navigate to the chat-app directory**
   ```bash
   cd chat-app
   ```

3. **Install PHP dependencies**
   ```bash
   composer install
   ```

4. **Install Node.js dependencies**
   ```bash
   npm install
   ```

5. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

6. **Configure database**
   - Update `.env` with your database credentials
   - Update `.env` with your SMTP2GO credentials

7. **Run migrations**
   ```bash
   php artisan migrate
   ```

8. **Build assets**
   ```bash
   npm run build
   ```

9. **Start development servers**
   ```bash
   # Laravel (Backend)
   php -S localhost:8093 -t public

   # Vite (Frontend Development)
   npm run dev

   # Laravel Reverb (WebSocket) - when needed
   php artisan reverb:start
   ```

## 🧪 Testing

Run the test suite:
```bash
# Backend tests
php artisan test

# Frontend tests
npm test
```

Current test coverage:
- **User Model**: 12 tests passing
- **Registration API**: 12 feature tests passing
- **Login/Logout API**: 20 feature tests passing
- **Email Verification Service**: 11 unit tests passing
- **Frontend Authentication**: 34 tests passing
  - Pinia store tests: 9 tests
  - Integration tests: 6 tests
  - Route guard tests: 7 tests
  - Component tests: 5 tests
  - Validation tests: 7 tests
- **Total**: 89+ tests passing

## 🗄️ Database Schema

### Core Tables

#### Users Table (Extended)
```sql
users
├── id (Primary Key)
├── name (VARCHAR)
├── email (UNIQUE VARCHAR)
├── email_verified_at (TIMESTAMP, nullable)
├── password (HASHED VARCHAR)
├── avatar (VARCHAR, nullable)
├── last_seen_at (TIMESTAMP, nullable)
├── remember_token (VARCHAR, nullable)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

#### Chats Table
```sql
chats
├── id (Primary Key)
├── type (ENUM: 'private', 'group') DEFAULT 'private'
├── name (VARCHAR, nullable) -- For group chats
├── last_message_at (TIMESTAMP, nullable)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)

Indexes:
- type (for filtering chat types)
- last_message_at (for sorting by activity)
```

#### Messages Table
```sql
messages
├── id (Primary Key)
├── chat_id (Foreign Key → chats.id) CASCADE DELETE
├── user_id (Foreign Key → users.id) CASCADE DELETE
├── content (TEXT)
├── message_type (ENUM: 'text', 'image', 'file') DEFAULT 'text'
├── read_at (TIMESTAMP, nullable)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)

Indexes:
- [chat_id, created_at] (for message history queries)
- [user_id, created_at] (for user message queries)
- read_at (for unread message counts)
```

#### Chat Participants Table
```sql
chat_participants
├── id (Primary Key)
├── chat_id (Foreign Key → chats.id) CASCADE DELETE
├── user_id (Foreign Key → users.id) CASCADE DELETE
├── joined_at (TIMESTAMP) DEFAULT CURRENT_TIMESTAMP
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)

Constraints:
- UNIQUE [chat_id, user_id] (prevent duplicate participants)

Indexes:
- chat_id (for finding chat participants)
- user_id (for finding user's chats)
```

#### Friendships Table
```sql
friendships
├── id (Primary Key)
├── requester_id (Foreign Key → users.id) CASCADE DELETE
├── addressee_id (Foreign Key → users.id) CASCADE DELETE
├── status (ENUM: 'pending', 'accepted', 'declined') DEFAULT 'pending'
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)

Constraints:
- UNIQUE [requester_id, addressee_id] (prevent duplicate requests)

Indexes:
- requester_id (for outgoing friend requests)
- addressee_id (for incoming friend requests)
- status (for filtering by request status)
- [requester_id, status] (for user's outgoing requests by status)
- [addressee_id, status] (for user's incoming requests by status)
```

### Relationships

#### User Model Relationships
- **HasMany**: messages (user's sent messages)
- **BelongsToMany**: chats (through chat_participants)
- **HasMany**: sentFriendRequests (as requester)
- **HasMany**: receivedFriendRequests (as addressee)

#### Chat Model Relationships
- **HasMany**: messages (chat's message history)
- **BelongsToMany**: participants (users in the chat)
- **HasOne**: lastMessage (most recent message)

#### Message Model Relationships
- **BelongsTo**: chat (parent chat)
- **BelongsTo**: user (message sender)

#### Friendship Model Relationships
- **BelongsTo**: requester (user who sent request)
- **BelongsTo**: addressee (user who received request)

### Performance Considerations

#### Indexing Strategy
- **Chat Queries**: Indexed on `type` and `last_message_at` for efficient chat listing
- **Message Queries**: Composite indexes on `[chat_id, created_at]` for message history
- **Friend Queries**: Multiple indexes on friendship combinations for fast lookups
- **User Queries**: Indexed on `last_seen_at` for online status queries

#### Query Optimization
- **Lazy Loading**: Message history loaded in chunks for performance
- **Eager Loading**: Chat participants and last messages loaded efficiently
- **Cascade Deletes**: Automatic cleanup of related records
- **Unique Constraints**: Prevent duplicate data and improve query performance

## 📁 Project Structure

```
├── chat-app/
│   ├── app/
│   │   ├── Models/
│   │   │   └── User.php              # User model with chat features
│   │   ├── Http/Controllers/
│   │   │   └── Auth/
│   │   │       └── RegisterController.php  # Registration API
│   │   ├── Services/
│   │   │   └── EmailVerificationService.php # Email verification logic
│   │   └── Notifications/
│   │       └── EmailVerificationNotification.php # Email templates
│   ├── database/
│   │   ├── migrations/               # Database schema
│   │   └── factories/                # Model factories for testing
│   ├── resources/
│   │   ├── js/
│   │   │   ├── stores/
│   │   │   │   └── auth.ts          # Pinia authentication store
│   │   │   ├── services/
│   │   │   │   └── authApi.ts       # API service layer
│   │   │   ├── router/
│   │   │   │   └── index.ts         # Vue Router with guards
│   │   │   ├── views/
│   │   │   │   └── auth/            # Authentication components
│   │   │   ├── types/
│   │   │   │   └── auth.ts          # TypeScript interfaces
│   │   │   ├── composables/
│   │   │   │   └── useValidation.ts # Validation composable
│   │   │   ├── tests/               # Frontend test suite
│   │   │   ├── App.vue              # Main Vue component
│   │   │   ├── app.ts               # Vue.js entry point
│   │   │   └── bootstrap.ts         # Laravel Echo setup
│   │   └── views/
│   │       └── app.blade.php        # SPA layout
│   ├── tests/
│   │   ├── Unit/
│   │   │   └── UserModelTest.php    # User model tests
│   │   └── Feature/                 # Feature tests
│   └── routes/
│       ├── web.php                  # Web routes
│       └── channels.php             # WebSocket channels
```

## 🔧 Configuration

### Email (SMTP2GO)
Update your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.smtp2go.com
MAIL_PORT=587
MAIL_USERNAME=your_smtp2go_username
MAIL_PASSWORD=your_smtp2go_password
MAIL_ENCRYPTION=tls
```

### WebSocket (Laravel Reverb)
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
```

## 🎯 Authentication State Management Features

### Pinia Store Features
- **Reactive State**: User, token, loading, and error states
- **Computed Properties**: Authentication status and email verification checks
- **Actions**: Login, register, logout, email verification, and user fetching
- **Persistence**: Automatic token storage and retrieval from localStorage
- **Error Handling**: Comprehensive error management with user-friendly messages

### API Service Features
- **Axios Integration**: Configured HTTP client with interceptors
- **Token Management**: Automatic token attachment and refresh handling
- **Error Transformation**: Consistent error format across the application
- **Type Safety**: Full TypeScript support with proper interfaces

### Route Guard Features
- **Authentication Protection**: Automatic redirection for unauthenticated users
- **Email Verification Enforcement**: Ensures verified users access protected routes
- **Guest Route Protection**: Prevents authenticated users from accessing login/register
- **Seamless Navigation**: Smooth user experience with proper redirects

## 🤝 Contributing

This is a learning project following a structured development approach with:
- Comprehensive requirements documentation
- Detailed technical design
- Test-driven development
- Git workflow with task-based commits

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🎯 Roadmap

- [x] Complete authentication system with state management
- [ ] Implement friend management
- [ ] Build real-time chat interface
- [ ] Add file sharing capabilities
- [ ] Mobile responsive design
- [ ] Push notifications
- [ ] Chat history search
- [ ] Group chat functionality

---

**Built with ❤️ using Laravel, Vue.js, TypeScript, and Pinia**