# ShiftEnd MVP Phase 2: FOD Compliance for E-Dagontvangstenboek

## 🎯 Overview
This document outlines the additional MVP features required for FOD (Federal Public Service) approval of our electronic "dagontvangstenboek" (daily receipts book) in Belgium.

## 📋 FOD Approval Requirements

### **1. Legal Compliance Features**

#### **📊 Daily Receipts Registration**
- ✅ **Daily Sales Recording**: Each day's total sales must be recorded
- ✅ **Receipt Numbering**: Sequential receipt numbering system
- ✅ **Daily Totals**: Automatic calculation of daily totals
- ✅ **Date Stamping**: Automatic date and time stamps for all entries
- ✅ **Daily Closure**: Ability to close and finalize each day's records

#### **🔒 Audit Trail & Security**
- ✅ **User Authentication**: Secure login with role-based access
- ✅ **Activity Logging**: Complete audit trail of all changes
- ✅ **Data Integrity**: Tamper-proof recording system
- ✅ **Backup System**: Automatic data backup and recovery
- ✅ **Digital Signatures**: Electronic signatures for daily closures

### **2. Tax Compliance Features**

#### **💰 VAT Handling**
- ✅ **VAT Registration**: Separate tracking of VAT amounts
- ✅ **VAT Rates**: Support for different VAT rates (0%, 6%, 12%, 21%)
- ✅ **VAT Calculation**: Automatic VAT calculation on sales
- ✅ **VAT Reporting**: Monthly/quarterly VAT summaries

#### **📈 Financial Reporting**
- ✅ **Daily Summaries**: Daily sales and tax summaries
- ✅ **Monthly Reports**: Monthly financial reports
- ✅ **Annual Reports**: Year-end financial summaries
- ✅ **Tax Declarations**: Export data for tax declarations

### **3. Belgian Business Requirements**

#### **🏢 Business Registration**
- ✅ **Company Details**: Business registration number (KBO)
- ✅ **VAT Number**: Belgian VAT number (BE-XXX.XXX.XXX)
- ✅ **Address Registration**: Official business address
- ✅ **Contact Information**: Business contact details

#### **👥 Employee Management**
- ✅ **Employee Registration**: Employee identification and roles
- ✅ **Shift Tracking**: Employee shift assignments
- ✅ **Responsibility Assignment**: Clear assignment of responsibilities
- ✅ **Access Control**: Role-based system access

### **4. Technical Requirements**

#### **💾 Data Storage & Retention**
- ✅ **7-Year Retention**: Data must be kept for 7 years
- ✅ **Secure Storage**: Encrypted data storage
- ✅ **Data Export**: Ability to export data in standard formats
- ✅ **Data Import**: Import capabilities for existing data

#### **🔍 Inspection Readiness**
- ✅ **FOD Access**: Read-only access for tax inspectors
- ✅ **Audit Reports**: Comprehensive audit reporting
- ✅ **Data Verification**: Built-in data verification tools
- ✅ **Compliance Checks**: Automatic compliance validation

## 🚀 Implementation Plan

### **Phase 2A: Core FOD Compliance (Priority 1)**

#### **Database Schema Updates**
```sql
-- Add VAT and compliance fields to reports table
ALTER TABLE reports ADD COLUMN vat_rate DECIMAL(5,2) DEFAULT 21.00;
ALTER TABLE reports ADD COLUMN vat_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE reports ADD COLUMN total_with_vat DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE reports ADD COLUMN receipt_number VARCHAR(50);
ALTER TABLE reports ADD COLUMN daily_closure_id INTEGER;
ALTER TABLE reports ADD COLUMN is_daily_closed BOOLEAN DEFAULT FALSE;

-- Create daily closures table
CREATE TABLE daily_closures (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    location_id BIGINT,
    closure_date DATE,
    total_sales DECIMAL(10,2),
    total_vat DECIMAL(10,2),
    total_receipts INTEGER,
    closed_by BIGINT,
    closed_at TIMESTAMP,
    digital_signature TEXT,
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (closed_by) REFERENCES users(id)
);

-- Create audit trail table
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    action VARCHAR(100),
    table_name VARCHAR(50),
    record_id BIGINT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create WhatsApp integration table
CREATE TABLE whatsapp_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    report_id BIGINT,
    owner_id BIGINT,
    message_type ENUM('pending_report', 'approval_confirmation'),
    message_content TEXT,
    whatsapp_message_id VARCHAR(100),
    status ENUM('sent', 'delivered', 'read', 'clicked'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id),
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- Add auto-approval settings to locations table
ALTER TABLE locations ADD COLUMN manager_auto_approval BOOLEAN DEFAULT FALSE;
ALTER TABLE locations ADD COLUMN owner_whatsapp_number VARCHAR(20);
ALTER TABLE locations ADD COLUMN whatsapp_webhook_url VARCHAR(255);
```

#### **New API Endpoints**
```php
// Daily closure endpoints
POST /api/daily-closures
GET /api/daily-closures/{date}
PUT /api/daily-closures/{id}/close
GET /api/daily-closures/{id}/sign

// VAT calculation endpoints
GET /api/vat-rates
POST /api/reports/calculate-vat
GET /api/vat-summary/{month}

// Audit trail endpoints
GET /api/audit-logs
GET /api/audit-logs/{table}/{record_id}

// FOD inspection endpoints
GET /api/fod/inspection-data
GET /api/fod/export-data

// WhatsApp integration endpoints
POST /api/whatsapp/send-notification
POST /api/whatsapp/webhook
GET /api/whatsapp/notifications/{report_id}
POST /api/whatsapp/approve/{report_id}

// Auto-approval settings endpoints
PUT /api/locations/{id}/auto-approval-settings
GET /api/locations/{id}/auto-approval-settings
```

### **Phase 2B: Enhanced Features (Priority 2)**

#### **Frontend Components**
- ✅ **Daily Closure Form**: Interface for closing daily records
- ✅ **VAT Calculator**: Real-time VAT calculation
- ✅ **Receipt Scanner**: Photo/scan receipt functionality
- ✅ **Audit Viewer**: View audit trail and changes
- ✅ **FOD Export**: Export data for tax authorities

#### **Mobile Features**
- ✅ **Mobile Receipt Entry**: Quick receipt entry on mobile
- ✅ **Offline Mode**: Offline functionality with sync
- ✅ **Photo Receipts**: Camera integration for receipts
- ✅ **Push Notifications**: Daily closure reminders

#### **WhatsApp Integration**
- ✅ **Report Notifications**: Owner receives WhatsApp message for pending reports
- ✅ **Approval via WhatsApp**: Owner can approve reports directly via WhatsApp
- ✅ **Auto-Feedback**: System sends approval confirmation via WhatsApp
- ✅ **Report Details**: WhatsApp messages include full report details
- ✅ **Quick Actions**: Click-to-approve functionality in WhatsApp

### **Phase 2C: Advanced Compliance (Priority 3)**

#### **Advanced Reporting**
- ✅ **FOD Compliance Reports**: Official FOD format reports
- ✅ **Tax Declaration Export**: Export for tax software
- ✅ **Audit Trail Reports**: Comprehensive audit reports
- ✅ **Data Verification Tools**: Built-in data validation

#### **Security Enhancements**
- ✅ **Digital Signatures**: Electronic signatures for closures
- ✅ **Encryption**: Enhanced data encryption
- ✅ **Access Control**: Granular permission system
- ✅ **Backup System**: Automated backup and recovery

## 📊 New User Flows

### **Flow 6: Daily Receipt Entry**
```
1. Employee logs in
2. Goes to "Daily Receipts" page
3. Enters receipt number (auto-incremented)
4. Enters sales amount
5. Selects VAT rate (0%, 6%, 12%, 21%)
6. System calculates VAT automatically
7. Adds receipt notes
8. Saves receipt
9. Receipt is added to daily total
```

### **Flow 7: Daily Closure Process**
```
1. Manager/Owner logs in
2. Goes to "Daily Closure" page
3. Reviews all receipts for the day
4. Verifies daily totals
5. Adds closure notes
6. Digitally signs the closure
7. System locks daily records
8. Generates closure report
9. Sends to FOD (if required)
```

### **Flow 8: FOD Inspection**
```
1. FOD inspector requests access
2. System provides read-only access
3. Inspector can view all records
4. Inspector can export data
5. Inspector can generate reports
6. System logs all inspector activity
7. Inspector submits findings
```

### **Flow 9: WhatsApp Report Approval**
```
1. Manager/Employee submits report
2. System checks auto-approval settings
3. If manager_auto_approval = TRUE and user is manager:
   - Report auto-approved
   - WhatsApp notification sent to owner
4. If auto-approval = FALSE or user is employee:
   - Report marked as pending
   - WhatsApp notification sent to owner with report details
   - Owner receives message: "New report pending: [Employee] - €[Amount] - [Date]"
5. Owner clicks "Approve" in WhatsApp
6. System receives webhook from WhatsApp
7. System approves report automatically
8. System sends confirmation: "Report [date_time] approved"
9. Employee receives notification of approval
```

### **Flow 10: Auto-Approval Settings**
```
1. Owner goes to Team Settings
2. Toggles "Auto-approve Manager Reports"
3. System saves setting to database
4. Future manager reports follow auto-approval rule
5. Owner can change setting anytime
6. Setting applies to all reports from that specific location/group
```

## 🔧 Technical Implementation

### **VAT Calculation System**
```php
class VatCalculator {
    private $rates = [
        '0' => 0.00,
        '6' => 6.00,
        '12' => 12.00,
        '21' => 21.00
    ];
    
    public function calculateVat($amount, $rate) {
        return ($amount * $rate) / 100;
    }
    
    public function calculateTotalWithVat($amount, $rate) {
        return $amount + $this->calculateVat($amount, $rate);
    }
}
```

### **Daily Closure System**
```php
class DailyClosure {
    public function closeDay($locationId, $date, $userId) {
        // Get all receipts for the day
        $receipts = Report::where('location_id', $locationId)
                         ->whereDate('created_at', $date)
                         ->where('is_daily_closed', false)
                         ->get();
        
        // Calculate totals
        $totalSales = $receipts->sum('sales_amount');
        $totalVat = $receipts->sum('vat_amount');
        $totalReceipts = $receipts->count();
        
        // Create closure record
        $closure = DailyClosure::create([
            'location_id' => $locationId,
            'closure_date' => $date,
            'total_sales' => $totalSales,
            'total_vat' => $totalVat,
            'total_receipts' => $totalReceipts,
            'closed_by' => $userId,
            'closed_at' => now()
        ]);
        
        // Mark receipts as closed
        $receipts->each(function($receipt) {
            $receipt->update(['is_daily_closed' => true]);
        });
        
        return $closure;
    }
}
```

### **Audit Trail System**
```php
class AuditLogger {
    public function log($userId, $action, $table, $recordId, $oldValues = null, $newValues = null) {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
```

### **WhatsApp Integration System**
```php
class WhatsAppService {
    private $apiKey;
    private $webhookUrl;
    
    public function __construct() {
        $this->apiKey = config('whatsapp.api_key');
        $this->webhookUrl = config('whatsapp.webhook_url');
    }
    
    public function sendPendingReportNotification($report, $owner) {
        $message = "New report pending: {$report->user->name} - €{$report->sales_amount} - {$report->created_at->format('d/m/Y H:i')}";
        
        $notification = WhatsAppNotification::create([
            'report_id' => $report->id,
            'owner_id' => $owner->id,
            'message_type' => 'pending_report',
            'message_content' => $message,
            'status' => 'sent'
        ]);
        
        // Send via WhatsApp API
        $this->sendMessage($owner->whatsapp_number, $message, [
            'report_id' => $report->id,
            'action' => 'approve'
        ]);
        
        return $notification;
    }
    
    public function sendApprovalConfirmation($report) {
        $message = "Report {$report->created_at->format('d/m/Y H:i')} approved";
        
        $notification = WhatsAppNotification::create([
            'report_id' => $report->id,
            'owner_id' => $report->location->owner->id,
            'message_type' => 'approval_confirmation',
            'message_content' => $message,
            'status' => 'sent'
        ]);
        
        // Send confirmation to employee
        $this->sendMessage($report->user->whatsapp_number, $message);
        
        return $notification;
    }
    
    public function handleWebhook($data) {
        if ($data['type'] === 'button_click' && $data['button_id'] === 'approve') {
            $reportId = $data['report_id'];
            $report = Report::find($reportId);
            
            if ($report && $report->status === 'pending') {
                $report->update(['status' => 'approved']);
                $this->sendApprovalConfirmation($report);
            }
        }
    }
}
```

### **Auto-Approval System**
```php
class AutoApprovalService {
    public function checkAutoApproval($report, $user) {
        $location = $report->location;
        
        // Check if manager auto-approval is enabled
        if ($location->manager_auto_approval && $user->role === 'manager') {
            $report->update(['status' => 'approved']);
            
            // Send notification to owner
            $whatsappService = new WhatsAppService();
            $whatsappService->sendPendingReportNotification($report, $location->owner);
            
            return true;
        }
        
        return false;
    }
    
    public function updateAutoApprovalSettings($locationId, $settings) {
        $location = Location::find($locationId);
        $location->update([
            'manager_auto_approval' => $settings['manager_auto_approval'],
            'owner_whatsapp_number' => $settings['owner_whatsapp_number']
        ]);
        
        return $location;
    }
}
```

## 📋 FOD Documentation Requirements

### **Required Documents**
1. **Technical Specification**: Detailed technical documentation
2. **User Manual**: Comprehensive user guide
3. **Security Documentation**: Security measures and protocols
4. **Compliance Report**: Proof of Belgian law compliance
5. **Test Results**: System testing and validation results
6. **Data Protection Impact Assessment**: GDPR compliance
7. **Backup and Recovery Procedures**: Data protection measures

### **Testing Requirements**
1. **Functional Testing**: All features work correctly
2. **Security Testing**: Penetration testing and vulnerability assessment
3. **Performance Testing**: System performance under load
4. **Compliance Testing**: Verification of legal requirements
5. **User Acceptance Testing**: End-user testing and feedback

## 🎯 Success Criteria

### **FOD Approval Criteria**
- ✅ System meets all Belgian tax requirements
- ✅ Data integrity and security verified
- ✅ Audit trail complete and tamper-proof
- ✅ 7-year data retention implemented
- ✅ FOD inspector access provided
- ✅ All required documentation submitted
- ✅ Testing completed successfully

### **Business Success Criteria**
- ✅ Reduced manual bookkeeping time
- ✅ Improved accuracy of financial records
- ✅ Better compliance with tax regulations
- ✅ Enhanced audit readiness
- ✅ Improved financial reporting
- ✅ User satisfaction with system

## 🚀 Timeline

### **Month 1-2: Phase 2A**
- Database schema updates
- VAT calculation system
- Daily closure functionality
- Basic audit trail
- WhatsApp integration setup

### **Month 3-4: Phase 2B**
- Enhanced frontend components
- Mobile functionality
- Receipt scanning
- Advanced reporting
- WhatsApp approval system
- Auto-approval settings

### **Month 5-6: Phase 2C**
- Security enhancements
- FOD documentation
- Testing and validation
- FOD submission
- WhatsApp webhook testing

---

*This document outlines the complete roadmap for FOD approval of our electronic dagontvangstenboek system, ensuring full compliance with Belgian tax and business regulations.* 