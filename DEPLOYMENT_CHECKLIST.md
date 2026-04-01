# 🚀 Hostinger Deployment Checklist - Maison Tech

## 📋 Pre-Deployment Preparation

### 1. Database Setup on Hostinger
- [ ] Login to Hostinger hPanel (https://hpanel.hostinger.com)
- [ ] Go to **Databases** → **MySQL Databases**
- [ ] Create new database:
  - Database Name: `u123456789_maison_tech` (replace with your actual username)
  - Username: `u123456789_maison` (same as above)
  - Password: Create a strong password (12+ characters, mix of letters, numbers, symbols)
  - Note down all credentials!

### 2. Update Database Configuration
- [ ] Open `dp.php.production` in your local editor
- [ ] Update lines 12-15 with your Hostinger credentials:
  ```php
  $username = "your_hostinger_username";
  $password = "your_strong_password";
  $dbname = "your_database_name";
  ```
- [ ] Save the file
- [ ] Rename it to `dp.php` (this will replace the local development version)

### 3. Prepare Files for Upload
- [ ] Remove development-only files:
  - Delete `.qoder/` folder (not needed in production)
  - Delete `website.zip.rar` (unnecessary backup)
  - Delete `*.sql` files (database already imported)
  - Delete `dp.php.production` (after renaming)
  
- [ ] Keep these folders:
  - ✅ `images/`
  - ✅ `uploads/`
  - ✅ All `.php` files
  - ✅ `.htaccess`

## 📤 Upload Methods

### Option A: Using Hostinger File Manager (Recommended for beginners)

1. **Login to hPanel**
   - Go to https://hpanel.hostinger.com
   - Navigate to **Files** → **File Manager**

2. **Navigate to public_html**
   - Open `public_html` folder
   - Delete default files if any (like `default.php`)

3. **Upload Files**
   - Click **Upload** button
   - Select all your PHP files and folders
   - Wait for upload to complete (may take 5-10 minutes)
   - Ensure `.htaccess` is uploaded (show hidden files if needed)

### Option B: Using FTP (FileZilla - Recommended for large projects)

1. **Get FTP Credentials**
   - In hPanel, go to **Files** → **FTP Accounts**
   - Note: Hostname, Username, Password, Port (usually 21)

2. **Connect with FileZilla**
   - Download FileZilla: https://filezilla-project.org
   - Enter FTP credentials
   - Connect to server

3. **Upload Files**
   - Local site: Your maison_tech folder
   - Remote site: `/public_html`
   - Select all files → Right-click → Upload
   - Wait for completion

## 🗄️ Database Import

### Step 1: Access phpMyAdmin
- In hPanel, go to **Databases** → **MySQL Databases**
- Find your database
- Click **Enter phpMyAdmin**

### Step 2: Import Database
- Click **Import** tab
- Choose file: Select `maison_tech.sql` (the largest SQL file - 50KB)
- Click **Go**
- Wait for success message

### Step 3: Verify Tables
- Check that these tables exist:
  - ✅ employees
  - ✅ categories
  - ✅ products
  - ✅ sales
  - ✅ sale_items
  - ✅ stock_movements
  - ✅ activity_logs
  - ✅ money_transactions
  - ✅ money_cash_opening
  - ✅ money_float_opening
  - ✅ money_daily_closing
  - ✅ expense_categories
  - ✅ expenses
  - ✅ attendance
  - ✅ settings

## ⚙️ Post-Deployment Configuration

### 1. Test Database Connection
- Visit: `https://yourdomain.com/login.php`
- If you see login page: ✅ Success!
- If database error: Check credentials in `dp.php`

### 2. Default Login Credentials
- **Username:** `admin`
- **Password:** `password` (CHANGE THIS IMMEDIATELY!)

### 3. Security Steps (CRITICAL!)
- [ ] Login as admin
- [ ] Go to Settings
- [ ] Change admin password immediately
- [ ] Create additional admin accounts if needed
- [ ] Set up employee accounts with appropriate roles

### 4. Configure System Settings
- [ ] Set up expense categories
- [ ] Configure opening cash balances
- [ ] Set up float balances for payment providers
- [ ] Add product categories
- [ ] Configure employee roles and permissions

### 5. Money Agent Permissions
- [ ] Go to Admin Settings
- [ ] Configure which Money Agents can access Balances page
- [ ] Test permission system

## 🔒 Security Hardening

### Essential Security Measures
- [ ] Enable SSL Certificate (hPanel → SSL)
- [ ] Force HTTPS (uncomment lines in `.htaccess`)
- [ ] Change default admin password
- [ ] Set strong passwords for all users
- [ ] Regular backups (hPanel → Backups)
- [ ] Keep PHP updated (hPanel shows current version)

### Recommended PHP Version
- Use PHP 7.4 or higher
- Check in hPanel → Advanced → Select PHP Version

## 🧪 Testing Checklist

### Core Functionality Tests
- [ ] Login/Logout works
- [ ] Dashboard loads correctly
- [ ] Can add/edit/delete products
- [ ] Sales transactions work
- [ ] Receipts generate properly
- [ ] Money agent operations work
- [ ] Expenses can be recorded
- [ ] Reports display correctly
- [ ] Employee management works
- [ ] Attendance tracking works

### Role-Based Access Tests
- [ ] Admin can access all features
- [ ] Chairman sees appropriate dashboard
- [ ] Manager has limited access (no profit visibility)
- [ ] Money Agent has restricted balances access
- [ ] Staff can only see own sales/receipts

### Permission Tests
- [ ] Grant Money Agent balances access
- [ ] Verify agent can edit balances
- [ ] Revoke access
- [ ] Verify agent gets "Access Denied"

## 📊 Performance Optimization

### Enable Caching
- Browser caching enabled via `.htaccess` ✅
- GZIP compression enabled ✅
- Consider adding Cloudflare (hPanel → Cloudflare)

### Image Optimization
- Compress images before upload
- Use WebP format where possible
- Recommended tools: TinyPNG, Squoosh

## 🔧 Troubleshooting

### Common Issues

**1. Database Connection Error**
```
Solution: Check dp.php credentials
- Verify username, password, database name
- Ensure database user has all privileges
- Check if database exists in phpMyAdmin
```

**2. Blank White Page**
```
Solution: Enable error logging temporarily
- Edit dp.php, line 16: change to $conn->connect_error
- Check /tmp/php_errors.log via FTP
- Common cause: PHP syntax error
```

**3. .htaccess Not Working**
```
Solution: 
- Ensure file is named exactly ".htaccess" (not .htaccess.txt)
- Check Apache mod_rewrite is enabled (usually is on Hostinger)
- File permissions should be 644
```

**4. Session/Login Issues**
```
Solution:
- Clear browser cookies/cache
- Check session settings in .htaccess
- Ensure HTTPS matches your configuration
```

**5. Images Not Uploading**
```
Solution:
- Check uploads/ folder permissions (755)
- Verify PHP upload_max_filesize in hPanel
- Check post_max_size setting
```

## 📞 Support Resources

### Hostinger Support
- Live Chat: Available 24/7 in hPanel
- Knowledge Base: https://www.hostinger.com/tutorials
- Video Tutorials: Hostinger YouTube channel

### Application Support
- Check activity logs for errors
- Review phpMyAdmin for database issues
- Test each module systematically

## 🎉 Go-Live Checklist

Before announcing your site is live:
- [ ] All tests passed ✅
- [ ] Admin password changed ✅
- [ ] SSL certificate active ✅
- [ ] Backup configured ✅
- [ ] Employee accounts created ✅
- [ ] Initial data entered ✅
- [ ] Money agent permissions set ✅
- [ ] Contact information updated ✅

## 📈 Next Steps After Deployment

1. **Daily Tasks**
   - Monitor activity logs
   - Check for low stock alerts
   - Review daily closing reports

2. **Weekly Tasks**
   - Generate weekly reports
   - Review employee performance
   - Check expense categories

3. **Monthly Tasks**
   - Full database backup
   - Review and optimize database
   - Audit user permissions
   - Review security logs

---

**🎊 Congratulations! Your Maison Tech system is now live on Hostinger!**

For technical support, contact Hostinger 24/7 chat or refer to their knowledge base.
