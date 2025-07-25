<?php
require_once '../config/config.php';

// Check if user is logged in
requireLogin();

$userRole = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Gate Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .help-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .help-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        
        .help-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        
        .help-header p {
            margin: 0;
            font-size: 1.2em;
            opacity: 0.9;
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
        
        .help-tab:hover:not(.active) {
            background: #e9ecef;
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
        
        .search-box {
            margin-bottom: 30px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: #007bff;
        }
        
        .search-box i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .faq-category {
            margin-bottom: 30px;
        }
        
        .faq-category h3 {
            color: #495057;
            padding: 15px 0;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 20px;
        }
        
        .faq-item {
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .faq-question {
            padding: 15px 20px;
            background: #f8f9fa;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question:hover {
            background: #e9ecef;
        }
        
        .faq-question h4 {
            margin: 0;
            font-size: 16px;
            color: #495057;
        }
        
        .faq-answer {
            display: none;
            padding: 20px;
            background: white;
            border-top: 1px solid #e9ecef;
        }
        
        .faq-answer.active {
            display: block;
        }
        
        .guide-item {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: shadow 0.3s ease;
        }
        
        .guide-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .guide-item h4 {
            margin: 0 0 10px 0;
            color: #007bff;
            cursor: pointer;
        }
        
        .guide-item p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        .ticket-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
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
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
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
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .ticket-list {
            margin-top: 30px;
        }
        
        .ticket-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .ticket-header {
            padding: 15px 20px;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .ticket-details {
            display: none;
            padding: 20px;
            background: white;
        }
        
        .ticket-details.active {
            display: block;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .guide-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .guide-modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .guide-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .guide-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .message-thread {
            margin-top: 20px;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .message-content {
            color: #495057;
        }
        
        .message-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .help-tabs {
                flex-direction: column;
            }
            
            .help-tab {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
            
            .help-tab:last-child {
                border-bottom: none;
            }
            
            .help-content {
                padding: 20px;
            }
            
            .guide-modal-content {
                margin: 2% auto;
                width: 95%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="help-container">
        <a href="<?php 
            switch($userRole) {
                case 'admin': echo 'admin/dashboard.php'; break;
                case 'security': echo 'security/dashboard.php'; break;
                case 'resident': echo 'resident/dashboard.php'; break;
                default: echo '../index.php';
            }
        ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="help-header">
            <h1><i class="fas fa-question-circle"></i> Help Center</h1>
            <p>Find answers to your questions or get support</p>
        </div>
        
        <div class="help-tabs">
            <button class="help-tab active" onclick="showHelpSection('faq')">
                <i class="fas fa-question"></i> FAQ
            </button>
            <button class="help-tab" onclick="showHelpSection('guides')">
                <i class="fas fa-book"></i> User Guides
            </button>
            <button class="help-tab" onclick="showHelpSection('support')">
                <i class="fas fa-headset"></i> Support Tickets
            </button>
        </div>
        
        <!-- FAQ Section -->
        <div id="faq-section" class="help-content active">
            <div class="search-box">
                <input type="text" id="faq-search" placeholder="Search FAQs..." onkeyup="searchHelp()">
                <i class="fas fa-search"></i>
            </div>
            
            <div id="faq-content">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading FAQs...
                </div>
            </div>
        </div>
        
        <!-- User Guides Section -->
        <div id="guides-section" class="help-content">
            <div class="search-box">
                <input type="text" id="guides-search" placeholder="Search user guides..." onkeyup="searchHelp()">
                <i class="fas fa-search"></i>
            </div>
            
            <div id="guides-content">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading user guides...
                </div>
            </div>
        </div>
        
        <!-- Support Tickets Section -->
        <div id="support-section" class="help-content">
            <div class="ticket-form">
                <h3><i class="fas fa-plus"></i> Create New Support Ticket</h3>
                <form id="support-form" onsubmit="createSupportTicket(event)">
                    <div class="form-group">
                        <label for="ticket-subject">Subject *</label>
                        <input type="text" id="ticket-subject" name="subject" required 
                               placeholder="Brief description of your issue">
                    </div>
                    
                    <div class="form-group">
                        <label for="ticket-category">Category *</label>
                        <select id="ticket-category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="technical">Technical Issue</option>
                            <option value="account">Account & Profile</option>
                            <option value="visitor_management">Visitor Management</option>
                            <option value="general">General Inquiry</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticket-priority">Priority</label>
                        <select id="ticket-priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticket-description">Description *</label>
                        <textarea id="ticket-description" name="description" required 
                                  placeholder="Please provide detailed information about your issue or question..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                </form>
            </div>
            
            <div class="ticket-list">
                <h3><i class="fas fa-ticket-alt"></i> Your Support Tickets</h3>
                <div id="tickets-content">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Loading support tickets...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Guide Modal -->
    <div id="guide-modal" class="guide-modal">
        <div class="guide-modal-content">
            <div class="guide-modal-header">
                <h3 id="guide-modal-title"></h3>
                <button class="guide-modal-close" onclick="closeGuideModal()">&times;</button>
            </div>
            <div id="guide-modal-content"></div>
        </div>
    </div>
    
    <script>
        let currentSection = 'faq';
        let faqData = [];
        let guidesData = [];
        let ticketsData = [];
        let searchTimeout;
        
        // Initialize help system
        document.addEventListener('DOMContentLoaded', function() {
            loadFAQs();
            loadUserGuides();
            loadSupportTickets();
        });
        
        // Show help section
        function showHelpSection(section) {
            // Update tabs
            document.querySelectorAll('.help-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelector(`button[onclick="showHelpSection('${section}')"]`).classList.add('active');
            
            // Update content
            document.querySelectorAll('.help-content').forEach(content => content.classList.remove('active'));
            document.getElementById(`${section}-section`).classList.add('active');
            
            currentSection = section;
        }
        
        // Load FAQs
        async function loadFAQs() {
            try {
                const response = await fetch('../api/help.php?action=get_faqs');
                const data = await response.json();
                
                if (data.success) {
                    faqData = data.data;
                    displayFAQs(faqData);
                } else {
                    document.getElementById('faq-content').innerHTML = 
                        '<div class="no-results">Failed to load FAQs</div>';
                }
            } catch (error) {
                console.error('Error loading FAQs:', error);
                document.getElementById('faq-content').innerHTML = 
                    '<div class="no-results">Error loading FAQs</div>';
            }
        }
        
        // Display FAQs
        function displayFAQs(data) {
            const container = document.getElementById('faq-content');
            
            if (data.length === 0) {
                container.innerHTML = '<div class="no-results">No FAQs found</div>';
                return;
            }
            
            let html = '';
            data.forEach(category => {
                if (category.items && category.items.length > 0) {
                    html += `
                        <div class="faq-category">
                            <h3>${category.category_name}</h3>
                            <p style="color: #6c757d; margin-bottom: 20px;">${category.category_description}</p>
                    `;
                    
                    category.items.forEach(item => {
                        html += `
                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(${item.id})">
                                    <h4>${item.question}</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer" id="faq-${item.id}">
                                    <p>${item.answer}</p>
                                    <small style="color: #6c757d;">Viewed ${item.views_count} times</small>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                }
            });
            
            container.innerHTML = html;
        }
        
        // Toggle FAQ
        function toggleFAQ(id) {
            const answer = document.getElementById(`faq-${id}`);
            const isOpen = answer.classList.contains('active');
            
            // Close all FAQ answers
            document.querySelectorAll('.faq-answer').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.faq-question i').forEach(el => el.style.transform = 'rotate(0deg)');
            
            if (!isOpen) {
                answer.classList.add('active');
                answer.parentElement.querySelector('.faq-question i').style.transform = 'rotate(180deg)';
                
                // Track view
                trackView('faq', id);
            }
        }
        
        // Load User Guides
        async function loadUserGuides() {
            try {
                const response = await fetch('../api/help.php?action=get_guides');
                const data = await response.json();
                
                if (data.success) {
                    guidesData = data.data;
                    displayUserGuides(guidesData);
                } else {
                    document.getElementById('guides-content').innerHTML = 
                        '<div class="no-results">Failed to load user guides</div>';
                }
            } catch (error) {
                console.error('Error loading user guides:', error);
                document.getElementById('guides-content').innerHTML = 
                    '<div class="no-results">Error loading user guides</div>';
            }
        }
        
        // Display User Guides
        function displayUserGuides(data) {
            const container = document.getElementById('guides-content');
            
            if (data.length === 0) {
                container.innerHTML = '<div class="no-results">No user guides found</div>';
                return;
            }
            
            let html = '';
            data.forEach(guide => {
                html += `
                    <div class="guide-item">
                        <h4 onclick="showGuide(${guide.id})">${guide.title}</h4>
                        <p>Category: ${guide.category || 'General'} | Views: ${guide.views_count}</p>
                        <p>Last updated: ${new Date(guide.updated_at).toLocaleDateString()}</p>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Show guide in modal
        function showGuide(id) {
            const guide = guidesData.find(g => g.id == id);
            if (guide) {
                document.getElementById('guide-modal-title').textContent = guide.title;
                document.getElementById('guide-modal-content').innerHTML = guide.content;
                document.getElementById('guide-modal').style.display = 'block';
                
                // Track view
                trackView('guide', id);
            }
        }
        
        // Close guide modal
        function closeGuideModal() {
            document.getElementById('guide-modal').style.display = 'none';
        }
        
        // Load Support Tickets
        async function loadSupportTickets() {
            try {
                const response = await fetch('../api/help.php?action=get_tickets');
                const data = await response.json();
                
                if (data.success) {
                    ticketsData = data.data;
                    displaySupportTickets(ticketsData);
                } else {
                    document.getElementById('tickets-content').innerHTML = 
                        '<div class="no-results">Failed to load support tickets</div>';
                }
            } catch (error) {
                console.error('Error loading support tickets:', error);
                document.getElementById('tickets-content').innerHTML = 
                    '<div class="no-results">Error loading support tickets</div>';
            }
        }
        
        // Display Support Tickets
        function displaySupportTickets(data) {
            const container = document.getElementById('tickets-content');
            
            if (data.length === 0) {
                container.innerHTML = '<div class="no-results">No support tickets found</div>';
                return;
            }
            
            let html = '';
            data.forEach(ticket => {
                html += `
                    <div class="ticket-item">
                        <div class="ticket-header" onclick="toggleTicket(${ticket.id})">
                            <div>
                                <h4 style="margin: 0;">${ticket.subject}</h4>
                                <small style="color: #6c757d;">
                                    #${ticket.id} | ${ticket.category} | 
                                    ${new Date(ticket.created_at).toLocaleDateString()}
                                </small>
                            </div>
                            <span class="ticket-status ${ticket.status}">${ticket.status.replace('_', ' ').toUpperCase()}</span>
                        </div>
                        <div class="ticket-details" id="ticket-${ticket.id}">
                            <p><strong>Description:</strong></p>
                            <p>${ticket.description}</p>
                            <p><strong>Priority:</strong> ${ticket.priority.toUpperCase()}</p>
                            ${ticket.assigned_name ? `<p><strong>Assigned to:</strong> ${ticket.assigned_name}</p>` : ''}
                            ${ticket.resolution ? `
                                <p><strong>Resolution:</strong></p>
                                <p>${ticket.resolution}</p>
                            ` : ''}
                            
                            <div class="message-thread" id="messages-${ticket.id}">
                                <div class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> Loading messages...
                                </div>
                            </div>
                            
                            ${ticket.status !== 'closed' ? `
                                <div class="message-form">
                                    <form onsubmit="addTicketMessage(event, ${ticket.id})">
                                        <div class="form-group">
                                            <textarea name="message" placeholder="Add a message..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn">
                                            <i class="fas fa-reply"></i> Reply
                                        </button>
                                    </form>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Toggle ticket details
        function toggleTicket(id) {
            const details = document.getElementById(`ticket-${id}`);
            const isOpen = details.classList.contains('active');
            
            if (!isOpen) {
                details.classList.add('active');
                loadTicketMessages(id);
            } else {
                details.classList.remove('active');
            }
        }
        
        // Load ticket messages
        async function loadTicketMessages(ticketId) {
            try {
                const response = await fetch(`../api/help.php?action=get_ticket_messages&ticket_id=${ticketId}`);
                const data = await response.json();
                
                if (data.success) {
                    displayTicketMessages(ticketId, data.data);
                } else {
                    document.getElementById(`messages-${ticketId}`).innerHTML = 
                        '<div class="no-results">Failed to load messages</div>';
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                document.getElementById(`messages-${ticketId}`).innerHTML = 
                    '<div class="no-results">Error loading messages</div>';
            }
        }
        
        // Display ticket messages
        function displayTicketMessages(ticketId, messages) {
            const container = document.getElementById(`messages-${ticketId}`);
            
            if (messages.length === 0) {
                container.innerHTML = '<div class="no-results">No messages found</div>';
                return;
            }
            
            let html = '<h4>Message History:</h4>';
            messages.forEach(message => {
                html += `
                    <div class="message">
                        <div class="message-header">
                            <strong>${message.user_name}</strong>
                            <span>${new Date(message.created_at).toLocaleString()}</span>
                        </div>
                        <div class="message-content">${message.message}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Create support ticket
        async function createSupportTicket(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = {
                subject: form.subject.value,
                category: form.category.value,
                priority: form.priority.value,
                description: form.description.value
            };
            
            try {
                const response = await fetch('../api/help.php?action=create_ticket', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Support ticket created successfully!');
                    form.reset();
                    loadSupportTickets(); // Reload tickets
                } else {
                    alert('Failed to create support ticket: ' + data.message);
                }
            } catch (error) {
                console.error('Error creating ticket:', error);
                alert('Error creating support ticket');
            }
        }
        
        // Add ticket message
        async function addTicketMessage(event, ticketId) {
            event.preventDefault();
            
            const form = event.target;
            const message = form.message.value;
            
            try {
                const response = await fetch('../api/help.php?action=add_ticket_message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        message: message
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    form.reset();
                    loadTicketMessages(ticketId); // Reload messages
                } else {
                    alert('Failed to add message: ' + data.message);
                }
            } catch (error) {
                console.error('Error adding message:', error);
                alert('Error adding message');
            }
        }
        
        // Search help content
        function searchHelp() {
            const searchInput = currentSection === 'faq' ? 
                document.getElementById('faq-search') : 
                document.getElementById('guides-search');
            
            const query = searchInput.value.trim();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Debounce search
            searchTimeout = setTimeout(async () => {
                if (query.length < 2) {
                    // Show all results if query is too short
                    if (currentSection === 'faq') {
                        displayFAQs(faqData);
                    } else {
                        displayUserGuides(guidesData);
                    }
                    return;
                }
                
                try {
                    const response = await fetch(`../api/help.php?action=search&q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        if (currentSection === 'faq') {
                            displaySearchResults(data.data.faqs, 'faq');
                        } else {
                            displaySearchResults(data.data.guides, 'guides');
                        }
                    }
                } catch (error) {
                    console.error('Error searching:', error);
                }
            }, 300);
        }
        
        // Display search results
        function displaySearchResults(results, type) {
            const container = document.getElementById(`${type}-content`);
            
            if (results.length === 0) {
                container.innerHTML = '<div class="no-results">No results found</div>';
                return;
            }
            
            if (type === 'faq') {
                let html = '<div class="faq-category"><h3>Search Results</h3>';
                results.forEach(item => {
                    html += `
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(${item.id})">
                                <h4>${item.question}</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer" id="faq-${item.id}">
                                <p>${item.answer}</p>
                                <small style="color: #6c757d;">Category: ${item.category_name}</small>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                let html = '';
                results.forEach(guide => {
                    html += `
                        <div class="guide-item">
                            <h4 onclick="showGuide(${guide.id})">${guide.title}</h4>
                            <p>Category: ${guide.category || 'General'}</p>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }
        }
        
        // Track view
        async function trackView(contentType, contentId) {
            try {
                await fetch('../api/help.php?action=track_view', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        content_type: contentType,
                        content_id: contentId
                    })
                });
            } catch (error) {
                console.error('Error tracking view:', error);
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('guide-modal');
            if (event.target === modal) {
                closeGuideModal();
            }
        }
    </script>
</body>
</html>