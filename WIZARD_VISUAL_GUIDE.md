# 🎨 Installation Wizard - Visual Guide

## What Users See When They First Open The Application

### 📱 Step-by-Step Screenshots Description

---

## **Step 1: System Requirements Check**

### Visual Elements:
```
┌─────────────────────────────────────────────────────────┐
│  🛡️ Zwicky License Manager                             │
│  Installation Wizard v1.0.0                            │
└─────────────────────────────────────────────────────────┘
│                                                         │
│  Progress: ● ○ ○ ○ ○                                  │
│           ↑                                            │
│        Requirements → Database → Import → Admin → Finish│
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  System Requirements Check                              │
│  Before we begin, let's make sure your server meets    │
│  all the requirements.                                  │
│                                                         │
│  ✅ PHP Version >= 7.4                                 │
│  ✅ PDO Extension                                      │
│  ✅ PDO MySQL Driver                                   │
│  ✅ mbstring Extension                                 │
│  ✅ JSON Extension                                     │
│  ✅ OpenSSL Extension                                  │
│  ✅ cURL Extension                                     │
│  ✅ Config Directory Writable                          │
│  ✅ Logs Directory Writable                            │
│                                                         │
│  ✅ All requirements are met! You're ready to proceed. │
│                                                         │
│  [   Next: Database Configuration  →   ]               │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Color Scheme:
- **Header**: Blue gradient (#2563eb → #1d4ed8)
- **Success Items**: Green background (#f0fdf4) with dark green text
- **Error Items**: Red background (#fef2f2) with dark red text
- **Progress Bar**: Active = Blue, Completed = Green, Inactive = Gray

---

## **Step 2: Database Configuration**

### Visual Elements:
```
┌─────────────────────────────────────────────────────────┐
│  🛡️ Zwicky License Manager                             │
│  Installation Wizard v1.0.0                            │
└─────────────────────────────────────────────────────────┘
│                                                         │
│  Progress: ✓ ● ○ ○ ○                                  │
│              ↑                                          │
│        Requirements → Database → Import → Admin → Finish│
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Database Configuration                                 │
│  Enter your database connection details. Make sure      │
│  the database exists before continuing.                 │
│                                                         │
│  Database Host                                          │
│  ┌─────────────────────────────────────────────┐      │
│  │ localhost                                    │      │
│  └─────────────────────────────────────────────┘      │
│  Usually "localhost" for shared hosting               │
│                                                         │
│  Database Name                                          │
│  ┌─────────────────────────────────────────────┐      │
│  │                                              │      │
│  └─────────────────────────────────────────────┘      │
│  The name of your MySQL database                       │
│                                                         │
│  Database Username                                      │
│  ┌─────────────────────────────────────────────┐      │
│  │                                              │      │
│  └─────────────────────────────────────────────┘      │
│  MySQL username with full database privileges         │
│                                                         │
│  Database Password                                      │
│  ┌─────────────────────────────────────────────┐      │
│  │ ••••••••                                     │      │
│  └─────────────────────────────────────────────┘      │
│  MySQL user password (leave empty if none)            │
│                                                         │
│  Table Prefix                                           │
│  ┌─────────────────────────────────────────────┐      │
│  │ zwicky_                                      │      │
│  └─────────────────────────────────────────────┘      │
│  Prefix for all database tables                        │
│                                                         │
│  [  ← Back  ]  [ Test Connection & Continue  → ]      │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Features:
- **Live Validation**: Tests database connection before proceeding
- **Error Messages**: Clear error display if connection fails
- **Help Text**: Gray helper text under each field
- **Form Focus**: Blue border and shadow on active input
- **Responsive**: Adapts to mobile screens

---

## **Step 3: Database Import**

### Visual Elements:
```
┌─────────────────────────────────────────────────────────┐
│  🛡️ Zwicky License Manager                             │
│  Installation Wizard v1.0.0                            │
└─────────────────────────────────────────────────────────┘
│                                                         │
│  Progress: ✓ ✓ ● ○ ○                                  │
│                ↑                                        │
│        Requirements → Database → Import → Admin → Finish│
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Import Database Schema                                 │
│  Click the button below to import the database          │
│  structure.                                             │
│                                                         │
│  ✅ Database connection successful!                    │
│                                                         │
│  ┌───────────────────────────────────────────────────┐ │
│  │ ℹ️ What will be imported?                          │ │
│  │                                                     │ │
│  │  • License management tables                       │ │
│  │  • License activation tracking                     │ │
│  │  • Admin user management                           │ │
│  │  • Activity logging system                         │ │
│  │  • System settings configuration                   │ │
│  │  • Default data and indexes                        │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
│  [  ← Back  ]  [ 🗄️ Import Database Schema ]          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### After Import Success:
```
│  ✅ Database imported successfully!                    │
│     All tables have been created.                      │
│                                                         │
│  [        Next: Create Admin Account  →        ]       │
```

---

## **Step 4: Admin Account Creation**

### Visual Elements:
```
┌─────────────────────────────────────────────────────────┐
│  🛡️ Zwicky License Manager                             │
│  Installation Wizard v1.0.0                            │
└─────────────────────────────────────────────────────────┘
│                                                         │
│  Progress: ✓ ✓ ✓ ● ○                                  │
│                  ↑                                      │
│        Requirements → Database → Import → Admin → Finish│
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Create Admin Account                                   │
│  Create your administrator account to access the        │
│  system.                                                │
│                                                         │
│  Full Name                                              │
│  ┌─────────────────────────────────────────────┐      │
│  │                                              │      │
│  └─────────────────────────────────────────────┘      │
│                                                         │
│  Username                                               │
│  ┌─────────────────────────────────────────────┐      │
│  │ admin                                        │      │
│  └─────────────────────────────────────────────┘      │
│  Used for login                                        │
│                                                         │
│  Email Address                                          │
│  ┌─────────────────────────────────────────────┐      │
│  │                                              │      │
│  └─────────────────────────────────────────────┘      │
│  For notifications and password recovery               │
│                                                         │
│  Password                                               │
│  ┌─────────────────────────────────────────────┐      │
│  │ ••••••••                                     │      │
│  └─────────────────────────────────────────────┘      │
│  Minimum 8 characters (strong password recommended)    │
│                                                         │
│  Confirm Password                                       │
│  ┌─────────────────────────────────────────────┐      │
│  │ ••••••••                                     │      │
│  └─────────────────────────────────────────────┘      │
│                                                         │
│  [  ← Back  ]  [  Create Admin Account  →  ]          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Validation:
- ✅ Password length check (min 8 chars)
- ✅ Password match verification
- ✅ Email format validation
- ✅ Required field checking

---

## **Step 5: Installation Complete!**

### Visual Elements:
```
┌─────────────────────────────────────────────────────────┐
│  🛡️ Zwicky License Manager                             │
│  Installation Wizard v1.0.0                            │
└─────────────────────────────────────────────────────────┘
│                                                         │
│  Progress: ✓ ✓ ✓ ✓ ●                                  │
│                    ↑                                    │
│        Requirements → Database → Import → Admin → Finish│
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│              ┌─────────────┐                           │
│              │      ✓      │  (animated green circle)  │
│              └─────────────┘                           │
│                                                         │
│         Installation Complete!                          │
│  Your Zwicky License Manager has been successfully     │
│  installed.                                             │
│                                                         │
│  ✅ All installation steps completed successfully!     │
│                                                         │
│  ┌───────────────────────────────────────────────────┐ │
│  │ 🔐 Your Admin Credentials                          │ │
│  │                                                     │ │
│  │ Username: admin                                    │ │
│  │ Email: admin@example.com                           │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
│  ⚠️ Security Notice: For security reasons, please      │
│     delete wizard.php after installation.              │
│                                                         │
│  ┌───────────────────────────────────────────────────┐ │
│  │ ✅ Next Steps                                      │ │
│  │                                                     │ │
│  │ • Click "Complete Installation" to finalize setup  │ │
│  │ • Login with your admin credentials                │ │
│  │ • Configure system settings (email, SMTP, etc.)    │ │
│  │ • Create your first license                        │ │
│  │ • Review security settings                         │ │
│  │ • Delete wizard.php file                           │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
│  [  🚀 Complete Installation & Go to Login  ]          │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🎨 Design Characteristics

### Color Palette:
```
Primary Blue:    #2563eb
Dark Blue:       #1d4ed8
Light Blue:      #3b82f6

Success Green:   #10b981
Success Light:   #f0fdf4
Success Text:    #15803d

Error Red:       #dc2626
Error Light:     #fef2f2
Error Text:      #dc2626

Warning Yellow:  #d97706
Warning Light:   #fffbeb

Info Blue:       #1d4ed8
Info Light:      #eff6ff

Gray Scale:
  Background:    #f8fafc
  Border:        #e2e8f0
  Text Light:    #64748b
  Text Dark:     #1e293b
```

### Typography:
- **Font Family**: Inter (Google Fonts)
- **Heading Size**: 24px - 28px
- **Body Text**: 14px - 16px
- **Help Text**: 12px - 13px
- **Weight**: 400 (normal), 600 (medium), 700 (bold)

### Spacing:
- **Container Padding**: 40px
- **Form Groups**: 20px margin bottom
- **Input Padding**: 12px 16px
- **Button Padding**: 14px 24px
- **Border Radius**: 8px (buttons, inputs), 16px (container)

### Animations:
```css
Slide In:     0.5s ease (container entrance)
Fade In:      0.3s ease (alerts)
Scale In:     0.5s ease (success icon)
Hover Lift:   0.2s ease (buttons)
Focus Ring:   0.2s ease (inputs)
```

---

## 📱 Responsive Behavior

### Desktop (> 800px)
- Container max-width: 800px
- Full 2-column layout where applicable
- All features visible
- Hover effects active

### Tablet (600px - 800px)
- Container width: 95%
- Single column layout
- Touch-friendly buttons
- Larger tap targets

### Mobile (< 600px)
- Full-width container with padding
- Stacked form fields
- Larger fonts for readability
- Optimized progress bar
- Simplified animations

---

## ⚡ User Experience Features

### 1. **Visual Feedback**
- ✅ Button hover effects (lift up on hover)
- ✅ Input focus states (blue glow)
- ✅ Progress indicator (always visible)
- ✅ Loading states during processing

### 2. **Error Handling**
- ✅ Clear error messages
- ✅ Color-coded alerts
- ✅ Field-level validation
- ✅ Helpful recovery instructions

### 3. **Accessibility**
- ✅ Keyboard navigation
- ✅ Focus indicators
- ✅ Semantic HTML
- ✅ ARIA labels where needed
- ✅ Clear contrast ratios

### 4. **Smart Defaults**
- ✅ Pre-filled common values
- ✅ Sensible placeholder text
- ✅ Helpful tooltips
- ✅ Contextual help text

---

## 🔧 Technical Implementation

### Frontend:
- **HTML5**: Modern semantic markup
- **CSS3**: Flexbox, animations, gradients
- **JavaScript**: Form validation (vanilla JS)
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Inter)

### Backend:
- **PHP 7.4+**: Server-side logic
- **PDO**: Database abstraction
- **Sessions**: State management
- **Password Hashing**: bcrypt via password_hash()

### Security:
- **XSS Protection**: htmlspecialchars() on output
- **SQL Injection**: PDO prepared statements
- **CSRF Protection**: Session tokens
- **One-Time Use**: Lock file prevents re-runs

---

## 🎯 User Journey

```
User Types URL
      ↓
index.php checks install.lock
      ↓
Not Found → Redirect to wizard.php
      ↓
Step 1: Requirements Check (auto-validates)
      ↓
Step 2: Database Config (tests connection)
      ↓
Step 3: Import Schema (creates tables)
      ↓
Step 4: Admin Account (validates password)
      ↓
Step 5: Success! (creates lock file)
      ↓
Redirect to admin/login.php
      ↓
User Logs In
      ↓
Dashboard
```

---

## 💡 Pro Tips

### For Developers:
- Easy to customize colors (all in one `<style>` block)
- Can add custom validation rules
- Can extend with additional steps
- Can integrate with external services

### For Users:
- Have database credentials ready before starting
- Use a strong password for admin account
- Save credentials in secure location
- Complete wizard in one session
- Don't refresh page during import

---

**Made with ❤️ by Zwicky Technology**

*The wizard that makes installation a breeze!*
