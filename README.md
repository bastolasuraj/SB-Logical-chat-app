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

### 🔄 In Progress

- **Task 2.2**: Registration API with email verification
- **Task 2.3**: Login/logout API endpoints
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

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

Current test coverage:
- **User Model**: 12 tests passing
- **Authentication**: Coming soon
- **Chat Features**: Coming soon

## 📁 Project Structure

```
├── app/
│   ├── Models/
│   │   └── User.php              # User model with chat features
│   └── Http/Controllers/         # API controllers
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