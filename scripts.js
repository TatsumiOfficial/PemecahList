const renameModal = document.getElementById('renameModal');
renameModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const filename = button.getAttribute('data-filename');
    renameModal.querySelector('#renameOriginal').value = filename;
    renameModal.querySelector('input[name="to"]').value = filename;
});

document.querySelectorAll('.table-modern tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
    });
    
    row.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        }
    });
});

document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('show.bs.modal', function() {
        document.body.style.overflow = 'hidden';
    });
    
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.style.overflow = 'auto';
    });
});

document.querySelectorAll('[data-size]').forEach(element => {
    const size = parseInt(element.dataset.size);
    if (size > 0) {
        element.style.animation = 'fadeInUp 0.5s ease-out';
    }
});

document.addEventListener('keydown', function(e) {

    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        document.querySelector('[data-bs-target="#createFileModal"]').click();
    }

    if (e.ctrlKey && e.shiftKey && e.key === 'N') {
        e.preventDefault();
        document.querySelector('[data-bs-target="#createFolderModal"]').click();
    }

    if (e.ctrlKey && e.key === 'u') {
        e.preventDefault();
        document.querySelector('[data-bs-target="#uploadModal"]').click();
    }
});

(function() {
    document.addEventListener('contextmenu', e => e.preventDefault());

    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.key === 'u')) {
            e.preventDefault();
    }
});

    console.clear();

    document.title = 'Loading...';
    setTimeout(() => {
        document.title = '🌟 Alfa - File Manager By Tatsumi Crew';
    }, 1000);
})();

const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .table-modern tbody tr {
        animation: fadeInUp 0.3s ease-out;
    }

    .header-card {
        animation: slideIn 0.5s ease-out;
    }

    .file-table-card {
        animation: fadeInUp 0.6s ease-out;
    }

    .breadcrumb-modern {
        animation: slideIn 0.4s ease-out;
    }
`;
document.head.appendChild(style);
