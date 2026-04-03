# 🌊 MAISON TECH - DIGITALOCEAN CLUSTER DEPLOYMENT GUIDE

## 📋 Overview

This guide covers deploying Maison Tech to DigitalOcean using a managed MySQL cluster.

---

## 🔐 DATABASE CREDENTIALS SUMMARY

### **For DigitalOcean Managed MySQL:**

You'll find these in your DigitalOcean Control Panel:

```
Location: Databases → Your Cluster → Connection Details

Hostname: your-cluster-do-user-xxxxx.db.ondigitalocean.com
Port: 25060 (default for DO Managed MySQL)
Username: doadmin
Password: [From your DO Control Panel]
Database: maison_tech
```

### **Default Application Login (After Import):**
```
Username: admin
Password: password
⚠️ CHANGE IMMEDIATELY AFTER FIRST LOGIN!
```

---

## 🚀 DEPLOYMENT STEPS

### **Step 1: Create DigitalOcean Managed Database**

1. Login to [DigitalOcean Control Panel](https://cloud.digitalocean.com/)
2. Go to **Databases** → **Create Database Cluster**
3. Choose:
   - **Provider:** DigitalOcean
   - **Database Type:** MySQL
   - **Version:** 8.x or latest
   - **Plan:** Basic/Pro (based on your needs)
   - **Region:** Same as your app droplets
4. Click **Create Database Cluster**
5. Wait for cluster to be ready (~5 minutes)

---

### **Step 2: Get Connection Details**

1. In your Database Cluster dashboard:
   - Click on your cluster name
   - Go to **Connection Details** tab
   - Note the following:
     ```
     Hostname: your-cluster-do-user-xxxxx.db.ondigitalocean.com
     Port: 25060
     Username: doadmin
     Password: [Click "Show Password"]
     ```

2. **Important:** Copy the password - you'll need it!

---

### **Step 3: Configure Database Connection**

1. **Copy the template file:**
   ```bash
   cp dp.php.digitalocean dp.php
   ```

2. **Edit `dp.php` with your credentials:**
   ```php
   $servername = "your-cluster-do-user-xxxxx.db.ondigitalocean.com";
   $username = "doadmin";
   $password = "YOUR_ACTUAL_PASSWORD_HERE";
   $dbname = "maison_tech";
   $port = 25060;
   ```

3. **Save and upload to your server**

---

### **Step 4: Create Database & Import Schema**

#### **Option A: Using DigitalOcean Admin Panel (Recommended)**

1. In DO Control Panel → Your Database → **Databases** tab
2. Click **Create Database**
3. Name it: `maison_tech`
4. Click **Create**

5. Now go to **Users** tab
6. Ensure user `doadmin` has full access

7. To import schema, use one of these methods:

#### **Option B: Using Command Line (SSH)**

```bash
# SSH into your app droplet/server
ssh root@your_server_ip

# Navigate to your web directory
cd /var/www/html/maison_tech

# Import the database
mysql -h your-cluster-do-user-xxxxx.db.ondigitalocean.com \
      -P 25060 \
      -u doadmin \
      -p'maissen_tech' < maison_tech_complete.sql
```

#### **Option C: Using phpMyAdmin (If installed)**

1. Install phpMyAdmin on your droplet:
   ```bash
   sudo apt update
   sudo apt install phpmyadmin
   ```

2. Configure phpMyAdmin to connect to DO database:
   - Host: your-cluster-do-user-xxxxx.db.ondigitalocean.com
   - Port: 25060
   - Username: doadmin
   - Password: [your password]

3. Import `maison_tech_complete.sql` via phpMyAdmin interface

---

### **Step 5: Configure Web Server**

#### **If Using Apache:**

```bash
sudo nano /etc/apache2/sites-available/maison_tech.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/maison_tech
    
    <Directory /var/www/html/maison_tech>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite maison_tech
sudo systemctl restart apache2
```

#### **If Using Nginx:**

```bash
sudo nano /etc/nginx/sites-available/maison_tech
```

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/maison_tech;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/maison_tech /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### **Step 6: Set File Permissions**

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/html/maison_tech

# Set directory permissions
sudo chmod -R 755 /var/www/html/maison_tech

# Uploads directory needs write access
sudo chmod -R 775 /var/www/html/maison_tech/uploads
```

---

### **Step 7: Enable SSL (HTTPS)**

#### **Using Let's Encrypt (Free):**

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
# OR
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Get SSL certificate
sudo certbot --apache -d yourdomain.com          # For Apache
# OR
sudo certbot --nginx -d yourdomain.com           # For Nginx

# Auto-renewal is configured automatically
# Test renewal with:
sudo certbot renew --dry-run
```

---

### **Step 8: Configure Firewall**

```bash
# Enable UFW firewall
sudo ufw enable

# Allow HTTP/HTTPS
sudo ufw allow 'Apache Full'    # For Apache
# OR
sudo ufw allow 'Nginx Full'     # For Nginx

# Allow SSH
sudo ufw allow OpenSSH

# Check status
sudo ufw status
```

---

### **Step 9: Configure Database Firewall (DigitalOcean)**

1. In DO Control Panel → Your Database → **Settings**
2. Go to **Trusted Sources**
3. Add your droplet's IP address:
   - Click **Add Source**
   - Enter your droplet's private IP (preferred) or public IP
   - Save

**Important:** Without this step, your app cannot connect to the database!

---

### **Step 10: Test Your Deployment**

1. **Visit your domain:** `http://yourdomain.com`
2. **Test login:** 
   - URL: `yourdomain.com/login.php`
   - Username: `admin`
   - Password: `password`
3. **Change admin password immediately!**

---

## 🔧 POST-DEPLOYMENT CHECKLIST

- [ ] Database connection working
- [ ] All tables imported successfully
- [ ] Can login as admin
- [ ] Changed default admin password
- [ ] SSL certificate installed (HTTPS)
- [ ] File permissions set correctly
- [ ] Uploads folder writable
- [ ] Database firewall allows app server
- [ ] Activity logs being recorded
- [ ] Money transactions working
- [ ] Sales processing working

---

## 🛡️ SECURITY HARDENING

### **1. Secure dp.php (Database Credentials)**

Move `dp.php` outside web root:
```bash
# Move config file
sudo mv /var/www/html/maison_tech/dp.php /var/www/config/dp.php

# Update all PHP files to reference new location
# Change: include 'dp.php';
# To: include '/var/www/config/dp.php';
```

### **2. Remove Setup Files**

```bash
# Delete after database setup
rm /var/www/html/maison_tech/setup_database.php
rm /var/www/html/maison_tech/dp.php.digitalocean
```

### **3. Disable Directory Listing**

Already configured in `.htaccess`, but also add to Apache/Nginx config.

### **4. Change Admin Password**

Login and change immediately from Settings page.

### **5. Regular Backups**

DigitalOcean provides automatic backups for managed databases. Enable them!

---

## 📊 MONITORING & MAINTENANCE

### **Check Database Connection:**

```sql
-- Connect to your DO database
mysql -h your-cluster.db.ondigitalocean.com -P 25060 -u doadmin -p

-- Check tables
USE maison_tech;
SHOW TABLES;

-- Check recent activity
SELECT * FROM activity_logs ORDER BY log_date DESC LIMIT 10;
```

### **Monitor Performance:**

1. **DigitalOcean Metrics:**
   - Go to your Database → Metrics tab
   - Monitor CPU, Memory, Connections

2. **Application Logs:**
   ```bash
   tail -f /var/log/apache2/error.log
   # OR
   tail -f /var/log/nginx/error.log
   ```

### **Database Maintenance:**

```sql
-- Optimize tables monthly
OPTIMIZE TABLE sales;
OPTIMIZE TABLE products;
OPTIMIZE TABLE money_transactions;

-- Check table health
CHECK TABLE employees;
```

---

## 🆘 TROUBLESHOOTING

### **"Connection refused" to Database**

**Problem:** App can't connect to DO database

**Solution:**
1. Check database firewall - add your droplet IP
2. Verify credentials in `dp.php`
3. Test connection manually:
   ```bash
   mysql -h your-cluster.db.ondigitalocean.com -P 25060 -u doadmin -p
   ```

### **"Table doesn't exist"**

**Solution:**
1. Import `maison_tech_complete.sql` again
2. Or run `setup_database.php` (if still available)
3. Check schema auto-verification in `dp.php`

### **"Access denied for user"**

**Solution:**
1. Verify username/password in `dp.php`
2. Check DO Control Panel → Users tab
3. Reset password if needed

### **Permission Denied Errors**

```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/html/maison_tech
sudo chmod -R 755 /var/www/html/maison_tech
sudo chmod -R 775 /var/www/html/maison_tech/uploads
```

---

## 📈 SCALING YOUR CLUSTER

### **When to Scale:**

- High CPU usage (>80% consistently)
- Memory pressure
- Slow query performance
- Many concurrent users

### **Scale Up (Vertical):**

1. DO Control Panel → Databases → Your Cluster
2. Click **Resize**
3. Choose larger plan
4. Changes apply immediately

### **Scale Out (Horizontal):**

1. Add read replicas
2. Configure load balancer
3. Use connection pooling

### **Optimize Queries:**

- Tables already indexed
- Use EXPLAIN to analyze slow queries
- Consider Redis caching for frequent queries

---

## 💰 COST ESTIMATES

### **Basic Setup (Small Business):**

- **Managed MySQL (Basic):** $15/month
- **App Droplet (Basic):** $6/month
- **Backups:** $2/month
- **Total:** ~$23/month

### **Standard Setup (Growing Business):**

- **Managed MySQL (Basic):** $30/month
- **App Droplet (Basic):** $12/month
- **Load Balancer:** $12/month
- **Backups:** $4/month
- **Total:** ~$58/month

### **Enterprise Setup (High Traffic):**

- **Managed MySQL (Professional):** $100+/month
- **Multiple App Droplets:** $40+/month
- **Load Balancer:** $12/month
- **Redis Cache:** $15/month
- **Total:** ~$167+/month

---

## ✅ FINAL NOTES

Your Maison Tech system is now running on DigitalOcean with:

✅ **Managed MySQL Cluster** - Automatic backups, updates, high availability  
✅ **Scalable Architecture** - Easy to add more app servers  
✅ **SSL Security** - Encrypted connections  
✅ **Firewall Protection** - Both DO and server-level  
✅ **Monitoring Ready** - Metrics available in DO panel  

**Need Help?**
- DigitalOcean Docs: https://docs.digitalocean.com/
- Community: https://www.digitalocean.com/community/questions
- Support: Via DO Control Panel

---

**🎉 Congratulations! Your Maison Tech system is deployed on DigitalOcean!** 🚀
