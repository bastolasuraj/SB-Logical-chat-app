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

### 🔄 In Progress
- **Task 3**: Frontend authentication components
- **Task 4**: Database schema for chat system
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
   git clone https://github.com/YOUR_USERNAME/SB-Logical-chat-app.git
   cd SB-Logical-chat-app
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   - Update `.env` with your database credentials
   - Update `.env` with your SMTP2GO credentials

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Build assets**
   ```bash
   npm run build
   ```

8. **Start development servers**
   ```bash
   # Laravel (Backend)
   php -S localhost:8093 -t public
   
   # Vite (Frontend Development)
   npm run dev
   
   # Laravel Reverb (WebSocket) - when needed
   php artisan reverb:start
   ```

## 📚 API Documentation

### Authentication Endpoints

#### Registration
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
  "message": "Registration successful. Please check your email for verification.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null
  }
}
```

#### Email Verification
```http
POST /api/auth/verify-email
Content-Type: application/json

{
  "email": "john@example.com",
  "code": "123456"
}
```
*OR*
```json
{
  "email": "john@example.com",
  "token": "verification-token-here"
}
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "avatar_url": "https://www.gravatar.com/avatar/...",
    "last_seen_at": "2024-01-01T00:00:00.000000Z"
  },
  "token": "1|abc123..."
}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

#### Logout from All Devices
```http
POST /api/auth/logout-all
Authorization: Bearer {token}
```

#### Get User Profile
```http
GET /api/auth/me
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "avatar_url": "https://www.gravatar.com/avatar/...",
    "last_seen_at": "2024-01-01T00:00:00.000000Z",
    "is_online": true
  }
}
```

### Error Responses

**Validation Error (422):**
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Authentication Error (401):**
```json
{
  "message": "Invalid credentials"
}
```

**Email Not Verified (403):**
```json
{
  "message": "Email not verified. Please verify your email before logging in.",
  "requires_verification": true
}
```

**Rate Limited (429):**
```json
{
  "message": "Too many login attempts. Please try again in 60 seconds."
}
```

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

Current test coverage:
- **User Model**: 12 tests passing
- **Registration API**: 12 feature tests passing
- **Login/Logout API**: 20 feature tests passing
- **Email Verification Service**: 11 unit tests passing
- **Total**: 55 tests passing
- **Chat Features**: Coming soon

## 📁 Project Structure

```
├── app/
│   ├── Models/
│   │   └── User.php              # User model with chat features
│   ├── Http/Controllers/
│   │   └── Auth/
│   │       └── RegisterController.php  # Registration API
│   ├── Services/
│   │   └── EmailVerificationService.php # Email verification logic
│   └── Notifications/
│       └── EmailVerificationNotification.php # Email templates
├── database/
│   ├── migrations/               # Database schema
│   └── factories/                # Model factories for testing
├── resources/
│   ├── js/
│   │   ├── App.vue              # Main Vue component
│   │   ├── app.ts               # Vue.js entry point
│   │   └── bootstrap.ts         # Laravel Echo setup
│   └── views/
│       └── app.blade.php        # SPA layout
├── tests/
│   ├── Unit/
│   │   └── UserModelTest.php    # User model tests
│   └── Feature/                 # Feature tests
└── routes/
    ├── web.php                  # Web routes
    └── channels.php             # WebSocket channels
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

## 🤝 Contributing

This is a learning project following a structured development approach with:
- Comprehensive requirements documentation
- Detailed technical design
- Test-driven development
- Git workflow with task-based commits

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🎯 Roadmap

- [ ] Complete authentication system
- [ ] Implement friend management
- [ ] Build real-time chat interface
- [ ] Add file sharing capabilities
- [ ] Mobile responsive design
- [ ] Push notifications
- [ ] Chat history search
- [ ] Group chat functionality

---

**Built with ❤️ using Laravel, Vue.js, and TypeScript**