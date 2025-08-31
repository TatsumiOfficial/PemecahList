// Global variables
let currentResults = [];
let currentPage = 1;
const resultsPerPage = 100;

// Navigation
const navLinks = document.querySelectorAll('.nav-link');
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const navMenu = document.getElementById('navMenu');

// Mobile menu
mobileMenuBtn.addEventListener('click', () => {
    navMenu.classList.toggle('active');
});

// Navigation handler
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const pageName = link.getAttribute('data-page');
        showPage(pageName);
        
        navLinks.forEach(nl => nl.classList.remove('active'));
        link.classList.add('active');
        navMenu.classList.remove('active');
    });
});

function showPage(pageName) {
    document.querySelectorAll('.page-content').forEach(page => {
        page.classList.remove('active');
    });
    
    const targetPage = document.getElementById(pageName + '-page');
    if (targetPage) {
        targetPage.classList.add('active');
    }

    if (pageName !== 'reverse-ip') {
        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) {
            resultsSection.style.display = 'none';
        }
    }
}

// Search form
document.getElementById('searchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const ipAddress = document.getElementById('ipAddress').value.trim();
    const recaptchaResponse = grecaptcha.getResponse();
    
    if (!recaptchaResponse) {
        showNotification('Please complete the reCAPTCHA verification first.', 'error');
        return;
    }

    const searchBtn = document.getElementById('searchBtn');
    const loading = document.getElementById('loading');
    const resultsSection = document.getElementById('resultsSection');
    
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<span>Searching...</span>';
    loading.style.display = 'block';
    resultsSection.style.display = 'none';

    try {
        const response = await fetch(`api.php?ip_address=${encodeURIComponent(ipAddress)}&g-recaptcha-response=${encodeURIComponent(recaptchaResponse)}`);
        const data = await response.json();
        displayResults(data, ipAddress);
    } catch (error) {
        console.error('Error:', error);
        displayError('An error occurred while processing the request. Please try again.');
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<span>Search Domains</span>';
        loading.style.display = 'none';
        grecaptcha.reset();
    }
});

function displayResults(data, ipAddress) {
    const resultsSection = document.getElementById('resultsSection');
    const resultsCount = document.getElementById('resultsCount');
    const resultsTableBody = document.getElementById('resultsTableBody');

    resultsSection.style.display = 'block';
    
    if (data.error) {
        resultsTableBody.innerHTML = `
            <tr>
                <td colspan="4">
                    <div class="error">${data.error}</div>
                </td>
            </tr>
        `;
        resultsCount.innerHTML = '<div class="results-icon">❌</div><span>Error</span>';
        document.getElementById('pagination').style.display = 'none';
        return;
    }

    const domains = data.domains || data;
    const totalFound = data.total_found || domains.length;
    const isLimited = data.limited || false;
    
    currentResults = domains;
    currentPage = 1;
    
    if (domains.length === 0) {
        resultsTableBody.innerHTML = `
            <tr>
                <td colspan="4">
                    <div class="no-results">
                        <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">😔</div>
                        <strong>No domains found</strong><br>
                        for IP address: <code>${ipAddress}</code>
                    </div>
                </td>
            </tr>
        `;
        resultsCount.innerHTML = '<div class="results-icon">📊</div><span>No results</span>';
        document.getElementById('pagination').style.display = 'none';
        return;
    }

    let countText = `Found ${totalFound.toLocaleString()} domains`;
    if (isLimited) {
        countText += ` (showing ${domains.length.toLocaleString()})`;
        setTimeout(() => {
            showNotification(`Found ${totalFound.toLocaleString()} domains, but only ${domains.length.toLocaleString()} are shown for optimal performance`, 'info');
        }, 1000);
    }
    
    resultsCount.innerHTML = `<div class="results-icon">📊</div><span>${countText}</span>`;
    
    updateTable();
    updatePagination();
    document.getElementById('pagination').style.display = 'flex';
    
    setTimeout(() => {
        resultsSection.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }, 100);
}

function updateTable() {
    const resultsTableBody = document.getElementById('resultsTableBody');
    const startIndex = (currentPage - 1) * resultsPerPage;
    const endIndex = Math.min(startIndex + resultsPerPage, currentResults.length);
    const pageResults = currentResults.slice(startIndex, endIndex);

    resultsTableBody.innerHTML = pageResults.map((item, index) => {
        let formattedDate = 'N/A';
        if (item.isotime) {
            try {
                const date = new Date(item.isotime);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                formattedDate = `${year}-${month}-${day}`;
            } catch (e) {
                formattedDate = item.isotime;
            }
        }

        return `
            <tr>
                <td class="row-number">${(startIndex + index + 1).toLocaleString()}</td>
                <td class="domain-name" onclick="copyToClipboard('${item.domain}', this)" title="Click to copy">${item.domain}</td>
                <td class="ip-address">${item.ip}</td>
                <td class="date-time">${formattedDate}</td>
            </tr>
        `;
    }).join('');
}

function updatePagination() {
    const totalPages = Math.ceil(currentResults.length / resultsPerPage);
    const startIndex = (currentPage - 1) * resultsPerPage + 1;
    const endIndex = Math.min(currentPage * resultsPerPage, currentResults.length);

    document.getElementById('totalPages').textContent = totalPages.toLocaleString();
    document.getElementById('pageInput').value = currentPage;
    document.getElementById('pageInput').max = totalPages;
    document.getElementById('pageInfo').textContent = `Showing ${startIndex.toLocaleString()}-${endIndex.toLocaleString()} of ${currentResults.length.toLocaleString()} results`;
    
    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage === totalPages;
}

function changePage(direction) {
    const totalPages = Math.ceil(currentResults.length / resultsPerPage);
    const newPage = currentPage + direction;
    
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        updateTable();
        updatePagination();
        
        document.getElementById('resultsSection').scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
}

function goToPage() {
    const pageInput = document.getElementById('pageInput');
    const totalPages = Math.ceil(currentResults.length / resultsPerPage);
    let newPage = parseInt(pageInput.value);
    
    if (isNaN(newPage)) newPage = 1;
    if (newPage < 1) newPage = 1;
    if (newPage > totalPages) newPage = totalPages;
    
    pageInput.value = newPage;
    currentPage = newPage;
    updateTable();
    updatePagination();
    
    document.getElementById('resultsSection').scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
    });
}

function displayError(message) {
    const resultsSection = document.getElementById('resultsSection');
    const resultsTableBody = document.getElementById('resultsTableBody');
    const resultsCount = document.getElementById('resultsCount');
    
    resultsSection.style.display = 'block';
    resultsTableBody.innerHTML = `
        <tr>
            <td colspan="4">
                <div class="error">${message}</div>
            </td>
        </tr>
    `;
    resultsCount.innerHTML = '<div class="results-icon">❌</div><span>Error</span>';
    document.getElementById('pagination').style.display = 'none';
}

function copyToClipboard(domain, element) {
    navigator.clipboard.writeText(domain).then(() => {
        const originalColor = element.style.color;
        const originalContent = element.textContent;
        
        element.style.color = '#10b981';
        element.style.fontWeight = 'bold';
        element.textContent = '✓ Copied!';
        
        setTimeout(() => {
            element.style.color = originalColor;
            element.style.fontWeight = '500';
            element.textContent = originalContent;
        }, 1500);
        
        showNotification('Domain copied successfully!', 'success');
        
    }).catch(err => {
        console.error('Failed to copy: ', err);
        showNotification('Failed to copy domain', 'error');
    });
}

function exportResults() {
    if (currentResults.length === 0) {
        showNotification('No data to export.', 'error');
        return;
    }
    
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
    
    const content = [
        `# Reverse IP Lookup Results - ${new Date().toLocaleString()}`,
        `# Exported: ${currentResults.length.toLocaleString()} domains`,
        '',
        ...currentResults.map((item, index) => {
            const isotime = item.isotime || 'N/A';
            return `${index + 1}. ${item.domain} - ${item.ip} - ${isotime}`;
        })
    ].join('\n');
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `reverse-ip-results-${timestamp}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showNotification(`File exported successfully with ${currentResults.length.toLocaleString()} domains!`, 'success');
}

function showNotification(message, type = 'info') {
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Input validation
document.getElementById('ipAddress').addEventListener('input', function(e) {
    const value = e.target.value;
    const isValid = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:\/(?:1[6-9]|2[0-9]|3[0-2]))?$/.test(value);
    
    if (value && !isValid) {
        e.target.style.borderColor = '#dc2626';
        e.target.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
    } else {
        e.target.style.borderColor = 'rgba(255, 255, 255, 0.08)';
        e.target.style.boxShadow = 'none';
    }
});

// Pricing functionality
const pricingToggle = document.getElementById('pricingToggle');
if (pricingToggle) {
    pricingToggle.addEventListener('change', function() {
        const monthlyPrices = document.querySelectorAll('.monthly-price');
        const annualPrices = document.querySelectorAll('.annual-price');
        
        if (this.checked) {
            monthlyPrices.forEach(price => price.style.display = 'none');
            annualPrices.forEach(price => price.style.display = 'inline');
        } else {
            monthlyPrices.forEach(price => price.style.display = 'inline');
            annualPrices.forEach(price => price.style.display = 'none');
        }
    });
}

// FAQ functionality
const faqItems = document.querySelectorAll('.faq-item');
faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');
    question.addEventListener('click', () => {
        faqItems.forEach(otherItem => {
            if (otherItem !== item) {
                otherItem.classList.remove('active');
            }
        });
        item.classList.toggle('active');
    });
});

// Telegram redirect function
function redirectToTelegram(planType) {
    // Replace with your actual Telegram username or link
    const telegramUsername = 'xiera0069'; // Change this to your actual Telegram username
    const telegramURL = `https://t.me/${telegramUsername}`;
    
    let message = '';
    switch (planType) {
        case 'free':
            message = '?text=Hi! I\'m interested in the Free plan for Tatsumi Crew - DNS Tools.';
            break;
        case 'pro':
            message = '?text=Hi! I\'m interested in the Pro plan ($29/month) for Tatsumi Crew - DNS Tools. Can you help me get started with the 14-day free trial?';
            break;
        case 'enterprise':
            message = '?text=Hi! I\'m interested in the Enterprise plan ($199/month) for Tatsumi Crew - DNS Tools. I\'d like to discuss custom solutions for my organization.';
            break;
        default:
            message = '?text=Hi! I\'m interested in Tatsumi Crew - DNS Tools pricing and features.';
    }
    
    // Show notification before redirect
    showNotification(`Redirecting to Telegram for ${planType} plan discussion...`, 'info');
    
    // Redirect to Telegram after short delay
    setTimeout(() => {
        window.open(telegramURL + message, '_blank');
    }, 1500);
}

// Database stats loading
async function loadDatabaseStats() {
    const statsElement = document.getElementById('totalDnsRecords');
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000);
        
        const response = await fetch('fast-stats.php', { 
            signal: controller.signal 
        });
        clearTimeout(timeoutId);
        
        const data = await response.json();
        
        if (data.success && data.total_dns_records > 0) {
            statsElement.textContent = data.formatted_total;
            console.log('Database stats loaded:', data.formatted_total);
        } else {
            throw new Error(data.error || 'No data available');
        }
        
    } catch (error) {
        console.error('Error loading stats:', error);
        statsElement.textContent = 'Loading...';
        setTimeout(loadDatabaseStats, 5000);
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
        const ipInput = document.getElementById('ipAddress');
        if (document.activeElement === ipInput) {
            document.getElementById('searchForm').dispatchEvent(new Event('submit'));
        }
    }
    
    if (document.getElementById('resultsSection').style.display === 'block') {
        if (e.key === 'ArrowLeft' && !document.getElementById('prevBtn').disabled) {
            changePage(-1);
        } else if (e.key === 'ArrowRight' && !document.getElementById('nextBtn').disabled) {
            changePage(1);
        }
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    showNotification('Welcome to Tatsumi Crew - DNS Tools!', 'success');
    loadDatabaseStats();
});

// Refresh stats every 5 minutes
setInterval(loadDatabaseStats, 300000);

// Add slideOutRight animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
