# Secure Authentication System

A comprehensive authentication and authorization system built with Laravel, featuring OAuth2, Two-Factor Authentication (2FA), and secure session management.

> **Security Notice**: For detailed security information and vulnerability reporting guidelines, please refer to our [Security Policy](SECURITY.md).

## Features

- 🔐 Secure Session Management
  - Session-based Authentication
  - Protection Against Session Fixation
  - Multi-device Support
  - Session Timeout & Auto-logout
  - Session Monitoring & Management

- 🔑 OAuth2 Implementation
  - Authorization Code Flow
  - Client Credentials Flow
  - Refresh Token Support
  - Scope-based Access Control
  - Token Revocation

- 📱 Two-Factor Authentication (2FA)
  - TOTP (Time-based One-Time Password)
  - QR Code Setup
  - Backup Recovery Codes
  - Compatible with Google Authenticator
  - Optional per User

- 🛡️ Security Features
  - Strong Password Policy
  - Rate Limiting
  - Security Headers
  - HTTPS Enforcement
  - SQL Injection Protection
  - XSS Protection
  - CSRF Protection
  - Audit Logging

## Quick Start

### Prerequisites
- Docker
- Docker Compose
- Git

### Installation

1. Clone the repository:
```bash
git clone https://github.com/mertsnmz/shield-auth.git
cd shield-auth
```

2. Set up environment:
```bash
cp .env.example .env
```

3. Start Docker containers:
```bash
docker-compose up -d --build
```

The system will automatically:
- Build and start all necessary containers
- Set up the MySQL database
- Run migrations and seeders
- Configure the web server

### Access Points
- Web Interface: http://localhost:8000
- 2FA Test Page: http://localhost:8000/2fa-test.html
- API Base URL: http://localhost:8000/api

### Default Test Account
- Email: test@example.com
- Password: Test123!@#$%^&*

## API Documentation

### Interactive Documentation & Testing

1. Generate API documentation:
```bash
docker exec auth-app php artisan scribe:generate
```

2. Access points:
- API Documentation: http://localhost:8000/docs/
- Postman Collection: http://localhost:8000/docs/collection.json
- OpenAPI (Swagger) Specification: http://localhost:8000/docs/openapi.yaml

The documentation includes:
- Detailed API endpoints
- Request/Response examples
- Authentication instructions
- Sample code snippets
- Interactive API testing interface

### Authentication Endpoints
- `POST /api/auth/login` - User Login
  ```json
  {
    "email": "user@example.com",
    "password": "password",
    "remember_me": false,
    "2fa_code": "123456" // Optional
  }
  ```

- `POST /api/auth/register` - User Registration
  ```json
  {
    "email": "user@example.com",
    "password": "password",
    "password_confirmation": "password"
  }
  ```

- `POST /api/auth/logout` - User Logout
  - Requires: Valid session cookie

### Password Management
- `POST /api/auth/password/forgot` - Password Reset Request
- `POST /api/auth/password/reset` - Password Reset Execution

### Two-Factor Authentication (2FA)
- `POST /api/auth/2fa/enable` - Enable 2FA
- `POST /api/auth/2fa/verify` - Verify 2FA Setup
- `POST /api/auth/2fa/disable` - Disable 2FA
- `GET /api/auth/2fa/backup-codes` - Get Backup Codes

### OAuth2 Endpoints
- `POST /api/oauth/token` - Get Access Token
- `POST /api/oauth/token/revoke` - Revoke Token
- `GET /api/oauth/authorize` - Authorization Request
- `POST /api/oauth/authorize` - Authorization Approval

### User Management
- `GET /api/users/me` - Get Profile
- `PUT /api/users/me` - Update Profile
- `PUT /api/users/me/password` - Change Password
- `GET /api/users/me/sessions` - List Active Sessions
- `DELETE /api/users/me/sessions/{id}` - Terminate Session

## Development Guide

### Container Management

1. Access containers:
```bash
# PHP container
docker exec -it auth-app bash

# MySQL container
docker exec -it auth-db mysql -uroot -p46t#kf86T
```

2. Laravel Commands:
```bash
# Run migrations
docker exec auth-app php artisan migrate

# Run seeders
docker exec auth-app php artisan db:seed

# Clear cache
docker exec auth-app php artisan optimize:clear
```

### Database Management

1. Connect to MySQL:
```bash
docker exec -it auth-db mysql -uroot -p46t#kf86T
```

2. Common MySQL commands:
```sql
-- List databases
SHOW DATABASES;

-- Use auth database
USE shield_auth_db;

-- List tables
SHOW TABLES;

-- Check user permissions
SHOW GRANTS FOR 'shield_auth_user'@'%';
```

### Security Maintenance

1. Run security audit:
```bash
docker exec auth-app php artisan security:audit
```

2. Clean expired tokens:
```bash
docker exec auth-app php artisan oauth:clean-tokens
```

3. Clean old sessions:
```bash
docker exec auth-app php artisan session:clean
```

### Troubleshooting

1. Container Issues:
```bash
# Check container status
docker-compose ps

# View container logs
docker-compose logs -f

# Restart containers
docker-compose restart
```

2. Database Issues:
```bash
# Reset database
docker exec auth-app php artisan migrate:fresh --seed

# Check MySQL status
docker exec auth-db mysqladmin -uroot -p46t#kf86T status
```

3. Cache Issues:
```bash
# Clear all Laravel cache
docker exec auth-app php artisan optimize:clear

# Rebuild cache
docker exec auth-app php artisan optimize
```

### Testing & Code Coverage

1. Run all tests:
```bash
docker exec auth-app php artisan test
```

2. Run tests with coverage report:
```bash
docker exec auth-app php artisan test --coverage
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'feat: add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security

If you discover any security vulnerabilities, please report them via email. All security vulnerabilities will be promptly addressed.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Security Considerations

### Authentication & Authorization
- **Session Management**
  - Session tokens are securely hashed using SHA-256
  - Sessions expire after 3 hours of inactivity
  - Sessions are bound to IP address and user agent
  - Automatic cleanup of expired sessions via scheduled task

- **Password Security**
  - Passwords are hashed using bcrypt with appropriate work factor
  - Password policy enforces minimum length and complexity requirements
  - Password reset tokens expire after 1 hour
  - Rate limiting on password reset attempts

- **Two-Factor Authentication (2FA)**
  - TOTP-based two-factor authentication (RFC 6238)
  - Secure backup codes for account recovery
  - Rate limiting on 2FA verification attempts
  - 2FA secrets are encrypted at rest

- **OAuth 2.0 Implementation**
  - Secure client registration with hashed client secrets
  - Authorization codes expire after 10 minutes
  - Refresh tokens are rotated on use
  - Scope-based access control
  - PKCE support for mobile clients

### API Security
- **Rate Limiting**
  - API-wide rate limiting to prevent abuse
  - Separate limits for authentication endpoints
  - IP-based and token-based rate limiting

- **Input Validation & Sanitization**
  - All user input is validated and sanitized
  - SQL injection prevention via prepared statements
  - XSS protection through proper output encoding
  - CSRF protection for session-based routes

- **Transport Security**
  - HTTPS required for all API endpoints
  - Strict Transport Security (HSTS) enabled
  - Secure cookie attributes (HttpOnly, Secure, SameSite)
  - TLS 1.2+ required

- **Data Protection**
  - Sensitive data is encrypted at rest
  - PII (Personally Identifiable Information) is properly handled
  - Logs are sanitized to prevent sensitive data exposure
  - Regular data cleanup for expired/inactive records

### Infrastructure Security
- **Environment Configuration**
  - Secure environment variable handling
  - Production configuration hardening
  - Separate configurations for development/staging/production

- **Error Handling**
  - Custom error handling to prevent information leakage
  - Detailed logging for security events
  - Proper exception handling throughout the application

### Security Headers
```php
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'
Referrer-Policy: strict-origin-when-cross-origin
```

### Monitoring & Auditing
- Security event logging
- Failed authentication attempts tracking
- Suspicious activity monitoring
- Regular security audits

### Compliance
- GDPR compliance measures
- Data retention policies
- User consent management
- Privacy policy implementation

## Security Testing
- Regular vulnerability scanning
- Penetration testing procedures
- Automated security testing in CI/CD
- Dependencies security scanning

For reporting security vulnerabilities, please email security@example.com or refer to our security policy at SECURITY.md.
