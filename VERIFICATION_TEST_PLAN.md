# Verification Flow Test Plan

## 🎯 Test Objectives
Verify that the email verification flow works correctly and users are properly redirected to login and onboarding.

## 📋 Test Scenarios

### **Test 1: Email Verification Flow**
**Objective**: Verify that email verification redirects to login page with success message

**Steps**:
1. Register a new user
2. Check email for verification link
3. Click verification link
4. **Expected Result**: Should redirect to `https://demo.shiftend.be/login?verified=1`
5. **Expected Result**: Should show success message "Email verified successfully! You can now log in."
6. **Expected Result**: Should NOT go directly to dashboard
7. **Expected Result**: Should NOT show sidebar navigation

**Console Debugging**:
- Check browser console for `🔍 checkAuth` logs
- Should see "No stored user/token - clearing data"
- Should see "Redirecting to login from: /"

### **Test 2: Authentication Guards**
**Objective**: Verify that unauthenticated users cannot access protected pages

**Steps**:
1. Clear browser localStorage (or open incognito)
2. Try to access `https://demo.shiftend.be/dashboard`
3. **Expected Result**: Should redirect to login page
4. Try to access `https://demo.shiftend.be/locations`
5. **Expected Result**: Should redirect to login page
6. Try to access `https://demo.shiftend.be/team`
7. **Expected Result**: Should redirect to login page

**Console Debugging**:
- Should see router guard logs
- Should see "No stored user/token - clearing data"

### **Test 3: Login After Verification**
**Objective**: Verify that users can log in after verification

**Steps**:
1. Complete email verification (from Test 1)
2. Enter email and password on login page
3. Click "Login"
4. **Expected Result**: Should check user status via `/api/user`
5. **Expected Result**: Should redirect to onboarding (if no role/location)
6. **Expected Result**: Should redirect to dashboard (if has role/location)

**Console Debugging**:
- Should see `🔍 checkAuth - calling /api/user`
- Should see user info logs
- Should see onboarding decision logs

### **Test 4: Onboarding Flow**
**Objective**: Verify that users without role/location go to onboarding

**Steps**:
1. Login with a user who has no role/location
2. **Expected Result**: Should redirect to `/onboarding`
3. **Expected Result**: Should show onboarding form
4. **Expected Result**: Should NOT show sidebar navigation

**Console Debugging**:
- Should see "User needs onboarding - redirecting to /onboarding"

### **Test 5: Dashboard Access**
**Objective**: Verify that users with role/location go to dashboard

**Steps**:
1. Login with a user who has role/location
2. **Expected Result**: Should redirect to `/dashboard`
3. **Expected Result**: Should show sidebar navigation
4. **Expected Result**: Should show role-based menu items

**Console Debugging**:
- Should see "User has role and locations - skipping onboarding"

## 🔍 Debugging Information

### **Router Guard Logs**
The router guard should handle verification params first:
```javascript
// Handle verification params first
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('verified') === '1' || urlParams.get('verification_error') === '1') {
  // Clear any stored user data when arriving from email verification
  localStorage.removeItem('user');
  localStorage.removeItem('token');
  
  // Always redirect to login for verification results
  next('/login');
  return;
}
```

### **App.vue Debug Logs**
The checkAuth function should show detailed logs:
- `🔍 checkAuth - storedUser: exists/null`
- `🔍 checkAuth - storedToken: exists/null`
- `🔍 checkAuth - currentPath: /path`
- `🔍 checkAuth - calling /api/user`
- `🔍 checkAuth - userInfo: {...}`
- `🔍 checkAuth - userInfo.role: role`
- `🔍 checkAuth - userInfo.locations: [...]`

### **Expected User Flow**
1. **Registration**: User registers → Email sent
2. **Verification**: User clicks link → Backend verifies → Redirects to login with success
3. **Login**: User enters credentials → API checks user status
4. **Onboarding**: If no role/location → Redirect to onboarding
5. **Dashboard**: If has role/location → Redirect to dashboard

## 🚨 Common Issues to Check

### **Issue 1: Verification Bypasses Login**
**Symptoms**: User goes directly to dashboard after verification
**Cause**: Router guard not handling verification params
**Fix**: Router guard should redirect to login for verification params

### **Issue 2: No Onboarding Redirect**
**Symptoms**: User goes to dashboard without role/location
**Cause**: Onboarding check not working
**Fix**: checkAuth should redirect to onboarding if no role/location

### **Issue 3: Sidebar Missing**
**Symptoms**: Dashboard shows without sidebar navigation
**Cause**: User state not properly set
**Fix**: User should be null when not authenticated

### **Issue 4: Route Guards Not Working**
**Symptoms**: Can access protected pages without login
**Cause**: Router guard not checking authentication
**Fix**: Router guard should redirect to login for protected routes

## ✅ Success Criteria

All tests should pass:
- [ ] Email verification redirects to login
- [ ] Unauthenticated users cannot access protected pages
- [ ] Users without role/location go to onboarding
- [ ] Users with role/location go to dashboard
- [ ] Sidebar navigation shows only for authenticated users
- [ ] Console logs show proper debugging information

## 📝 Test Results

**Date**: _______________
**Tester**: _______________

| Test | Status | Notes |
|------|--------|-------|
| Test 1: Email Verification Flow | ⬜ Pass ⬜ Fail | |
| Test 2: Authentication Guards | ⬜ Pass ⬜ Fail | |
| Test 3: Login After Verification | ⬜ Pass ⬜ Fail | |
| Test 4: Onboarding Flow | ⬜ Pass ⬜ Fail | |
| Test 5: Dashboard Access | ⬜ Pass ⬜ Fail | |

**Overall Status**: ⬜ PASS ⬜ FAIL

**Issues Found**:
1. _________________________________
2. _________________________________
3. _________________________________

**Next Steps**:
- [ ] Fix any failing tests
- [ ] Retest all scenarios
- [ ] Update implementation plan if needed 