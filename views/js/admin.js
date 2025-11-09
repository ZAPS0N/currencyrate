/**
 * Generate a cryptographically secure random token
 *
 * @param {number} length - Token length in bytes
 * @returns {string} Hexadecimal token string
 */
function generateSecureToken(length) {
    const array = new Uint8Array(length);
    window.crypto.getRandomValues(array);
    return Array.from(array, function (byte) {
        return ('0' + byte.toString(16)).slice(-2);
    }).join('');
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize chosen for multi-select
    if (typeof $ !== 'undefined' && $.fn.chosen) {
        $('.chosen').chosen({
            width: '100%',
            no_results_text: 'No results found for:',
            placeholder_text_multiple: 'Select currencies'
        });
    }

    // Generate new cron token
    const generateTokenButton = document.getElementById('generateCronToken');

    if (generateTokenButton) {
        generateTokenButton.addEventListener('click', function (e) {
            e.preventDefault();

            const tokenInput = document.querySelector('input[name="cron_token"]');
            
            if (tokenInput) {
                const newToken = generateSecureToken(32);
                tokenInput.value = newToken;
                showNotification('New security token generated! Remember to save the configuration.', 'info');
            }
        });
    }

    // Copy cron URL to clipboard
    const cronUrl = document.querySelector('pre.well');

    if (cronUrl) {
        cronUrl.style.cursor = 'pointer';
        cronUrl.title = 'Click to copy';

        cronUrl.addEventListener('click', function () {
            const text = this.textContent;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () {
                    showNotification('Cron command copied to clipboard!');
                }).catch(function (err) {
                    console.error('Failed to copy:', err);
                });
            } 
        });
    }

    // Show notification helper
    function showNotification(message, type = 'success') {
        // Check if PrestaShop's showSuccessMessage is available
        if (typeof showSuccessMessage === 'function') {
            showSuccessMessage(message);
        } else {
            // otherwise create simple notification
            const notification = document.createElement('div');

            notification.className = 'alert alert-' + type;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '250px';
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(function () {
                notification.remove();
            }, 3000);
        }
    }

    // Validate cache TTL
    const cacheTtlInput = document.querySelector('input[name="cache_ttl"]');

    if (cacheTtlInput) {
        cacheTtlInput.addEventListener('input', function () {
            const value = parseInt(this.value);

            if (value < 3600) {
                this.value = 3600;
            } else if (value > 604800) {
                this.value = 604800;
            }
        });
    }

    const tableTypeSelect = document.querySelector('select[name="table_type"]');

    if (tableTypeSelect) {
        let originalValue = tableTypeSelect.value;

        tableTypeSelect.addEventListener('change', function (e) {
            const newValue = this.value;

            if (confirm('Changing table type will reload available currencies and may reset your currency selection. Continue?')) {
                originalValue = newValue;

                let tempInput = document.querySelector('input[name="temp_table_type"]');

                if (!tempInput) {
                    tempInput = document.createElement('input');
                    tempInput.type = 'hidden';
                    tempInput.name = 'temp_table_type';
                    this.form.appendChild(tempInput);
                }

                tempInput.value = newValue;

                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('reload_currencies', '1');
                currentUrl.searchParams.set('new_table_type', newValue);
                
                window.location.href = currentUrl.toString();
            } else {
                this.value = originalValue;
            }
        });
    }
});