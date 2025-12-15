// virgosoft-trade-forge/resources/js/utils/formatters.js

export function formatCurrency(value, currency = 'USD') {
    if (value === null || value === undefined) return '';
    const number = parseFloat(value);
    if (isNaN(number)) return '';
    return number.toLocaleString('en-US', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

export function formatCrypto(value) {
    if (value === null || value === undefined) return '';
    const number = parseFloat(value);
    if (isNaN(number)) return '';
    // Cryptocurrencies typically need more precision, up to 8 decimal places
    return number.toLocaleString('en-US', {
        style: 'decimal',
        minimumFractionDigits: 0, // Allow 0 for whole numbers
        maximumFractionDigits: 8
    });
}

export function formatDate(dateString) {
    if (!dateString) return '';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}
