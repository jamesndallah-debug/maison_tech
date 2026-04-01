# 🚀 QUICK DEPLOYMENT CARD - Maison Tech

## ⚡ Fast Track (15 Minutes)

### 1️⃣ Database Setup (5 min)
```
hPanel → Databases → MySQL Databases
- Name: u123456789_maison_tech
- User: u123456789_maison  
- Pass: [Create strong password]
✅ SAVE ALL 3!
```

### 2️⃣ Update Config (2 min)
```
Edit dp.php.production:
Line 12: $username = "your_username"
Line 13: $password = "your_password"
Line 14: $dbname = "your_database"
Save as: dp.php
```

### 3️⃣ Upload Files (5 min)
```
File Manager or FTP → public_html/
Upload: All PHP files + folders
Include: .htaccess (hidden file)
```

### 4️⃣ Import Database (3 min)
```
Option A (Auto):
Visit: yourdomain.com/setup_database.php
Follow prompts, then DELETE file!

Option B (Manual):
phpMyAdmin → Import → maison_tech.sql
```

### 5️⃣ First Login (1 min)
```
URL: yourdomain.com/login.php
User: admin
Pass: password
⚠️ CHANGE IMMEDIATELY!
```

---

## ✅ Must-Do Checklist

- [ ] Database credentials updated in dp.php
- [ ] All files uploaded to public_html
- [ ] Database imported successfully
- [ ] Can access login page
- [ ] Changed admin password
- [ ] SSL certificate enabled
- [ ] Deleted setup_database.php

---

## 🔑 Default Credentials

**Username:** `admin`  
**Password:** `password`  

⚠️ **CHANGE THESE NOW!**

---

## 📞 Hostinger Quick Links

- **hPanel:** https://hpanel.hostinger.com
- **File Manager:** hPanel → Files
- **Database:** hPanel → Databases
- **phpMyAdmin:** Via MySQL Databases
- **SSL:** hPanel → Security
- **Support:** 24/7 Live Chat

---

## 🆘 Emergency Contacts

**Database Issues:** Check phpMyAdmin  
**File Issues:** Use File Manager  
**Login Issues:** Clear cookies/cache  
**Blank Page:** Check /tmp/php_errors.log  

---

## 🎯 Post-Deployment Tests

1. ✅ Login works
2. ✅ Dashboard loads
3. ✅ Can add product
4. ✅ Can make sale
5. ✅ Receipt prints
6. ✅ Reports show data

---

**Need Help?** See `DEPLOYMENT_CHECKLIST.md` for detailed guide!
