# 🏢 Maison Tech - Business Management System

A comprehensive PHP-based business management system for retail operations, inventory management, financial tracking, and employee management.

## 📦 Deployment Package Contents

### Core Files
- **PHP Application Files** (30+ files)
  - Dashboard & Reports
  - Product & Inventory Management
  - Sales & POS System
  - Money Agent Operations
  - Employee Management
  - Expense Tracking
  - Attendance System
  
- **Database Files**
  - `maison_tech.sql` - Complete database schema (50KB)
  - `setup_database.php` - Automated setup script
  
- **Configuration**
  - `dp.php` - Database connection (UPDATE FOR PRODUCTION)
  - `.htaccess` - Security & performance rules
  
- **Assets**
  - `/images/` - Company logo and graphics
  - `/uploads/` - User uploaded files

## 🚀 Quick Start - Hostinger Deployment

### Step 1: Prepare Your Hostinger Account

1. **Login to Hostinger hPanel**
   - URL: https://hpanel.hostinger.com
   
2. **Create Database**
   - Navigate to: Databases → MySQL Databases
   - Create new database with these details:
     - Database Name: `u123456789_maison_tech`
     - Username: `u123456789_maison`
     - Password: [Generate strong password]
   - **Save all credentials!**

### Step 2: Configure Database Connection

1. Open `dp.php.production` in text editor
2. Update lines 12-15:
   ```php
   $username = "your_actual_username";
   $password = "your_strong_password";
   $dbname = "your_database_name";
   ```
3. Save as `dp.php` (replacing the development version)

### Step 3: Upload Files

**Option A: File Manager (Easiest)**
1. hPanel → Files → File Manager
2. Navigate to `public_html`
3. Upload all PHP files and folders
4. Ensure `.htaccess` is included

**Option B: FTP (Recommended for large uploads)**
1. Get FTP credentials from hPanel → FTP Accounts
2. Connect using FileZilla
3. Upload to `/public_html`

### Step 4: Setup Database

**Method 1: Automated (Recommended)**
1. Visit: `https://yourdomain.com/setup_database.php`
2. Follow on-screen instructions
3. Delete `setup_database.php` after success!

**Method 2: Manual via phpMyAdmin**
1. hPanel → Databases → phpMyAdmin
2. Select your database
3. Import tab → Choose `maison_tech.sql`
4. Click Go

### Step 5: Access Your System

1. Visit: `https://yourdomain.com/login.php`
2. Default login:
   - **Username:** `admin`
   - **Password:** `password`
3. **⚠️ CHANGE PASSWORD IMMEDIATELY!**

## 🔐 Security Checklist

After deployment:
- ✅ Change admin password
- ✅ Enable SSL certificate (hPanel → SSL)
- ✅ Force HTTPS (edit `.htaccess`)
- ✅ Set strong passwords for all users
- ✅ Regular backups (hPanel → Backups)
- ✅ Keep PHP updated (7.4+)
- ✅ Delete `setup_database.php` after use

## 👥 User Roles & Permissions

### Admin (Full Access)
- All system features
- Employee management
- Settings configuration
- Reports & analytics
- Permission management

### Chairman (Executive Access)
- View all dashboards
- Company-wide reports
- Profit visibility
- Employee monitoring
- No operational restrictions

### Manager (Limited Access)
- Own sales only
- No profit visibility
- Cannot view other employees' sales
- Receipts limited to own transactions
- No bill payment access

### Money Agent (Specialized Access)
- Mobile money operations
- Transaction management
- Balances page (requires admin permission)
- Daily closing reports

### Staff (Basic Access)
- Own sales records
- Personal receipts
- Basic operations
- No administrative features

## 📊 System Features

### 💰 Sales Management
- Point of Sale (POS) interface
- Multiple payment methods (Cash, Mobile Money, Bank)
- Receipt generation
- Return management
- Sales reports

### 📦 Inventory Control
- Product catalog
- Stock tracking
- Low stock alerts
- Stock movements history
- Category management

### 💵 Money Operations
- Cash-in/Cash-out tracking
- Multiple providers (M-Pesa, Mixx by Yass, Airtel Money, etc.)
- Opening balances
- Daily closing
- Commission tracking
- Agent monitoring

### 👨‍💼 HR Management
- Employee database
- Role-based access
- Attendance tracking
- Activity logs
- Salary management

### 📈 Financial Reports
- Income statements
- Profit analysis
- Expense tracking
- Bill payments
- Custom reports

### 🎯 Additional Features
- Client orders
- About Us management
- Contact information
- Backup/export tools
- Responsive design

## 🗄️ Database Structure

### Main Tables
- `employees` - User accounts & roles
- `products` - Inventory items
- `categories` - Product categories
- `sales` - Sales transactions
- `sale_items` - Sale line items
- `stock_movements` - Inventory changes
- `activity_logs` - User actions

### Money Tables
- `money_transactions` - Payment operations
- `money_cash_opening` - Cash balance
- `money_float_opening` - Provider floats
- `money_daily_closing` - End-of-day reports

### Financial Tables
- `expenses` - Company expenses
- `expense_categories` - Expense categories
- `settings` - System configuration

### HR Tables
- `attendance` - Employee attendance
- `salary_management` - Payroll (if used)

## 🔧 Configuration

### Environment Variables (Optional)
Edit `dp.php` for production:
```php
$servername = "localhost";
$username = "your_db_username";
$password = "your_db_password";
$dbname = "your_db_name";
```

### .htaccess Features
- Directory browsing disabled
- Sensitive files protected
- GZIP compression enabled
- Browser caching configured
- Security headers set
- Session security hardened

## 📱 Mobile Responsiveness

The system is fully responsive and works on:
- Desktop computers
- Tablets (iPad, Android tablets)
- Mobile phones (iOS, Android)
- Touch screen devices

## 🎨 Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Charts:** Chart.js
- **Icons:** Font Awesome
- **Server:** Apache (mod_rewrite)

## 📊 Performance Optimization

### Built-in Optimizations
- GZIP compression
- Browser caching
- Database connection pooling
- Session-based schema verification
- Query optimization

### Recommended Add-ons
- Cloudflare CDN (free tier)
- Hostinger auto-cache
- Image optimization (WebP format)

## 🐛 Troubleshooting

### Common Issues

**Blank white page:**
```
Solution: Check /tmp/php_errors.log or enable display_errors temporarily
```

**Database connection error:**
```
Solution: Verify credentials in dp.php match Hostinger database
```

**Login not working:**
```
Solution: Clear browser cookies, check session settings in .htaccess
```

**Images not uploading:**
```
Solution: Check uploads/ folder permissions (755), verify PHP upload limits
```

**Session timeout:**
```
Solution: Increase session.gc_maxlifetime in PHP settings
```

## 📞 Support

### Hostinger Resources
- **Live Chat:** 24/7 via hPanel
- **Knowledge Base:** hostinger.com/tutorials
- **Video Guides:** Hostinger YouTube channel

### Application Issues
1. Check activity logs
2. Review database in phpMyAdmin
3. Test each module systematically
4. Contact Hostinger support for server issues

## 🔄 Updates & Maintenance

### Regular Tasks
- **Daily:** Monitor activity, check low stock
- **Weekly:** Generate reports, review expenses
- **Monthly:** Database backup, security audit

### Backup Strategy
1. Export database via phpMyAdmin weekly
2. Download uploads/ folder monthly
3. Keep off-site backups
4. Test restores periodically

## 📄 License & Credits

This is a custom business management system developed for Maison Tech.

## 🎉 Ready to Deploy?

Follow the deployment checklist in `DEPLOYMENT_CHECKLIST.md` for step-by-step instructions!

---

**Version:** 2.0  
**Last Updated:** 2026  
**PHP Required:** 7.4+  
**MySQL Required:** 5.7+
