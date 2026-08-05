# Umma Directory - Yelp-Style Muslim Community Platform

## Phase 1: Database & Authentication Setup ✅

### Completed Files

#### 1. Database Schema (`database/schema.sql`)
Complete MySQL database with 20+ tables including:
- **Users & Authentication**: users, user_sessions, password_resets
- **Listings**: businesses, mosques, fundis, charities with Yelp-style features
- **Reviews & Ratings**: reviews, review_photos, review_helpful votes
- **Photos & Media**: Separate photo tables for each listing type
- **Check-ins & Activity**: checkins, favorites
- **Donations**: charities, campaigns, donations with payment tracking
- **Advertising**: ad_placements, ads, impressions, clicks tracking
- **Communication**: messages, notifications
- **Moderation**: reports system

**Key Yelp-Style Features:**
- Rating averages with review counts
- Check-in system with badges
- Photo uploads with categories
- Helpful vote system on reviews
- Business verification & claiming
- Contributor levels & badges
- Price range indicators ($-$$$$)

#### 2. Configuration (`config/database.php`)
- Database connection settings
- Application constants
- Security settings (hash cost, session lifetime)
- File upload configuration
- Payment gateway settings (M-Pesa, PayPal)
- Prayer times API configuration
- Environment-based error reporting

#### 3. Core Classes

**Database Class** (`includes/Database.php`):
- Singleton PDO connection
- Prepared statements for security
- Query methods: fetchOne, fetchAll, insert, update, delete
- Transaction support
- Error handling

**Auth Class** (`includes/Auth.php`):
- User registration with validation
- Login/logout with session management
- Password reset functionality
- CSRF token generation/verification
- Role-based access control
- Session persistence in database

#### 4. Helper Functions (`includes/helpers.php`)
- HTML escaping (`e()`)
- URL generation (`url()`, `asset()`)
- JSON responses for AJAX
- Input sanitization
- Star rating display
- Distance calculation (Haversine formula)
- Phone formatting
- Date/time formatting
- Time ago calculations
- Currency formatting
- Prayer time calculations
- Business hours checking

### Project Structure
```
/workspace
├── config/
│   └── database.php          # Configuration & constants
├── database/
│   └── schema.sql            # Complete database schema
├── includes/
│   ├── Database.php          # Database connection class
│   ├── Auth.php              # Authentication system
│   └── helpers.php           # Utility functions
├── assets/
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   └── images/               # Static images
├── uploads/
│   ├── businesses/           # Business photos
│   ├── mosques/              # Mosque photos
│   ├── fundis/               # Fundi portfolio
│   ├── charities/            # Charity images
│   ├── reviews/              # Review photos
│   ├── ads/                  # Ad creatives
│   └── profiles/             # User profile photos
├── pages/
│   ├── auth/                 # Login, register, password reset
│   ├── businesses/           # Business listing pages
│   ├── mosques/              # Mosque directory pages
│   ├── fundis/               # Fundi directory pages
│   ├── charities/            # Charity & donation pages
│   ├── user/                 # User dashboard
│   └── admin/                # Admin panel
├── api/                      # REST API endpoints
└── index.php                 # Homepage
```

### Next Steps - Choose One:

1. **Create Authentication Pages**
   - Login/Register forms
   - Password reset flow
   - User profile management

2. **Build Homepage & Navigation**
   - Main landing page with search
   - Header/footer components
   - Category browsing

3. **Implement Business Listings**
   - Business CRUD operations
   - Search & filter functionality
   - Business detail page with reviews

4. **Create Review System**
   - Write review form
   - Star rating component
   - Photo upload for reviews
   - Helpful vote system

5. **Set Up Mosques Directory**
   - Mosque listings
   - Prayer times integration (Aladhan API)
   - Mosque detail page

Which would you like to tackle next?
