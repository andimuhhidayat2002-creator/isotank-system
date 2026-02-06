# SECURITY QUICK REFERENCE CARD
**Isotank Management System - Security Best Practices**

---

## 🔐 PASSWORD POLICY

### Requirements
- ✅ Minimal **12 karakter**
- ✅ Kombinasi **huruf besar & kecil**
- ✅ Minimal **1 angka**
- ✅ Minimal **1 simbol** (@, !, #, $, %, *, &)

### Good Examples
```
✅ Admin2026!Isotank
✅ Inspect#Kayan2026
✅ Maint@Tank2026!
```

### Bad Examples
```
❌ password
❌ 123456
❌ admin123
❌ isotank2026 (no symbol, no uppercase)
```

### Password Change
- 🔄 Change every **90 days**
- 🚫 Don't reuse last **5 passwords**
- 📧 Notify admin if you forget password

---

## 🚨 ACCOUNT LOCKOUT

### Rules
- ⚠️ **5 failed attempts** = Account locked
- ⏱️ Lock duration: **30 minutes**
- 📧 Email notification sent to user
- 🔓 Admin can unlock manually

### If Locked
1. Wait 30 minutes, OR
2. Contact admin to unlock
3. Check email for notification

---

## 🔒 SESSION SECURITY

### Timeouts
- **Field Users** (Inspector, Maintenance): 8 hours
- **Office Users** (Admin, Management): 8 hours
- **Auto-logout** after timeout

### Best Practices
- 🚪 Always **logout** when done
- 🔒 Lock screen if leaving computer
- 🚫 Don't share login credentials
- 📱 Don't save passwords in browser

---

## 📸 FILE UPLOAD LIMITS

### Photo Uploads
- 📏 Max size: **5 MB** per file
- 📁 Allowed formats: **JPEG, PNG, JPG**
- 🚫 No PDF, DOC, or other formats

### If Upload Fails
- ✂️ Compress photo before upload
- 📱 Use lower resolution camera setting
- 🔄 Try again with smaller file

---

## 🌐 NETWORK SECURITY

### HTTPS Only
- ✅ Always use: `https://kayanconect.com`
- ❌ Never use: `http://kayanconect.com`
- 🔒 Look for padlock icon in browser

### Public WiFi
- ⚠️ Avoid using public WiFi for sensitive operations
- 📶 Use mobile data if possible
- 🛡️ Use VPN if available

---

## 📊 RATE LIMITING

### Login Attempts
- 🔢 Max **5 attempts per minute** per user
- 🔢 Max **10 attempts per minute** per IP
- ⏱️ Wait 1 minute if exceeded

### API Calls
- 🔢 Max **60 requests per minute** per user
- ⏱️ Automatic throttling if exceeded

---

## 🚨 SECURITY INCIDENTS

### If You Suspect Breach
1. 🛑 **STOP** using the account immediately
2. 📧 Email: security@kayanconect.com
3. 📞 Call: [Admin Phone Number]
4. 📝 Document: What happened, when, where

### Don't
- ❌ Don't try to "fix" it yourself
- ❌ Don't share on public channels
- ❌ Don't delete evidence

---

## 📱 MOBILE APP SECURITY

### Device Security
- 🔒 Enable device lock (PIN/Pattern/Biometric)
- 🔄 Keep app updated to latest version
- 🚫 Don't root/jailbreak device
- 📵 Don't install from unknown sources

### Data Protection
- 💾 Offline data is encrypted
- 🔄 Sync regularly to avoid data loss
- 🗑️ Clear cache if device lost/stolen

---

## 🔍 WHAT TO REPORT

### Suspicious Activity
- 👤 Unknown login attempts
- 📧 Unexpected password reset emails
- 🔓 Account locked without reason
- 📊 Unusual data changes
- 🐛 Security bugs/vulnerabilities

### How to Report
1. Email: security@kayanconect.com
2. Subject: "[SECURITY] Brief description"
3. Include: Date, time, screenshots, details

---

## ✅ DAILY SECURITY CHECKLIST

### For All Users
- [ ] Using strong password (changed in last 90 days)
- [ ] Logged out when done
- [ ] No password sharing
- [ ] Using HTTPS (padlock visible)
- [ ] Device locked when not in use

### For Admins (Additional)
- [ ] Review login attempts log
- [ ] Check for locked accounts
- [ ] Monitor error logs
- [ ] Verify backup completion
- [ ] Review activity logs for anomalies

---

## 🆘 EMERGENCY CONTACTS

### Security Team
- **Email:** security@kayanconect.com
- **Phone:** [To be filled]
- **Response Time:** < 1 hour (critical), < 4 hours (normal)

### IT Support
- **Email:** support@kayanconect.com
- **Phone:** [To be filled]
- **Hours:** 08:00 - 17:00 WIB (Mon-Fri)

---

## 📚 TRAINING & RESOURCES

### Required Training
- 🎓 Security Awareness (Annual)
- 🎓 Password Management (Annual)
- 🎓 Incident Response (Annual)

### Resources
- 📖 Full Security Policy: [Link to policy document]
- 📖 User Manual: [Link to user manual]
- 📖 FAQ: [Link to FAQ]

---

## 🔄 VERSION HISTORY

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-07 | Initial security policy |

---

**Print this card and keep it visible at your workstation!**

---

*Last Updated: 7 Februari 2026*  
*Prepared by: Antigravity AI Security Agent*
