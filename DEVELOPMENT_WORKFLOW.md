# ShiftEnd Development Workflow

## 🌿 Branching Strategy

### **Main Branches**
- **`main`**: Production-ready code (stable releases)
- **`dev`**: Development branch (integration of features)
- **`develop`**: Backend development branch (equivalent to `dev`)

### **Feature Development Workflow**

For each new feature we work on:

1. **Create Feature Branch**
   ```bash
   git checkout dev
   git pull origin dev
   git checkout -b feature/feature-name
   ```

2. **Implement Feature**
   - Work on the feature in the new branch
   - Make commits with clear, descriptive messages
   - Test the feature thoroughly

3. **Agent Review**
   - Agent asks: "Can we now merge this feature to dev?"
   - This ensures proper review before merging

4. **Merge to Dev**
   ```bash
   git checkout dev
   git merge feature/feature-name
   git push origin dev
   ```

5. **Return to Dev**
   ```bash
   git checkout dev
   git branch -d feature/feature-name  # Delete local feature branch
   ```

6. **Repeat**
   - Continue this cycle for each new feature
   - Always start new features from `dev` branch

## 📋 Workflow Checklist

### **Before Starting a Feature**
- [ ] Check current `dev` branch is up to date
- [ ] Create new feature branch from `dev`
- [ ] Reference analysis documents (COMPREHENSIVE_ANALYSIS.md, MVP_ANALYSIS.md, USER_STORIES.md)
- [ ] Understand requirements and current state

### **During Feature Development**
- [ ] Implement backend APIs first (if needed)
- [ ] Implement frontend components
- [ ] Test thoroughly
- [ ] Update documentation
- [ ] Commit with clear messages

### **Before Merging to Dev**
- [ ] Agent asks: "Can we now merge this feature to dev?"
- [ ] Ensure all tests pass
- [ ] Ensure documentation is updated
- [ ] Ensure no conflicts with `dev` branch

### **After Merging to Dev**
- [ ] Switch back to `dev` branch
- [ ] Push changes to remote `dev`
- [ ] Verify deployment works correctly
- [ ] Delete feature branch

## 🎯 Best Practices

### **Branch Naming**
- Use descriptive names: `feature/user-management`, `feature/analytics-dashboard`
- Use kebab-case for branch names
- Include the type of change: `feature/`, `bugfix/`, `hotfix/`

### **Commit Messages**
- Use clear, descriptive commit messages
- Start with a verb: "Add user management feature", "Fix authentication flow"
- Include reference to analysis documents if applicable

### **Code Quality**
- Follow Laravel best practices for backend
- Follow Vue.js best practices for frontend
- Add proper error handling and validation
- Test thoroughly before merging

### **Documentation**
- Always update relevant analysis documents
- Keep implementation plan current
- Document any breaking changes
- Update API documentation

## 🔄 Deployment Workflow

### **Backend (Railway)**
- Deploy from `develop` branch
- Environment variables set in Railway dashboard
- Automatic deployment on push to `develop`

### **Frontend (Netlify)**
- Deploy from `dev` branch
- Automatic deployment on push to `dev`
- Build settings configured in Netlify

## 📝 Example Workflow

### **Example: Adding User Management Feature**

1. **Start**
   ```bash
   git checkout dev
   git pull origin dev
   git checkout -b feature/user-management
   ```

2. **Develop**
   - Implement backend APIs
   - Implement frontend components
   - Test thoroughly
   - Update documentation

3. **Review**
   - Agent asks: "Can we now merge this feature to dev?"

4. **Merge**
   ```bash
   git checkout dev
   git merge feature/user-management
   git push origin dev
   git branch -d feature/user-management
   ```

5. **Deploy**
   - Changes automatically deploy to Railway/Netlify
   - Verify deployment works correctly

## 🚨 Important Notes

- **Always start from `dev`**: Never create feature branches from other branches
- **Agent review required**: Agent must ask before merging to `dev`
- **Test thoroughly**: Ensure features work before merging
- **Update documentation**: Keep analysis documents current
- **Clear commits**: Use descriptive commit messages
- **No direct pushes to main**: All changes go through `dev` first

---

**Remember**: This workflow ensures clean, organized development with proper review and testing before merging to the main development branch. 
