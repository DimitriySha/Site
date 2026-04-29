# Uyut - Short-term Apartment Rental Agency

A full-stack web application for managing short-term apartment rentals in Astana, Kazakhstan. The platform supports both guest and admin modes with comprehensive booking management.

## Features

### Guest Features
- Browse available apartments with advanced filtering (city, dates, guests, price range)
- Sort listings by price, popularity, or date
- View detailed apartment information (photos, amenities, location map)
- Book apartments for specific dates
- Manage bookings (view, cancel)
- Communicate with admin via messaging system
- Save favorite apartments
- Edit profile information

### Admin Features
- Dashboard with key statistics
- Add, edit, and delete apartment listings
- Manage all bookings (view, confirm, cancel)
- View and reply to user messages
- Basic analytics (reservations, users)

## Tech Stack

**Frontend**
- HTML5, CSS3 (custom responsive design)
- Vanilla JavaScript (ES6+)
- Font Awesome icons

**Backend**
- PHP 7.4+ with PDO
- SQLite database

**Server**
- Apache (via OpenServer or XAMPP)
- Compatible with GitHub Pages for hosting (static files)

## Installation

### Requirements
- PHP 7.4 or higher
- SQLite extension (enabled by default)
- Apache web server

### Setup Steps

1. **Clone or download the repository**

2. **Place files in web server directory**
   - For XAMPP: `C:\xampp\htdocs\uyut\`
   - For OpenServer: `domains\uyut\`

3. **Initialize the database**
   - Visit `http://localhost/uyut/api/init_db.php`
   - This creates the SQLite database and sample data

4. **Access the site**
   - Homepage: `http://localhost/uyut/`
   - Admin credentials:
     - Email: `admin@uyut.kz`
     - Password: `admin123`

## File Structure

```
uyut/
├── index.html              # Homepage with search
├── login.html              # User login
├── register.html           # User registration
├── listings.html           # Apartment listings with filters
├── apartment.html          # Apartment details & booking
├── bookings.html           # User's bookings
├── messages.html           # User messaging
├── favorites.html          # Saved apartments
├── profile.html            # User profile
├── admin.html              # Admin portal redirect
├── css/
│   └── style.css           # Main stylesheet
├── js/
│   └── app.js              # Client-side JavaScript
├── images/
│   └── apartments/         # Apartment photos
├── api/
│   ├── auth.php            # Authentication API
│   ├── apartments.php      # Apartment management API
│   ├── bookings.php        # Booking API
│   ├── favorites.php       # Favorites API
│   ├── messages.php        # Messaging API
│   └── admin.php           # Admin operations
├── admin/
│   └── dashboard.html      # Admin dashboard
└── database/
    └── db_connect.php      # Database connection
```

## API Endpoints

All API endpoints return JSON responses.

### Authentication
- `POST /api/auth.php` - Login/Register/Profile update
- `GET /api/auth.php?action=session` - Check current session

### Apartments
- `GET /api/apartments.php` - List apartments (with filters)
- `GET /api/apartments.php/{id}` - Get apartment details
- `POST /api/apartments.php` - Create apartment (admin)
- `PUT /api/apartments.php/{id}` - Update apartment (admin)
- `DELETE /api/apartments.php/{id}` - Delete apartment (admin)

### Bookings
- `GET /api/bookings.php` - List user bookings
- `POST /api/bookings.php` - Create booking
- `DELETE /api/bookings.php` - Cancel booking

### Favorites
- `GET /api/favorites.php` - List favorites
- `POST /api/favorites.php` - Add to favorites
- `DELETE /api/favorites.php` - Remove from favorites

### Messages
- `GET /api/messages.php` - List conversations
- `POST /api/messages.php` - Send message
- `PUT /api/messages.php` - Mark as read

### Admin
- `GET /api/admin.php?action=analytics` - Get dashboard stats
- `PUT /api/admin.php?action=booking` - Update booking status

## Features

### Search & Filtering
- Filter apartments by price range, number of guests, city
- Sort by price, date added, or capacity
- Search by title/description/address

### Booking System
- Check availability for selected dates
- Automatic price calculation
- Booking status tracking (pending, confirmed, cancelled, completed)

### Map Integration
- Each apartment shows its location on a Google Maps embed (Astana)

### Responsive Design
- Mobile-friendly interface
- Adapts to different screen sizes

## Database Schema

### Users
- `id`, `email`, `password`, `first_name`, `last_name`, `phone`, `role` (guest/admin), `created_at`

### Apartments
- `id`, `title`, `description`, `address`, `city`, `latitude`, `longitude`, `price_per_night`, `guests`, `bedrooms`, `beds`, `bathrooms`, `amenities` (JSON), `images` (JSON), `owner_id`, `is_available`, `created_at`

### Bookings
- `id`, `user_id`, `apartment_id`, `check_in`, `check_out`, `guests`, `total_price`, `status`, `created_at`

### Favorites
- `id`, `user_id`, `apartment_id`, `created_at`

### Messages
- `id`, `sender_id`, `receiver_id`, `apartment_id`, `subject`, `message`, `is_read`, `created_at`

## Security Notes

- Passwords are hashed using `password_hash()` with bcrypt
- SQLite queries use PDO prepared statements to prevent SQL injection
- Session-based authentication
- Admin routes protected by role checks
- Input sanitization on all form fields

## Deployment

### Local Development
1. Install OpenServer or XAMPP
2. Configure `localhost/uyut` to point to project directory
3. Initialize database via browser
4. Begin development

### Production Deployment
1. Upload all files to web server
2. Ensure PHP has SQLite extension enabled
3. Set proper file permissions (755 for directories, 644 for files)
4. Initialize database via browser
5. Change default admin credentials (modify `api/auth.php` insert)

### GitHub Pages Note
Static pages (HTML, CSS, JS) are GitHub Pages compatible. The PHP backend requires a PHP-enabled server. To deploy the full application, use a hosting service that supports PHP (shared hosting, VPS, etc.).

## Future Enhancements

- Payment gateway integration (Stripe/Kaspi)
- Email notifications
- User reviews and ratings
- Photo upload functionality for admins
- Advanced calendar booking
- Multiple currency support
- Mobile app (React Native)

## Credits

Developed for Uyut Rental Agency, Astana, Kazakhstan.

## License

Proprietary - All rights reserved.
