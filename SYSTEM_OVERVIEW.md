# Learning Management System - Process Overview

## 🎯 System Purpose
A web-based learning management system for elementary students with role-based access control for administrators and staff.

---

## 👥 User Roles & Permissions

### 1. **Admin (ROLE_ADMIN)**
- ✅ Full system access
- ✅ Can view, create, edit, and delete ALL records
- ✅ Manage users (create, edit, delete staff accounts)
- ✅ View activity logs
- ✅ Access all sections

### 2. **Staff (ROLE_STAFF)**
- ✅ Can view ALL records (courses, categories, students, tutors, sessions)
- ✅ Can create new records
- ✅ Can ONLY edit/delete records they created themselves
- ❌ Cannot edit/delete records created by other staff or admin
- ❌ Cannot manage users or view activity logs

### 3. **Regular User (ROLE_USER)**
- ✅ Can access their own profile
- ✅ Can change password
- ❌ Cannot access admin dashboard

---

## 🔐 Authentication Flow

```
1. User visits login page
   ↓
2. Enters email and password
   ↓
3. System checks:
   - User exists?
   - Password correct?
   - Account is active?
   ↓
4. If valid:
   - Admin/Staff → Redirected to Dashboard
   - Regular User → Redirected to Profile
   ↓
5. If invalid:
   - Show error message
   - Stay on login page
```

---

## 📊 Data Management Process

### **Ownership System**
Every record (Course, Category, Student, Tutor, Lesson) has a `createdBy` field that tracks who created it.

**When Creating:**
```
Staff creates new record
   ↓
System automatically sets createdBy = current user
   ↓
Record is saved with ownership
```

**When Editing/Deleting:**
```
User clicks Edit/Delete
   ↓
System checks:
   - Is user Admin? → Allow
   - Is user the creator? → Allow
   - Otherwise → Show error, deny access
```

### **Viewing Records**
- All staff can VIEW all records (no restrictions)
- Edit/Delete buttons only appear for:
  - Records you created, OR
  - If you're an Admin

---

## 🗂️ Main Sections

### **1. Dashboard**
- Shows statistics (users, staff, records, activities)
- Displays chart: "Students & Tutors Added" over last 24 hours
- Recent activity feed
- Quick action buttons

### **2. Courses**
- List all courses
- Create/Edit/Delete (with ownership check)
- Search and filter

### **3. Categories**
- Organize courses into categories
- Create/Edit/Delete (with ownership check)

### **4. Tutors**
- Manage tutor profiles
- Card-based display
- Filter by specialty
- Create/Edit/Delete (with ownership check)

### **5. Students**
- Manage student profiles
- Card-based display
- Filter by grade
- Grade dropdown: Kindergarten to Grade 6 (elementary only)
- Create/Edit/Delete (with ownership check)

### **6. Sessions (Lessons)**
- Manage lesson sessions
- Link to courses
- Schedule dates
- Create/Edit/Delete (with ownership check)

### **7. Users** (Admin Only)
- Manage staff accounts
- Set roles (Admin/Staff)
- Set status (Active/Disabled)
- Change passwords

### **8. Activity Logs** (Admin Only)
- View all system activities
- Filter by user, action, date
- Track: CREATE, UPDATE, DELETE, LOGIN, LOGOUT

### **9. Messages**
- Internal messaging system
- Send messages between users
- Inbox/Sent folders
- Mark as read/unread

---

## 🔍 Search Functionality

### **Global Search (Top Bar)**
```
User types in search box (2+ characters)
   ↓
System searches:
   - Courses (title, description, level)
   - Categories (name, description)
   - Sessions/Lessons (title, description, course)
   ↓
Dropdown shows matching results
   ↓
User clicks result → Navigate to that item's page
```

---

## 📝 CRUD Operations Flow

### **CREATE**
```
1. User clicks "Add New [Entity]"
   ↓
2. Form appears
   ↓
3. User fills form and submits
   ↓
4. System:
   - Validates data
   - Sets createdBy = current user
   - Saves to database
   - Logs activity (CREATE)
   ↓
5. Redirect to index page
```

### **READ (View)**
```
1. User clicks "View" or item name
   ↓
2. System shows details page
   ↓
3. All staff can view (no restrictions)
```

### **UPDATE (Edit)**
```
1. User clicks "Edit"
   ↓
2. System checks ownership:
   - Admin? → Show form
   - Creator? → Show form
   - Otherwise → Show error, redirect
   ↓
3. If allowed:
   - Form pre-filled with data
   - User makes changes
   - Submits
   - System validates and saves
   - Logs activity (UPDATE)
```

### **DELETE**
```
1. User clicks "Delete"
   ↓
2. Confirmation dialog appears
   ↓
3. User confirms
   ↓
4. System checks ownership:
   - Admin? → Delete
   - Creator? → Delete
   - Otherwise → Show error, redirect
   ↓
5. If allowed:
   - Delete from database
   - Delete associated files (images)
   - Logs activity (DELETE)
```

---

## 🎨 UI/UX Features

### **Dark Mode**
- Toggle button in top bar
- Preference saved in browser
- All components adapt automatically
- Charts update theme dynamically

### **Responsive Design**
- Works on desktop, tablet, mobile
- Sidebar collapses on small screens
- Tables become scrollable

### **Consistent Styling**
- Pink gradient theme throughout
- Professional form styling
- Card-based layouts
- Smooth transitions and hover effects

---

## 🔒 Security Features

1. **Authentication Required**
   - Must login to access admin area
   - Session-based authentication

2. **Role-Based Access Control**
   - Routes protected by roles
   - Controllers check permissions
   - Templates hide/show based on roles

3. **Ownership Validation**
   - Staff can only modify their own work
   - Admin bypasses all restrictions
   - Server-side validation (not just UI)

4. **CSRF Protection**
   - All forms have CSRF tokens
   - Prevents cross-site request forgery

5. **Account Status Check**
   - Disabled accounts cannot login
   - UserChecker validates on authentication

---

## 📈 Activity Logging

**Automatically Logs:**
- CREATE: When any entity is created
- UPDATE: When any entity is modified
- DELETE: When any entity is removed
- LOGIN: When user logs in
- LOGOUT: When user logs out

**Log Contains:**
- User who performed action
- User's role
- Action type
- Target entity type
- Target entity label/name
- Timestamp

---

## 🗄️ Database Structure

**Main Entities:**
- `user` - System users (admin, staff)
- `student` - Student profiles
- `tutor` - Tutor profiles
- `course` - Course information
- `category` - Course categories
- `lessons` - Lesson sessions
- `message` - Internal messages
- `activity_log` - System activity records

**Key Relationships:**
- Course → createdBy (User)
- Student → createdBy (User)
- Tutor → createdBy (User)
- Category → createdBy (User)
- Lessons → createdBy (User)
- Lessons → course (Course)
- Message → sender/recipient (User)

---

## 🚀 Key Workflows

### **Staff Creating a Student:**
1. Navigate to Students section
2. Click "Add New Student"
3. Fill form (name, email, phone, grade dropdown, image)
4. Submit
5. System saves with `createdBy = current staff member`
6. Activity logged: "CREATE student [name]"
7. Redirected to student list

### **Staff Trying to Edit Another's Record:**
1. Staff sees all records in list
2. Edit button only visible for own records
3. If they try to access edit URL directly:
   - System checks ownership
   - Shows error: "You cannot edit this because you did not create it"
   - Redirects back to list

### **Admin Workflow:**
1. Admin sees all records
2. All Edit/Delete buttons visible
3. Can modify any record
4. Can manage users
5. Can view activity logs
6. Full system control

---

## 💡 Summary

**Simple Flow:**
1. **Login** → Role determines access
2. **View** → All staff see everything
3. **Create** → Anyone can create, ownership tracked
4. **Edit/Delete** → Only your own records (or Admin can do all)
5. **Activities** → Everything is logged automatically

**Key Principle:** 
- **View = Open** (all staff can see all records)
- **Modify = Restricted** (only your own work, unless Admin)






