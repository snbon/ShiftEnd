# ShiftEnd User Stories & Flows

## 👥 User Types & Their Flows

### **1. Restaurant Owner**
*The person who owns one or more restaurants and manages the entire operation*

#### **Owner Journey:**
1. **Registration & Setup**
   - Registers with email/password
   - Verifies email
   - Logs in for first time
   - Completes onboarding (automatically assigned "Owner" role)
   - Creates first restaurant location/group through onboarding process

2. **Daily Operations**
   - Views dashboard with sales overview
   - Reviews pending reports from employees
   - Approves/rejects submitted reports
   - Manages team members (add/remove/change roles)
   - Creates additional restaurant locations
   - Views analytics and performance metrics

3. **Management Functions**
   - Invites new team members via email
   - Assigns roles (Manager, Employee) to team members
   - Manages multiple restaurant locations
   - Views consolidated reports across all locations
   - Sets up permissions and access levels

### **2. Restaurant Manager**
*The person who manages day-to-day operations at a specific restaurant*

#### **Manager Journey:**
1. **Onboarding**
   - Receives invitation email from owner
   - Registers through invitation link
   - Verifies email
   - Logs in (role/location pre-assigned)
   - Views assigned restaurant location

2. **Daily Operations**
   - Views dashboard with location-specific data
   - Reviews employee reports
   - Approves employee shift reports
   - Creates own shift reports
   - Manages team at their location
   - Invites new employees (with owner permission)

3. **Team Management**
   - Views team members at their location
   - Sends invitations to new employees
   - Reviews employee performance
   - Manages shift schedules

### **3. Restaurant Employee**
*The person who works shifts and submits reports*

#### **Employee Journey:**
1. **Onboarding**
   - Receives invitation email from owner/manager
   - Registers through invitation link
   - Verifies email
   - Logs in (role/location pre-assigned)
   - Views assigned restaurant location

2. **Daily Work**
   - Views dashboard with personal metrics
   - Creates shift reports after each shift
   - Enters sales data, tips, and notes
   - Submits reports for approval
   - Views personal report history
   - Tracks personal performance

3. **Report Management**
   - Creates new shift report
   - Fills in sales amount, tips received
   - Adds notes about shift
   - Submits report to manager/owner
   - Views approval status
   - Cannot edit submitted reports

## 🔄 Complete User Flows

### **Flow 1: New Owner Setup**
```
1. Owner visits ShiftEnd
2. Clicks "Register"
3. Fills registration form (name, email, password)
4. Receives verification email
5. Clicks verification link
6. Redirected to login page with success message
7. Logs in with credentials
8. Redirected to onboarding (no role/location yet)
9. Selects "Owner" role
10. Creates first restaurant location
11. Dashboard loads with owner features
12. Can now invite team members
```

### **Flow 2: Team Member Invitation**
```
1. Owner/Manager goes to Team page
2. Clicks "Invite Team Member"
3. Enters email, selects role (Manager/Employee)
4. System sends invitation email
5. Invited person receives email
6. Clicks invitation link
7. Fills registration form
8. Verifies email
9. Logs in (role/location already set)
10. Goes directly to dashboard (no onboarding needed)
```

### **Flow 3: Shift Report Creation**
```
1. Employee logs in
2. Goes to "Add Report" page
3. Selects date and shift time
4. Enters sales amount
5. Enters tips received (with checkbox to include/exclude tips from sales total)
6. Adds notes about shift
7. Clicks "Submit Report"
8. Report marked as "Pending" (Owner reports auto-approve)
9. Manager/Owner receives notification
10. Manager/Owner reviews report
11. Manager/Owner approves/rejects
12. Employee sees approval status
```

### **Flow 4: Report Approval Process**
```
1. Manager/Owner logs in
2. Sees dashboard with pending reports
3. Clicks on pending report
4. Reviews sales, tips, and notes
5. Clicks "Approve" or "Reject"
6. If approved: Report becomes final
7. If rejected: Employee can edit and resubmit
8. Employee receives notification
9. Dashboard updates with new status
```

### **Flow 5: User Removal & Account Management**
```
1. Owner/Manager removes user from location
2. System checks if user has other locations
3. If no locations remaining:
   - User is redirected to onboarding page
   - Shows "Delete Account" option
   - User can choose to delete account or join new location
4. If user has other locations:
   - User continues with remaining locations
   - No onboarding required
```

## 🎯 Core Functions by User Type

### **Owner Functions:**
- ✅ **User Management**: Invite, assign roles, remove team members
  - If user is removed from all locations and has no remaining groups → redirected to onboarding with "Delete Account" option
- ✅ **Location Management**: Create, edit, manage multiple restaurants
- ✅ **Report Oversight**: View all reports across all locations
- ✅ **Analytics**: View consolidated sales and performance data
- ✅ **Approval Authority**: Approve/reject any report (Owner reports auto-approve)
- ✅ **System Administration**: Manage permissions and access

### **Manager Functions:**
- ✅ **Team Management**: Invite employees, manage team at their location
- ✅ **Report Review**: Approve/reject employee reports
- ✅ **Personal Reports**: Create and submit own shift reports
- ✅ **Location Data**: View location-specific analytics
- ✅ **Employee Oversight**: Monitor team performance

### **Employee Functions:**
- ✅ **Report Creation**: Create shift reports with sales, tips, notes
- ✅ **Report Submission**: Submit reports for approval
- ✅ **Personal History**: View own report history
- ✅ **Performance Tracking**: See personal metrics
- ✅ **Status Monitoring**: Check approval status of submitted reports

## 🔧 Technical Implementation

### **Authentication Flow:**
```
Registration → Email Verification → Login → Role Check → Onboarding (if needed) → Dashboard
```

### **Role-Based Access:**
- **Owner**: Full access to all features
- **Manager**: Access to location-specific features + team management
- **Employee**: Access to personal features + report creation

### **Data Flow:**
```
Employee creates report → Submits to manager → Manager reviews → Approves/rejects → Data stored → Analytics updated
```

### **Database Schema Updates:**
- **Reports Table**: Add `tips_included_in_sales` boolean column
- **User Management**: Handle users removed from all locations → redirect to onboarding
- **Auto-Approval**: Owner reports automatically approved on submission

## 📊 Key Metrics by User Type

### **Owner Metrics:**
- Total sales across all locations
- Number of active team members
- Report approval rate
- Location performance comparison

### **Manager Metrics:**
- Location-specific sales
- Team performance
- Report processing time
- Employee productivity

### **Employee Metrics:**
- Personal sales performance
- Tips received
- Report submission rate
- Approval success rate

## 🚀 Future Enhancements

### **For Owners:**
- Multi-location analytics dashboard
- Advanced reporting tools
- Team performance insights
- Financial reporting

### **For Managers:**
- Shift scheduling tools
- Team communication features
- Performance tracking
- Inventory management

### **For Employees:**
- Mobile app for quick reporting
- Photo receipt upload
- Shift reminders
- Performance goals

---

*This document focuses on actual user journeys and practical functions rather than feature lists. Each user type has clear, actionable flows that guide their experience through the application.* 