// Form validation and submission
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = this.querySelector('input[name="username"]').value;
            const password = this.querySelector('input[name="password"]').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    }
    
    // Mobile Menu Toggle
    initializeMobileMenu();
});

// Notification system
class NotificationSystem {
    static show(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Visitor management functions
class VisitorManager {
    static async addVisitor(visitorData) {
        try {
            const response = await fetch('/api/visitors.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(visitorData)
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error:', error);
            throw error;
        }
    }
    
    static async updateVisitorStatus(visitorId, status) {
        try {
            const response = await fetch(`/api/visitors.php?id=${visitorId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status })
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error:', error);
            throw error;
        }
    }
}

// Mobile navigation functions
function toggleMobileNav() {
    const nav = document.querySelector('.mobile-nav');
    if (nav) {
        nav.classList.toggle('active');
    }
}

// Add mobile nav toggle button event listener
document.addEventListener('DOMContentLoaded', function() {
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    if (mobileNavToggle) {
        mobileNavToggle.addEventListener('click', toggleMobileNav);
    }
});

function initializeMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Menu toggle clicked'); // Debug log
            sidebar.classList.toggle('active');
            
            // Force a reflow
            void sidebar.offsetWidth;
            
            // Add visible class for opacity transition
            if (sidebar.classList.contains('active')) {
                sidebar.classList.add('visible');
            } else {
                sidebar.classList.remove('visible');
            }
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active', 'visible');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active', 'visible');
            }
        });
    }
}

// Initialize all features when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mobile menu
    initializeMobileMenu();
    
    // Initialize camera only if we're on the dashboard page
    const visitorForm = document.getElementById('visitorForm');
    if (visitorForm) {
        initializeCamera();
    }
});

// Camera handling
async function initializeCamera() {
    const camera = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const captureButton = document.getElementById('capturePhoto');
    const retakeButton = document.getElementById('retakePhoto');
    const startButton = document.getElementById('startCamera');
    const photoPreview = document.getElementById('photoPreview');
    const errorDisplay = document.querySelector('.camera-error');
    let stream = null;

    // Function to start the camera
    async function startCamera() {
        try {
            // Stop any existing streams
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }

            // Request camera access
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });

            // Set up video stream
            camera.srcObject = stream;
            await camera.play();

            // Update UI
            camera.style.display = 'block';
            captureButton.style.display = 'inline-block';
            startButton.style.display = 'none';
            photoPreview.style.display = 'none';
            if (errorDisplay) {
                errorDisplay.style.display = 'none';
            }

            return true;
        } catch (error) {
            console.error('Camera error:', error);
            if (errorDisplay) {
                errorDisplay.textContent = 'Could not access camera. Please check your permissions and try again.';
                errorDisplay.style.display = 'block';
            }
            startButton.style.display = 'inline-block';
            captureButton.style.display = 'none';
            return false;
        }
    }

    // Try to start camera automatically
    try {
        await startCamera();
    } catch (error) {
        console.error('Failed to start camera automatically:', error);
    }

    // Handle capture button click
    captureButton.addEventListener('click', function() {
        if (!stream) return;

        // Capture frame from video
        canvas.width = camera.videoWidth;
        canvas.height = camera.videoHeight;
        canvas.getContext('2d').drawImage(camera, 0, 0);

        // Convert to data URL
        const photoData = canvas.toDataURL('image/jpeg');
        
        // Update UI
        photoPreview.style.backgroundImage = `url(${photoData})`;
        camera.style.display = 'none';
        photoPreview.style.display = 'block';
        captureButton.style.display = 'none';
        retakeButton.style.display = 'inline-block';

        // Store photo data
        document.getElementById('visitorForm').dataset.photo = photoData;
    });

    // Handle retake button click
    retakeButton.addEventListener('click', async function() {
        // Try to restart camera
        const success = await startCamera();
        if (success) {
            photoPreview.style.display = 'none';
            retakeButton.style.display = 'none';
        }
    });

    // Handle start camera button click
    startButton.addEventListener('click', startCamera);

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    });
}

// Add form submission handling
function initializeFormHandling() {
    const form = document.getElementById('visitorForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!this.dataset.photo) {
                alert('Please take a photo of the visitor');
                return;
            }

            const formData = {
                name: document.getElementById('visitorName').value,
                phone: document.getElementById('visitorPhone').value,
                host_id: document.getElementById('hostId').value,
                purpose: document.getElementById('purpose').value,
                photo: this.dataset.photo
            };

            try {
                const response = await fetch('../../api/visitors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (data.success) {
                    alert('Visitor checked in successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to check in visitor'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error checking in visitor. Please try again.');
            }
        });
    }
}

// Handle rejected visitor
function handleRejectedVisitor(visitorId) {
    if (!visitorId) return;

    const action = confirm('What action would you like to take?\n\nOK - Allow visitor to re-register\nCancel - Mark as handled');
    
    fetch('../../api/visitors.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            visitor_id: visitorId,
            action: action ? 're_register' : 'mark_handled'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error handling rejected visitor. Please try again.');
    });
}

// Initialize all features when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mobile menu
    initializeMobileMenu();
    
    // Initialize camera and form handling only if we're on the dashboard page
    const visitorForm = document.getElementById('visitorForm');
    if (visitorForm) {
        initializeCamera();
        initializeFormHandling();
    }
});

// Visitor checkout function
function checkoutVisitor(visitorId) {
    if (!visitorId) {
        console.error('No visitor ID provided');
        return;
    }
    
    if (confirm('Are you sure you want to checkout this visitor?')) {
        fetch('../../api/visitors.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: visitorId,
                action: 'checkout'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error checking out visitor: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error checking out visitor. Please try again.');
        });
    }
}

// Add smooth scrolling to tables on mobile
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        if (window.innerWidth <= 768) {
            table.style.WebkitOverflowScrolling = 'touch';
        }
    });
});
