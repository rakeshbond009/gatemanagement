<?php
require_once '../../config/config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$conn = connectDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Management - Gate Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .help-management {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .help-tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .help-tab {
            flex: 1;
            padding: 15px 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-right: 1px solid #dee2e6;
        }
        
        .help-tab:last-child {
            border-right: none;
        }
        
        .help-tab.active {
            background: #007bff;
            color: white;
        }
        
        .help-content {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .help-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .content-list {
            margin-top: 30px;
        }
        
        .content-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .content-header {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-details {
            display: none;
            padding: 20px;
            background: white;
        }
        
        .content-details.active {
            display: block;
        }
        
        .ticket-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .ticket-status.open { background: #d4edda; color: #155724; }
        .ticket-status.in-progress { background: #fff3cd; color: #856404; }
        .ticket-status.resolved { background: #d1ecf1; color: #0c5460; }
        .ticket-status.closed { background: #f8d7da; color: #721c24; }
        
        .admin-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .stat-card p {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php">User Management</a></li>
                    <li><a href="units.php">Unit Management</a></li>
                    <li><a href="staff.php">Staff Management</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="help_management.php" class="active">Help Management</a></li>
                    <li><a href="../help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                    <li><a href="../../api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="help-management">
                <div class="header">
                    <h1><i class="fas fa-cogs"></i> Help System Management</h1>
                    <p>Manage FAQs, user guides, and support tickets</p>
                </div>
                
                <div id="help-stats" class="stats-grid">
                    <!-- Stats will be loaded here -->
                </div>
                
                <div class="help-tabs">
                    <button class="help-tab active" onclick="showSection('tickets')">
                        <i class="fas fa-ticket-alt"></i> Support Tickets
                    </button>
                    <button class="help-tab" onclick="showSection('faqs')">
                        <i class="fas fa-question"></i> Manage FAQs
                    </button>
                    <button class="help-tab" onclick="showSection('guides')">
                        <i class="fas fa-book"></i> User Guides
                    </button>
                </div>
                
                <!-- Support Tickets Section -->
                <div id="tickets-section" class="help-content active">
                    <h3><i class="fas fa-ticket-alt"></i> Support Tickets Management</h3>
                    
                    <div id="tickets-list">
                        <!-- Tickets will be loaded here -->
                    </div>
                </div>
                
                <!-- FAQ Management Section -->
                <div id="faqs-section" class="help-content">
                    <h3><i class="fas fa-question"></i> FAQ Management</h3>
                    
                    <div class="admin-form">
                        <h4>Add New FAQ</h4>
                        <form id="faq-form" onsubmit="saveFAQ(event)">
                            <input type="hidden" id="faq-id" name="id">
                            <div class="form-group">
                                <label for="faq-category">Category</label>
                                <select id="faq-category" name="category" required>
                                    <!-- Categories will be loaded -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="faq-question">Question *</label>
                                <input type="text" id="faq-question" name="question" required>
                            </div>
                            <div class="form-group">
                                <label for="faq-answer">Answer *</label>
                                <textarea id="faq-answer" name="answer" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="faq-role">Visible to Role</label>
                                <select id="faq-role" name="role">
                                    <option value="all">All Users</option>
                                    <option value="resident">Residents Only</option>
                                    <option value="security">Security Only</option>
                                    <option value="admin">Admins Only</option>
                                </select>
                            </div>
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Save FAQ
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetFAQForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </form>
                    </div>
                    
                    <div id="faqs-list" class="content-list">
                        <!-- FAQs will be loaded here -->
                    </div>
                </div>
                
                <!-- User Guides Section -->
                <div id="guides-section" class="help-content">
                    <h3><i class="fas fa-book"></i> User Guides Management</h3>
                    
                    <div class="admin-form">
                        <h4>Add New User Guide</h4>
                        <form id="guide-form" onsubmit="saveGuide(event)">
                            <input type="hidden" id="guide-id" name="id">
                            <div class="form-group">
                                <label for="guide-title">Title *</label>
                                <input type="text" id="guide-title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="guide-category">Category</label>
                                <input type="text" id="guide-category" name="category" placeholder="e.g., Getting Started, Operations">
                            </div>
                            <div class="form-group">
                                <label for="guide-content">Content (HTML allowed) *</label>
                                <textarea id="guide-content" name="content" required style="height: 200px;"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="guide-role">Visible to Role</label>
                                <select id="guide-role" name="role">
                                    <option value="all">All Users</option>
                                    <option value="resident">Residents Only</option>
                                    <option value="security">Security Only</option>
                                    <option value="admin">Admins Only</option>
                                </select>
                            </div>
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Save Guide
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetGuideForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </form>
                    </div>
                    
                    <div id="guides-list" class="content-list">
                        <!-- User guides will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentSection = 'tickets';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadHelpStats();
            loadSupportTickets();
            loadFAQCategories();
            loadFAQs();
            loadUserGuides();
        });
        
        // Show section
        function showSection(section) {
            document.querySelectorAll('.help-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelector(`button[onclick="showSection('${section}')"]`).classList.add('active');
            
            document.querySelectorAll('.help-content').forEach(content => content.classList.remove('active'));
            document.getElementById(`${section}-section`).classList.add('active');
            
            currentSection = section;
        }
        
        // Load help statistics
        async function loadHelpStats() {
            try {
                const [ticketsResponse, faqsResponse, guidesResponse] = await Promise.all([
                    fetch('../../api/help.php?action=get_tickets'),
                    fetch('../../api/help.php?action=get_faqs'),
                    fetch('../../api/help.php?action=get_guides')
                ]);
                
                const ticketsData = await ticketsResponse.json();
                const faqsData = await faqsResponse.json();
                const guidesData = await guidesResponse.json();
                
                const totalTickets = ticketsData.success ? ticketsData.data.length : 0;
                const openTickets = ticketsData.success ? ticketsData.data.filter(t => t.status === 'open').length : 0;
                const totalFAQs = faqsData.success ? faqsData.data.reduce((sum, cat) => sum + cat.items.length, 0) : 0;
                const totalGuides = guidesData.success ? guidesData.data.length : 0;
                
                document.getElementById('help-stats').innerHTML = `
                    <div class="stat-card">
                        <h3>Total Support Tickets</h3>
                        <p>${totalTickets}</p>
                    </div>
                    <div class="stat-card">
                        <h3>Open Tickets</h3>
                        <p>${openTickets}</p>
                    </div>
                    <div class="stat-card">
                        <h3>FAQ Items</h3>
                        <p>${totalFAQs}</p>
                    </div>
                    <div class="stat-card">
                        <h3>User Guides</h3>
                        <p>${totalGuides}</p>
                    </div>
                `;
            } catch (error) {
                console.error('Error loading help stats:', error);
            }
        }
        
        // Load support tickets for admin management
        async function loadSupportTickets() {
            try {
                const response = await fetch('../../api/help.php?action=get_tickets');
                const data = await response.json();
                
                if (data.success) {
                    displaySupportTickets(data.data);
                } else {
                    document.getElementById('tickets-list').innerHTML = 
                        '<div class="no-results">Failed to load support tickets</div>';
                }
            } catch (error) {
                console.error('Error loading support tickets:', error);
                document.getElementById('tickets-list').innerHTML = 
                    '<div class="no-results">Error loading support tickets</div>';
            }
        }
        
        // Display support tickets for admin
        function displaySupportTickets(tickets) {
            const container = document.getElementById('tickets-list');
            
            if (tickets.length === 0) {
                container.innerHTML = '<div class="no-results">No support tickets found</div>';
                return;
            }
            
            let html = '';
            tickets.forEach(ticket => {
                html += `
                    <div class="content-item">
                        <div class="content-header" onclick="toggleTicketDetails(${ticket.id})">
                            <div>
                                <h4 style="margin: 0;">${ticket.subject}</h4>
                                <small>By: ${ticket.user_name || 'User'} | #${ticket.id} | ${ticket.category} | ${new Date(ticket.created_at).toLocaleDateString()}</small>
                            </div>
                            <div>
                                <span class="ticket-status ${ticket.status}">${ticket.status.replace('_', ' ').toUpperCase()}</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="content-details" id="ticket-details-${ticket.id}">
                            <p><strong>Description:</strong> ${ticket.description}</p>
                            <p><strong>Priority:</strong> ${ticket.priority.toUpperCase()}</p>
                            ${ticket.assigned_name ? `<p><strong>Assigned to:</strong> ${ticket.assigned_name}</p>` : ''}
                            ${ticket.resolution ? `<p><strong>Resolution:</strong> ${ticket.resolution}</p>` : ''}
                            
                            <div style="margin-top: 20px;">
                                <h5>Update Ticket:</h5>
                                <form onsubmit="updateTicket(event, ${ticket.id})">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 10px; align-items: end;">
                                        <div class="form-group" style="margin: 0;">
                                            <label>Status</label>
                                            <select name="status" required>
                                                <option value="open" ${ticket.status === 'open' ? 'selected' : ''}>Open</option>
                                                <option value="in_progress" ${ticket.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                                <option value="resolved" ${ticket.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                                <option value="closed" ${ticket.status === 'closed' ? 'selected' : ''}>Closed</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Assign to Admin</label>
                                            <select name="assigned_to">
                                                <option value="">Unassigned</option>
                                                <!-- Admin users will be loaded here -->
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label>Resolution</label>
                                            <input type="text" name="resolution" value="${ticket.resolution || ''}" placeholder="Resolution notes...">
                                        </div>
                                        <button type="submit" class="btn">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Toggle ticket details
        function toggleTicketDetails(id) {
            const details = document.getElementById(`ticket-details-${id}`);
            details.classList.toggle('active');
        }
        
        // Update ticket
        async function updateTicket(event, ticketId) {
            event.preventDefault();
            
            const form = event.target;
            const formData = {
                ticket_id: ticketId,
                status: form.status.value,
                assigned_to: form.assigned_to.value || null,
                resolution: form.resolution.value
            };
            
            try {
                const response = await fetch('../../api/help.php?action=update_ticket', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Ticket updated successfully!');
                    loadSupportTickets();
                    loadHelpStats();
                } else {
                    alert('Failed to update ticket: ' + data.message);
                }
            } catch (error) {
                console.error('Error updating ticket:', error);
                alert('Error updating ticket');
            }
        }
        
        // Load FAQ categories
        async function loadFAQCategories() {
            // For now, we'll use static categories. In a full implementation,
            // you might want to create a separate API endpoint for categories
            const categories = [
                { id: 1, name: 'Getting Started' },
                { id: 2, name: 'Visitor Management' },
                { id: 3, name: 'Account & Profile' },
                { id: 4, name: 'Security Features' },
                { id: 5, name: 'Technical Issues' },
                { id: 6, name: 'General' }
            ];
            
            const select = document.getElementById('faq-category');
            select.innerHTML = categories.map(cat => 
                `<option value="${cat.id}">${cat.name}</option>`
            ).join('');
        }
        
        // Load FAQs for admin management
        async function loadFAQs() {
            try {
                const response = await fetch('../../api/help.php?action=get_faqs');
                const data = await response.json();
                
                if (data.success) {
                    displayFAQs(data.data);
                } else {
                    document.getElementById('faqs-list').innerHTML = 
                        '<div class="no-results">Failed to load FAQs</div>';
                }
            } catch (error) {
                console.error('Error loading FAQs:', error);
                document.getElementById('faqs-list').innerHTML = 
                    '<div class="no-results">Error loading FAQs</div>';
            }
        }
        
        // Display FAQs for admin
        function displayFAQs(categories) {
            const container = document.getElementById('faqs-list');
            
            if (categories.length === 0) {
                container.innerHTML = '<div class="no-results">No FAQs found</div>';
                return;
            }
            
            let html = '';
            categories.forEach(category => {
                if (category.items && category.items.length > 0) {
                    html += `<h4>${category.category_name}</h4>`;
                    category.items.forEach(item => {
                        html += `
                            <div class="content-item">
                                <div class="content-header">
                                    <div>
                                        <h5 style="margin: 0;">${item.question}</h5>
                                        <small>Role: ${item.role || 'all'} | Views: ${item.views_count}</small>
                                    </div>
                                    <div>
                                        <button class="btn btn-secondary" onclick="editFAQ(${item.id})">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger" onclick="deleteFAQ(${item.id})">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
            });
            
            container.innerHTML = html;
        }
        
        // Load user guides for admin management
        async function loadUserGuides() {
            try {
                const response = await fetch('../../api/help.php?action=get_guides');
                const data = await response.json();
                
                if (data.success) {
                    displayUserGuides(data.data);
                } else {
                    document.getElementById('guides-list').innerHTML = 
                        '<div class="no-results">Failed to load user guides</div>';
                }
            } catch (error) {
                console.error('Error loading user guides:', error);
                document.getElementById('guides-list').innerHTML = 
                    '<div class="no-results">Error loading user guides</div>';
            }
        }
        
        // Display user guides for admin
        function displayUserGuides(guides) {
            const container = document.getElementById('guides-list');
            
            if (guides.length === 0) {
                container.innerHTML = '<div class="no-results">No user guides found</div>';
                return;
            }
            
            let html = '';
            guides.forEach(guide => {
                html += `
                    <div class="content-item">
                        <div class="content-header">
                            <div>
                                <h5 style="margin: 0;">${guide.title}</h5>
                                <small>Category: ${guide.category || 'General'} | Role: ${guide.role || 'all'} | Views: ${guide.views_count}</small>
                            </div>
                            <div>
                                <button class="btn btn-secondary" onclick="editGuide(${guide.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger" onclick="deleteGuide(${guide.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // FAQ management functions
        function saveFAQ(event) {
            event.preventDefault();
            alert('FAQ save functionality would be implemented here');
        }
        
        function editFAQ(id) {
            alert('FAQ edit functionality would be implemented here');
        }
        
        function deleteFAQ(id) {
            if (confirm('Are you sure you want to delete this FAQ?')) {
                alert('FAQ delete functionality would be implemented here');
            }
        }
        
        function resetFAQForm() {
            document.getElementById('faq-form').reset();
            document.getElementById('faq-id').value = '';
        }
        
        // User guide management functions
        function saveGuide(event) {
            event.preventDefault();
            alert('User guide save functionality would be implemented here');
        }
        
        function editGuide(id) {
            alert('User guide edit functionality would be implemented here');
        }
        
        function deleteGuide(id) {
            if (confirm('Are you sure you want to delete this user guide?')) {
                alert('User guide delete functionality would be implemented here');
            }
        }
        
        function resetGuideForm() {
            document.getElementById('guide-form').reset();
            document.getElementById('guide-id').value = '';
        }
    </script>
</body>
</html>