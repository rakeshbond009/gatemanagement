-- Help System Database Schema

-- FAQ Categories table
CREATE TABLE IF NOT EXISTS faq_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- FAQ Items table
CREATE TABLE IF NOT EXISTS faq_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    role ENUM('all', 'resident', 'security', 'admin') DEFAULT 'all',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES faq_categories(id) ON DELETE CASCADE
);

-- User Guides table
CREATE TABLE IF NOT EXISTS user_guides (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    role ENUM('all', 'resident', 'security', 'admin') DEFAULT 'all',
    category VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Support Tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('technical', 'account', 'visitor_management', 'general') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT NULL,
    resolution TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Support Ticket Messages table
CREATE TABLE IF NOT EXISTS support_ticket_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Help Article Views tracking
CREATE TABLE IF NOT EXISTS help_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    content_type ENUM('faq', 'guide') NOT NULL,
    content_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default FAQ categories
INSERT INTO faq_categories (name, description, sort_order) VALUES
('Getting Started', 'Basic questions about using the system', 1),
('Visitor Management', 'Questions about registering and managing visitors', 2),
('Account & Profile', 'Account settings and profile management', 3),
('Security Features', 'Security-related questions and procedures', 4),
('Technical Issues', 'Common technical problems and solutions', 5),
('General', 'General questions about the system', 6);

-- Insert default FAQ items
INSERT INTO faq_items (category_id, question, answer, role, sort_order) VALUES
(1, 'How do I log in to the system?', 'To log in, go to the main page, enter your username and password, select your role (Resident, Security, or Admin), and click Login. If you don''t have an account, click "Register here" to create one.', 'all', 1),
(1, 'I forgot my password. What should I do?', 'Contact your building administrator or submit a support ticket through the help system. They can reset your password for you.', 'all', 2),
(1, 'How do I register as a new resident?', 'Click on "Register here" from the login page, fill out all required information including your unit details, and submit. Wait for admin approval before you can access the system.', 'resident', 3),

(2, 'How do I register a visitor?', 'Go to your dashboard, click on "Register Visitor", fill in the visitor details including name, phone, purpose of visit, and upload a photo if required. Submit for security approval.', 'resident', 1),
(2, 'Can I register multiple visitors at once?', 'Currently, you need to register each visitor individually to ensure proper documentation and security verification.', 'resident', 2),
(2, 'How long does visitor approval take?', 'Visitor approval is typically instant if security staff is available at the gate. During peak hours, it may take a few minutes.', 'resident', 3),
(2, 'What information do I need to register a visitor?', 'You need the visitor''s full name, phone number, purpose of visit, and ideally a photo for security verification.', 'resident', 4),

(2, 'How do I approve a visitor?', 'In your security dashboard, you''ll see pending visitor requests. Click on each request to view details and choose "Approve" or "Reject" based on verification.', 'security', 1),
(2, 'What should I do if a visitor arrives without pre-registration?', 'Use the "Walk-in Visitor" option in your dashboard to register them on the spot. Contact the resident for confirmation before approving entry.', 'security', 2),
(2, 'How do I check out a visitor?', 'Go to the "Check Out Visitor" section, search for the visitor by name or phone, and mark their exit time when they leave.', 'security', 3),

(3, 'How do I update my profile information?', 'Go to your dashboard and click on "Profile" or "Account Settings". Update your information and save changes.', 'all', 1),
(3, 'Can I change my username?', 'Username changes must be requested through the admin. Submit a support ticket with your request and reason for the change.', 'all', 2),
(3, 'How do I add or remove unit associations?', 'Contact your building administrator to update unit associations. This requires admin approval for security reasons.', 'resident', 3),

(4, 'What happens if a visitor is rejected?', 'Rejected visitors cannot enter the premises. The resident who registered them will be notified of the rejection and reason.', 'all', 1),
(4, 'Can I see who approved or rejected my visitor?', 'Yes, the visitor history shows which security staff member made the decision and when.', 'resident', 2),
(4, 'What if there''s an emergency?', 'Emergency contacts are available in the system. Security staff can also contact emergency services directly and log the incident.', 'all', 3),

(5, 'The system is running slowly. What can I do?', 'Try refreshing your browser, clearing your browser cache, or logging out and back in. If the problem persists, submit a support ticket.', 'all', 1),
(5, 'I can''t upload photos. What''s wrong?', 'Ensure your photo is in JPG, PNG, or GIF format and under 5MB. Check your internet connection and try again.', 'all', 2),
(5, 'The page won''t load properly.', 'Try using a different browser or clearing your browser cache. Ensure JavaScript is enabled in your browser settings.', 'all', 3),

(6, 'Who can I contact for help?', 'You can submit a support ticket through this help system, contact your building administrator, or call the emergency number for urgent issues.', 'all', 1),
(6, 'How do I provide feedback about the system?', 'Submit a support ticket with your feedback, suggestions, or complaints. We value your input to improve the system.', 'all', 2),
(6, 'Is my personal information secure?', 'Yes, all personal information is encrypted and stored securely. Only authorized personnel can access your data for legitimate purposes.', 'all', 3);

-- Insert default user guides
INSERT INTO user_guides (title, content, role, category, sort_order) VALUES
('Quick Start Guide for Residents', 
'<h3>Welcome to the Gate Management System!</h3>
<p>This guide will help you get started with managing visitors and using the system effectively.</p>
<h4>Step 1: Complete Your Profile</h4>
<p>After your account is approved, log in and update your profile with accurate contact information.</p>
<h4>Step 2: Register Your First Visitor</h4>
<p>1. Click "Register Visitor" from your dashboard<br>
2. Fill in visitor details completely<br>
3. Upload a clear photo if possible<br>
4. Submit for approval</p>
<h4>Step 3: Track Your Visitors</h4>
<p>Monitor visitor status in real-time through your dashboard. You''ll see pending, approved, and rejected visitors.</p>
<h4>Need Help?</h4>
<p>Check the FAQ section or submit a support ticket for assistance.</p>', 
'resident', 'Getting Started', 1),

('Security Staff Operations Guide', 
'<h3>Security Dashboard Overview</h3>
<p>Your dashboard is the central hub for managing all visitor activities.</p>
<h4>Daily Tasks:</h4>
<p>1. <strong>Review Pending Visitors:</strong> Check and approve/reject visitor requests<br>
2. <strong>Monitor Active Visitors:</strong> Keep track of who is currently inside<br>
3. <strong>Process Check-outs:</strong> Record when visitors leave</p>
<h4>Visitor Approval Process:</h4>
<p>1. Review visitor details and photo<br>
2. Verify with resident if needed<br>
3. Check ID at gate<br>
4. Approve or reject based on verification</p>
<h4>Emergency Procedures:</h4>
<p>1. Contact emergency services immediately<br>
2. Log incident in system<br>
3. Notify building management<br>
4. Follow up with incident report</p>', 
'security', 'Operations', 1),

('Admin Management Guide', 
'<h3>Administrator Functions</h3>
<p>As an admin, you have full control over the system and user management.</p>
<h4>User Management:</h4>
<p>1. <strong>Approve New Residents:</strong> Review and approve registration requests<br>
2. <strong>Manage User Accounts:</strong> Edit, deactivate, or reactivate accounts<br>
3. <strong>Assign Units:</strong> Link residents to their apartments</p>
<h4>System Configuration:</h4>
<p>1. <strong>Emergency Contacts:</strong> Maintain up-to-date emergency information<br>
2. <strong>System Settings:</strong> Configure system parameters<br>
3. <strong>Reports:</strong> Generate usage and security reports</p>
<h4>Support Management:</h4>
<p>1. <strong>Review Support Tickets:</strong> Address user issues and requests<br>
2. <strong>Update Help Content:</strong> Keep FAQs and guides current<br>
3. <strong>Monitor System Health:</strong> Ensure optimal performance</p>', 
'admin', 'Administration', 1);