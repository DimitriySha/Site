/**
 * Uyut Rental Agency - Main JavaScript
 * Common functionality across all pages
 */

// Global navigation and authentication
document.addEventListener('DOMContentLoaded', function() {
    updateNavigation();
    updateHeaderActions();
});

async function updateNavigation() {
    try {
        const response = await fetch('api/auth.php?action=session');
        const data = await response.json();

        if (data.logged_in) {
            const user = data.user;
            const navHTML = `
                <li><a href="index.html">Home</a></li>
                <li><a href="listings.html">Listings</a></li>
                ${user.role === 'admin' ? '<li><a href="admin/index.html">Admin</a></li>' : ''}
                <li><a href="profile.html">Profile</a></li>
                <li><a href="messages.html">Messages</a></li>
            `;
            document.querySelectorAll('#main-nav').forEach(el => {
                el.innerHTML = navHTML;
            });
        } else {
            const navHTML = `
                <li><a href="index.html">Home</a></li>
                <li><a href="listings.html">Listings</a></li>
                <li><a href="login.html">Login</a></li>
                <li><a href="register.html">Sign Up</a></li>
            `;
            document.querySelectorAll('#main-nav').forEach(el => {
                el.innerHTML = navHTML;
            });
        }
    } catch (error) {
        console.error('Navigation load error:', error);
    }
}

async function updateHeaderActions() {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    const containers = document.querySelectorAll('#header-actions');

    if (containers.length === 0) return;

    if (user) {
        containers.forEach(el => {
            el.innerHTML = `
                <a href="favorites.html" class="btn btn-secondary">
                    <i class="far fa-heart"></i>
                </a>
                <a href="profile.html" class="btn btn-primary">
                    <i class="fas fa-user"></i> ${user.first_name}
                </a>
                <button class="btn btn-secondary" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            `;
        });
    } else {
        containers.forEach(el => {
            el.innerHTML = `
                <a href="login.html" class="btn btn-primary">Login</a>
                <a href="register.html" class="btn btn-secondary">Sign Up</a>
            `;
        });
    }
}

async function initializeAuth() {
    const user = JSON.parse(localStorage.getItem('user') || 'null');

    if (!user) {
        // Check if session exists on server
        try {
            const response = await fetch('api/auth.php?action=session');
            const data = await response.json();

            if (data.logged_in) {
                localStorage.setItem('user', JSON.stringify(data.user));
                updateHeaderActions();
                updateNavigation();
            }
        } catch (error) {
            console.error('Session check failed:', error);
        }
    } else {
        updateHeaderActions();
        updateNavigation();
    }
}

function checkAuth() {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    if (!user) {
        const currentPage = window.location.pathname;
        if (currentPage.includes('profile.html') ||
            currentPage.includes('bookings.html') ||
            currentPage.includes('messages.html') ||
            currentPage.includes('favorites.html')) {
            localStorage.setItem('after_login', currentPage);
            window.location.href = 'login.html';
        }
    }
    return user;
}

function logout() {
    fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    }).then(() => {
        localStorage.removeItem('user');
        updateHeaderActions();
        updateNavigation();
        window.location.href = 'index.html';
    });
}

// Utility functions
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? 'var(--success)' : 'var(--danger)'};
        color: white;
        border-radius: var(--radius);
        z-index: 10000;
        box-shadow: var(--shadow);
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Format currency
function formatPrice(price) {
    return '₸' + Number(price).toLocaleString();
}
